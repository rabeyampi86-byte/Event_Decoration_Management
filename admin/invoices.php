
<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   Invoice Statistics
========================= */

$stats = [
    'total' => 0,
    'subtotal' => 0,
    'discount' => 0,
    'tax' => 0,
    'total_amount' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(subtotal), 0) AS subtotal,
        COALESCE(SUM(discount), 0) AS discount,
        COALESCE(SUM(tax), 0) AS tax,
        COALESCE(SUM(total), 0) AS total_amount
    FROM invoices
");

if ($result) {
    $stats = $result->fetch_assoc();
}

/* =========================
   Search
========================= */

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        invoice_id,
        booking_id,
        subtotal,
        discount,
        tax,
        total,
        issued_at
    FROM invoices
    WHERE 1=1
";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= "
        AND (
            CAST(invoice_id AS CHAR) LIKE ?
            OR CAST(booking_id AS CHAR) LIKE ?
        )
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types = 'ss';
}

$sql .= " ORDER BY issued_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$invoices = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Invoice Management | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>

        .invoice-page {
            padding: 25px 30px 35px;
        }

        .invoice-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .invoice-page-header h2 {
            color: #1e293b;
            font-size: 23px;
            margin-bottom: 5px;
        }

        .invoice-page-header p {
            color: #64748b;
            font-size: 14px;
        }

        .invoice-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .invoice-add-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .invoice-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .invoice-summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .invoice-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 19px;
            flex-shrink: 0;
        }

        .invoice-summary-card h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .invoice-summary-card strong {
            color: #1e293b;
            font-size: 19px;
        }

        .invoice-management-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
        }

        .invoice-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .invoice-management-header h3 {
            color: #1e293b;
            font-size: 18px;
        }

        .invoice-search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .invoice-search-form input {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 13px;
            outline: none;
        }

        .invoice-search-form input:focus {
            border-color: #2563eb;
        }

        .invoice-search-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .invoice-reset-btn {
            background: #f1f5f9;
            color: #475569;
            padding: 10px 14px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
        }

        .invoice-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .invoice-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .invoice-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
        }

        .invoice-table tr:hover {
            background: #f8fafc;
        }

        .invoice-id {
            color: #2563eb;
            font-weight: 700;
        }

        .invoice-total {
            color: #1e293b;
            font-weight: 700;
        }

        .invoice-actions {
            display: flex;
            gap: 7px;
        }

        .invoice-action-btn {
            width: 31px;
            height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
        }

        .invoice-view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .invoice-edit-btn {
            background: #fef3c7;
            color: #b45309;
        }

        .invoice-delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        .invoice-empty {
            text-align: center;
            padding: 35px !important;
            color: #64748b !important;
        }

        @media(max-width:1100px) {
            .invoice-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width:600px) {
            .invoice-page {
                padding: 20px 15px;
            }

            .invoice-summary-grid {
                grid-template-columns: 1fr;
            }

            .invoice-search-form input {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="invoice-page">

            <div class="invoice-page-header">

                <div>
                    <h2>Invoice Management</h2>
                    <p>Manage and monitor all booking invoices.</p>
                </div>

                <a href="add_invoice.php" class="invoice-add-btn">
                    <i class="fas fa-plus"></i>
                    Create Invoice
                </a>

            </div>

            <!-- Invoice Statistics -->

            <div class="invoice-summary-grid">

                <div class="invoice-summary-card">
                    <div class="invoice-summary-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3>Total Invoices</h3>
                        <strong><?php echo (int)$stats['total']; ?></strong>
                    </div>
                </div>

                <div class="invoice-summary-card">
                    <div class="invoice-summary-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h3>Total Invoice Amount</h3>
                        <strong>
                            ৳<?php echo number_format((float)$stats['total_amount'], 2); ?>
                        </strong>
                    </div>
                </div>

                <div class="invoice-summary-card">
                    <div class="invoice-summary-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3>Total Discount</h3>
                        <strong>
                            ৳<?php echo number_format((float)$stats['discount'], 2); ?>
                        </strong>
                    </div>
                </div>

                <div class="invoice-summary-card">
                    <div class="invoice-summary-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div>
                        <h3>Total Tax</h3>
                        <strong>
                            ৳<?php echo number_format((float)$stats['tax'], 2); ?>
                        </strong>
                    </div>
                </div>

            </div>

            <!-- Invoice Records -->

            <div class="invoice-management-card">

                <div class="invoice-management-header">

                    <h3>Invoice Records</h3>

                    <form method="GET" class="invoice-search-form">

                        <input
                            type="search"
                            name="search"
                            placeholder="Search Invoice ID or Booking ID..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                        <button type="submit" class="invoice-search-btn">
                            <i class="fas fa-search"></i>
                            Search
                        </button>

                        <a href="invoices.php" class="invoice-reset-btn">
                            Reset
                        </a>

                    </form>

                </div>

                <div class="invoice-table-wrapper">

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Booking ID</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Issued At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($invoices && $invoices->num_rows > 0): ?>

                            <?php while ($invoice = $invoices->fetch_assoc()): ?>

                                <tr>

                                    <td class="invoice-id">
                                        #<?php echo (int)$invoice['invoice_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$invoice['booking_id']; ?>
                                    </td>

                                    <td>
                                        ৳<?php echo number_format((float)$invoice['subtotal'], 2); ?>
                                    </td>

                                    <td>
                                        ৳<?php echo number_format((float)$invoice['discount'], 2); ?>
                                    </td>

                                    <td>
                                        ৳<?php echo number_format((float)$invoice['tax'], 2); ?>
                                    </td>

                                    <td class="invoice-total">
                                        ৳<?php echo number_format((float)$invoice['total'], 2); ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $invoice['issued_at']
                                            ? date('d M Y, h:i A', strtotime($invoice['issued_at']))
                                            : '-';
                                        ?>
                                    </td>

                                    <td>

                                        <div class="invoice-actions">

                                            <a href="view_invoice.php?invoice_id=<?php echo (int)$invoice['invoice_id']; ?>"
                                               class="invoice-action-btn invoice-view-btn"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="edit_invoice.php?invoice_id=<?php echo (int)$invoice['invoice_id']; ?>"
                                               class="invoice-action-btn invoice-edit-btn"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a href="delete_invoice.php?invoice_id=<?php echo (int)$invoice['invoice_id']; ?>"
                                               class="invoice-action-btn invoice-delete-btn"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="invoice-empty">
                                    <i class="fas fa-file-invoice"></i>
                                    No invoice records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>

</body>
</html>

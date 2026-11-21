
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
   Payment Statistics
========================= */

$stats = [
    'total' => 0,
    'completed' => 0,
    'pending' => 0,
    'total_amount' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS total_amount
    FROM payments
");

if ($result) {
    $stats = $result->fetch_assoc();
}

/* =========================
   Search and Filter
========================= */

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatus = ['pending', 'completed', 'failed', 'refunded'];

$sql = "
    SELECT
        payment_id,
        booking_id,
        amount,
        method,
        transaction_reference,
        status,
        paid_at,
        created_at
    FROM payments
    WHERE 1=1
";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= "
        AND (
            CAST(payment_id AS CHAR) LIKE ?
            OR CAST(booking_id AS CHAR) LIKE ?
            OR transaction_reference LIKE ?
        )
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types .= 'sss';
}

if (in_array($status, $allowedStatus, true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$payments = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Management | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .payment-page {
            padding: 25px 30px 35px;
        }

        .payment-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .payment-page-header h2 {
            color: #1e293b;
            font-size: 23px;
            margin-bottom: 5px;
        }

        .payment-page-header p {
            color: #64748b;
            font-size: 14px;
        }

        .payment-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .payment-summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            background: #eff6ff;
            color: #2563eb;
        }

        .payment-summary-card h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .payment-summary-card strong {
            color: #1e293b;
            font-size: 21px;
        }

        .payment-management-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
        }

        .payment-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-management-header h3 {
            color: #1e293b;
            font-size: 18px;
        }

        .payment-filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .payment-filter-form input,
        .payment-filter-form select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 13px;
            color: #334155;
            outline: none;
        }

        .payment-filter-form input:focus,
        .payment-filter-form select:focus {
            border-color: #2563eb;
        }

        .payment-filter-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .payment-reset-btn {
            background: #f1f5f9;
            color: #475569;
            padding: 10px 14px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
        }

        .payment-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        .payment-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .payment-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
        }

        .payment-table tr:hover {
            background: #f8fafc;
        }

        .payment-id {
            color: #2563eb;
            font-weight: 700;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .payment-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-status.completed {
            background: #dcfce7;
            color: #166534;
        }

        .payment-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-status.refunded {
            background: #e0e7ff;
            color: #3730a3;
        }

        .payment-actions {
            display: flex;
            gap: 7px;
        }

        .payment-action-btn {
            width: 31px;
            height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
        }

        .payment-view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .payment-edit-btn {
            background: #fef3c7;
            color: #b45309;
        }

        .payment-delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        .payment-empty {
            text-align: center;
            padding: 35px !important;
            color: #64748b !important;
        }

        @media (max-width: 1000px) {
            .payment-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .payment-page {
                padding: 20px 15px;
            }

            .payment-summary-grid {
                grid-template-columns: 1fr;
            }

            .payment-filter-form {
                width: 100%;
            }

            .payment-filter-form input {
                width: 100%;
            }
        }
        .payment-add-btn {
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
    transition: 0.2s;
}

.payment-add-btn:hover {
    background: #1d4ed8;
    color: #fff;
}
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="payment-page">

            <div class="payment-page-header">
    <div>
        <h2>Payment Management</h2>
        <p>Manage and monitor all customer payments.</p>
    </div>

    <a href="add_payment.php" class="payment-add-btn">
        <i class="fas fa-plus"></i>
        Add Payment
    </a>
</div>

            <!-- Statistics -->

            <div class="payment-summary-grid">

                <div class="payment-summary-card">
                    <div class="payment-summary-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3>Total Payments</h3>
                        <strong><?php echo (int)$stats['total']; ?></strong>
                    </div>
                </div>

                <div class="payment-summary-card">
                    <div class="payment-summary-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3>Completed Payments</h3>
                        <strong><?php echo (int)$stats['completed']; ?></strong>
                    </div>
                </div>

                <div class="payment-summary-card">
                    <div class="payment-summary-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3>Pending Payments</h3>
                        <strong><?php echo (int)$stats['pending']; ?></strong>
                    </div>
                </div>

                <div class="payment-summary-card">
                    <div class="payment-summary-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h3>Completed Amount</h3>
                        <strong>
                            ৳<?php echo number_format((float)$stats['total_amount'], 2); ?>
                        </strong>
                    </div>
                </div>

            </div>

            <!-- Payment List -->

            <div class="payment-management-card">

                <div class="payment-management-header">

                    <h3>Payment Records</h3>

                    <form method="GET" class="payment-filter-form">

                        <input
                            type="search"
                            name="search"
                            placeholder="Search payment, booking or reference..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                        <select name="status">
                            <option value="">All Status</option>

                            <?php foreach ($allowedStatus as $item): ?>
                                <option value="<?php echo $item; ?>"
                                    <?php echo $status === $item ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($item); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="payment-filter-btn">
                            <i class="fas fa-search"></i> Search
                        </button>

                        <a href="payments.php" class="payment-reset-btn">
                            Reset
                        </a>

                    </form>

                </div>

                <div class="payment-table-wrapper">

                    <table class="payment-table">

                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Booking ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction Reference</th>
                                <th>Status</th>
                                <th>Paid At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($payments && $payments->num_rows > 0): ?>

                            <?php while ($payment = $payments->fetch_assoc()): ?>

                                <tr>

                                    <td class="payment-id">
                                        #<?php echo (int)$payment['payment_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$payment['booking_id']; ?>
                                    </td>

                                    <td>
                                        ৳<?php echo number_format((float)$payment['amount'], 2); ?>
                                    </td>

                                    <td>
                                        <?php echo ucfirst(htmlspecialchars($payment['method'])); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($payment['transaction_reference'] ?? '-'); ?>
                                    </td>

                                    <td>
                                        <span class="payment-status <?php echo htmlspecialchars($payment['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        echo $payment['paid_at']
                                            ? date('d M Y, h:i A', strtotime($payment['paid_at']))
                                            : '-';
                                        ?>
                                    </td>

                                    <td>
                                        <div class="payment-actions">

                                            <a href="view_payment.php?payment_id=<?php echo (int)$payment['payment_id']; ?>"
                                               class="payment-action-btn payment-view-btn"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="edit_payment.php?payment_id=<?php echo (int)$payment['payment_id']; ?>"
                                               class="payment-action-btn payment-edit-btn"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a href="delete_payment.php?payment_id=<?php echo (int)$payment['payment_id']; ?>"
                                               class="payment-action-btn payment-delete-btn"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="payment-empty">
                                    <i class="fas fa-receipt"></i>
                                    No payment records found.
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

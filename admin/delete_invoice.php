
<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$invoice_id = filter_input(INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT);

if (!$invoice_id) {
    header("Location: invoices.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT invoice_id, booking_id, total, issued_at
    FROM invoices
    WHERE invoice_id = ?
");

$stmt->bind_param("i", $invoice_id);
$stmt->execute();

$result = $stmt->get_result();
$invoice = $result->fetch_assoc();
$stmt->close();

if (!$invoice) {
    header("Location: invoices.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['confirm_delete'])) {

        $stmt = $conn->prepare("
            DELETE FROM invoices
            WHERE invoice_id = ?
        ");

        $stmt->bind_param("i", $invoice_id);

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: invoices.php?success=deleted");
            exit;
        } else {
            $error = "Failed to delete invoice. Please try again.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delete Invoice</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .invoice-delete-page {
            padding: 25px 30px 35px;
            margin-top: 20px;
            color: #1e293b;
        }

        .invoice-delete-header {
            margin-bottom: 25px;
        }

        .invoice-delete-header h2 {
            margin: 0 0 7px;
            font-size: 24px;
        }

        .invoice-delete-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .invoice-delete-card {
            max-width: 650px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .invoice-delete-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fef2f2;
            color: #dc2626;
            border-radius: 50%;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .invoice-delete-card h3 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #1e293b;
        }

        .invoice-delete-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .invoice-delete-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin: 22px 0;
        }

        .invoice-delete-info div {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 8px 0;
            font-size: 14px;
        }

        .invoice-delete-info span {
            color: #64748b;
        }

        .invoice-delete-info strong {
            color: #1e293b;
        }

        .invoice-delete-error {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 7px;
            font-size: 14px;
        }

        .invoice-delete-actions {
            display: flex;
            gap: 12px;
            margin-top: 22px;
        }

        .invoice-confirm-btn,
        .invoice-cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 19px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .invoice-confirm-btn {
            background: #dc2626;
            color: #fff;
        }

        .invoice-confirm-btn:hover {
            background: #b91c1c;
        }

        .invoice-cancel-btn {
            background: #f1f5f9;
            color: #334155;
        }

        .invoice-cancel-btn:hover {
            background: #e2e8f0;
        }

        @media (max-width: 600px) {
            .invoice-delete-page {
                padding: 20px 15px;
            }

            .invoice-delete-card {
                padding: 20px;
            }

            .invoice-delete-info div {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="invoice-delete-page">

            <div class="invoice-delete-header">
                <h2>Delete Invoice</h2>
                <p>Review the invoice information before deleting.</p>
            </div>

            <div class="invoice-delete-card">

                <div class="invoice-delete-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h3>Are you sure you want to delete this invoice?</h3>

                <p>
                    This action will permanently remove this invoice record.
                    This operation cannot be undone.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="invoice-delete-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="invoice-delete-info">

                    <div>
                        <span>Invoice ID</span>
                        <strong>#<?php echo (int)$invoice['invoice_id']; ?></strong>
                    </div>

                    <div>
                        <span>Booking ID</span>
                        <strong>#<?php echo (int)$invoice['booking_id']; ?></strong>
                    </div>

                    <div>
                        <span>Total Amount</span>
                        <strong>
                            ৳<?php echo number_format((float)$invoice['total'], 2); ?>
                        </strong>
                    </div>

                    <div>
                        <span>Issued At</span>
                        <strong>
                            <?php
                            echo $invoice['issued_at']
                                ? htmlspecialchars($invoice['issued_at'])
                                : 'Not specified';
                            ?>
                        </strong>
                    </div>

                </div>

                <form method="POST">

                    <div class="invoice-delete-actions">

                        <button
                            type="submit"
                            name="confirm_delete"
                            value="1"
                            class="invoice-confirm-btn"
                            onclick="return confirm('Are you sure you want to permanently delete this invoice?');"
                        >
                            <i class="fas fa-trash"></i>
                            Yes, Delete Invoice
                        </button>

                        <a href="invoices.php" class="invoice-cancel-btn">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

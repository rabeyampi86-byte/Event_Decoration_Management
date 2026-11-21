
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
    SELECT invoice_id, booking_id, subtotal, discount, tax, total, issued_at
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

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Invoice</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .invoice-view-page {
            padding: 25px 30px 35px;
            margin-top: 20px;
            color: #1e293b;
        }

        .invoice-view-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .invoice-view-header h2 {
            margin: 0 0 6px;
            font-size: 24px;
        }

        .invoice-view-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .invoice-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .invoice-back-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .invoice-view-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .invoice-card-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding-bottom: 22px;
            border-bottom: 1px solid #e2e8f0;
        }

        .invoice-card-heading h3 {
            margin: 0 0 7px;
            font-size: 21px;
        }

        .invoice-card-heading p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .invoice-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 10px;
            font-size: 22px;
        }

        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
            padding: 25px 0;
        }

        .invoice-info-item small {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .invoice-info-item strong {
            font-size: 15px;
            color: #1e293b;
        }

        .invoice-calculation {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            margin-top: 5px;
        }

        .invoice-calculation-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            color: #475569;
            font-size: 14px;
        }

        .invoice-calculation-row strong {
            color: #1e293b;
        }

        .invoice-total-row {
            border-top: 1px solid #e2e8f0;
            margin-top: 10px;
            padding-top: 18px;
            font-size: 17px;
            font-weight: 700;
        }

        .invoice-total-row strong {
            color: #2563eb;
            font-size: 20px;
        }

        .invoice-view-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .invoice-edit-btn,
        .invoice-print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .invoice-edit-btn {
            background: #2563eb;
            color: #fff;
        }

        .invoice-edit-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .invoice-print-btn {
            background: #f1f5f9;
            color: #334155;
        }

        .invoice-print-btn:hover {
            background: #e2e8f0;
        }

        @media print {
            .sidebar,
            .admin-sidebar,
            .header,
            .admin-header,
            .invoice-view-header,
            .invoice-view-actions {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                width: 100% !important;
            }

            .invoice-view-page {
                margin: 0;
                padding: 0;
            }

            .invoice-view-card {
                max-width: 100%;
                box-shadow: none;
                border: none;
            }
        }

        @media (max-width: 600px) {
            .invoice-view-page {
                padding: 20px 15px;
            }

            .invoice-view-card {
                padding: 18px;
            }

            .invoice-info-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .invoice-view-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="invoice-view-page">

            <div class="invoice-view-header">
                <div>
                    <h2>Invoice Details</h2>
                    <p>View complete information about this invoice.</p>
                </div>

                <a href="invoices.php" class="invoice-back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Invoices
                </a>
            </div>

            <div class="invoice-view-card">

                <div class="invoice-card-heading">
                    <div>
                        <h3>Invoice #<?php echo e($invoice['invoice_id']); ?></h3>
                        <p>Event Decoration Management System</p>
                    </div>

                    <div class="invoice-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>

                <div class="invoice-info-grid">

                    <div class="invoice-info-item">
                        <small>Invoice ID</small>
                        <strong>#<?php echo e($invoice['invoice_id']); ?></strong>
                    </div>

                    <div class="invoice-info-item">
                        <small>Booking ID</small>
                        <strong>#<?php echo e($invoice['booking_id']); ?></strong>
                    </div>

                    <div class="invoice-info-item">
                        <small>Issued Date</small>
                        <strong>
                            <?php
                            echo $invoice['issued_at']
                                ? e(date('d M Y', strtotime($invoice['issued_at'])))
                                : 'Not specified';
                            ?>
                        </strong>
                    </div>

                    <div class="invoice-info-item">
                        <small>Issued Time</small>
                        <strong>
                            <?php
                            echo $invoice['issued_at']
                                ? e(date('h:i A', strtotime($invoice['issued_at'])))
                                : 'Not specified';
                            ?>
                        </strong>
                    </div>

                </div>

                <div class="invoice-calculation">

                    <div class="invoice-calculation-row">
                        <span>Subtotal</span>
                        <strong>
                            ৳<?php echo number_format((float)$invoice['subtotal'], 2); ?>
                        </strong>
                    </div>

                    <div class="invoice-calculation-row">
                        <span>Discount</span>
                        <strong>
                            − ৳<?php echo number_format((float)$invoice['discount'], 2); ?>
                        </strong>
                    </div>

                    <div class="invoice-calculation-row">
                        <span>Tax</span>
                        <strong>
                            + ৳<?php echo number_format((float)$invoice['tax'], 2); ?>
                        </strong>
                    </div>

                    <div class="invoice-calculation-row invoice-total-row">
                        <span>Total Amount</span>
                        <strong>
                            ৳<?php echo number_format((float)$invoice['total'], 2); ?>
                        </strong>
                    </div>

                </div>

                <div class="invoice-view-actions">

                    <a href="edit_invoice.php?invoice_id=<?php echo (int)$invoice['invoice_id']; ?>"
                       class="invoice-edit-btn">
                        <i class="fas fa-edit"></i>
                        Edit Invoice
                    </a>

                    <button type="button"
                            class="invoice-print-btn"
                            onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Print Invoice
                    </button>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

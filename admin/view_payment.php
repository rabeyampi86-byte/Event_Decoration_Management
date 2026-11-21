
<?php
include_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);

if (!$payment_id) {
    header("Location: payments.php");
    exit;
}

/* =====================================
   Fetch Payment + Booking Information
===================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        b.status AS booking_status,
        b.event_date,
        b.package_name_snapshot
    FROM payments p
    LEFT JOIN bookings b
        ON p.booking_id = b.booking_id
    WHERE p.payment_id = ?
");

$stmt->bind_param("i", $payment_id);
$stmt->execute();

$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    header("Location: payments.php?error=not_found");
    exit;
}

/* =====================================
   Receipt Eligibility
===================================== */

$canPrintReceipt = (
    $payment['status'] === 'completed' &&
    $payment['booking_status'] === 'completed'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Payment | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .payment-view-page {
            padding: 25px 30px 35px;
        }

        .payment-view-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
        }

        .payment-view-card h2 {
            color: #1e293b;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .payment-view-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .payment-details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .payment-detail-item {
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .payment-detail-item label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .payment-detail-item strong {
            color: #1e293b;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .payment-view-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .payment-view-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-view-status.completed {
            background: #dcfce7;
            color: #166534;
        }

        .payment-view-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-view-status.refunded {
            background: #e0e7ff;
            color: #3730a3;
        }

        .payment-view-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .payment-view-actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .payment-view-edit {
            background: #2563eb;
            color: #fff;
        }

        .payment-view-receipt {
            background: #16a34a;
            color: #fff;
        }

        .payment-view-receipt:hover {
            background: #15803d;
        }

        .payment-view-back {
            background: #f1f5f9;
            color: #475569;
        }

        .receipt-notice {
            margin-top: 20px;
            padding: 13px 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            font-size: 13px;
        }

        @media(max-width:650px) {
            .payment-view-page {
                padding: 20px 15px;
            }

            .payment-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="payment-view-page">

            <div class="payment-view-card">

                <h2><i class="fas fa-receipt"></i> Payment Details</h2>

                <p class="payment-view-description">
                    Complete information about this payment record.
                </p>

                <div class="payment-details-grid">

                    <div class="payment-detail-item">
                        <label>Payment ID</label>
                        <strong>#<?php echo (int)$payment['payment_id']; ?></strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Booking ID</label>
                        <strong>#<?php echo (int)$payment['booking_id']; ?></strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Amount</label>
                        <strong>
                            ৳<?php echo number_format((float)$payment['amount'], 2); ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Payment Method</label>
                        <strong>
                            <?php echo ucfirst(htmlspecialchars($payment['method'])); ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Transaction Reference</label>
                        <strong>
                            <?php echo htmlspecialchars($payment['transaction_reference'] ?? '-'); ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Payment Status</label>
                        <strong>
                            <span class="payment-view-status <?php echo htmlspecialchars($payment['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                            </span>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Booking Status</label>
                        <strong>
                            <?php echo htmlspecialchars(ucfirst($payment['booking_status'] ?? 'Unknown')); ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Event Date</label>
                        <strong>
                            <?php
                            echo !empty($payment['event_date'])
                                ? date('d M Y', strtotime($payment['event_date']))
                                : 'Not available';
                            ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Package</label>
                        <strong>
                            <?php echo htmlspecialchars($payment['package_name_snapshot'] ?? '-'); ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Paid At</label>
                        <strong>
                            <?php
                            echo $payment['paid_at']
                                ? date('d M Y, h:i A', strtotime($payment['paid_at']))
                                : 'Not recorded';
                            ?>
                        </strong>
                    </div>

                    <div class="payment-detail-item">
                        <label>Created At</label>
                        <strong>
                            <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?>
                        </strong>
                    </div>

                </div>

                <div class="payment-view-actions">

                    <a href="edit_payment.php?payment_id=<?php echo (int)$payment_id; ?>"
                       class="payment-view-edit">
                        <i class="fas fa-edit"></i>
                        Edit Payment
                    </a>

                    <?php if ($canPrintReceipt): ?>

                        <a href="print-payment-receipt.php?payment_id=<?php echo (int)$payment_id; ?>"
                           class="payment-view-receipt">
                            <i class="fas fa-file-pdf"></i>
                            Print Payment Receipt
                        </a>

                    <?php endif; ?>

                    <a href="payments.php" class="payment-view-back">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>

                </div>

                <?php if (!$canPrintReceipt): ?>

                    <div class="receipt-notice">
                        <i class="fas fa-info-circle"></i>

                        Receipt will be available after both Payment and Booking
                        statuses become Completed.
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>


<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);

if (!$payment_id) {
    $payment_id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
}

if (!$payment_id) {
    header("Location: payments.php");
    exit;
}

// Fetch payment
$stmt = $conn->prepare("
    SELECT payment_id, booking_id, amount, method, status
    FROM payments
    WHERE payment_id = ?
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

$error = '';

// Delete after confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $delete = $conn->prepare("
        DELETE FROM payments
        WHERE payment_id = ?
    ");

    if ($delete) {
        $delete->bind_param("i", $payment_id);

        if ($delete->execute()) {
            $delete->close();
            header("Location: payments.php?success=deleted");
            exit;
        } else {
            $error = "Unable to delete payment. Please try again.";
        }

        $delete->close();
    } else {
        $error = "Database query preparation failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delete Payment | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .payment-delete-page {
            padding: 25px 30px 35px;
        }

        .payment-delete-card {
            max-width: 600px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
        }

        .payment-delete-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .payment-delete-card h2 {
            color: #1e293b;
            font-size: 21px;
            margin-bottom: 12px;
        }

        .payment-delete-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .payment-delete-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 18px;
        }

        .payment-delete-info div {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 8px 0;
            font-size: 13px;
        }

        .payment-delete-info span {
            color: #64748b;
        }

        .payment-delete-info strong {
            color: #1e293b;
            text-align: right;
        }

        .payment-delete-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .payment-confirm-delete,
        .payment-cancel-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .payment-confirm-delete {
            background: #dc2626;
            color: #fff;
        }

        .payment-confirm-delete:hover {
            background: #b91c1c;
        }

        .payment-cancel-delete {
            background: #f1f5f9;
            color: #475569;
        }

        .payment-delete-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 7px;
            margin-top: 15px;
            font-size: 14px;
        }

        @media(max-width:650px) {
            .payment-delete-page {
                padding: 20px 15px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="payment-delete-page">

            <div class="payment-delete-card">

                <div class="payment-delete-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h2>Delete Payment</h2>

                <p>
                    Are you sure you want to delete this payment record?
                    This action cannot be undone.
                </p>

                <div class="payment-delete-info">

                    <div>
                        <span>Payment ID</span>
                        <strong>#<?php echo (int)$payment['payment_id']; ?></strong>
                    </div>

                    <div>
                        <span>Booking ID</span>
                        <strong>#<?php echo (int)$payment['booking_id']; ?></strong>
                    </div>

                    <div>
                        <span>Amount</span>
                        <strong>৳<?php echo number_format((float)$payment['amount'], 2); ?></strong>
                    </div>

                    <div>
                        <span>Method</span>
                        <strong><?php echo ucfirst(htmlspecialchars($payment['method'])); ?></strong>
                    </div>

                    <div>
                        <span>Status</span>
                        <strong><?php echo ucfirst(htmlspecialchars($payment['status'])); ?></strong>
                    </div>

                </div>

                <?php if ($error !== ''): ?>
                    <div class="payment-delete-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="payment-delete-actions">

                    <form method="POST">
                        <input
                            type="hidden"
                            name="payment_id"
                            value="<?php echo (int)$payment_id; ?>"
                        >

                        <button type="submit" class="payment-confirm-delete">
                            <i class="fas fa-trash"></i>
                            Confirm Delete
                        </button>
                    </form>

                    <a href="payments.php" class="payment-cancel-delete">
                        Cancel
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

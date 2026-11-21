
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
    header("Location: payments.php");
    exit;
}

// Fetch existing payment
$stmt = $conn->prepare("
    SELECT *
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

$amount = $payment['amount'];
$method = $payment['method'];
$transaction_reference = $payment['transaction_reference'] ?? '';
$status = $payment['status'];

$paid_at = $payment['paid_at']
    ? date('Y-m-d\TH:i', strtotime($payment['paid_at']))
    : '';

$allowedMethods = ['cash', 'bkash', 'nagad'];
$allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = trim($_POST['amount'] ?? '');
    $method = $_POST['method'] ?? '';
    $transaction_reference = trim($_POST['transaction_reference'] ?? '');
    $status = $_POST['status'] ?? '';
    $paid_at = trim($_POST['paid_at'] ?? '');

    if (!is_numeric($amount) || (float)$amount <= 0) {
        $error = "Please enter a valid payment amount.";
    } elseif (!in_array($method, $allowedMethods, true)) {
        $error = "Invalid payment method.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid payment status.";
    } elseif (strlen($transaction_reference) > 191) {
        $error = "Transaction reference cannot exceed 191 characters.";
    } else {

        $amount = (float)$amount;
        $referenceValue = ($transaction_reference === '') ? null : $transaction_reference;
        $paidAtValue = ($paid_at === '') ? null : date('Y-m-d H:i:s', strtotime($paid_at));

        $update = $conn->prepare("
            UPDATE payments
            SET amount = ?,
                method = ?,
                transaction_reference = ?,
                status = ?,
                paid_at = ?
            WHERE payment_id = ?
        ");

        if ($update) {
            $update->bind_param(
                "dssssi",
                $amount,
                $method,
                $referenceValue,
                $status,
                $paidAtValue,
                $payment_id
            );

            if ($update->execute()) {
                $update->close();
                header("Location: view_payment.php?payment_id=" . $payment_id . "&success=updated");
                exit;
            } else {
                $error = "Unable to update payment. Please try again.";
            }

            $update->close();
        } else {
            $error = "Database query preparation failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Payment | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .payment-form-page {
            padding: 25px 30px 35px;
        }

        .payment-form-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
        }

        .payment-form-card h2 {
            color: #1e293b;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .payment-form-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .payment-booking-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 13px 15px;
            border-radius: 7px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .payment-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .payment-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .payment-form-group label {
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .payment-form-group input,
        .payment-form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        .payment-form-group input:focus,
        .payment-form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
        }

        .payment-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .payment-submit-btn,
        .payment-cancel-btn {
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

        .payment-submit-btn {
            background: #2563eb;
            color: #fff;
        }

        .payment-submit-btn:hover {
            background: #1d4ed8;
        }

        .payment-cancel-btn {
            background: #f1f5f9;
            color: #475569;
        }

        .payment-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media(max-width:650px) {
            .payment-form-page {
                padding: 20px 15px;
            }

            .payment-form-grid {
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

        <section class="payment-form-page">

            <div class="payment-form-card">

                <h2><i class="fas fa-edit"></i> Edit Payment</h2>

                <p class="payment-form-description">
                    Update the payment information below.
                </p>

                <div class="payment-booking-info">
                    <strong>Booking ID:</strong>
                    #<?php echo (int)$payment['booking_id']; ?>
                    <br>
                    <strong>Payment ID:</strong>
                    #<?php echo (int)$payment_id; ?>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="payment-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="payment-form-grid">

                        <div class="payment-form-group">
                            <label for="amount">Amount (৳) *</label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="0.01"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string)$amount); ?>"
                                required
                            >
                        </div>

                        <div class="payment-form-group">
                            <label for="method">Payment Method *</label>
                            <select id="method" name="method" required>
                                <?php foreach ($allowedMethods as $item): ?>
                                    <option value="<?php echo $item; ?>"
                                        <?php echo $method === $item ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="payment-form-group">
                            <label for="transaction_reference">Transaction Reference</label>
                            <input
                                type="text"
                                id="transaction_reference"
                                name="transaction_reference"
                                maxlength="191"
                                value="<?php echo htmlspecialchars($transaction_reference); ?>"
                            >
                        </div>

                        <div class="payment-form-group">
                            <label for="status">Payment Status *</label>
                            <select id="status" name="status" required>
                                <?php foreach ($allowedStatuses as $item): ?>
                                    <option value="<?php echo $item; ?>"
                                        <?php echo $status === $item ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="payment-form-group">
                            <label for="paid_at">Paid At</label>
                            <input
                                type="datetime-local"
                                id="paid_at"
                                name="paid_at"
                                value="<?php echo htmlspecialchars($paid_at); ?>"
                            >
                        </div>

                    </div>

                    <div class="payment-form-actions">

                        <button type="submit" class="payment-submit-btn">
                            <i class="fas fa-save"></i> Update Payment
                        </button>

                        <a href="payments.php" class="payment-cancel-btn">
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

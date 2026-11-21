
<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';

$booking_id = '';
$amount = '';
$method = 'cash';
$transaction_reference = '';
$status = 'pending';
$paid_at = '';

$allowedMethods = ['cash', 'bkash', 'nagad'];
$allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $booking_id = trim($_POST['booking_id'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $method = $_POST['method'] ?? 'cash';
    $transaction_reference = trim($_POST['transaction_reference'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    $paid_at = trim($_POST['paid_at'] ?? '');

    if ($booking_id === '' || !ctype_digit($booking_id) || (int)$booking_id < 1) {
        $error = "Please enter a valid Booking ID.";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = "Please enter a valid payment amount.";
    } elseif (!in_array($method, $allowedMethods, true)) {
        $error = "Invalid payment method.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid payment status.";
    } else {

        $booking_id = (int)$booking_id;
        $amount = (float)$amount;
        $paidAtValue = ($paid_at === '') ? null : date('Y-m-d H:i:s', strtotime($paid_at));
        $referenceValue = ($transaction_reference === '') ? null : $transaction_reference;

        // Verify that the Booking ID exists
        $check = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE booking_id = ?
        ");

        $check->bind_param("i", $booking_id);
        $check->execute();
        $bookingResult = $check->get_result();
        $bookingExists = $bookingResult->num_rows > 0;
        $check->close();

        if (!$bookingExists) {
            $error = "This Booking ID does not exist.";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO payments
                (booking_id, amount, method, transaction_reference, status, paid_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($stmt) {
                $stmt->bind_param(
                    "idssss",
                    $booking_id,
                    $amount,
                    $method,
                    $referenceValue,
                    $status,
                    $paidAtValue
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: payments.php?success=added");
                    exit;
                } else {
                    $error = "Unable to add payment. Please try again.";
                }

                $stmt->close();
            } else {
                $error = "Database query preparation failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment | Admin Panel</title>

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

        .payment-form-group.full-width {
            grid-column: 1 / -1;
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

            .payment-form-group.full-width {
                grid-column: auto;
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

                <h2><i class="fas fa-plus-circle"></i> Add New Payment</h2>

                <p class="payment-form-description">
                    Enter the booking and payment information below.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="payment-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="payment-form-grid">

                        <div class="payment-form-group">
                            <label for="booking_id">Booking ID *</label>
                            <input
                                type="number"
                                id="booking_id"
                                name="booking_id"
                                min="1"
                                value="<?php echo htmlspecialchars((string)$booking_id); ?>"
                                placeholder="Enter existing Booking ID"
                                required
                            >
                        </div>

                        <div class="payment-form-group">
                            <label for="amount">Amount (৳) *</label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="0.01"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string)$amount); ?>"
                                placeholder="Enter amount"
                                required
                            >
                        </div>

                        <div class="payment-form-group">
                            <label for="method">Payment Method *</label>
                            <select id="method" name="method" required>
                                <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                <option value="bkash" <?php echo $method === 'bkash' ? 'selected' : ''; ?>>bKash</option>
                                <option value="nagad" <?php echo $method === 'nagad' ? 'selected' : ''; ?>>Nagad</option>
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
                                placeholder="Enter transaction reference"
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
                            <i class="fas fa-save"></i> Save Payment
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

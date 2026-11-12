
<?php
include_once 'layout-start.php';

/* =====================================
   CSRF Token
===================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

/* =====================================
   Update Payment Status
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $message = "Invalid request. Please try again.";
        $messageType = "error";

    } else {

        $paymentId = filter_input(
            INPUT_POST,
            'payment_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = $_POST['new_status'] ?? '';

        if (
            $paymentId &&
            in_array($newStatus, ['completed', 'failed'], true)
        ) {

            $stmt = $conn->prepare("
                UPDATE payments
                SET
                    status = ?,
                    paid_at = CASE
                        WHEN ? = 'completed' THEN NOW()
                        ELSE NULL
                    END
                WHERE payment_id = ?
                AND status = 'pending'
            ");

            $stmt->bind_param(
                "ssi",
                $newStatus,
                $newStatus,
                $paymentId
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {
                    $message = $newStatus === 'completed'
                        ? "Payment successfully marked as completed."
                        : "Payment marked as failed.";

                    $messageType = "success";
                } else {
                    $message = "Payment not found or already processed.";
                    $messageType = "error";
                }

            } else {

                $message = "Unable to update payment.";
                $messageType = "error";
            }

            $stmt->close();

        } else {

            $message = "Invalid payment information.";
            $messageType = "error";
        }
    }
}

/* =====================================
   Payment Statistics
===================================== */

$paymentStats = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0,
    'failed' => 0,
    'refunded' => 0
];

$result = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM payments
    GROUP BY status
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($paymentStats[$row['status']])) {
            $paymentStats[$row['status']] = (int)$row['total'];
        }

        $paymentStats['total'] += (int)$row['total'];
    }
}

/* =====================================
   Get Payment Records
===================================== */

$paymentsResult = $conn->query("
    SELECT
        p.payment_id,
        p.booking_id,
        p.amount,
        p.method,
        p.transaction_reference,
        p.status,
        p.paid_at,
        u.full_name,
        u.email,
        b.event_date
    FROM payments p
    LEFT JOIN bookings b
        ON p.booking_id = b.booking_id
    LEFT JOIN customers c
        ON b.customer_id = c.customer_id
    LEFT JOIN users u
        ON c.user_id = u.user_id
    ORDER BY p.payment_id DESC
");

?>

<style>

.payment-page {
    padding-bottom: 30px;
}

/* Payment Statistics */

.payment-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin: 22px 0;
}

.payment-stat {
    background: #17111f;
    padding: 22px;
    border-radius: 12px;
    border: 1px solid #292929;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.payment-stat h3 {
    font-size: 15px;
    font-weight: 500;
    color: #b8b8b8;
    margin: 0 0 12px;
}

.payment-stat h2 {
    font-size: 25px;
    font-weight: 600;
    margin: 0;
    color: #f6f8fa;
}

/* Payment Panel */

.payment-panel {
    background: #17111f;
    padding: 22px;
    border-radius: 12px;
    border: 1px solid #292929;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-top: 20px;
}

.payment-panel h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 20px;
    color: #f6f8fa;
}

/* Alerts */

.payment-alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin: 15px 0;
    font-size: 14px;
}

.payment-alert.success {
    background: #12351f;
    color: #86efac;
}

.payment-alert.error {
    background: #351616;
    color: #fca5a5;
}

/* Payment Table */

.payment-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
    font-size: 14px;
}

.payment-table th,
.payment-table td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #292929;
    font-size: 14px;
    vertical-align: middle;
}

.payment-table th {
    background: #17111f;
    color: #d1d5db;
    font-weight: 600;
    white-space: nowrap;
}

.payment-table td {
    color: #e5e7eb;
}

.payment-table td small {
    display: inline-block;
    margin-top: 4px;
    font-size: 12px;
    color: #9ca3af;
}

.payment-table tr:hover {
    background: #17111f;
}

/* Payment Status */

.payment-status {
    display: inline-block;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.status-pending {
    background: #422f0c;
    color: #fbbf24;
}

.status-completed {
    background: #12351f;
    color: #86efac;
}

.status-failed {
    background: #351616;
    color: #fca5a5;
}

.status-refunded {
    background: #252044;
    color: #c4b5fd;
}

/* Action Buttons */

.payment-action {
    display: flex;
    gap: 7px;
}

.payment-btn {
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.btn-complete {
    background: #16a34a;
    color: white;
}

.btn-failed {
    background: #dc2626;
    color: white;
}

.payment-btn:hover {
    opacity: 0.85;
}

.no-payment {
    text-align: center;
    padding: 35px;
    color: #9ca3af;
}

/* Responsive */

@media(max-width: 1000px) {
    .payment-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 550px) {
    .payment-stats {
        grid-template-columns: 1fr;
    }

    .payment-panel {
        padding: 15px;
    }
}

</style>

<div class="payment-page">

    <div class="page-heading">
        <div>
            <h2>Payment Records</h2>
            <p>Manage and verify customer payments.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="payment-alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Payment Statistics -->

    <div class="payment-stats">

        <div class="payment-stat">
            <h3>Total Payments</h3>
            <h2><?= $paymentStats['total'] ?></h2>
        </div>

        <div class="payment-stat">
            <h3>Pending Payments</h3>
            <h2><?= $paymentStats['pending'] ?></h2>
        </div>

        <div class="payment-stat">
            <h3>Completed Payments</h3>
            <h2><?= $paymentStats['completed'] ?></h2>
        </div>

        <div class="payment-stat">
            <h3>Failed Payments</h3>
            <h2><?= $paymentStats['failed'] ?></h2>
        </div>

    </div>

    <!-- Payment Records Table -->

    <div class="payment-panel">

        <h3>All Payment Transactions</h3>

        <div class="payment-table-wrapper">

            <table class="payment-table">

                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th>Event Date</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($paymentsResult && $paymentsResult->num_rows > 0): ?>

                    <?php while ($payment = $paymentsResult->fetch_assoc()): ?>

                        <tr>

                            <td>#<?= (int)$payment['payment_id'] ?></td>

                            <td>#<?= (int)$payment['booking_id'] ?></td>

                            <td>
                                <?= htmlspecialchars($payment['full_name'] ?? 'Unknown') ?>
                                <br>
                                <small>
                                    <?= htmlspecialchars($payment['email'] ?? '') ?>
                                </small>
                            </td>

                            <td>
                                <strong><?= money($payment['amount']) ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars(ucfirst($payment['method'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($payment['transaction_reference']) ?>
                            </td>

                            <td>
                                <?= $payment['event_date']
                                    ? date('d M Y', strtotime($payment['event_date']))
                                    : 'N/A' ?>
                            </td>

                            <td>
                                <span class="payment-status status-<?= htmlspecialchars($payment['status']) ?>">
                                    <?= htmlspecialchars($payment['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= $payment['paid_at']
                                    ? date('d M Y, h:i A', strtotime($payment['paid_at']))
                                    : 'Not paid' ?>
                            </td>

                            <td>

                                <?php if ($payment['status'] === 'pending'): ?>

                                    <div class="payment-action">

                                        <form method="POST"
                                              onsubmit="return confirm('Have you verified this transaction and received the payment?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="payment_id"
                                                   value="<?= (int)$payment['payment_id'] ?>">

                                            <input type="hidden"
                                                   name="new_status"
                                                   value="completed">

                                            <button type="submit"
                                                    class="payment-btn btn-complete">
                                                Complete
                                            </button>

                                        </form>

                                        <form method="POST"
                                              onsubmit="return confirm('Are you sure you want to mark this payment as failed?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="payment_id"
                                                   value="<?= (int)$payment['payment_id'] ?>">

                                            <input type="hidden"
                                                   name="new_status"
                                                   value="failed">

                                            <button type="submit"
                                                    class="payment-btn btn-failed">
                                                Failed
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">Processed</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10" class="no-payment">
                            No payment records found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'layout-end.php'; ?>

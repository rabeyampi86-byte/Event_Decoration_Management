
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

$pageTitle = "Booking Details";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookingId || $bookingId < 1) {
    die("Invalid booking ID.");
}

// Delete booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $token)) {
        die("Invalid request.");
    }

    $deleteStmt = $conn->prepare("
        DELETE FROM bookings
        WHERE booking_id = ?
    ");

    $deleteStmt->bind_param("i", $bookingId);
    $deleteStmt->execute();

    if ($deleteStmt->affected_rows > 0) {
        header("Location: bookings.php?deleted=1");
        exit;
    }

    die("Unable to delete booking.");
}

// Booking details
$sql = "
    SELECT
        b.*,
        c.user_id,
        c.address AS customer_address,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        v.name AS venue_name,
        v.address AS venue_address
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN venues v ON b.venue_id = v.venue_id
    WHERE b.booking_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();

$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Payment history
$paymentStmt = $conn->prepare("
    SELECT
        payment_id,
        amount,
        method,
        transaction_reference,
        status,
        paid_at,
        created_at
    FROM payments
    WHERE booking_id = ?
    ORDER BY created_at DESC
");

$paymentStmt->bind_param("i", $bookingId);
$paymentStmt->execute();

$payments = $paymentStmt->get_result();

function detailsStatusClass($status) {
    return match ($status) {
        'pending' => 'status-pending',
        'quoted' => 'status-quoted',
        'confirmed' => 'status-confirmed',
        'in_progress' => 'status-progress',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => 'status-default'
    };
}

function detailsStatusLabel($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>

<?php include_once 'includes/sidebar.php'; ?>

<div class="main-content">

<?php include_once 'includes/header.php'; ?>
<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<main class="dashboard-content">

    <div class="details-page-heading">
        <div>
            <h2>Booking Details</h2>
            <p>Manage booking #<?php echo (int)$booking['booking_id']; ?></p>
        </div>

        <a href="bookings.php" class="details-back-btn">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="details-alert success">
            <i class="fas fa-check-circle"></i>
            Booking information updated successfully.
        </div>
    <?php endif; ?>

    <div class="details-layout">

        <div class="details-main">

            <!-- Customer Information -->
            <section class="details-card">

                <div class="details-card-heading">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                </div>

                <div class="details-info-grid">

                    <div class="details-info-item">
                        <span>Customer Name</span>
                        <strong><?php echo htmlspecialchars($booking['customer_name'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Email Address</span>
                        <strong><?php echo htmlspecialchars($booking['customer_email'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Phone Number</span>
                        <strong><?php echo htmlspecialchars($booking['customer_phone'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Customer Address</span>
                        <strong><?php echo htmlspecialchars($booking['customer_address'] ?? 'N/A'); ?></strong>
                    </div>

                </div>

            </section>

            <!-- Package Information -->
            <section class="details-card">

                <div class="details-card-heading">
                    <h3><i class="fas fa-box-open"></i> Package Information</h3>
                </div>

                <div class="details-info-grid">

                    <div class="details-info-item">
                        <span>Package Name</span>
                        <strong><?php echo htmlspecialchars($booking['package_name_snapshot'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Package Price</span>
                        <strong>৳<?php echo number_format((float)$booking['package_price_snapshot'], 2); ?></strong>
                    </div>

                    <div class="details-info-item full-width">
                        <span>Package Description</span>
                        <p><?php echo nl2br(htmlspecialchars($booking['package_description_snapshot'] ?? 'N/A')); ?></p>
                    </div>

                </div>

            </section>

            <!-- Event Information -->
            <section class="details-card">

                <div class="details-card-heading">
                    <h3><i class="fas fa-calendar-alt"></i> Event Information</h3>
                </div>

                <div class="details-info-grid">

                    <div class="details-info-item">
                        <span>Event Date</span>
                        <strong>
                            <?php echo $booking['event_date']
                                ? date('d M Y', strtotime($booking['event_date']))
                                : 'N/A'; ?>
                        </strong>
                    </div>

                    <div class="details-info-item">
                        <span>Start Time</span>
                        <strong><?php echo htmlspecialchars($booking['start_time'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>End Time</span>
                        <strong><?php echo htmlspecialchars($booking['end_time'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Guest Count</span>
                        <strong><?php echo (int)$booking['guest_count']; ?> Guests</strong>
                    </div>

                    <div class="details-info-item">
                        <span>Venue</span>
                        <strong><?php echo htmlspecialchars($booking['venue_name'] ?? 'Not assigned'); ?></strong>
                    </div>

                    <div class="details-info-item">
                        <span>Venue Address</span>
                        <strong><?php echo htmlspecialchars($booking['venue_address'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item full-width">
                        <span>Event Location</span>
                        <strong><?php echo htmlspecialchars($booking['event_location'] ?? 'N/A'); ?></strong>
                    </div>

                    <div class="details-info-item full-width">
                        <span>Special Request</span>
                        <p>
                            <?php echo nl2br(htmlspecialchars(
                                $booking['special_request'] ?: 'No special request.'
                            )); ?>
                        </p>
                    </div>

                </div>

            </section>

            <!-- Payment History -->
            <section class="details-card">

                <div class="details-card-heading">
                    <h3><i class="fas fa-credit-card"></i> Payment History</h3>
                </div>

                <div class="details-table-wrapper">

                    <table class="details-table">

                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction Reference</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($payments->num_rows > 0): ?>

                            <?php while ($payment = $payments->fetch_assoc()): ?>

                                <tr>
                                    <td>#<?php echo (int)$payment['payment_id']; ?></td>

                                    <td>৳<?php echo number_format((float)$payment['amount'], 2); ?></td>

                                    <td><?php echo htmlspecialchars(ucfirst($payment['method'])); ?></td>

                                    <td><?php echo htmlspecialchars($payment['transaction_reference'] ?? 'N/A'); ?></td>

                                    <td>
                                        <span class="booking-status <?php
                                            echo $payment['status'] === 'completed'
                                                ? 'status-completed'
                                                : ($payment['status'] === 'pending'
                                                    ? 'status-pending'
                                                    : 'status-default');
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($payment['status'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo $payment['paid_at']
                                            ? date('d M Y', strtotime($payment['paid_at']))
                                            : 'Not paid'; ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="details-empty">
                                    No payment records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

        <!-- Booking Summary -->
        <aside class="details-sidebar">

            <section class="details-card">

                <div class="details-card-heading">
                    <h3><i class="fas fa-info-circle"></i> Booking Summary</h3>
                </div>

                <div class="booking-summary-details">

                    <div>
                        <span>Booking ID</span>
                        <strong>#<?php echo (int)$booking['booking_id']; ?></strong>
                    </div>

                    <div>
                        <span>Booking Date</span>
                        <strong>
                            <?php echo date('d M Y', strtotime($booking['created_at'])); ?>
                        </strong>
                    </div>

                    <div>
                        <span>Package Price</span>
                        <strong>৳<?php echo number_format((float)$booking['package_price_snapshot'], 2); ?></strong>
                    </div>

                    <div>
                        <span>Quoted Total</span>
                        <strong>
                            <?php echo $booking['quoted_total'] !== null
                                ? '৳' . number_format((float)$booking['quoted_total'], 2)
                                : 'Not quoted'; ?>
                        </strong>
                    </div>

                    <div>
                        <span>Current Status</span>
                        <span class="booking-status <?php echo detailsStatusClass($booking['status']); ?>">
                            <?php echo detailsStatusLabel($booking['status']); ?>
                        </span>
                    </div>

                </div>

            </section>

        </aside>

    </div>

    <!-- Bottom Actions -->
    <div class="booking-details-actions">

        <a href="booking_form.php?id=<?php echo (int)$bookingId; ?>"
           class="booking-save-btn">
            <i class="fas fa-edit"></i> Update Booking
        </a>

        <form method="POST"
              onsubmit="return confirm('Are you sure you want to permanently delete this booking?');">

            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($csrfToken); ?>">

            <button type="submit" name="delete_booking"
                    class="booking-delete-large-btn">
                <i class="fas fa-trash"></i> Delete Booking
            </button>

        </form>

    </div>

</main>

<?php include_once 'includes/footer.php'; ?>

</div>
</head>
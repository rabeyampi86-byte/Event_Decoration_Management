
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Customer Login Check
if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$customerId = (int) ($_SESSION['customer_id'] ?? 0);

// Customer ID না থাকলে Database থেকে বের করা
if ($customerId <= 0) {
    $customerStmt = $conn->prepare("
        SELECT customer_id
        FROM customers
        WHERE user_id = ?
        LIMIT 1
    ");

    $customerStmt->bind_param("i", $userId);
    $customerStmt->execute();

    $customerResult = $customerStmt->get_result();
    $customer = $customerResult->fetch_assoc();
    $customerStmt->close();

    if (!$customer) {
        die("Customer profile not found.");
    }

    $customerId = (int) $customer['customer_id'];
    $_SESSION['customer_id'] = $customerId;
}

// Fetch only logged-in customer's bookings
$sql = "
    SELECT
        b.booking_id,
        b.package_name_snapshot,
        b.package_price_snapshot,
        b.package_description_snapshot,
        b.event_date,
        b.start_time,
        b.end_time,
        b.guest_count,
        b.status,
        b.quoted_total,
        b.event_location,
        b.special_request,
        b.created_at,

        (
            SELECT p.method
            FROM payments p
            WHERE p.booking_id = b.booking_id
            ORDER BY p.payment_id DESC
            LIMIT 1
        ) AS payment_method,

        (
            SELECT p.status
            FROM payments p
            WHERE p.booking_id = b.booking_id
            ORDER BY p.payment_id DESC
            LIMIT 1
        ) AS payment_status,

        (
            SELECT p.amount
            FROM payments p
            WHERE p.booking_id = b.booking_id
            ORDER BY p.payment_id DESC
            LIMIT 1
        ) AS payment_amount,

        (
            SELECT i.total
            FROM invoices i
            WHERE i.booking_id = b.booking_id
            LIMIT 1
        ) AS invoice_total

    FROM bookings b
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

$totalBookings = count($bookings);
$confirmedBookings = 0;
$completedBookings = 0;
$pendingBookings = 0;

foreach ($bookings as $booking) {
    if ($booking['status'] === 'confirmed' ||
        $booking['status'] === 'in_progress' ||
        $booking['status'] === 'completed') {
        $confirmedBookings++;
    }

    if ($booking['status'] === 'completed') {
        $completedBookings++;
    }

    if ($booking['status'] === 'pending' ||
        $booking['status'] === 'quoted') {
        $pendingBookings++;
    }
}

// Status formatting
function customerBookingStatus($status) {
    return match ($status) {
        'pending' => 'Pending',
        'quoted' => 'Quotation Sent',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => ucfirst($status)
    };
}

function customerPaymentStatus($status) {
    return match ($status) {
        'pending' => 'Pending',
        'completed' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        default => 'Not Available'
    };
}

function customerPaymentMethod($method) {
    return match ($method) {
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'cash' => 'Cash',
        default => 'Not Selected'
    };
}

function customerMoney($amount) {
    return '৳' . number_format((float)$amount, 2);
}

// Progress calculation
function bookingProgress($status) {
    return match ($status) {
        'pending', 'quoted' => 1,
        'confirmed' => 2,
        'in_progress', 'completed' => 3,
        default => 0
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | DecorEase</title>

    <?php include_once __DIR__ . '/includes/nav_style.php'; ?>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

       body {
    margin: 0;
    padding: 0;
    background: #f7f7f9;
    color: #333;
    font-family: Arial, sans-serif;
}

        .my-booking-page {
            max-width: 1200px;
            width: 92%;
            margin: 45px auto 70px;
        }

        .booking-page-heading {
            margin-bottom: 30px;
        }

        .booking-page-heading h1 {
            font-size: 30px;
            color: #252525;
            margin-bottom: 8px;
        }

        .booking-page-heading p {
            color: #777;
            font-size: 15px;
        }

        /* Summary Cards */

        .my-booking-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .booking-stat-card {
            background: #fff;
            padding: 22px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .booking-stat-card span {
            color: #777;
            font-size: 14px;
        }

        .booking-stat-card h3 {
            font-size: 27px;
            margin-top: 8px;
            color: #292929;
        }

        .booking-stat-card i {
            font-size: 27px;
            color: #a66b3f;
            background: #f8eee7;
            padding: 15px;
            border-radius: 10px;
        }

        /* Booking Card */

        .booking-list-title {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .customer-booking-card {
            background: #fff;
            border-radius: 13px;
            margin-bottom: 25px;
            box-shadow: 0 3px 18px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .customer-booking-header {
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #eee;
        }

        .customer-booking-header h3 {
            font-size: 19px;
            margin-bottom: 5px;
        }

        .customer-booking-header small {
            color: #888;
        }

        .booking-status-badge {
            padding: 7px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff4d9;
            color: #a36b00;
        }

        .status-quoted {
            background: #e9e7ff;
            color: #5145a5;
        }

        .status-confirmed {
            background: #e1f5e8;
            color: #237b43;
        }

        .status-progress {
            background: #e2efff;
            color: #2464a5;
        }

        .status-completed {
            background: #dff5ed;
            color: #167653;
        }

        .status-cancelled {
            background: #ffe5e5;
            color: #bd3333;
        }

        /* Booking Details */

        .customer-booking-body {
            padding: 25px;
        }

        .booking-package-title {
            font-size: 20px;
            color: #a66b3f;
            margin-bottom: 20px;
        }

        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 25px;
        }

        .booking-info-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .booking-info-item i {
            color: #a66b3f;
            font-size: 17px;
            margin-top: 3px;
            width: 20px;
        }

        .booking-info-item span {
            display: block;
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
        }

        .booking-info-item strong {
            font-size: 14px;
            color: #333;
            overflow-wrap: anywhere;
        }

        /* Progress */

        .booking-progress-section {
            background: #faf8f6;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 25px;
        }

        .booking-progress-section h4 {
            margin-bottom: 22px;
            font-size: 16px;
        }

        .booking-progress {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
        }

        .booking-progress::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 9%;
            right: 9%;
            height: 3px;
            background: #ddd;
        }

        .progress-step {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 33.33%;
        }

        .progress-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ddd;
            color: #777;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 9px;
            font-size: 13px;
        }

        .progress-step.active .progress-circle {
            background: #a66b3f;
            color: #fff;
        }

        .progress-step small {
            font-size: 12px;
            color: #777;
        }

        .progress-step.active small {
            color: #8b552e;
            font-weight: 600;
        }

        .cancelled-message {
            background: #fff0f0;
            color: #b52f2f;
            padding: 13px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Payment */

        .booking-payment-box {
            border-top: 1px solid #eee;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .payment-details {
            display: flex;
            gap: 35px;
            flex-wrap: wrap;
        }

        .payment-details span {
            display: block;
            color: #888;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .payment-details strong {
            font-size: 15px;
        }

        .payment-paid {
            color: #23834b;
        }

        .payment-pending {
            color: #b07813;
        }

        .payment-failed {
            color: #c0392b;
        }

        .payment-refunded {
            color: #555;
        }

        .booking-special-request {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .booking-special-request strong {
            display: block;
            margin-bottom: 5px;
        }

        /* Empty State */

        .no-bookings {
            background: #fff;
            padding: 65px 20px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.04);
        }

        .no-bookings i {
            font-size: 55px;
            color: #c9a58b;
            margin-bottom: 20px;
        }

        .no-bookings h3 {
            font-size: 21px;
            margin-bottom: 10px;
        }

        .no-bookings p {
            color: #888;
            margin-bottom: 22px;
        }

        .browse-packages-btn {
            display: inline-block;
            padding: 12px 22px;
            background: #a66b3f;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .browse-packages-btn:hover {
            background: #87532f;
        }

        @media (max-width: 768px) {
            .my-booking-stats {
                grid-template-columns: 1fr;
            }

            .booking-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .customer-booking-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 500px) {
            .booking-info-grid {
                grid-template-columns: 1fr;
            }

            .customer-booking-body {
                padding: 18px;
            }

            .customer-booking-header {
                padding: 18px;
            }

            .booking-progress-section {
                padding: 16px 8px;
            }

            .progress-step small {
                font-size: 10px;
            }

            .booking-payment-box {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<?php include_once __DIR__ . '/includes/navber.php'; ?>

<main class="my-booking-page">

    <!-- Page Heading -->
    <div class="booking-page-heading">
        <h1>My Bookings</h1>
        <p>Track your event bookings, payment information and booking progress.</p>
    </div>

    <!-- Summary Cards -->
    <div class="my-booking-stats">

        <div class="booking-stat-card">
            <div>
                <span>Total Bookings</span>
                <h3><?php echo $totalBookings; ?></h3>
            </div>
            <i class="fas fa-calendar-check"></i>
        </div>

        <div class="booking-stat-card">
            <div>
                <span>Confirmed Bookings</span>
                <h3><?php echo $confirmedBookings; ?></h3>
            </div>
            <i class="fas fa-check-circle"></i>
        </div>

        <div class="booking-stat-card">
            <div>
                <span>Completed Bookings</span>
                <h3><?php echo $completedBookings; ?></h3>
            </div>
            <i class="fas fa-clipboard-check"></i>
        </div>

    </div>

    <h2 class="booking-list-title">Your Booking History</h2>

    <?php if ($totalBookings > 0): ?>

        <?php foreach ($bookings as $booking): ?>

            <?php
                $status = $booking['status'];
                $progress = bookingProgress($status);

                $statusClass = match ($status) {
                    'pending' => 'status-pending',
                    'quoted' => 'status-quoted',
                    'confirmed' => 'status-confirmed',
                    'in_progress' => 'status-progress',
                    'completed' => 'status-completed',
                    'cancelled' => 'status-cancelled',
                    default => ''
                };

                $paymentStatus = $booking['payment_status'];
                $paymentClass = match ($paymentStatus) {
                    'completed' => 'payment-paid',
                    'pending' => 'payment-pending',
                    'failed' => 'payment-failed',
                    'refunded' => 'payment-refunded',
                    default => ''
                };

                $displayAmount = $booking['invoice_total']
                    ?? $booking['quoted_total']
                    ?? $booking['payment_amount']
                    ?? $booking['package_price_snapshot'];
            ?>

            <article class="customer-booking-card">

                <!-- Booking Header -->
                <div class="customer-booking-header">

                    <div>
                        <h3>
                            Booking #<?php echo (int)$booking['booking_id']; ?>
                        </h3>

                        <small>
                            Booked on
                            <?php echo date('d M Y', strtotime($booking['created_at'])); ?>
                        </small>
                    </div>

                    <span class="booking-status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars(customerBookingStatus($status)); ?>
                    </span>

                </div>

                <div class="customer-booking-body">

                    <h3 class="booking-package-title">
                        <?php echo htmlspecialchars($booking['package_name_snapshot']); ?>
                    </h3>

                    <!-- Booking Information -->
                    <div class="booking-info-grid">

                        <div class="booking-info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <span>Event Date</span>
                                <strong>
                                    <?php echo date('d M Y', strtotime($booking['event_date'])); ?>
                                </strong>
                            </div>
                        </div>

                        <div class="booking-info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <span>Event Time</span>
                                <strong>
                                    <?php
                                    echo date('h:i A', strtotime($booking['start_time']));
                                    ?>
                                    -
                                    <?php
                                    echo date('h:i A', strtotime($booking['end_time']));
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="booking-info-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <span>Guest Count</span>
                                <strong>
                                    <?php echo (int)$booking['guest_count']; ?> Guests
                                </strong>
                            </div>
                        </div>

                        <div class="booking-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <span>Event Location</span>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $booking['event_location'] ?: 'Not specified'
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="booking-info-item">
                            <i class="fas fa-box"></i>
                            <div>
                                <span>Package Price</span>
                                <strong>
                                    <?php
                                    echo customerMoney($booking['package_price_snapshot']);
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="booking-info-item">
                            <i class="fas fa-receipt"></i>
                            <div>
                                <span>Current Total</span>
                                <strong>
                                    <?php echo customerMoney($displayAmount); ?>
                                </strong>
                            </div>
                        </div>

                    </div>

                    <!-- Special Request -->
                    <?php if (!empty($booking['special_request'])): ?>

                        <div class="booking-special-request">
                            <strong>
                                <i class="fas fa-comment-alt"></i>
                                Special Request
                            </strong>

                            <?php
                            echo nl2br(
                                htmlspecialchars($booking['special_request'])
                            );
                            ?>
                        </div>

                    <?php endif; ?>

                    <!-- Booking Progress -->
                    <?php if ($status === 'cancelled'): ?>

                        <div class="cancelled-message">
                            <i class="fas fa-times-circle"></i>
                            This booking has been cancelled.
                        </div>

                    <?php else: ?>

                        <div class="booking-progress-section">

                            <h4>Booking Progress</h4>

                            <div class="booking-progress">

                                <div class="progress-step <?php echo $progress >= 1 ? 'active' : ''; ?>">
                                    <div class="progress-circle">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <small>Booking Received</small>
                                </div>

                                <div class="progress-step <?php echo $progress >= 2 ? 'active' : ''; ?>">
                                    <div class="progress-circle">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <small>Booking Confirmed</small>
                                </div>

                                <div class="progress-step <?php echo $progress >= 3 ? 'active' : ''; ?>">
                                    <div class="progress-circle">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <small>Event Progress</small>
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- Payment Information -->
                    <div class="booking-payment-box">

                        <div class="payment-details">

                            <div>
                                <span>Payment Method</span>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        customerPaymentMethod($booking['payment_method'])
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div>
                                <span>Payment Status</span>
                                <strong class="<?php echo $paymentClass; ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        customerPaymentStatus($paymentStatus)
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div>
                                <span>Payment Amount</span>
                                <strong>
                                    <?php
                                    echo $booking['payment_amount'] !== null
                                        ? customerMoney($booking['payment_amount'])
                                        : 'Not Available';
                                    ?>
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <!-- Empty Booking State -->
        <div class="no-bookings">

            <i class="fas fa-calendar-times"></i>

            <h3>No Bookings Yet</h3>

            <p>
                You haven't made any event bookings yet.
                Explore our packages and plan your special event.
            </p>

            <a href="services.php" class="browse-packages-btn">
                <i class="fas fa-search"></i>
                Browse Packages
            </a>

        </div>

    <?php endif; ?>

</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>


<?php
// =============================================
// DecorEase Admin Dashboard
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

// Database connection: $conn

// ---------------------------------------------
// Helper function for single value queries
// ---------------------------------------------

function getDashboardValue($conn, $sql, $default = 0)
{
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'] ?? $default;
    }

    return $default;
}


// =============================================
// 1. DASHBOARD STATISTICS
// =============================================

// Total Customers
$totalCustomers = getDashboardValue(
    $conn,
    "SELECT COUNT(*) AS total FROM customers"
);

// Total Bookings
$totalBookings = getDashboardValue(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings"
);

// Total Packages
$totalPackages = getDashboardValue(
    $conn,
    "SELECT COUNT(*) AS total FROM packages"
);

// Total Employees
$totalEmployees = getDashboardValue(
    $conn,
    "SELECT COUNT(*) AS total FROM employee_profiles"
);

// Completed Payment Revenue
$totalRevenue = getDashboardValue(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'completed'"
);

// Pending Payments
$pendingPayments = getDashboardValue(
    $conn,
    "SELECT COUNT(*) AS total
     FROM payments
     WHERE status = 'pending'"
);


// =============================================
// 2. BOOKING STATUS SUMMARY
// =============================================

$bookingStatuses = [
    'pending'     => 0,
    'quoted'      => 0,
    'confirmed'   => 0,
    'in_progress' => 0,
    'completed'   => 0,
    'cancelled'   => 0
];

$statusQuery = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM bookings
    GROUP BY status
");

if ($statusQuery) {
    while ($row = $statusQuery->fetch_assoc()) {

        if (isset($bookingStatuses[$row['status']])) {
            $bookingStatuses[$row['status']] = (int) $row['total'];
        }

    }
}


// =============================================
// 3. LAST 7 DAYS BOOKING ANALYTICS
// =============================================

$weeklyBookings = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date('Y-m-d', strtotime("-$i days"));

    $weeklyBookings[$date] = [
        'label' => date('D', strtotime($date)),
        'total' => 0
    ];

}

$weeklyQuery = $conn->query("
    SELECT DATE(created_at) AS booking_date,
           COUNT(*) AS total
    FROM bookings
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    GROUP BY DATE(created_at)
    ORDER BY booking_date ASC
");

if ($weeklyQuery) {

    while ($row = $weeklyQuery->fetch_assoc()) {

        $date = $row['booking_date'];

        if (isset($weeklyBookings[$date])) {
            $weeklyBookings[$date]['total'] = (int) $row['total'];
        }

    }

}

$maxWeeklyBookings = 1;

foreach ($weeklyBookings as $day) {
    $maxWeeklyBookings = max(
        $maxWeeklyBookings,
        $day['total']
    );
}


// =============================================
// 4. RECENT BOOKINGS
// =============================================

$recentBookings = $conn->query("
    SELECT
        b.booking_id,
        b.package_name_snapshot,
        b.package_price_snapshot,
        b.event_date,
        b.status,
        b.created_at,
        u.full_name AS customer_name

    FROM bookings b

    LEFT JOIN customers c
        ON b.customer_id = c.customer_id

    LEFT JOIN users u
        ON c.user_id = u.user_id

    ORDER BY b.created_at DESC
    LIMIT 5
");


// =============================================
// 5. PAGE CONFIGURATION
// =============================================

$pageTitle = 'Dashboard';


// Start HTML
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard | DecorEase Admin
    </title>

    <!-- Admin CSS -->
    <link rel="stylesheet"
          href="includes/css/admin.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<?php include_once 'includes/sidebar.php'; ?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<div class="main-content">


    <!-- HEADER -->

    <?php require_once __DIR__ . '/includes/header.php'; ?>


    <!-- DASHBOARD CONTENT -->

    <main class="dashboard-content">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <div>
                <h2>Dashboard Overview</h2>

                <p>
                    Welcome to DecorEase Admin Panel.
                    Here's what's happening with your business.
                </p>
            </div>

            <div>
                <span class="btn btn-outline">
                    <i class="far fa-calendar-alt"></i>

                    <?php echo date('d M Y'); ?>
                </span>
            </div>

        </div>


        <!-- =====================================
             STATISTICS CARDS
        ====================================== -->

        <div class="stats-grid">


            <!-- CUSTOMERS -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Total Customers</p>

                        <h3>
                            <?php echo number_format($totalCustomers); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon blue">
                        <i class="fas fa-users"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Registered customers</span>
                </div>

            </div>


            <!-- BOOKINGS -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Total Bookings</p>

                        <h3>
                            <?php echo number_format($totalBookings); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon purple">
                        <i class="fas fa-calendar-check"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>All booking records</span>
                </div>

            </div>


            <!-- PACKAGES -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Total Packages</p>

                        <h3>
                            <?php echo number_format($totalPackages); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon orange">
                        <i class="fas fa-box-open"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Available package records</span>
                </div>

            </div>


            <!-- EMPLOYEES -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Total Employees</p>

                        <h3>
                            <?php echo number_format($totalEmployees); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon green">
                        <i class="fas fa-user-tie"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Employee profiles</span>
                </div>

            </div>


        </div>


        <!-- =====================================
             REVENUE & PAYMENT SUMMARY
        ====================================== -->

        <div class="stats-grid">


            <!-- REVENUE -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Total Revenue</p>

                        <h3>
                            ৳<?php echo number_format((float) $totalRevenue, 2); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon green">
                        <i class="fas fa-wallet"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Completed payments</span>
                </div>

            </div>


            <!-- PENDING PAYMENTS -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Pending Payments</p>

                        <h3>
                            <?php echo number_format($pendingPayments); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Awaiting payment completion</span>
                </div>

            </div>


            <!-- PENDING BOOKINGS -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Pending Bookings</p>

                        <h3>
                            <?php echo number_format($bookingStatuses['pending']); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon blue">
                        <i class="fas fa-hourglass-half"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Awaiting booking review</span>
                </div>

            </div>


            <!-- COMPLETED BOOKINGS -->

            <div class="stat-card">

                <div class="stat-card-top">

                    <div>
                        <p>Completed Events</p>

                        <h3>
                            <?php echo number_format($bookingStatuses['completed']); ?>
                        </h3>
                    </div>

                    <div class="stat-card-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>

                </div>

                <div class="stat-card-footer">
                    <span>Successfully completed bookings</span>
                </div>

            </div>


        </div>


        <!-- =====================================
             ANALYTICS & BOOKING STATUS
        ====================================== -->

        <div class="dashboard-grid">


            <!-- WEEKLY BOOKING ANALYTICS -->

            <div class="content-card">

                <div class="content-card-header">

                    <div>
                        <h3>Booking Analytics</h3>

                        <p>Bookings received during the last 7 days</p>
                    </div>

                    <span class="btn btn-outline btn-sm">
                        Last 7 Days
                    </span>

                </div>


                <div class="content-card-body">

                    <div class="weekly-chart">

                        <?php foreach ($weeklyBookings as $day): ?>

                            <?php
                                $height = ($day['total'] / $maxWeeklyBookings) * 100;
                            ?>

                            <div class="chart-column">

                                <div class="chart-value">
                                    <?php echo $day['total']; ?>
                                </div>

                                <div class="chart-bar-area">

                                    <div class="chart-bar"
                                         style="height: <?php echo $height; ?>%;">
                                    </div>

                                </div>

                                <div class="chart-label">
                                    <?php echo htmlspecialchars($day['label']); ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <!-- BOOKING STATUS -->

            <div class="content-card">

                <div class="content-card-header">

                    <div>
                        <h3>Booking Status</h3>

                        <p>Overview of all bookings</p>
                    </div>

                    <a href="bookings.php"
                       class="btn btn-outline btn-sm">
                        View All
                    </a>

                </div>


                <div class="content-card-body">

                    <?php
                    $statusLabels = [
                        'pending'     => 'Pending',
                        'quoted'      => 'Quoted',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled'
                    ];

                    $statusColors = [
                        'pending'     => '#d97706',
                        'quoted'      => '#0284c7',
                        'confirmed'   => '#16a34a',
                        'in_progress' => '#6366f1',
                        'completed'   => '#15803d',
                        'cancelled'   => '#dc2626'
                    ];
                    ?>


                    <div class="booking-status-list">

                        <?php foreach ($statusLabels as $key => $label): ?>

                            <?php
                            $count = $bookingStatuses[$key];

                            $percentage = $totalBookings > 0
                                ? ($count / $totalBookings) * 100
                                : 0;
                            ?>

                            <div class="booking-status-item">

                                <div class="booking-status-info">

                                    <span>
                                        <i class="fas fa-circle"
                                           style="color: <?php echo $statusColors[$key]; ?>;">
                                        </i>

                                        <?php echo $label; ?>
                                    </span>

                                    <strong>
                                        <?php echo $count; ?>
                                    </strong>

                                </div>


                                <div class="progress">

                                    <div class="progress-bar"
                                         style="
                                            width: <?php echo $percentage; ?>%;
                                            background: <?php echo $statusColors[$key]; ?>;
                                         ">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


        </div>


        <!-- =====================================
             RECENT BOOKINGS TABLE
        ====================================== -->

        <div class="content-card">

            <div class="content-card-header">

                <div>
                    <h3>Recent Bookings</h3>

                    <p>Latest customer booking activities</p>
                </div>

                <a href="bookings.php"
                   class="btn btn-primary btn-sm">

                    View All Bookings

                    <i class="fas fa-arrow-right"></i>

                </a>

            </div>


            <div class="table-responsive">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Event Date</th>
                            <th>Package Price</th>
                            <th>Status</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if ($recentBookings && $recentBookings->num_rows > 0): ?>

                            <?php while ($booking = $recentBookings->fetch_assoc()): ?>

                                <?php
                                $statusClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '_',
                                        $booking['status']
                                    )
                                );
                                ?>

                                <tr>

                                    <td>
                                        #<?php echo (int) $booking['booking_id']; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $booking['customer_name'] ?? 'Unknown Customer'
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $booking['package_name_snapshot'] ?? 'Custom Package'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo !empty($booking['event_date'])
                                            ? date('d M Y', strtotime($booking['event_date']))
                                            : 'Not specified';
                                        ?>
                                    </td>

                                    <td>
                                        ৳<?php
                                        echo number_format(
                                            (float) $booking['package_price_snapshot'],
                                            2
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="status <?php echo htmlspecialchars($statusClass); ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                ucfirst(str_replace('_', ' ', $booking['status']))
                                            );
                                            ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6">

                                    <div class="empty-state">

                                        <i class="fas fa-calendar-times"></i>

                                        <h3>No Bookings Found</h3>

                                        <p>
                                            There are no booking records available yet.
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </main>


    <!-- FOOTER -->

    <?php include_once 'includes/footer.php'; ?>


</div>


</body>
</html>

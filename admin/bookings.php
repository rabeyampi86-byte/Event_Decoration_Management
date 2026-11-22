
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

$pageTitle = "Booking Management";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$deleteError = '';

$allowedStatuses = [
    'pending',
    'quoted',
    'confirmed',
    'in_progress',
    'completed',
    'cancelled'
];

// Delete booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {

    $token = $_POST['csrf_token'] ?? '';
    $deleteId = (int)($_POST['booking_id'] ?? 0);

    if (!hash_equals($csrfToken, $token)) {
        $deleteError = "Invalid request. Please try again.";
    } elseif ($deleteId < 1) {
        $deleteError = "Invalid booking ID.";
    } else {

        $deleteStmt = $conn->prepare("
            DELETE FROM bookings
            WHERE booking_id = ?
        ");

        if ($deleteStmt) {
            $deleteStmt->bind_param("i", $deleteId);

            if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                header("Location: bookings.php?deleted=1");
                exit;
            } else {
                $deleteError = "Unable to delete booking.";
            }

            $deleteStmt->close();
        } else {
            $deleteError = "Database error: " . $conn->error;
        }
    }
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        b.booking_id,
        b.package_name_snapshot,
        b.package_price_snapshot,
        b.event_date,
        b.start_time,
        b.guest_count,
        b.status,
        b.created_at,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM bookings b
    LEFT JOIN customers c
        ON b.customer_id = c.customer_id
    LEFT JOIN users u
        ON c.user_id = u.user_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== '') {

    $sql .= "
        AND (
            CAST(b.booking_id AS CHAR) LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR b.package_name_snapshot LIKE ?
        )
    ";

    $keyword = "%{$search}%";

    $params = [
        $keyword,
        $keyword,
        $keyword,
        $keyword
    ];

    $types .= "ssss";
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND b.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Status counts
$statusCounts = [];

$countResult = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM bookings
    GROUP BY status
");

if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $statusCounts[$row['status']] = (int)$row['total'];
    }
}

// Status CSS class
function bookingStatusClass($status) {
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

// Status label
function bookingStatusLabel($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>

<?php include_once 'includes/sidebar.php'; ?>

<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<div class="main-content">

    <?php include_once 'includes/header.php'; ?>

    <main class="dashboard-content">

        <!-- Page Heading -->
        <div class="page-heading booking-heading">

            <div>
                <h2>Booking Management</h2>
                <p>View and manage all customer bookings.</p>
            </div>

            <a href="booking_form.php" class="booking-add-btn">
                <i class="fas fa-plus"></i>
                Add New Booking
            </a>

        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="details-alert success">
                <i class="fas fa-check-circle"></i>
                Booking deleted successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['saved'])): ?>
            <div class="details-alert success">
                <i class="fas fa-check-circle"></i>
                Booking saved successfully.
            </div>
        <?php endif; ?>

        <!-- Delete Error -->
        <?php if ($deleteError !== ''): ?>
            <div class="details-alert error">
                <?php echo htmlspecialchars($deleteError); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="booking-summary">

            <div class="booking-summary-card">
                <div>
                    <span>Total Bookings</span>
                    <h3><?php echo array_sum($statusCounts); ?></h3>
                </div>
                <i class="fas fa-calendar-check"></i>
            </div>

            <div class="booking-summary-card">
                <div>
                    <span>Pending</span>
                    <h3><?php echo $statusCounts['pending'] ?? 0; ?></h3>
                </div>
                <i class="fas fa-clock"></i>
            </div>

            <div class="booking-summary-card">
                <div>
                    <span>Confirmed</span>
                    <h3><?php echo $statusCounts['confirmed'] ?? 0; ?></h3>
                </div>
                <i class="fas fa-check-circle"></i>
            </div>

            <div class="booking-summary-card">
                <div>
                    <span>Completed</span>
                    <h3><?php echo $statusCounts['completed'] ?? 0; ?></h3>
                </div>
                <i class="fas fa-clipboard-check"></i>
            </div>

        </div>

        <!-- Booking Table -->
        <div class="booking-panel">

            <div class="booking-panel-header">
                <div>
                    <h3>All Bookings</h3>
                    <p>
                        <?php echo $result->num_rows; ?> booking(s) found
                    </p>
                </div>
            </div>

            <!-- Search and Filter -->
            <form method="GET" class="booking-filter-form">

                <div class="booking-search">
                    <i class="fas fa-search"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search booking, customer or package..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <select name="status">
                    <option value="">All Status</option>

                    <?php foreach ($allowedStatuses as $item): ?>
                        <option
                            value="<?php echo htmlspecialchars($item); ?>"
                            <?php echo $status === $item ? 'selected' : ''; ?>
                        >
                            <?php echo bookingStatusLabel($item); ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <button type="submit" class="booking-filter-btn">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>

                <a href="bookings.php" class="booking-reset-btn">
                    Reset
                </a>

            </form>

            <!-- Table -->
            <div class="booking-table-wrapper">

                <table class="booking-table">

                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Event Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($booking = $result->fetch_assoc()): ?>

                            <tr>

                                <!-- Booking ID -->
                                <td>
                                    <strong>
                                        #<?php echo (int)$booking['booking_id']; ?>
                                    </strong>
                                </td>

                                <!-- Customer -->
                                <td>
                                    <div class="booking-customer">

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $booking['customer_name'] ?? 'Unknown'
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            <?php
                                            echo htmlspecialchars(
                                                $booking['customer_email'] ?? ''
                                            );
                                            ?>
                                        </small>

                                    </div>
                                </td>

                                <!-- Package -->
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $booking['package_name_snapshot'] ?? 'N/A'
                                    );
                                    ?>
                                </td>

                                <!-- Event Date -->
                                <td>
                                    <?php
                                    echo $booking['event_date']
                                        ? date('d M Y', strtotime($booking['event_date']))
                                        : 'N/A';
                                    ?>
                                </td>

                                <!-- Guests -->
                                <td>
                                    <?php echo (int)$booking['guest_count']; ?>
                                </td>

                                <!-- Amount -->
                                <td>
                                    <strong>
                                        ৳<?php
                                        echo number_format(
                                            (float)$booking['package_price_snapshot'],
                                            2
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <!-- Status -->
                                <td>
                                    <span class="booking-status <?php
                                        echo bookingStatusClass($booking['status']);
                                    ?>">
                                        <?php
                                        echo bookingStatusLabel($booking['status']);
                                        ?>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>

                                    <div class="booking-action-group">

                                        <!-- View Details -->
                                        <a
                                            href="booking_details.php?id=<?php echo (int)$booking['booking_id']; ?>"
                                            class="booking-view-btn"
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Edit Booking -->
                                        <a
                                            href="booking_form.php?id=<?php echo (int)$booking['booking_id']; ?>"
                                            class="booking-edit-btn"
                                            title="Edit Booking"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- Assign Team -->
                                        <?php if ($booking['status'] === 'confirmed'): ?>

                                            <a
                                                href="assign_team.php?id=<?php echo (int)$booking['booking_id']; ?>"
                                                class="booking-team-btn"
                                                title="Assign Team"
                                            >
                                                <i class="fas fa-users"></i>
                                            </a>

                                        <?php endif; ?>

                                        <!-- Delete Booking -->
                                        <form
                                            method="POST"
                                            class="booking-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars($csrfToken); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?php echo (int)$booking['booking_id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_booking"
                                                class="booking-delete-btn"
                                                title="Delete Booking"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="booking-empty">
                                <i class="fas fa-calendar-times"></i>
                                <p>No bookings found.</p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <?php include_once 'includes/footer.php'; ?>

</div>

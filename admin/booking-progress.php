
<?php
include_once '../db.php';

/* =====================================
   Helper Functions
===================================== */

function progressEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function progressCount($conn, $sql)
{
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }

    return 0;
}

/* =====================================
   Task Statistics
===================================== */

$totalTasks = progressCount(
    $conn,
    "SELECT COUNT(*) AS total FROM event_tasks"
);

$completedTasks = progressCount(
    $conn,
    "SELECT COUNT(*) AS total FROM event_tasks WHERE status = 'completed'"
);

$pendingTasks = progressCount(
    $conn,
    "SELECT COUNT(*) AS total FROM event_tasks WHERE status = 'pending'"
);

$inProgressTasks = progressCount(
    $conn,
    "SELECT COUNT(*) AS total FROM event_tasks WHERE status = 'in_progress'"
);

$overdueTasks = progressCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM event_tasks
     WHERE due_date < CURDATE()
     AND status != 'completed'"
);

$overallProgress = 0;

if ($totalTasks > 0) {
    $overallProgress = round(
        (float)($conn->query("
            SELECT AVG(progress_percent) AS average_progress
            FROM event_tasks
        ")->fetch_assoc()['average_progress'] ?? 0)
    );
}

/* =====================================
   Booking Progress Summary
===================================== */

$bookingProgress = $conn->query("
    SELECT
        b.booking_id,
        b.package_name_snapshot,
        b.event_date,
        b.status AS booking_status,

        COUNT(t.task_id) AS total_tasks,

        SUM(
            CASE WHEN t.status = 'completed'
            THEN 1 ELSE 0 END
        ) AS completed_tasks,

        SUM(
            CASE WHEN t.status = 'pending'
            THEN 1 ELSE 0 END
        ) AS pending_tasks,

        SUM(
            CASE WHEN t.status = 'in_progress'
            THEN 1 ELSE 0 END
        ) AS in_progress_tasks,

        COALESCE(AVG(t.progress_percent), 0) AS progress

    FROM bookings b

    LEFT JOIN event_tasks t
        ON b.booking_id = t.booking_id

    GROUP BY
        b.booking_id,
        b.package_name_snapshot,
        b.event_date,
        b.status

    ORDER BY b.event_date DESC
");

/* =====================================
   Recent Task Details
===================================== */

$taskDetails = $conn->query("
    SELECT
        t.task_id,
        t.booking_id,
        t.task_title,
        t.progress_percent,
        t.status,
        t.start_date,
        t.due_date,

        b.package_name_snapshot,

        COUNT(DISTINCT ta.assignment_id) AS assigned_employees

    FROM event_tasks t

    LEFT JOIN bookings b
        ON t.booking_id = b.booking_id

    LEFT JOIN task_assignments ta
        ON t.task_id = ta.task_id

    GROUP BY
        t.task_id,
        t.booking_id,
        t.task_title,
        t.progress_percent,
        t.status,
        t.start_date,
        t.due_date,
        b.package_name_snapshot

    ORDER BY t.created_at DESC
    LIMIT 15
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Progress | Admin Panel</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        .progress-page {
            padding: 25px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            font-size: 25px;
            color: #1e293b;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .progress-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .progress-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .progress-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .green {
            background: #dcfce7;
            color: #16a34a;
        }

        .orange {
            background: #ffedd5;
            color: #ea580c;
        }

        .purple {
            background: #f3e8ff;
            color: #9333ea;
        }

        .red {
            background: #fee2e2;
            color: #dc2626;
        }

        .progress-card h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .progress-card h2 {
            font-size: 24px;
            color: #1e293b;
        }

        .progress-card small {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
        }

        .overall-panel,
        .progress-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .panel-heading h3 {
            color: #1e293b;
            font-size: 17px;
        }

        .overall-value {
            color: #2563eb;
            font-size: 24px;
            font-weight: 700;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 20px;
            transition: width 0.3s ease;
        }

        .progress-table-wrapper {
            overflow-x: auto;
        }

        .progress-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .progress-table th,
        .progress-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }

        .progress-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .progress-table td {
            color: #475569;
        }

        .progress-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: capitalize;
            background: #eff6ff;
            color: #2563eb;
            white-space: nowrap;
        }

        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-pending {
            background: #ffedd5;
            color: #c2410c;
        }

        .status-in_progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-confirmed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .mini-progress {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 120px;
        }

        .mini-progress .progress-track {
            height: 7px;
            flex: 1;
        }

        .mini-progress span {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            min-width: 35px;
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
        }

        @media (max-width: 1100px) {
            .progress-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .progress-cards {
                grid-template-columns: 1fr;
            }

            .progress-page {
                padding: 15px;
            }
        }

    </style>

</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="progress-page">

            <div class="page-heading">
                <h2>Booking Progress Tracking</h2>
                <p>Monitor event tasks and booking work progress.</p>
            </div>

            <!-- SUMMARY CARDS -->

            <div class="progress-cards">

                <div class="progress-card">
                    <div class="progress-icon blue">
                        <i class="fas fa-list-check"></i>
                    </div>

                    <div>
                        <h3>Total Tasks</h3>
                        <h2><?php echo number_format($totalTasks); ?></h2>
                        <small>All event tasks</small>
                    </div>
                </div>

                <div class="progress-card">
                    <div class="progress-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <div>
                        <h3>Completed Tasks</h3>
                        <h2><?php echo number_format($completedTasks); ?></h2>
                        <small>Successfully completed</small>
                    </div>
                </div>

                <div class="progress-card">
                    <div class="progress-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>

                    <div>
                        <h3>Pending Tasks</h3>
                        <h2><?php echo number_format($pendingTasks); ?></h2>
                        <small>Waiting to start</small>
                    </div>
                </div>

                <div class="progress-card">
                    <div class="progress-icon purple">
                        <i class="fas fa-spinner"></i>
                    </div>

                    <div>
                        <h3>In Progress</h3>
                        <h2><?php echo number_format($inProgressTasks); ?></h2>
                        <small>Currently ongoing</small>
                    </div>
                </div>

                <div class="progress-card">
                    <div class="progress-icon red">
                        <i class="fas fa-calendar-xmark"></i>
                    </div>

                    <div>
                        <h3>Overdue Tasks</h3>
                        <h2><?php echo number_format($overdueTasks); ?></h2>
                        <small>Past due date and incomplete</small>
                    </div>
                </div>

            </div>

            <!-- OVERALL PROGRESS -->

            <div class="overall-panel">

                <div class="panel-heading">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Overall Task Progress
                    </h3>

                    <span class="overall-value">
                        <?php echo $overallProgress; ?>%
                    </span>
                </div>

                <div class="progress-track">
                    <div class="progress-fill"
                         style="width: <?php echo max(0, min(100, $overallProgress)); ?>%">
                    </div>
                </div>

            </div>

            <!-- BOOKING PROGRESS TABLE -->

            <div class="progress-panel">

                <div class="panel-heading">
                    <h3>
                        <i class="fas fa-calendar-check"></i>
                        Booking-wise Progress
                    </h3>
                </div>

                <div class="progress-table-wrapper">

                    <table class="progress-table">

                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Package</th>
                                <th>Event Date</th>
                                <th>Booking Status</th>
                                <th>Total Tasks</th>
                                <th>Completed</th>
                                <th>Progress</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($bookingProgress && $bookingProgress->num_rows > 0): ?>

                            <?php while ($row = $bookingProgress->fetch_assoc()): ?>

                                <?php
                                    $percentage = (int)round((float)$row['progress']);
                                    $percentage = max(0, min(100, $percentage));
                                    $statusClass = 'status-' . $row['booking_status'];
                                ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$row['booking_id']; ?>
                                    </td>

                                    <td>
                                        <?php echo progressEscape($row['package_name_snapshot']); ?>
                                    </td>

                                    <td>
                                        <?php echo progressEscape($row['event_date']); ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo progressEscape($statusClass); ?>">
                                            <?php echo progressEscape($row['booking_status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo (int)$row['total_tasks']; ?>
                                    </td>

                                    <td>
                                        <?php echo (int)$row['completed_tasks']; ?>
                                    </td>

                                    <td>
                                        <div class="mini-progress">

                                            <div class="progress-track">
                                                <div class="progress-fill"
                                                     style="width: <?php echo $percentage; ?>%">
                                                </div>
                                            </div>

                                            <span><?php echo $percentage; ?>%</span>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-message">
                                    No booking records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- RECENT TASKS -->

            <div class="progress-panel">

                <div class="panel-heading">
                    <h3>
                        <i class="fas fa-tasks"></i>
                        Recent Task Details
                    </h3>
                </div>

                <div class="progress-table-wrapper">

                    <table class="progress-table">

                        <thead>
                            <tr>
                                <th>Task ID</th>
                                <th>Booking</th>
                                <th>Task Title</th>
                                <th>Assigned Employees</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($taskDetails && $taskDetails->num_rows > 0): ?>

                            <?php while ($row = $taskDetails->fetch_assoc()): ?>

                                <?php
                                    $taskPercentage = max(
                                        0,
                                        min(100, (int)$row['progress_percent'])
                                    );
                                ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$row['task_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$row['booking_id']; ?>
                                    </td>

                                    <td>
                                        <?php echo progressEscape($row['task_title']); ?>
                                    </td>

                                    <td>
                                        <?php echo (int)$row['assigned_employees']; ?>
                                    </td>

                                    <td>
                                        <?php echo $row['due_date']
                                            ? progressEscape($row['due_date'])
                                            : 'Not set'; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?php echo progressEscape($row['status']); ?>">
                                            <?php echo progressEscape($row['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="mini-progress">

                                            <div class="progress-track">
                                                <div class="progress-fill"
                                                     style="width: <?php echo $taskPercentage; ?>%">
                                                </div>
                                            </div>

                                            <span><?php echo $taskPercentage; ?>%</span>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-message">
                                    No task records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

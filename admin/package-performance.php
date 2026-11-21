
<?php
include_once '../db.php';

/* =====================================
   Helper Functions
===================================== */

function packageReportEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function packageReportMoney($amount)
{
    return '৳ ' . number_format((float)$amount, 2);
}

function packageReportCount($conn, $sql)
{
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }

    return 0;
}

/* =====================================
   Summary Statistics
===================================== */

$totalPackages = packageReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM packages"
);

$activePackages = packageReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM packages WHERE status = 'active'"
);

$packagesWithBookings = packageReportCount(
    $conn,
    "SELECT COUNT(DISTINCT package_id) AS total
     FROM bookings
     WHERE package_id IS NOT NULL"
);

$totalPackageBookings = packageReportCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE package_id IS NOT NULL"
);

$totalPackageBookingValue = 0;
$result = $conn->query("
    SELECT COALESCE(
        SUM(COALESCE(quoted_total, package_price_snapshot)), 0
    ) AS total
    FROM bookings
    WHERE package_id IS NOT NULL
");

if ($result && $row = $result->fetch_assoc()) {
    $totalPackageBookingValue = (float)$row['total'];
}

$totalPackageReceived = 0;
$result = $conn->query("
    SELECT COALESCE(SUM(p.amount), 0) AS total
    FROM payments p
    INNER JOIN bookings b ON p.booking_id = b.booking_id
    WHERE p.status = 'completed'
      AND b.package_id IS NOT NULL
");

if ($result && $row = $result->fetch_assoc()) {
    $totalPackageReceived = (float)$row['total'];
}

/* =====================================
   Package Performance Details
===================================== */

$packagePerformance = $conn->query("
    SELECT
        p.package_id,
        p.package_name,
        p.base_price,
        p.status,

        COUNT(DISTINCT b.booking_id) AS total_bookings,

        COUNT(DISTINCT CASE
            WHEN b.status = 'confirmed'
            THEN b.booking_id
        END) AS confirmed_bookings,

        COUNT(DISTINCT CASE
            WHEN b.status = 'pending'
            THEN b.booking_id
        END) AS pending_bookings,

        COALESCE(SUM(
            COALESCE(b.quoted_total, b.package_price_snapshot)
        ), 0) AS booking_value,

        COALESCE(pay.received_amount, 0) AS received_amount

    FROM packages p

    LEFT JOIN bookings b
        ON p.package_id = b.package_id

    LEFT JOIN (
        SELECT
            b.package_id,
            SUM(
                CASE
                    WHEN payments.status = 'completed'
                    THEN payments.amount
                    ELSE 0
                END
            ) AS received_amount
        FROM bookings b
        LEFT JOIN payments
            ON b.booking_id = payments.booking_id
        WHERE b.package_id IS NOT NULL
        GROUP BY b.package_id
    ) pay
        ON p.package_id = pay.package_id

    GROUP BY
        p.package_id,
        p.package_name,
        p.base_price,
        p.status,
        pay.received_amount

    ORDER BY total_bookings DESC, p.package_name ASC
");

/* =====================================
   Most Popular Package
===================================== */

$mostPopularPackage = 'No bookings yet';
$mostPopularCount = 0;

$popularResult = $conn->query("
    SELECT
        package_name_snapshot,
        COUNT(*) AS total
    FROM bookings
    WHERE package_id IS NOT NULL
    GROUP BY package_id, package_name_snapshot
    ORDER BY total DESC
    LIMIT 1
");

if ($popularResult && $popularRow = $popularResult->fetch_assoc()) {
    $mostPopularPackage = $popularRow['package_name_snapshot'];
    $mostPopularCount = (int)$popularRow['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Package Performance | Admin Panel</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        .package-report-page {
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .summary-icon {
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

        .summary-card h3 {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .summary-card h2 {
            color: #1e293b;
            font-size: 22px;
            overflow-wrap: anywhere;
        }

        .summary-card small {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 12px;
        }

        .popular-highlight {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .popular-highlight h3 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 7px;
        }

        .popular-highlight p {
            color: #64748b;
            font-size: 13px;
        }

        .popular-highlight .popular-name {
            color: #2563eb;
            font-size: 20px;
            font-weight: 700;
        }

        .performance-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
        }

        .panel-heading {
            margin-bottom: 20px;
        }

        .panel-heading h3 {
            color: #1e293b;
            font-size: 17px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        .performance-table th,
        .performance-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }

        .performance-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .performance-table td {
            color: #475569;
        }

        .performance-table tr:last-child td {
            border-bottom: none;
        }

        .package-name {
            font-weight: 600;
            color: #1e293b;
        }

        .package-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: capitalize;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
        }

        .report-note {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px 18px;
            border-radius: 7px;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
            margin-top: 20px;
        }

        @media (max-width: 1100px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .package-report-page {
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

        <section class="package-report-page">

            <div class="page-heading">
                <h2>Package Performance</h2>
                <p>Analyze package popularity, bookings and payment performance.</p>
            </div>

            <!-- SUMMARY CARDS -->

            <div class="summary-grid">

                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-box"></i>
                    </div>

                    <div>
                        <h3>Total Packages</h3>
                        <h2><?php echo number_format($totalPackages); ?></h2>
                        <small>All available package records</small>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-box-open"></i>
                    </div>

                    <div>
                        <h3>Active Packages</h3>
                        <h2><?php echo number_format($activePackages); ?></h2>
                        <small>Currently active packages</small>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-calendar-check"></i>
                    </div>

                    <div>
                        <h3>Total Package Bookings</h3>
                        <h2><?php echo number_format($totalPackageBookings); ?></h2>
                        <small>
                            <?php echo number_format($packagesWithBookings); ?>
                            packages have bookings
                        </small>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon orange">
                        <i class="fas fa-wallet"></i>
                    </div>

                    <div>
                        <h3>Received Payments</h3>
                        <h2><?php echo packageReportMoney($totalPackageReceived); ?></h2>
                        <small>Completed payments only</small>
                    </div>
                </div>

            </div>

            <!-- POPULAR PACKAGE -->

            <div class="popular-highlight">

                <div>
                    <h3>
                        <i class="fas fa-trophy"></i>
                        Most Popular Package
                    </h3>

                    <div class="popular-name">
                        <?php echo packageReportEscape($mostPopularPackage); ?>
                    </div>

                    <p>
                        <?php echo number_format($mostPopularCount); ?>
                        booking(s)
                    </p>
                </div>

                <div>
                    <h3>Total Booking Value</h3>
                    <div class="popular-name">
                        <?php echo packageReportMoney($totalPackageBookingValue); ?>
                    </div>
                </div>

            </div>

            <!-- PACKAGE PERFORMANCE TABLE -->

            <div class="performance-panel">

                <div class="panel-heading">
                    <h3>
                        <i class="fas fa-chart-column"></i>
                        Package Performance Details
                    </h3>
                </div>

                <div class="table-wrapper">

                    <table class="performance-table">

                        <thead>
                            <tr>
                                <th>Package ID</th>
                                <th>Package Name</th>
                                <th>Base Price</th>
                                <th>Total Bookings</th>
                                <th>Confirmed</th>
                                <th>Pending</th>
                                <th>Booking Value</th>
                                <th>Received Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($packagePerformance && $packagePerformance->num_rows > 0): ?>

                            <?php while ($row = $packagePerformance->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$row['package_id']; ?>
                                    </td>

                                    <td class="package-name">
                                        <?php echo packageReportEscape($row['package_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo packageReportMoney($row['base_price']); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($row['total_bookings']); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($row['confirmed_bookings']); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($row['pending_bookings']); ?>
                                    </td>

                                    <td>
                                        <?php echo packageReportMoney($row['booking_value']); ?>
                                    </td>

                                    <td>
                                        <?php echo packageReportMoney($row['received_amount']); ?>
                                    </td>

                                    <td>
                                        <span class="package-status status-<?php echo packageReportEscape($row['status']); ?>">
                                            <?php echo packageReportEscape($row['status']); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="empty-message">
                                    No package records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="report-note">
                <strong>Note:</strong>
                Booking Value is calculated from each booking's quoted_total
                when available; otherwise package_price_snapshot is used.
                Received Payment includes completed transactions only.
                Package performance is grouped using the package linked to each booking.
            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

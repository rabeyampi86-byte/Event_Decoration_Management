
<?php
include_once '../db.php';

/* =====================================
   Helper Function
===================================== */
function reportNumber($conn, $sql, $field)
{
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row[$field] ?? 0;
    }

    return 0;
}

function reportMoney($amount)
{
    return '৳ ' . number_format((float)$amount, 2);
}

function reportEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =====================================
   Booking Statistics
===================================== */

$totalBookings = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings",
    "total"
);

$confirmedBookings = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'",
    "total"
);

$pendingBookings = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'",
    "total"
);

$totalBookingValue = reportNumber(
    $conn,
    "SELECT COALESCE(SUM(COALESCE(quoted_total, package_price_snapshot)), 0) AS total
     FROM bookings",
    "total"
);

/* =====================================
   Package Statistics
===================================== */

$totalPackages = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total FROM packages",
    "total"
);

$activePackages = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total FROM packages WHERE status = 'active'",
    "total"
);

/* =====================================
   Payment Statistics
===================================== */

$receivedPayments = reportNumber(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'completed'",
    "total"
);

$pendingPayments = reportNumber(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'pending'",
    "total"
);

$completedPaymentCount = reportNumber(
    $conn,
    "SELECT COUNT(*) AS total
     FROM payments
     WHERE status = 'completed'",
    "total"
);

/* =====================================
   Booking Status Breakdown
===================================== */

$bookingStatuses = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM bookings
    GROUP BY status
    ORDER BY total DESC
");

/* =====================================
   Payment Method Breakdown
===================================== */

$paymentMethods = $conn->query("
    SELECT
        method,
        COUNT(*) AS total_transactions,
        COALESCE(SUM(
            CASE WHEN status = 'completed' THEN amount ELSE 0 END
        ), 0) AS received_amount
    FROM payments
    GROUP BY method
    ORDER BY total_transactions DESC
");

/* =====================================
   Popular Packages
===================================== */

$popularPackages = $conn->query("
    SELECT
        package_name_snapshot AS package_name,
        COUNT(*) AS total_bookings,
        COALESCE(
            SUM(COALESCE(quoted_total, package_price_snapshot)), 0
        ) AS booking_value
    FROM bookings
    GROUP BY package_name_snapshot
    ORDER BY total_bookings DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Business Overview | Admin Panel</title>

    <link rel="stylesheet"
          href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .report-page {
            padding: 25px;
        }

        .report-heading {
            margin-bottom: 25px;
        }

        .report-heading h2 {
            font-size: 25px;
            color: #1e293b;
            margin-bottom: 7px;
        }

        .report-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .report-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .icon-blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .icon-green {
            background: #dcfce7;
            color: #16a34a;
        }

        .icon-orange {
            background: #ffedd5;
            color: #ea580c;
        }

        .icon-purple {
            background: #f3e8ff;
            color: #9333ea;
        }

        .report-card h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .report-card h2 {
            font-size: 22px;
            color: #1e293b;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .report-card small {
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 12px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 22px;
        }

        .report-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            min-width: 0;
        }

        .report-panel h3 {
            color: #1e293b;
            font-size: 17px;
            margin-bottom: 20px;
        }

        .report-table-wrapper {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 350px;
        }

        .report-table th,
        .report-table td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .report-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .report-table td {
            color: #475569;
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .status-label {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            text-transform: capitalize;
        }

        .empty-report {
            text-align: center;
            color: #94a3b8;
            padding: 22px;
            font-size: 13px;
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

        @media (max-width: 1200px) {
            .report-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .report-grid {
                grid-template-columns: 1fr;
            }

            .report-page {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .report-cards {
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

        <section class="report-page">

            <div class="report-heading">
                <h2>Business Overview</h2>
                <p>Monitor your business bookings, packages and payments.</p>
            </div>

            <!-- SUMMARY CARDS -->

            <div class="report-cards">

                <div class="report-card">
                    <div class="report-icon icon-blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>

                    <div>
                        <h3>Total Bookings</h3>
                        <h2><?php echo number_format($totalBookings); ?></h2>
                        <small>
                            <?php echo number_format($confirmedBookings); ?> confirmed
                        </small>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-icon icon-orange">
                        <i class="fas fa-clock"></i>
                    </div>

                    <div>
                        <h3>Pending Bookings</h3>
                        <h2><?php echo number_format($pendingBookings); ?></h2>
                        <small>Awaiting further action</small>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-icon icon-purple">
                        <i class="fas fa-box-open"></i>
                    </div>

                    <div>
                        <h3>Total Packages</h3>
                        <h2><?php echo number_format($totalPackages); ?></h2>
                        <small>
                            <?php echo number_format($activePackages); ?> active packages
                        </small>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-icon icon-green">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>

                    <div>
                        <h3>Received Payments</h3>
                        <h2><?php echo reportMoney($receivedPayments); ?></h2>
                        <small>
                            <?php echo number_format($completedPaymentCount); ?>
                            completed transactions
                        </small>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-icon icon-blue">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div>
                        <h3>Total Booking Value</h3>
                        <h2><?php echo reportMoney($totalBookingValue); ?></h2>
                        <small>Based on quoted or package snapshot price</small>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-icon icon-orange">
                        <i class="fas fa-hourglass-half"></i>
                    </div>

                    <div>
                        <h3>Pending Payments</h3>
                        <h2><?php echo reportMoney($pendingPayments); ?></h2>
                        <small>Awaiting payment completion</small>
                    </div>
                </div>

            </div>

            <!-- REPORT TABLES -->

            <div class="report-grid">

                <!-- BOOKING STATUS -->

                <div class="report-panel">

                    <h3>
                        <i class="fas fa-chart-pie"></i>
                        Booking Status Breakdown
                    </h3>

                    <div class="report-table-wrapper">

                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Booking Status</th>
                                    <th>Total Bookings</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($bookingStatuses && $bookingStatuses->num_rows > 0): ?>

                                <?php while ($row = $bookingStatuses->fetch_assoc()): ?>

                                    <tr>
                                        <td>
                                            <span class="status-label">
                                                <?php echo reportEscape($row['status']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php echo number_format($row['total']); ?>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="2" class="empty-report">
                                        No booking records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>
                        </table>

                    </div>
                </div>

                <!-- PAYMENT METHODS -->

                <div class="report-panel">

                    <h3>
                        <i class="fas fa-wallet"></i>
                        Payment Method Breakdown
                    </h3>

                    <div class="report-table-wrapper">

                        <table class="report-table">

                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Transactions</th>
                                    <th>Received Amount</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($paymentMethods && $paymentMethods->num_rows > 0): ?>

                                <?php while ($row = $paymentMethods->fetch_assoc()): ?>

                                    <tr>
                                        <td>
                                            <?php echo strtoupper(reportEscape($row['method'])); ?>
                                        </td>

                                        <td>
                                            <?php echo number_format($row['total_transactions']); ?>
                                        </td>

                                        <td>
                                            <?php echo reportMoney($row['received_amount']); ?>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="3" class="empty-report">
                                        No payment records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>
                </div>

            </div>

            <!-- POPULAR PACKAGES -->

            <div class="report-panel">

                <h3>
                    <i class="fas fa-trophy"></i>
                    Popular Packages
                </h3>

                <div class="report-table-wrapper">

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Total Bookings</th>
                                <th>Booking Value</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($popularPackages && $popularPackages->num_rows > 0): ?>

                            <?php while ($row = $popularPackages->fetch_assoc()): ?>

                                <tr>
                                    <td>
                                        <?php echo reportEscape($row['package_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($row['total_bookings']); ?>
                                    </td>

                                    <td>
                                        <?php echo reportMoney($row['booking_value']); ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3" class="empty-report">
                                    No package booking records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>
            </div>

            <div class="report-note">
                <strong>Note:</strong>
                Received Payments includes only completed transactions.
                Refunded, pending and failed payments are excluded.
                Booking Value is calculated using quoted_total when available;
                otherwise package_price_snapshot is used.
            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

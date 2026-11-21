
<?php
include_once '../db.php';

/* =====================================
   Helper Functions
===================================== */

function incomeEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function incomeMoney($amount)
{
    return '৳ ' . number_format((float)$amount, 2);
}

function incomeStat($conn, $sql)
{
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'] ?? 0;
    }

    return 0;
}

/* =====================================
   Payment Summary
===================================== */

$totalTransactions = incomeStat(
    $conn,
    "SELECT COUNT(*) AS total FROM payments"
);

$completedCount = incomeStat(
    $conn,
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'completed'"
);

$pendingCount = incomeStat(
    $conn,
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'pending'"
);

$failedCount = incomeStat(
    $conn,
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'failed'"
);

$refundedCount = incomeStat(
    $conn,
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'refunded'"
);

$totalReceived = incomeStat(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'completed'"
);

$totalPending = incomeStat(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'pending'"
);

$totalRefunded = incomeStat(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'refunded'"
);

$totalFailed = incomeStat(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'failed'"
);

/* =====================================
   Payment Method Summary
===================================== */

$methodReport = $conn->query("
    SELECT
        method,
        COUNT(*) AS total_transactions,

        COALESCE(SUM(
            CASE
                WHEN status = 'completed' THEN amount
                ELSE 0
            END
        ), 0) AS received_amount,

        COALESCE(SUM(
            CASE
                WHEN status = 'pending' THEN amount
                ELSE 0
            END
        ), 0) AS pending_amount

    FROM payments
    GROUP BY method
    ORDER BY received_amount DESC
");

/* =====================================
   Monthly Income (Last 6 Months)
===================================== */

$monthlyIncome = $conn->query("
    SELECT
        DATE_FORMAT(
            COALESCE(paid_at, created_at), '%Y-%m'
        ) AS income_month,

        DATE_FORMAT(
            COALESCE(paid_at, created_at), '%b %Y'
        ) AS month_label,

        SUM(amount) AS total_income

    FROM payments

    WHERE status = 'completed'
      AND COALESCE(paid_at, created_at) >=
          DATE_SUB(CURDATE(), INTERVAL 5 MONTH)

    GROUP BY income_month, month_label
    ORDER BY income_month ASC
");

/* =====================================
   Recent Payment Transactions
===================================== */

$recentPayments = $conn->query("
    SELECT
        p.payment_id,
        p.booking_id,
        p.amount,
        p.method,
        p.transaction_reference,
        p.status,
        p.paid_at,
        p.created_at,

        b.package_name_snapshot,
        b.event_date

    FROM payments p

    LEFT JOIN bookings b
        ON p.booking_id = b.booking_id

    ORDER BY p.created_at DESC
    LIMIT 30
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Income & Payments | Admin Panel</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

        .income-page {
            padding: 25px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .income-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .income-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .income-icon {
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

        .income-card h3 {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .income-card h2 {
            font-size: 21px;
            color: #1e293b;
            overflow-wrap: anywhere;
        }

        .income-card small {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 12px;
        }

        .income-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 22px;
        }

        .income-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            min-width: 0;
        }

        .income-panel h3 {
            font-size: 17px;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .income-table-wrapper {
            overflow-x: auto;
        }

        .income-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 450px;
        }

        .income-table th,
        .income-table td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .income-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .income-table td {
            color: #475569;
        }

        .income-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: capitalize;
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

        .status-failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-refunded {
            background: #f3e8ff;
            color: #9333ea;
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
        }

        .monthly-income-list {
            display: flex;
            flex-direction: column;
            gap: 17px;
        }

        .month-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .month-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .month-info span:first-child {
            color: #475569;
            font-weight: 500;
        }

        .month-info span:last-child {
            color: #1e293b;
            font-weight: 600;
        }

        .month-track {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }

        .month-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 20px;
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
            .income-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 750px) {
            .income-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .income-summary {
                grid-template-columns: 1fr;
            }

            .income-page {
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

        <section class="income-page">

            <div class="page-heading">
                <h2>Income & Payment Reports</h2>
                <p>Track received payments, pending transactions and income sources.</p>
            </div>

            <!-- FINANCIAL SUMMARY -->

            <div class="income-summary">

                <div class="income-card">
                    <div class="income-icon green">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>

                    <div>
                        <h3>Total Received</h3>
                        <h2><?php echo incomeMoney($totalReceived); ?></h2>
                        <small>
                            <?php echo number_format($completedCount); ?>
                            completed transactions
                        </small>
                    </div>
                </div>

                <div class="income-card">
                    <div class="income-icon orange">
                        <i class="fas fa-hourglass-half"></i>
                    </div>

                    <div>
                        <h3>Pending Amount</h3>
                        <h2><?php echo incomeMoney($totalPending); ?></h2>
                        <small>
                            <?php echo number_format($pendingCount); ?>
                            pending transactions
                        </small>
                    </div>
                </div>

                <div class="income-card">
                    <div class="income-icon purple">
                        <i class="fas fa-rotate-left"></i>
                    </div>

                    <div>
                        <h3>Refunded Amount</h3>
                        <h2><?php echo incomeMoney($totalRefunded); ?></h2>
                        <small>
                            <?php echo number_format($refundedCount); ?>
                            refunded transactions
                        </small>
                    </div>
                </div>

                <div class="income-card">
                    <div class="income-icon red">
                        <i class="fas fa-circle-xmark"></i>
                    </div>

                    <div>
                        <h3>Failed Amount</h3>
                        <h2><?php echo incomeMoney($totalFailed); ?></h2>
                        <small>
                            <?php echo number_format($failedCount); ?>
                            failed transactions
                        </small>
                    </div>
                </div>

                <div class="income-card">
                    <div class="income-icon blue">
                        <i class="fas fa-receipt"></i>
                    </div>

                    <div>
                        <h3>Total Transactions</h3>
                        <h2><?php echo number_format($totalTransactions); ?></h2>
                        <small>All payment records</small>
                    </div>
                </div>

            </div>

            <!-- PAYMENT METHOD AND MONTHLY REPORT -->

            <div class="income-grid">

                <div class="income-panel">

                    <h3>
                        <i class="fas fa-wallet"></i>
                        Payment Method Summary
                    </h3>

                    <div class="income-table-wrapper">

                        <table class="income-table">

                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Transactions</th>
                                    <th>Received</th>
                                    <th>Pending</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($methodReport && $methodReport->num_rows > 0): ?>

                                <?php while ($row = $methodReport->fetch_assoc()): ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo strtoupper(incomeEscape($row['method'])); ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php echo number_format($row['total_transactions']); ?>
                                        </td>

                                        <td>
                                            <?php echo incomeMoney($row['received_amount']); ?>
                                        </td>

                                        <td>
                                            <?php echo incomeMoney($row['pending_amount']); ?>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="empty-message">
                                        No payment records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- MONTHLY INCOME -->

                <div class="income-panel">

                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Monthly Received Income
                    </h3>

                    <div class="monthly-income-list">

                    <?php
                    $monthlyRows = [];

                    if ($monthlyIncome) {
                        while ($row = $monthlyIncome->fetch_assoc()) {
                            $monthlyRows[] = $row;
                        }
                    }

                    $maxMonthlyIncome = 0;

                    foreach ($monthlyRows as $month) {
                        $maxMonthlyIncome = max(
                            $maxMonthlyIncome,
                            (float)$month['total_income']
                        );
                    }
                    ?>

                    <?php if (!empty($monthlyRows)): ?>

                        <?php foreach ($monthlyRows as $month): ?>

                            <?php
                                $barWidth = $maxMonthlyIncome > 0
                                    ? ((float)$month['total_income'] / $maxMonthlyIncome) * 100
                                    : 0;
                            ?>

                            <div class="month-row">

                                <div class="month-info">
                                    <span>
                                        <?php echo incomeEscape($month['month_label']); ?>
                                    </span>

                                    <span>
                                        <?php echo incomeMoney($month['total_income']); ?>
                                    </span>
                                </div>

                                <div class="month-track">
                                    <div class="month-fill"
                                         style="width: <?php echo $barWidth; ?>%">
                                    </div>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-message">
                            No completed payments in the last 6 months.
                        </div>

                    <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- RECENT TRANSACTIONS -->

            <div class="income-panel">

                <h3>
                    <i class="fas fa-file-invoice-dollar"></i>
                    Recent Payment Transactions
                </h3>

                <div class="income-table-wrapper">

                    <table class="income-table">

                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Booking ID</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction Reference</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($recentPayments && $recentPayments->num_rows > 0): ?>

                            <?php while ($row = $recentPayments->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$row['payment_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$row['booking_id']; ?>
                                    </td>

                                    <td>
                                        <?php echo incomeEscape(
                                            $row['package_name_snapshot'] ?? 'Not available'
                                        ); ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo incomeMoney($row['amount']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo strtoupper(incomeEscape($row['method'])); ?>
                                    </td>

                                    <td>
                                        <?php echo !empty($row['transaction_reference'])
                                            ? incomeEscape($row['transaction_reference'])
                                            : 'N/A'; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?php echo incomeEscape($row['status']); ?>">
                                            <?php echo incomeEscape($row['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                            echo !empty($row['paid_at'])
                                                ? date('d M Y', strtotime($row['paid_at']))
                                                : date('d M Y', strtotime($row['created_at']));
                                        ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="empty-message">
                                    No payment transactions found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="report-note">
                <strong>Financial Report Note:</strong>
                Total Received includes completed payments only.
                Pending payments are not counted as income.
                Refunded and failed transactions are shown separately.
                Monthly income is grouped by paid_at; if paid_at is NULL,
                created_at is used instead.
            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

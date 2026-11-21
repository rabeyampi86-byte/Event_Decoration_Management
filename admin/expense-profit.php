
<?php
include_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================
   Helper
===================================== */

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$successMessage = '';
$errorMessage = '';

/* =====================================
   Add New Expense
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {

    $bookingId = !empty($_POST['booking_id'])
        ? (int)$_POST['booking_id']
        : null;

    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $vendorName = trim($_POST['vendor_name'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expenseDate = $_POST['expense_date'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $reference = trim($_POST['reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $allowedMethods = ['cash', 'bkash', 'nagad', 'bank', 'other'];

    if (
        $category === '' ||
        $amount <= 0 ||
        $expenseDate === '' ||
        !in_array($paymentMethod, $allowedMethods, true)
    ) {
        $errorMessage = "Please provide valid expense information.";
    } else {

        $recordedBy = !empty($_SESSION['admin_id'])
            ? (int)$_SESSION['admin_id']
            : null;

        $stmt = $conn->prepare("
            INSERT INTO business_expenses
            (
                booking_id,
                recorded_by,
                category,
                description,
                vendor_name,
                amount,
                expense_date,
                payment_method,
                reference,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param(
                "iisssdssss",
                $bookingId,
                $recordedBy,
                $category,
                $description,
                $vendorName,
                $amount,
                $expenseDate,
                $paymentMethod,
                $reference,
                $notes
            );

            if ($stmt->execute()) {
                header("Location: expense-profit.php?added=1");
                exit;
            } else {
                $errorMessage = "Unable to save expense: " . $stmt->error;
            }

            $stmt->close();

        } else {
            $errorMessage = "Database error: " . $conn->error;
        }
    }
}

if (isset($_GET['added'])) {
    $successMessage = "Expense record added successfully.";
}

/* =====================================
   Financial Summary
===================================== */

$summary = [
    'revenue' => 0,
    'expenses' => 0,
    'profit' => 0
];

// Total received revenue
$result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE status = 'completed'
");

if ($result) {
    $summary['revenue'] = (float)$result->fetch_assoc()['total'];
}

// Total business expenses
$result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM business_expenses
");

if ($result) {
    $summary['expenses'] = (float)$result->fetch_assoc()['total'];
}

// Net profit / loss
$summary['profit'] = $summary['revenue'] - $summary['expenses'];

/* =====================================
   Expense Category Breakdown
===================================== */

$categoryExpenses = [];

$result = $conn->query("
    SELECT
        category,
        COUNT(*) AS total_records,
        SUM(amount) AS total_amount
    FROM business_expenses
    GROUP BY category
    ORDER BY total_amount DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categoryExpenses[] = $row;
    }
}

/* =====================================
   Monthly Income & Expenses - Last 6 Months
===================================== */

$monthlyData = [];

$startDate = new DateTimeImmutable('first day of this month');
$startDate = $startDate->modify('-5 months');

$endDate = (new DateTimeImmutable('first day of next month'))
    ->format('Y-m-d');

$startDateString = $startDate->format('Y-m-d');

for ($i = 0; $i < 6; $i++) {

    $month = $startDate->modify("+$i months");

    $key = $month->format('Y-m');

    $monthlyData[$key] = [
        'label' => $month->format('M Y'),
        'income' => 0,
        'expenses' => 0
    ];
}

// Monthly income
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') AS month_key,
        SUM(amount) AS total
    FROM payments
    WHERE status = 'completed'
      AND COALESCE(paid_at, created_at) >= ?
      AND COALESCE(paid_at, created_at) < ?
    GROUP BY DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')
");

if ($stmt) {

    $stmt->bind_param("ss", $startDateString, $endDate);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if (isset($monthlyData[$row['month_key']])) {
            $monthlyData[$row['month_key']]['income'] =
                (float)$row['total'];
        }
    }

    $stmt->close();
}

// Monthly expenses
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(expense_date, '%Y-%m') AS month_key,
        SUM(amount) AS total
    FROM business_expenses
    WHERE expense_date >= ?
      AND expense_date < ?
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
");

if ($stmt) {

    $stmt->bind_param("ss", $startDateString, $endDate);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if (isset($monthlyData[$row['month_key']])) {
            $monthlyData[$row['month_key']]['expenses'] =
                (float)$row['total'];
        }
    }

    $stmt->close();
}

/* =====================================
   Maximum Monthly Amount
===================================== */

$maxMonthlyAmount = 0;

foreach ($monthlyData as $month) {

    $maxMonthlyAmount = max(
        $maxMonthlyAmount,
        $month['income'],
        $month['expenses']
    );
}

if ($maxMonthlyAmount <= 0) {
    $maxMonthlyAmount = 1;
}

/* =====================================
   Recent Expense Records
===================================== */

$recentExpenses = [];

$result = $conn->query("
    SELECT
        be.expense_id,
        be.booking_id,
        be.category,
        be.description,
        be.vendor_name,
        be.amount,
        be.expense_date,
        be.payment_method,
        be.reference,
        be.created_at,
        u.full_name AS admin_name,
        b.package_name_snapshot

    FROM business_expenses be

    LEFT JOIN users u
        ON be.recorded_by = u.user_id

    LEFT JOIN bookings b
        ON be.booking_id = b.booking_id

    ORDER BY be.expense_date DESC, be.expense_id DESC
    LIMIT 30
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentExpenses[] = $row;
    }
}

/* =====================================
   Booking List for Expense Form
===================================== */

$bookings = [];

$result = $conn->query("
    SELECT booking_id, package_name_snapshot, event_date
    FROM bookings
    ORDER BY booking_id DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Expense & Profit Analysis</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>

        .page-content {
            padding: 25px;
            background: #f5f7fb;
            min-height: 100vh;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin-bottom: 5px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #ffffff;
            padding: 23px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .summary-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            flex-shrink: 0;
        }

        .income-icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .expense-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .profit-icon {
            background: #dbeafe;
            color: #2563eb;
        }

        .summary-info h4 {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .summary-info h2 {
            color: #1e293b;
            font-size: 23px;
            font-weight: 700;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 23px;
            margin-bottom: 25px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .panel-header h3 {
            color: #1e293b;
            font-size: 17px;
        }

        .panel-header span {
            color: #64748b;
            font-size: 12px;
        }

        /* Monthly Chart */

        .chart-area {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 15px;
            height: 245px;
            padding-top: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .month-column {
            flex: 1;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
        }

        .bar-group {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 6px;
            width: 100%;
            height: 195px;
        }

        .bar {
            width: 35%;
            max-width: 35px;
            min-height: 2px;
            border-radius: 5px 5px 0 0;
            position: relative;
        }

        .income-bar {
            background: #16a34a;
        }

        .expense-bar {
            background: #ef4444;
        }

        .month-label {
            color: #64748b;
            font-size: 11px;
            margin-top: 10px;
            text-align: center;
        }

        .chart-legend {
            display: flex;
            gap: 20px;
            margin-top: 18px;
            font-size: 13px;
            color: #475569;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        .income-dot {
            background: #16a34a;
        }

        .expense-dot {
            background: #ef4444;
        }

        /* Category Breakdown */

        .category-row {
            margin-bottom: 20px;
        }

        .category-info {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            color: #475569;
            font-size: 13px;
        }

        .category-info strong {
            color: #1e293b;
        }

        .progress-track {
            width: 100%;
            height: 9px;
            background: #f1f5f9;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 20px;
        }

        /* Form */

        .expense-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 17px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 13px;
            color: #334155;
            outline: none;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 75px;
        }

        .submit-btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 12px 22px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Table */

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        .expense-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .expense-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
        }

        .expense-table tr:hover {
            background: #f8fafc;
        }

        .amount-cell {
            color: #dc2626 !important;
            font-weight: 700;
        }

        .method-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: capitalize;
        }

        .empty-state {
            text-align: center;
            padding: 35px 10px;
            color: #94a3b8;
            font-size: 14px;
        }

        .empty-state i {
            font-size: 30px;
            margin-bottom: 10px;
        }

        @media(max-width: 1000px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {
            .page-content {
                padding: 15px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .expense-form {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .summary-info h2 {
                font-size: 20px;
            }
        }

    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div>
                    <h2>Expense & Profit Analysis</h2>
                    <p>Monitor business income, expenses and profitability.</p>
                </div>

            </div>

            <!-- ALERTS -->

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= e($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- FINANCIAL SUMMARY -->

            <div class="summary-grid">

                <div class="summary-card">

                    <div class="summary-icon income-icon">
                        <i class="fas fa-money-bill-trend-up"></i>
                    </div>

                    <div class="summary-info">
                        <h4>Total Revenue</h4>
                        <h2>৳<?= number_format($summary['revenue'], 2) ?></h2>
                    </div>

                </div>

                <div class="summary-card">

                    <div class="summary-icon expense-icon">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>

                    <div class="summary-info">
                        <h4>Total Expenses</h4>
                        <h2>৳<?= number_format($summary['expenses'], 2) ?></h2>
                    </div>

                </div>

                <div class="summary-card">

                    <div class="summary-icon profit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div class="summary-info">
                        <h4>Net Profit / Loss</h4>
                        <h2 style="color: <?= $summary['profit'] >= 0 ? '#16a34a' : '#dc2626' ?>">
                            ৳<?= number_format($summary['profit'], 2) ?>
                        </h2>
                    </div>

                </div>

            </div>

            <!-- MONTHLY CHART & CATEGORY -->

            <div class="content-grid">

                <div class="panel">

                    <div class="panel-header">
                        <h3>Monthly Income vs Expenses</h3>
                        <span>Last 6 Months</span>
                    </div>

                    <div class="chart-area">

                        <?php foreach ($monthlyData as $month): ?>

                            <?php
                                $incomeHeight =
                                    ($month['income'] / $maxMonthlyAmount) * 180;

                                $expenseHeight =
                                    ($month['expenses'] / $maxMonthlyAmount) * 180;
                            ?>

                            <div class="month-column">

                                <div class="bar-group">

                                    <div
                                        class="bar income-bar"
                                        style="height: <?= max(2, $incomeHeight) ?>px;"
                                        title="Income: ৳<?= number_format($month['income'], 2) ?>">
                                    </div>

                                    <div
                                        class="bar expense-bar"
                                        style="height: <?= max(2, $expenseHeight) ?>px;"
                                        title="Expenses: ৳<?= number_format($month['expenses'], 2) ?>">
                                    </div>

                                </div>

                                <div class="month-label">
                                    <?= e($month['label']) ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <div class="chart-legend">

                        <div class="legend-item">
                            <span class="legend-dot income-dot"></span>
                            Income
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot expense-dot"></span>
                            Expenses
                        </div>

                    </div>

                </div>

                <!-- CATEGORY BREAKDOWN -->

                <div class="panel">

                    <div class="panel-header">
                        <h3>Expense Category Breakdown</h3>
                        <span><?= count($categoryExpenses) ?> Categories</span>
                    </div>

                    <?php if (!empty($categoryExpenses)): ?>

                        <?php foreach ($categoryExpenses as $category): ?>

                            <?php
                                $percentage = $summary['expenses'] > 0
                                    ? ($category['total_amount'] / $summary['expenses']) * 100
                                    : 0;
                            ?>

                            <div class="category-row">

                                <div class="category-info">

                                    <span><?= e($category['category']) ?></span>

                                    <strong>
                                        ৳<?= number_format((float)$category['total_amount'], 2) ?>
                                    </strong>

                                </div>

                                <div class="progress-track">

                                    <div
                                        class="progress-fill"
                                        style="width: <?= min(100, $percentage) ?>%;">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <p>No expense data available yet.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <!-- ADD NEW EXPENSE -->

            <div class="panel">

                <div class="panel-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Expense</h3>
                </div>

                <form method="POST" class="expense-form">

                    <div class="form-group">

                        <label>Expense Category *</label>

                        <select name="category" required>

                            <option value="">Select Category</option>
                            <option value="Employee Salary">Employee Salary</option>
                            <option value="Decoration Materials">Decoration Materials</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Vendor Payment">Vendor Payment</option>
                            <option value="Venue Expense">Venue Expense</option>
                            <option value="Office Expense">Office Expense</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Other">Other</option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label>Amount (৳) *</label>

                        <input
                            type="number"
                            name="amount"
                            min="0.01"
                            step="0.01"
                            placeholder="Enter amount"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Expense Date *</label>

                        <input
                            type="date"
                            name="expense_date"
                            value="<?= date('Y-m-d') ?>"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Payment Method *</label>

                        <select name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="bank">Bank</option>
                            <option value="other">Other</option>
                        </select>

                    </div>

                    <div class="form-group">

                        <label>Related Booking (Optional)</label>

                        <select name="booking_id">

                            <option value="">General Business Expense</option>

                            <?php foreach ($bookings as $booking): ?>

                                <option value="<?= (int)$booking['booking_id'] ?>">
                                    Booking #<?= (int)$booking['booking_id'] ?>
                                    - <?= e($booking['package_name_snapshot']) ?>
                                    (<?= e($booking['event_date']) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label>Vendor / Recipient Name</label>

                        <input
                            type="text"
                            name="vendor_name"
                            placeholder="Enter vendor or recipient name">

                    </div>

                    <div class="form-group">

                        <label>Transaction Reference</label>

                        <input
                            type="text"
                            name="reference"
                            placeholder="Transaction ID / Reference">

                    </div>

                    <div class="form-group">

                        <label>Description</label>

                        <input
                            type="text"
                            name="description"
                            placeholder="Short expense description">

                    </div>

                    <div class="form-group full-width">

                        <label>Additional Notes</label>

                        <textarea
                            name="notes"
                            placeholder="Additional information about this expense"></textarea>

                    </div>

                    <div class="form-group full-width">

                        <button type="submit" name="add_expense" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Save Expense
                        </button>

                    </div>

                </form>

            </div>

            <!-- RECENT EXPENSE RECORDS -->

            <div class="panel">

                <div class="panel-header">
                    <h3>Recent Expense Records</h3>
                    <span>Latest 30 Records</span>
                </div>

                <div class="table-responsive">

                    <table class="expense-table">

                        <thead>

                            <tr>
                                <th>Expense ID</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Booking</th>
                                <th>Vendor / Recipient</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Recorded By</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($recentExpenses)): ?>

                                <?php foreach ($recentExpenses as $expense): ?>

                                    <tr>

                                        <td>#<?= (int)$expense['expense_id'] ?></td>

                                        <td><?= e($expense['expense_date']) ?></td>

                                        <td><?= e($expense['category']) ?></td>

                                        <td>
                                            <?= e($expense['description'] ?: '-') ?>
                                        </td>

                                        <td>

                                            <?php if ($expense['booking_id']): ?>

                                                Booking #<?= (int)$expense['booking_id'] ?>

                                            <?php else: ?>

                                                General

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= e($expense['vendor_name'] ?: '-') ?>
                                        </td>

                                        <td class="amount-cell">
                                            ৳<?= number_format((float)$expense['amount'], 2) ?>
                                        </td>

                                        <td>
                                            <span class="method-badge">
                                                <?= e($expense['payment_method']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($expense['admin_name'] ?: 'Unknown') ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9">

                                        <div class="empty-state">
                                            <i class="fas fa-receipt"></i>
                                            <p>No expense records found.</p>
                                        </div>

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

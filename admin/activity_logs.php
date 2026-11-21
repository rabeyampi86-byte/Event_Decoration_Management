
<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin information
$adminId = (int)($_SESSION['admin_id'] ?? 0);

// Filters
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$action = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$allowedRoles = ['admin', 'customer', 'employee'];

if (!in_array($role, $allowedRoles, true)) {
    $role = '';
}

// Pagination
$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));

// Build WHERE conditions
$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR al.action LIKE ?
        OR al.description LIKE ?
        OR al.ip_address LIKE ?
    )";

    $searchTerm = "%{$search}%";

    for ($i = 0; $i < 5; $i++) {
        $params[] = $searchTerm;
        $types .= 's';
    }
}

if ($role !== '') {
    $where[] = "u.role = ?";
    $params[] = $role;
    $types .= 's';
}

if ($action !== '') {
    $where[] = "al.action = ?";
    $params[] = $action;
    $types .= 's';
}

if ($dateFrom !== '') {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if ($dateTo !== '') {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

$whereSQL = count($where) > 0
    ? 'WHERE ' . implode(' AND ', $where)
    : '';


// Total activity count
$countSQL = "
    SELECT COUNT(*) AS total
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $whereSQL
";

$countStmt = $conn->prepare($countSQL);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalLogs = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalLogs / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;


// Activity statistics
$stats = [
    'total' => 0,
    'admin' => 0,
    'customer' => 0,
    'employee' => 0
];

$statsQuery = "
    SELECT
        COUNT(*) AS total,
        SUM(u.role = 'admin') AS admin_count,
        SUM(u.role = 'customer') AS customer_count,
        SUM(u.role = 'employee') AS employee_count
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
";

$statsResult = $conn->query($statsQuery);

if ($statsResult) {
    $row = $statsResult->fetch_assoc();

    $stats['total'] = (int)$row['total'];
    $stats['admin'] = (int)$row['admin_count'];
    $stats['customer'] = (int)$row['customer_count'];
    $stats['employee'] = (int)$row['employee_count'];
}


// Action filter options
$actionOptions = [];

$actionResult = $conn->query("
    SELECT DISTINCT action
    FROM activity_logs
    WHERE action IS NOT NULL AND action != ''
    ORDER BY action ASC
");

if ($actionResult) {
    while ($row = $actionResult->fetch_assoc()) {
        $actionOptions[] = $row['action'];
    }
}


// Fetch activity records
$sql = "
    SELECT
        al.log_id,
        al.user_id,
        al.action,
        al.description,
        al.ip_address,
        al.created_at,
        u.full_name,
        u.email,
        u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $whereSQL
    ORDER BY al.created_at DESC, al.log_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$finalTypes = $types . 'ii';
$finalParams = $params;
$finalParams[] = $limit;
$finalParams[] = $offset;

$stmt->bind_param($finalTypes, ...$finalParams);
$stmt->execute();

$result = $stmt->get_result();

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$stmt->close();


// Escape output
function logEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Preserve filters in pagination
function activityPageUrl($pageNumber) {
    $query = $_GET;
    $query['page'] = $pageNumber;

    return '?' . http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Logs | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .activity-page {
            padding: 25px;
        }

        .activity-heading {
            margin-bottom: 22px;
        }

        .activity-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin-bottom: 6px;
        }

        .activity-heading p {
            color: #64748b;
            margin: 0;
        }

        .activity-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .activity-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .activity-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 20px;
        }

        .activity-stat h3 {
            font-size: 23px;
            margin: 0 0 4px;
            color: #1e293b;
        }

        .activity-stat p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .activity-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .activity-card-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-card-header h3 {
            margin: 0;
            color: #1e293b;
            font-size: 18px;
        }

        .activity-filters {
            padding: 18px 20px;
            display: grid;
            grid-template-columns: 2fr repeat(4, 1fr) auto;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-filters input,
        .activity-filters select {
            width: 100%;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 11px;
            color: #475569;
            background: #fff;
            outline: none;
        }

        .activity-filters input:focus,
        .activity-filters select:focus {
            border-color: #2563eb;
        }

        .filter-btn {
            height: 42px;
            padding: 0 17px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-btn:hover {
            background: #1d4ed8;
        }

        .reset-btn {
            display: inline-flex;
            height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            white-space: nowrap;
        }

        .activity-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        .activity-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
            vertical-align: middle;
        }

        .activity-table tr:hover {
            background: #f8fafc;
        }

        .activity-user strong {
            display: block;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .activity-user small {
            color: #64748b;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .role-customer {
            background: #dcfce7;
            color: #15803d;
        }

        .role-employee {
            background: #fef3c7;
            color: #92400e;
        }

        .role-unknown {
            background: #f1f5f9;
            color: #64748b;
        }

        .action-label {
            color: #1e293b;
            font-weight: 600;
        }

        .activity-description {
            max-width: 280px;
            line-height: 1.5;
        }

        .activity-ip {
            font-family: monospace;
        }

        .activity-date {
            white-space: nowrap;
        }

        .empty-activity {
            text-align: center;
            padding: 55px 20px !important;
            color: #64748b !important;
        }

        .empty-activity i {
            font-size: 35px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        .activity-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .activity-pagination p {
            color: #64748b;
            font-size: 13px;
            margin: 0;
        }

        .pagination-links {
            display: flex;
            gap: 6px;
        }

        .pagination-links a,
        .pagination-links span {
            min-width: 34px;
            height: 34px;
            padding: 0 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            text-decoration: none;
            color: #475569;
            font-size: 13px;
        }

        .pagination-links .active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        @media(max-width: 1100px) {
            .activity-filters {
                grid-template-columns: repeat(3, 1fr);
            }

            .activity-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 650px) {
            .activity-page {
                padding: 15px;
            }

            .activity-stats {
                grid-template-columns: 1fr;
            }

            .activity-filters {
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

        <section class="activity-page">

            <div class="activity-heading">
                <h2>Activity Logs</h2>
                <p>Monitor activities performed by administrators, customers and employees.</p>
            </div>

            <!-- Statistics -->
            <div class="activity-stats">

                <div class="activity-stat">
                    <div class="activity-stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Activities</p>
                    </div>
                </div>

                <div class="activity-stat">
                    <div class="activity-stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h3><?php echo number_format($stats['admin']); ?></h3>
                        <p>Admin Activities</p>
                    </div>
                </div>

                <div class="activity-stat">
                    <div class="activity-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3><?php echo number_format($stats['customer']); ?></h3>
                        <p>Customer Activities</p>
                    </div>
                </div>

                <div class="activity-stat">
                    <div class="activity-stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h3><?php echo number_format($stats['employee']); ?></h3>
                        <p>Employee Activities</p>
                    </div>
                </div>

            </div>


            <!-- Activity Table -->
            <div class="activity-card">

                <div class="activity-card-header">
                    <h3><i class="fas fa-list"></i> All Activities</h3>
                </div>

                <!-- Filters -->
                <form method="GET" class="activity-filters">

                    <input
                        type="search"
                        name="search"
                        placeholder="Search user, action, IP..."
                        value="<?php echo logEscape($search); ?>"
                    >

                    <select name="role">
                        <option value="">All Roles</option>

                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>
                            Admin
                        </option>

                        <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>
                            Customer
                        </option>

                        <option value="employee" <?php echo $role === 'employee' ? 'selected' : ''; ?>>
                            Employee
                        </option>
                    </select>

                    <select name="action">
                        <option value="">All Actions</option>

                        <?php foreach ($actionOptions as $actionOption): ?>
                            <option
                                value="<?php echo logEscape($actionOption); ?>"
                                <?php echo $action === $actionOption ? 'selected' : ''; ?>
                            >
                                <?php echo logEscape($actionOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input
                        type="date"
                        name="date_from"
                        value="<?php echo logEscape($dateFrom); ?>"
                        title="From date"
                    >

                    <input
                        type="date"
                        name="date_to"
                        value="<?php echo logEscape($dateTo); ?>"
                        title="To date"
                    >

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                    <a href="activity-logs.php" class="reset-btn">
                        <i class="fas fa-rotate-left"></i> Reset
                    </a>

                </form>


                <!-- Table -->
                <div class="activity-table-wrap">

                    <table class="activity-table">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($logs)): ?>

                            <?php foreach ($logs as $index => $log): ?>

                                <?php
                                    $userRole = $log['role'] ?? '';
                                    $roleClass = in_array(
                                        $userRole,
                                        $allowedRoles,
                                        true
                                    ) ? 'role-' . $userRole : 'role-unknown';
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $offset + $index + 1; ?>
                                    </td>

                                    <td class="activity-user">

                                        <strong>
                                            <?php
                                            echo logEscape(
                                                $log['full_name'] ?? 'Unknown User'
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            <?php echo logEscape($log['email'] ?? ''); ?>
                                        </small>

                                    </td>

                                    <td>
                                        <span class="role-badge <?php echo $roleClass; ?>">
                                            <?php echo logEscape($userRole ?: 'Unknown'); ?>
                                        </span>
                                    </td>

                                    <td class="action-label">
                                        <?php echo logEscape($log['action']); ?>
                                    </td>

                                    <td class="activity-description">
                                        <?php
                                        echo logEscape(
                                            $log['description'] ?: 'No description'
                                        );
                                        ?>
                                    </td>

                                    <td class="activity-ip">
                                        <?php echo logEscape($log['ip_address'] ?: 'N/A'); ?>
                                    </td>

                                    <td class="activity-date">
                                        <?php
                                        echo date(
                                            'd M Y, h:i A',
                                            strtotime($log['created_at'])
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-activity">
                                    <i class="fas fa-clipboard-list"></i>
                                    <br>
                                    No activity records found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- Pagination -->
                <div class="activity-pagination">

                    <p>
                        Showing
                        <?php echo $totalLogs > 0 ? $offset + 1 : 0; ?>
                        to
                        <?php echo min($offset + $limit, $totalLogs); ?>
                        of
                        <?php echo number_format($totalLogs); ?>
                        activities
                    </p>

                    <?php if ($totalPages > 1): ?>

                        <div class="pagination-links">

                            <?php if ($page > 1): ?>
                                <a href="<?php echo logEscape(activityPageUrl($page - 1)); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>

                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo logEscape(activityPageUrl($i)); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>

                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo logEscape(activityPageUrl($page + 1)); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

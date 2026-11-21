
<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminId = (int)($_SESSION['admin_id'] ?? 0);

if ($adminId <= 0) {
    die("Admin session not found. Please login again.");
}

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'unread', 'read'];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$search = trim($_GET['search'] ?? '');

$where = "WHERE user_id = ?";
$params = [$adminId];
$types = "i";

if ($filter === 'unread') {
    $where .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $where .= " AND is_read = 1";
}

if ($search !== '') {
    $where .= " AND (title LIKE ? OR message LIKE ? OR notification_type LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= "sss";
}

$sql = "
    SELECT notification_id, title, message,
           notification_type, related_id, is_read, created_at
    FROM notifications
    $where
    ORDER BY created_at DESC, notification_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

/* Summary counts */
$countStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(is_read = 0) AS unread,
        SUM(is_read = 1) AS read_count
    FROM notifications
    WHERE user_id = ?
");
$countStmt->bind_param("i", $adminId);
$countStmt->execute();
$summary = $countStmt->get_result()->fetch_assoc();
$countStmt->close();

function notificationEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Admin Panel</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    .notification-page { padding: 25px; }
    .notification-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }
    .notification-stat {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
    }
    .notification-stat span {
        color: #64748b;
        font-size: 13px;
    }
    .notification-stat h2 {
        margin: 8px 0 0;
        color: #1e293b;
        font-size: 26px;
    }
    .notification-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .notification-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        padding: 18px;
        border-bottom: 1px solid #e2e8f0;
    }
    .notification-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .notification-filters a {
        padding: 8px 13px;
        border-radius: 6px;
        text-decoration: none;
        color: #475569;
        background: #f1f5f9;
        font-size: 13px;
    }
    .notification-filters a.active {
        background: #2563eb;
        color: #fff;
    }
    .notification-search {
        display: flex;
        gap: 8px;
    }
    .notification-search input {
        padding: 9px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        min-width: 210px;
    }
    .notification-search button {
        border: 0;
        background: #2563eb;
        color: #fff;
        padding: 9px 14px;
        border-radius: 6px;
        cursor: pointer;
    }
    .notification-row {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px;
        border-bottom: 1px solid #f1f5f9;
    }
    .notification-row.unread {
        background: #eff6ff;
    }
    .notification-row:last-child { border-bottom: 0; }
    .notification-row-icon {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #2563eb;
        border-radius: 50%;
    }
    .notification-row-content { flex: 1; min-width: 0; }
    .notification-row-content h3 {
        margin: 0 0 6px;
        color: #1e293b;
        font-size: 15px;
    }
    .notification-row-content p {
        margin: 0 0 8px;
        color: #64748b;
        font-size: 13px;
        overflow-wrap: anywhere;
    }
    .notification-meta {
        color: #94a3b8;
        font-size: 12px;
    }
    .notification-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .notification-actions a,
    .notification-actions button {
        border: 0;
        text-decoration: none;
        padding: 8px 10px;
        border-radius: 5px;
        font-size: 12px;
        cursor: pointer;
    }
    .btn-view { background: #dbeafe; color: #1d4ed8; }
    .btn-status { background: #f1f5f9; color: #475569; }
    .notification-empty-state {
        text-align: center;
        padding: 55px 20px;
        color: #94a3b8;
    }
    .notification-empty-state i {
        font-size: 35px;
        margin-bottom: 12px;
    }
    @media(max-width:700px) {
        .notification-summary { grid-template-columns: 1fr; }
        .notification-toolbar { align-items: stretch; }
        .notification-search input { min-width: 0; width: 100%; }
        .notification-row { flex-wrap: wrap; }
        .notification-actions { width: 100%; padding-left: 56px; }
    }
    </style>
</head>
<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="notification-page">

            <div class="notification-summary">

                <div class="notification-stat">
                    <span>Total Notifications</span>
                    <h2><?php echo (int)($summary['total'] ?? 0); ?></h2>
                </div>

                <div class="notification-stat">
                    <span>Unread Notifications</span>
                    <h2><?php echo (int)($summary['unread'] ?? 0); ?></h2>
                </div>

                <div class="notification-stat">
                    <span>Read Notifications</span>
                    <h2><?php echo (int)($summary['read_count'] ?? 0); ?></h2>
                </div>

            </div>

            <div class="notification-panel">

                <div class="notification-toolbar">

                    <div class="notification-filters">
                        <a href="notifications.php?filter=all"
                           class="<?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All
                        </a>

                        <a href="notifications.php?filter=unread"
                           class="<?php echo $filter === 'unread' ? 'active' : ''; ?>">
                            Unread
                        </a>

                        <a href="notifications.php?filter=read"
                           class="<?php echo $filter === 'read' ? 'active' : ''; ?>">
                            Read
                        </a>
                    </div>

                    <form method="GET" class="notification-search">
                        <input type="hidden" name="filter"
                               value="<?php echo notificationEscape($filter); ?>">

                        <input type="search" name="search"
                               placeholder="Search notifications..."
                               value="<?php echo notificationEscape($search); ?>">

                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                </div>

                <?php if (empty($notifications)): ?>

                    <div class="notification-empty-state">
                        <i class="far fa-bell-slash"></i>
                        <h3>No notifications found</h3>
                        <p>There are no notifications in this section.</p>
                    </div>

                <?php else: ?>

                    <?php foreach ($notifications as $notification): ?>

                        <div class="notification-row
                            <?php echo (int)$notification['is_read'] === 0 ? 'unread' : ''; ?>">

                            <div class="notification-row-icon">
                                <i class="fas fa-bell"></i>
                            </div>

                            <div class="notification-row-content">

                                <h3>
                                    <?php echo notificationEscape($notification['title']); ?>
                                </h3>

                                <p>
                                    <?php echo nl2br(notificationEscape($notification['message'])); ?>
                                </p>

                                <div class="notification-meta">
                                    <?php echo notificationEscape($notification['notification_type'] ?: 'General'); ?>
                                    &nbsp;·&nbsp;
                                    <?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?>
                                    &nbsp;·&nbsp;
                                    <?php echo (int)$notification['is_read'] === 0 ? 'Unread' : 'Read'; ?>
                                </div>

                            </div>

                            <div class="notification-actions">

                                <a class="btn-view"
                                   href="view_notification.php?id=<?php echo (int)$notification['notification_id']; ?>">
                                    <i class="far fa-eye"></i> View
                                </a>

                                <form method="POST" action="mark_notification_read.php">
                                    <input type="hidden" name="notification_id"
                                           value="<?php echo (int)$notification['notification_id']; ?>">

                                    <input type="hidden" name="status"
                                           value="<?php echo (int)$notification['is_read'] === 0 ? 'read' : 'unread'; ?>">

                                    <input type="hidden" name="return_to"
                                           value="<?php echo notificationEscape($_SERVER['REQUEST_URI']); ?>">

                                    <button type="submit" class="btn-status">
                                        <?php echo (int)$notification['is_read'] === 0
                                            ? 'Mark as Read' : 'Mark as Unread'; ?>
                                    </button>
                                </form>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

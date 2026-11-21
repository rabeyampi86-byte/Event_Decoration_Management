
<?php
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@decorease.com';
$adminId = (int)($_SESSION['admin_id'] ?? 0);

$currentPage = basename($_SERVER['PHP_SELF']);

$pageTitles = [
    'admin_dashboard.php' => 'Dashboard',
    'pages.php' => 'Pages',
    'page-sections.php' => 'Page Sections',
    'banners.php' => 'Banners',
    'navigation.php' => 'Navigation Menu',
    'media.php' => 'Media Library',
    'site-settings.php' => 'Site Settings',
    'team-members.php' => 'Team Members',
    'gallery.php' => 'Gallery',
    'testimonials.php' => 'Testimonials',
    'event-categories.php' => 'Event Categories',
    'packages.php' => 'Packages',
    'package-details.php' => 'Package Details',
    'venues.php' => 'Venues',
    'decoration-areas.php' => 'Decoration Areas',
    'flowers.php' => 'Flowers',
    'flower-designs.php' => 'Flower Designs',
    'lighting-equipment.php' => 'Lighting Equipment',
    'food-menu.php' => 'Food Menu',
    'facilities.php' => 'Facilities',
    'inventory.php' => 'Inventory',
    'vendors.php' => 'Vendors',
    'customers.php' => 'Customers',
    'bookings.php' => 'Bookings',
    'payments.php' => 'Payments',
    'invoices.php' => 'Invoices',
    'reviews.php' => 'Reviews',
    'contact-messages.php' => 'Contact Messages',
    'employees.php' => 'Employees',
    'team-roles.php' => 'Team Roles',
    'event-tasks.php' => 'Event Tasks',
    'task-assignments.php' => 'Task Assignments',
    'reports.php' => 'Reports & Analytics',
    'notifications.php' => 'Notifications',
    'view_notification.php' => 'Notification Details',
    'activity-logs.php' => 'Activity Logs'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Admin Panel';

/* Notification data */
$unreadCount = 0;
$recentNotifications = [];

if (isset($conn) && $conn instanceof mysqli && $adminId > 0) {

    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");

    if ($countStmt) {
        $countStmt->bind_param("i", $adminId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $unreadCount = (int)($countRow['total'] ?? 0);
        $countStmt->close();
    }

    $listStmt = $conn->prepare("
        SELECT notification_id, title, message,
               notification_type, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, notification_id DESC
        LIMIT 5
    ");

    if ($listStmt) {
        $listStmt->bind_param("i", $adminId);
        $listStmt->execute();
        $listResult = $listStmt->get_result();

        while ($row = $listResult->fetch_assoc()) {
            $recentNotifications[] = $row;
        }

        $listStmt->close();
    }
}
?>

<header class="admin-header">

    <link rel="stylesheet" href="includes/css/admin.css">

    <div class="header-left">

        <button type="button" class="sidebar-toggle"
                id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-page-info">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

            <div class="breadcrumb">
                <a href="dashboard.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($pageTitle); ?></span>
            </div>
        </div>

    </div>

    <div class="header-right">

        <form class="header-search" action="search.php" method="GET">
            <i class="fas fa-search"></i>
            <input type="search" name="q"
                   placeholder="Search anything..."
                   aria-label="Search">
        </form>

        <!-- Notification Bell -->
        <div class="header-notification" id="notificationDropdown">

            <button type="button"
                    class="notification-btn"
                    id="notificationToggle"
                    aria-label="Notifications">

                <i class="far fa-bell"></i>

                <?php if ($unreadCount > 0): ?>
                    <span class="notification-count" id="notificationCount">
                        <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                    </span>
                <?php endif; ?>

            </button>

            <div class="notification-dropdown-menu" id="notificationMenu">

                <div class="notification-dropdown-header">
                    <strong>Notifications</strong>
                    <span><?php echo $unreadCount; ?> unread</span>
                </div>

                <div class="notification-dropdown-list">

                    <?php if (empty($recentNotifications)): ?>

                        <div class="notification-empty">
                            <i class="far fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($recentNotifications as $notification): ?>

                            <a href="view_notification.php?id=<?php
                                echo (int)$notification['notification_id'];
                            ?>"
                               class="notification-item <?php
                               echo ((int)$notification['is_read'] === 0)
                                   ? 'unread' : '';
                               ?>">

                                <span class="notification-item-icon">
                                    <i class="fas fa-bell"></i>
                                </span>

                                <span class="notification-item-content">
                                    <strong>
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </strong>

                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            mb_strimwidth(
                                                $notification['message'] ?? '',
                                                0, 75, '...'
                                            )
                                        );
                                        ?>
                                    </small>

                                    <small class="notification-time">
                                        <?php
                                        echo date(
                                            'd M, h:i A',
                                            strtotime($notification['created_at'])
                                        );
                                        ?>
                                    </small>
                                </span>

                                <?php if ((int)$notification['is_read'] === 0): ?>
                                    <span class="notification-unread-dot"></span>
                                <?php endif; ?>

                            </a>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <a href="notifications.php" class="view-all-notifications">
                    View All Notifications
                </a>

            </div>
        </div>

        <!-- Admin Profile -->
        <div class="header-profile" id="profileDropdown">

            <button type="button" class="profile-btn" id="profileToggle">

                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>

                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($adminName); ?></strong>
                    <small>Administrator</small>
                </div>

                <i class="fas fa-chevron-down profile-arrow"></i>

            </button>

            <div class="profile-dropdown-menu" id="profileMenu">

                <div class="dropdown-user-info">
                    <strong><?php echo htmlspecialchars($adminName); ?></strong>
                    <small><?php echo htmlspecialchars($adminEmail); ?></small>
                </div>

                <div class="dropdown-divider"></div>

                <a href="profile.php">
                    <i class="far fa-user"></i>
                    My Profile
                </a>

                <a href="site-settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>

                <div class="dropdown-divider"></div>

                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>

            </div>
        </div>

    </div>

</header>

<style>
.header-notification {
    position: relative;
}

.notification-btn {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 20px;
    color: #475569;
    padding: 10px;
}

.notification-count {
    position: absolute;
    top: 0;
    right: 0;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 20px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 350px;
    max-width: calc(100vw - 30px);
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
    z-index: 9999;
    overflow: hidden;
}

.notification-dropdown-menu.show {
    display: block;
}

.notification-dropdown-header {
    display: flex;
    justify-content: space-between;
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}

.notification-dropdown-header span {
    color: #64748b;
    font-size: 12px;
}

.notification-dropdown-list {
    max-height: 360px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px;
    text-decoration: none;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
}

.notification-item:hover {
    background: #f8fafc;
}

.notification-item.unread {
    background: #eff6ff;
}

.notification-item-icon {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #dbeafe;
    color: #2563eb;
}

.notification-item-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.notification-item-content strong {
    font-size: 13px;
}

.notification-item-content small {
    font-size: 12px;
    color: #64748b;
    overflow-wrap: anywhere;
}

.notification-time {
    font-size: 11px !important;
}

.notification-unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2563eb;
    margin-top: 6px;
}

.notification-empty {
    padding: 30px 15px;
    text-align: center;
    color: #94a3b8;
}

.notification-empty i {
    font-size: 25px;
}

.view-all-notifications {
    display: block;
    text-align: center;
    padding: 13px;
    color: #2563eb;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border-top: 1px solid #e2e8f0;
}

.view-all-notifications:hover {
    background: #f8fafc;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const notificationToggle = document.getElementById('notificationToggle');
    const notificationMenu = document.getElementById('notificationMenu');

    if (notificationToggle && notificationMenu) {
        notificationToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            notificationMenu.classList.toggle('show');
        });

        notificationMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            notificationMenu.classList.remove('show');
        });
    }

});
</script>

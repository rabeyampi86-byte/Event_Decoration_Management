<?php
$adminName = $adminName ?? 'Admin';
// Count pending bookings
$notificationCount = 0;

$countQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'pending'
");

if ($countQuery) {
    $notificationCount = (int)$countQuery->fetch_assoc()['total'];
}

// Get latest pending bookings
$notifications = $conn->query("
    SELECT
        b.booking_id,
        b.event_date,
        u.full_name,
        dp.package_name
    FROM bookings b
    LEFT JOIN customers c
        ON b.customer_id = c.customer_id
    LEFT JOIN users u
        ON c.user_id = u.user_id
    LEFT JOIN decoration_packages dp
        ON b.package_id = dp.id
    WHERE b.status = 'pending'
    ORDER BY b.booking_id DESC
    LIMIT 5
");
?>

<style>
.header-actions {
    display: flex;
    align-items: center;
    gap: 22px;
}

.notification-box,
.profile-box {
    position: relative;
    cursor: pointer;
}

.notification-icon {
    font-size: 21px;
    color: #49356f;
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -10px;
    background: #e74c7b;
    color: #fff;
    font-size: 10px;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-dropdown,
.profile-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 38px;
    width: 290px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0,0,0,.15);
    z-index: 1000;
    overflow: hidden;
}

.notification-box:hover .notification-dropdown,
.profile-box:hover .profile-dropdown {
    display: block;
}

.dropdown-heading {
    padding: 14px;
    font-weight: 700;
    color: #49356f;
    border-bottom: 1px solid #eee;
}

.notification-item {
    display: block;
    padding: 12px 14px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

.notification-item:hover,
.profile-dropdown a:hover {
    background: #f7f4fc;
}

.notification-empty {
    padding: 18px;
    text-align: center;
    color: #777;
    font-size: 13px;
}

.profile-dropdown a {
    display: block;
    padding: 15px;
    text-decoration: none;
    color: #333;
    font-size: 15px;
}

.profile-dropdown .logout-link {
    color: #dc3545;
    border-top: 1px solid #f8f6f6;
}
</style>

<div class="header-actions">

    <!-- Notification -->
    <div class="notification-box">

        <div class="notification-icon">
            🔔

            <?php if ($notificationCount > 0): ?>
                <span class="notification-badge">
                    <?= $notificationCount ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="notification-dropdown">

            <div class="dropdown-heading">
                Pending Bookings (<?= $notificationCount ?>)
            </div>

            <?php if ($notifications && $notifications->num_rows > 0): ?>

                <?php while ($notice = $notifications->fetch_assoc()): ?>

                    <a class="notification-item"
                       href="bookings.php?booking_id=<?= (int)$notice['booking_id'] ?>">

                        <strong>
                            <?= h($notice['full_name'] ?? 'Customer') ?>
                        </strong>

                        <br>

                        <?= h($notice['package_name'] ?? 'Package unavailable') ?>

                        <br>

                        <small>
                            Event Date: <?= h($notice['event_date']) ?>
                        </small>

                    </a>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="notification-empty">
                    No pending bookings.
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Admin Profile -->
    <div class="profile-box">

        <div class="profile">
            <div class="profile-avatar">
                <?= strtoupper(substr($adminName, 0, 1)) ?>
            </div>

            <div>
                <div class="profile-name">
                    <?= h($adminName) ?>
                </div>

                <div class="profile-role">
                    Administrator
                </div>
            </div>
        </div>

        <div class="profile-dropdown">
            <a href="../logout.php" class="logout-link">↪ Logout</a>
        </div>

    </div>

</div>

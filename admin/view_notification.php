
<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($adminId <= 0 || $id <= 0) {
    header("Location: notifications.php");
    exit;
}

/* Mark as read and get notification */
$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE notification_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $id, $adminId);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("
    SELECT notification_id, user_id, title, message,
           notification_type, related_id, is_read, created_at
    FROM notifications
    WHERE notification_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $id, $adminId);
$stmt->execute();
$result = $stmt->get_result();
$notification = $result->fetch_assoc();
$stmt->close();

if (!$notification) {
    header("Location: notifications.php?error=not_found");
    exit;
}

function viewNotificationEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Details</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    .view-notification-page { padding: 25px; }
    .notification-detail-card {
        max-width: 850px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .notification-detail-header {
        padding: 22px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }
    .notification-detail-header h2 {
        margin: 0;
        color: #1e293b;
        font-size: 20px;
    }
    .notification-detail-body { padding: 25px; }
    .notification-detail-body p {
        color: #475569;
        line-height: 1.8;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .notification-detail-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    .notification-detail-meta span {
        display: block;
        color: #64748b;
        font-size: 12px;
        margin-bottom: 5px;
    }
    .notification-detail-meta strong {
        color: #1e293b;
        font-size: 14px;
    }
    .notification-back {
        display: inline-block;
        margin-bottom: 18px;
        color: #2563eb;
        text-decoration: none;
    }
    .notification-back:hover { text-decoration: underline; }
    @media(max-width:600px) {
        .notification-detail-meta { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="view-notification-page">

            <a href="notifications.php" class="notification-back">
                <i class="fas fa-arrow-left"></i> Back to Notifications
            </a>

            <div class="notification-detail-card">

                <div class="notification-detail-header">
                    <h2><?php echo viewNotificationEscape($notification['title']); ?></h2>
                    <span style="color:#16a34a;font-size:13px;">
                        <i class="fas fa-check-circle"></i> Read
                    </span>
                </div>

                <div class="notification-detail-body">

                    <p><?php
                        echo nl2br(viewNotificationEscape($notification['message']));
                    ?></p>

                    <div class="notification-detail-meta">

                        <div>
                            <span>Notification Type</span>
                            <strong>
                                <?php echo viewNotificationEscape(
                                    $notification['notification_type'] ?: 'General'
                                ); ?>
                            </strong>
                        </div>

                        <div>
    <span>Related ID</span>
    <strong>
        <?php
        echo !empty($notification['related_id'])
            ? (int)$notification['related_id']
            : 'Not available';
        ?>
    </strong>
</div>

                        <div>
                            <span>Created At</span>
                            <strong>
                                <?php echo date(
                                    'd M Y, h:i A',
                                    strtotime($notification['created_at'])
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Notification Status</span>
                            <strong>Read</strong>
                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

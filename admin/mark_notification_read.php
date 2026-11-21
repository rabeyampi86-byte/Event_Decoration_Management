
<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: notifications.php");
    exit;
}

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$notificationId = (int)($_POST['notification_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($adminId <= 0 || $notificationId <= 0 ||
    !in_array($status, ['read', 'unread'], true)) {
    header("Location: notifications.php?error=invalid_request");
    exit;
}

$isRead = ($status === 'read') ? 1 : 0;

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = ?
    WHERE notification_id = ?
      AND user_id = ?
");

$stmt->bind_param("iii", $isRead, $notificationId, $adminId);
$stmt->execute();
$stmt->close();

// Return to notifications page
$returnTo = $_POST['return_to'] ?? 'notifications.php';

// Only allow local admin pages
if (
    strpos($returnTo, 'notifications.php') !== false &&
    strpos($returnTo, '://') === false &&
    strpos($returnTo, '..') === false
) {
    header("Location: " . $returnTo);
} else {
    header("Location: notifications.php");
}

exit;

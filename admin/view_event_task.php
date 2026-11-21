
<?php
require_once __DIR__ . '/../db.php';

$task_id = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);

if (!$task_id || $task_id < 1) {
    header("Location: event_tasks.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM event_tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();

$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: event_tasks.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Event Task</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.task-view-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.task-view-card{max-width:850px;background:#fff;padding:28px;border:1px solid #e2e8f0;border-radius:10px}
.task-view-card h2{color:#1e293b;margin-top:0}
.task-detail{padding:16px 0;border-bottom:1px solid #e2e8f0}
.task-detail label{display:block;color:#64748b;font-size:13px;margin-bottom:7px}
.task-detail p{color:#1e293b;margin:0;line-height:1.7}
.task-view-progress{height:12px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin:8px 0}
.task-view-progress span{display:block;height:100%;background:#2563eb}
.task-view-status{display:inline-block;padding:6px 12px;border-radius:20px;font-size:13px;font-weight:600}
.status-pending{background:#fef3c7;color:#92400e}
.status-in_progress{background:#dbeafe;color:#1d4ed8}
.status-completed{background:#dcfce7;color:#166534}
.task-view-actions{display:flex;gap:10px;margin-top:25px;flex-wrap:wrap}
.task-view-actions a{padding:11px 17px;border-radius:7px;text-decoration:none;color:#fff}
.task-edit-btn{background:#2563eb}
.task-back-btn{background:#64748b}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="task-view-page">
<div class="task-view-card">

    <h2><i class="fas fa-tasks"></i> Event Task Details</h2>

    <div class="task-detail">
        <label>Task ID</label>
        <p><?php echo (int)$task['task_id']; ?></p>
    </div>

    <div class="task-detail">
        <label>Booking ID</label>
        <p>#<?php echo (int)$task['booking_id']; ?></p>
    </div>

    <div class="task-detail">
        <label>Task Title</label>
        <p><?php echo htmlspecialchars($task['task_title']); ?></p>
    </div>

    <div class="task-detail">
        <label>Description</label>
        <p><?php echo nl2br(htmlspecialchars($task['description'] ?? 'Not provided')); ?></p>
    </div>

    <div class="task-detail">
        <label>Progress</label>
        <div class="task-view-progress">
            <span style="width:<?php echo (int)$task['progress_percent']; ?>%"></span>
        </div>
        <p><?php echo (int)$task['progress_percent']; ?>%</p>
    </div>

    <div class="task-detail">
        <label>Status</label>
        <p>
            <span class="task-view-status status-<?php echo htmlspecialchars($task['status']); ?>">
                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))); ?>
            </span>
        </p>
    </div>

    <div class="task-detail">
        <label>Start Date</label>
        <p><?php echo htmlspecialchars($task['start_date'] ?? 'Not set'); ?></p>
    </div>

    <div class="task-detail">
        <label>Due Date</label>
        <p><?php echo htmlspecialchars($task['due_date'] ?? 'Not set'); ?></p>
    </div>

    <div class="task-detail">
        <label>Created At</label>
        <p><?php echo htmlspecialchars($task['created_at']); ?></p>
    </div>

    <div class="task-detail">
        <label>Last Updated</label>
        <p><?php echo htmlspecialchars($task['updated_at']); ?></p>
    </div>

    <div class="task-view-actions">
        <a class="task-edit-btn" href="edit_event_task.php?task_id=<?php echo (int)$task['task_id']; ?>">
            <i class="fas fa-edit"></i> Edit Task
        </a>

        <a class="task-back-btn" href="event_tasks.php">
            <i class="fas fa-arrow-left"></i> Back to Tasks
        </a>
    </div>

</div>
</section>

</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>


<?php
require_once __DIR__ . '/../db.php';

$task_id = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);

if (!$task_id || $task_id < 1) {
    header("Location: event_tasks.php");
    exit;
}

$stmt = $conn->prepare("SELECT task_id, task_title, booking_id FROM event_tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();

$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: event_tasks.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $delete = $conn->prepare("DELETE FROM event_tasks WHERE task_id = ?");
    $delete->bind_param("i", $task_id);

    if ($delete->execute()) {
        header("Location: event_tasks.php?deleted=1");
        exit;
    } else {
        $error = "Failed to delete event task.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Event Task</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.task-delete-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.task-delete-card{max-width:600px;background:#fff;padding:30px;border:1px solid #e2e8f0;border-radius:10px;text-align:center}
.task-delete-icon{font-size:45px;color:#dc2626;margin-bottom:15px}
.task-delete-card h2{color:#1e293b}
.task-delete-card p{color:#64748b;line-height:1.8}
.task-delete-name{font-weight:700;color:#1e293b}
.task-delete-btn{background:#dc2626;color:#fff;padding:11px 20px;border:0;border-radius:7px;cursor:pointer;font-weight:600}
.task-delete-btn:hover{background:#b91c1c}
.task-cancel-btn{display:inline-block;margin-left:10px;padding:11px 18px;background:#e2e8f0;color:#334155;text-decoration:none;border-radius:7px}
.task-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:7px;margin-bottom:18px;text-align:left}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="task-delete-page">
<div class="task-delete-card">

    <div class="task-delete-icon">
        <i class="fas fa-trash-alt"></i>
    </div>

    <h2>Delete Event Task</h2>

    <p>
        Are you sure you want to delete this task?
        <br>
        <span class="task-delete-name">
            <?php echo htmlspecialchars($task['task_title']); ?>
        </span>
        <br>
        Booking ID: #<?php echo (int)$task['booking_id']; ?>
    </p>

    <?php if ($error): ?>
        <div class="task-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST"
          onsubmit="return confirm('Are you sure you want to delete this task?');">

        <button type="submit" class="task-delete-btn">
            <i class="fas fa-trash"></i> Yes, Delete
        </button>

        <a href="event_tasks.php" class="task-cancel-btn">Cancel</a>

    </form>

</div>
</section>

</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

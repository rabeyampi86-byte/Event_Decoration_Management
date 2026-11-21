
<?php
require_once __DIR__ . '/../db.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: task_updates.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM task_updates WHERE update_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: task_updates.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $progress = filter_input(INPUT_POST, 'progress_percent', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['update_note'] ?? '');

    if ($progress === false || $progress === null || $progress < 0 || $progress > 100) {
        $error = "Progress must be between 0 and 100.";
    } elseif (!in_array($status, ['pending','in_progress','completed'], true)) {
        $error = "Invalid status.";
    } else {
        if ($progress === 100) {
            $status = 'completed';
        } elseif ($status === 'completed') {
            $progress = 100;
        }

        $conn->begin_transaction();

try {
    $update = $conn->prepare("
        UPDATE task_updates
        SET progress_percent=?, status=?, update_note=?
        WHERE update_id=?
    ");
    $update->bind_param("issi", $progress, $status, $note, $id);

    if (!$update->execute()) {
        throw new Exception("Unable to update task update record.");
    }

    // Find the latest update for this task
    $latest = $conn->prepare("
        SELECT progress_percent, status
        FROM task_updates
        WHERE task_id=?
        ORDER BY created_at DESC, update_id DESC
        LIMIT 1
    ");
    $latest->bind_param("i", $row['task_id']);
    $latest->execute();
    $last = $latest->get_result()->fetch_assoc();

    if ($last) {
        $currentProgress = (int)$last['progress_percent'];
        $currentStatus = $last['status'];
    } else {
        $currentProgress = 0;
        $currentStatus = 'pending';
    }

    $taskUpdate = $conn->prepare("
        UPDATE event_tasks
        SET progress_percent=?, status=?
        WHERE task_id=?
    ");
    $taskUpdate->bind_param(
        "isi",
        $currentProgress,
        $currentStatus,
        $row['task_id']
    );

    if (!$taskUpdate->execute()) {
        throw new Exception("Unable to synchronize task progress.");
    }

    $conn->commit();
    header("Location: view_task_update.php?id=$id&updated=1");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    $error = $e->getMessage();
}
    }
}

$progressValue = $_POST['progress_percent'] ?? $row['progress_percent'];
$statusValue = $_POST['status'] ?? $row['status'];
$noteValue = $_POST['update_note'] ?? $row['update_note'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Edit Task Update</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.tu-page{padding:25px 30px}.tu-card{max-width:750px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.tu-field{margin:18px 0}.tu-field label{display:block;margin-bottom:7px;font-weight:600}
.tu-field input,.tu-field select,.tu-field textarea{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:6px;box-sizing:border-box}
.tu-btn{padding:11px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;text-decoration:none;cursor:pointer}
.tu-error{color:#dc2626}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="tu-page"><div class="tu-card"><h2>Edit Task Update</h2>
<p><strong>Task ID:</strong> <?php echo (int)$row['task_id']; ?></p>
<?php if($error): ?><p class="tu-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
<div class="tu-field"><label>Progress (%)</label>
<input type="number" name="progress_percent" min="0" max="100" value="<?php echo htmlspecialchars((string)$progressValue); ?>" required></div>
<div class="tu-field"><label>Status</label>
<select name="status" required>
<option value="pending" <?php echo $statusValue==='pending'?'selected':''; ?>>Pending</option>
<option value="in_progress" <?php echo $statusValue==='in_progress'?'selected':''; ?>>In Progress</option>
<option value="completed" <?php echo $statusValue==='completed'?'selected':''; ?>>Completed</option>
</select></div>
<div class="tu-field"><label>Update Note</label>
<textarea name="update_note" rows="5"><?php echo htmlspecialchars($noteValue ?? ''); ?></textarea></div>
<button class="tu-btn">Update Record</button>
<a class="tu-btn" href="task_updates.php">Cancel</a>
</form></div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

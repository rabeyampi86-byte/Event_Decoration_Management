
<?php
require_once '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $progress = filter_input(INPUT_POST, 'progress_percent', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['update_note'] ?? '');

    if (!$task_id || $progress === false || $progress === null || $progress < 0 || $progress > 100) {
        $error = "Select a task and enter progress between 0 and 100.";
    } elseif (!in_array($status, ['pending','in_progress','completed'], true)) {
        $error = "Invalid task status.";
    } else {
        if ($progress === 100) {
            $status = 'completed';
        } elseif ($status === 'completed') {
            $progress = 100;
        }

        $check = $conn->prepare("SELECT task_id FROM event_tasks WHERE task_id=?");
        $check->bind_param("i", $task_id);
        $check->execute();

        if (!$check->get_result()->num_rows) {
            $error = "Selected task does not exist.";
        } else {
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("
                    INSERT INTO task_updates
                    (task_id, updated_by, progress_percent, status, update_note)
                    VALUES (?, NULL, ?, ?, ?)
                ");
                $stmt->bind_param("iiss", $task_id, $progress, $status, $note);
                if (!$stmt->execute()) {
                    throw new Exception("Unable to save task update.");
                }

                $updateTask = $conn->prepare("
                    UPDATE event_tasks
                    SET progress_percent=?, status=?
                    WHERE task_id=?
                ");
                $updateTask->bind_param("isi", $progress, $status, $task_id);
                if (!$updateTask->execute()) {
                    throw new Exception("Unable to synchronize task progress.");
                }

                $conn->commit();
                header("Location: task_updates.php?added=1");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$tasks = $conn->query("SELECT task_id, task_title FROM event_tasks ORDER BY task_id DESC");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Add Task Update</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.tu-page{padding:25px 30px}.tu-card{max-width:750px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.tu-field{margin:18px 0}.tu-field label{display:block;margin-bottom:7px;font-weight:600;color:#475569}
.tu-field select,.tu-field input,.tu-field textarea{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:6px;box-sizing:border-box}
.tu-btn{display:inline-block;padding:11px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;text-decoration:none;cursor:pointer}
.tu-cancel{background:#64748b}.tu-error{color:#dc2626}
</style></head>
<body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="tu-page"><div class="tu-card">
<h2>Add Task Update</h2>
<?php if($error): ?><p class="tu-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
<div class="tu-field"><label>Select Task</label>
<select name="task_id" required><option value="">Choose Task</option>
<?php while($t=$tasks->fetch_assoc()): ?>
<option value="<?php echo (int)$t['task_id']; ?>" <?php echo (($_POST['task_id'] ?? '')==$t['task_id'])?'selected':''; ?>>
<?php echo htmlspecialchars($t['task_title'].' (#'.$t['task_id'].')'); ?>
</option><?php endwhile; ?></select></div>
<div class="tu-field"><label>Progress (%)</label>
<input type="number" name="progress_percent" min="0" max="100" value="<?php echo htmlspecialchars($_POST['progress_percent'] ?? '0'); ?>" required></div>
<div class="tu-field"><label>Status</label>
<select name="status" required>
<option value="pending" <?php echo ($_POST['status'] ?? '')==='pending'?'selected':''; ?>>Pending</option>
<option value="in_progress" <?php echo ($_POST['status'] ?? 'in_progress')==='in_progress'?'selected':''; ?>>In Progress</option>
<option value="completed" <?php echo ($_POST['status'] ?? '')==='completed'?'selected':''; ?>>Completed</option>
</select></div>
<div class="tu-field"><label>Update Note</label>
<textarea name="update_note" rows="5"><?php echo htmlspecialchars($_POST['update_note'] ?? ''); ?></textarea></div>
<button class="tu-btn">Save Update</button>
<a class="tu-btn tu-cancel" href="task_updates.php">Cancel</a>
</form></div></section></main></div>
<?php include 'includes/footer.php'; ?></body></html>

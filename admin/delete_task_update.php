
<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: task_updates.php"); exit; }

$stmt = $conn->prepare("
SELECT tu.*, et.task_title
FROM task_updates tu
LEFT JOIN event_tasks et ON tu.task_id=et.task_id
WHERE tu.update_id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: task_updates.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        $task_id = (int)$row['task_id'];

        $delete = $conn->prepare("DELETE FROM task_updates WHERE update_id=?");
        $delete->bind_param("i", $id);
        if (!$delete->execute()) {
            throw new Exception("Unable to delete update.");
        }

        $latest = $conn->prepare("
            SELECT progress_percent, status
            FROM task_updates
            WHERE task_id=?
            ORDER BY created_at DESC, update_id DESC
            LIMIT 1
        ");
        $latest->bind_param("i", $task_id);
        $latest->execute();
        $last = $latest->get_result()->fetch_assoc();

        if ($last) {
            $progress = (int)$last['progress_percent'];
            $status = $last['status'];
        } else {
            $progress = 0;
            $status = 'pending';
        }

        $sync = $conn->prepare("
            UPDATE event_tasks
            SET progress_percent=?, status=?
            WHERE task_id=?
        ");
        $sync->bind_param("isi", $progress, $status, $task_id);
        if (!$sync->execute()) {
            throw new Exception("Unable to synchronize task.");
        }

        $conn->commit();
        header("Location: task_updates.php?deleted=1");
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Delete Task Update</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.tu-page{padding:25px 30px}.tu-card{max-width:650px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.tu-btn{padding:11px 17px;border:0;border-radius:6px;color:#fff;background:#2563eb;text-decoration:none;cursor:pointer}
.tu-delete{background:#dc2626}.tu-error{color:#dc2626}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="tu-page"><div class="tu-card"><h2>Delete Task Update</h2>
<p>Are you sure you want to delete this update?</p>
<p><strong>Task:</strong> <?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></p>
<p><strong>Progress:</strong> <?php echo (int)$row['progress_percent']; ?>%</p>
<p><strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?></p>
<?php if($error): ?><p class="tu-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
<button class="tu-btn tu-delete" onclick="return confirm('Delete this task update?')">Confirm Delete</button>
<a class="tu-btn" href="task_updates.php">Cancel</a>
</form>
</div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

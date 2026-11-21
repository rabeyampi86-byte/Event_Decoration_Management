
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
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>View Task Update</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.tu-page{padding:25px 30px}.tu-card{max-width:750px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.tu-item{padding:14px 0;border-bottom:1px solid #e2e8f0}.tu-item strong{display:block;color:#64748b;margin-bottom:6px}
.tu-note{white-space:pre-wrap;background:#f8fafc;padding:15px;border-radius:7px}
.tu-btn{display:inline-block;margin-top:20px;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="tu-page"><div class="tu-card"><h2>Task Update Details</h2>
<div class="tu-item"><strong>Update ID</strong><?php echo (int)$row['update_id']; ?></div>
<div class="tu-item"><strong>Task</strong><?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></div>
<div class="tu-item"><strong>Progress</strong><?php echo (int)$row['progress_percent']; ?>%</div>
<div class="tu-item"><strong>Status</strong><?php echo htmlspecialchars(ucwords(str_replace('_',' ',$row['status']))); ?></div>
<div class="tu-item"><strong>Updated By</strong><?php echo $row['updated_by'] === null ? '-' : (int)$row['updated_by']; ?></div>
<div class="tu-item"><strong>Update Note</strong><div class="tu-note"><?php echo htmlspecialchars($row['update_note'] ?? 'No note provided.'); ?></div></div>
<div class="tu-item"><strong>Created At</strong><?php echo htmlspecialchars($row['created_at']); ?></div>
<a class="tu-btn" href="task_updates.php">Back</a>
<a class="tu-btn" href="edit_task_update.php?id=<?php echo $id; ?>">Edit</a>
</div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

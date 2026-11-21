
<?php
require_once __DIR__ . '/../db.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: task_assignments.php"); exit; }

$stmt = $conn->prepare("
SELECT ta.assignment_id, ta.task_id, ta.employee_id, et.task_title, u.full_name
FROM task_assignments ta
LEFT JOIN event_tasks et ON ta.task_id=et.task_id
LEFT JOIN employee_profiles ep ON ta.employee_id=ep.employee_id
LEFT JOIN users u ON ep.user_id=u.user_id
WHERE ta.assignment_id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: task_assignments.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $del = $conn->prepare("DELETE FROM task_assignments WHERE assignment_id=?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        header("Location: task_assignments.php?deleted=1");
        exit;
    }
    $error = "Unable to delete assignment.";
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Delete Assignment</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.ta-page{padding:25px 30px}.ta-card{max-width:600px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.ta-btn{padding:11px 17px;border:0;border-radius:6px;color:#fff;background:#2563eb;text-decoration:none;cursor:pointer}
.ta-delete{background:#dc2626}.ta-error{color:#dc2626}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="ta-page"><div class="ta-card"><h2>Delete Assignment</h2>
<p>Are you sure you want to remove this employee assignment?</p>
<p><strong>Task:</strong> <?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></p>
<p><strong>Employee:</strong> <?php echo htmlspecialchars($row['full_name'] ?? 'Employee #'.$row['employee_id']); ?></p>
<?php if(!empty($error)): ?><p class="ta-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST"><button class="ta-btn ta-delete" onclick="return confirm('Delete this assignment?')">Confirm Delete</button>
<a class="ta-btn" href="task_assignments.php">Cancel</a></form>
</div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

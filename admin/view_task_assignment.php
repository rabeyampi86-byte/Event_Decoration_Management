
<?php
require_once __DIR__ . '/../db.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: task_assignments.php"); exit; }

$stmt = $conn->prepare("
SELECT ta.*, et.task_title, u.full_name, ep.designation
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
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>View Assignment</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.ta-page{padding:25px 30px}.ta-card{max-width:700px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.ta-item{padding:14px 0;border-bottom:1px solid #e2e8f0}.ta-item strong{display:block;color:#64748b;margin-bottom:5px}
.ta-btn{display:inline-block;margin-top:20px;padding:10px 16px;background:#2563eb;color:white;text-decoration:none;border-radius:6px}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="ta-page"><div class="ta-card"><h2>Assignment Details</h2>
<div class="ta-item"><strong>Assignment ID</strong><?php echo (int)$row['assignment_id']; ?></div>
<div class="ta-item"><strong>Task</strong><?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></div>
<div class="ta-item"><strong>Employee</strong><?php echo htmlspecialchars($row['full_name'] ?? 'Employee #'.$row['employee_id']); ?></div>
<div class="ta-item"><strong>Designation</strong><?php echo htmlspecialchars($row['designation'] ?? '-'); ?></div>
<div class="ta-item"><strong>Assigned At</strong><?php echo htmlspecialchars($row['assigned_at']); ?></div>
<a class="ta-btn" href="task_assignments.php">Back</a>
<a class="ta-btn" href="edit_task_assignment.php?id=<?php echo $id; ?>">Edit</a>
</div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

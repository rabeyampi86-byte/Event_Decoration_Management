
<?php
require_once __DIR__ . '/../db.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: task_assignments.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM task_assignments WHERE assignment_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: task_assignments.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);

    if (!$task_id || !$employee_id) {
        $error = "Select a valid task and employee.";
    } else {
        $check = $conn->prepare("SELECT assignment_id FROM task_assignments WHERE task_id=? AND employee_id=? AND assignment_id<>?");
        $check->bind_param("iii", $task_id, $employee_id, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "This employee is already assigned to this task.";
        } else {
            $update = $conn->prepare("UPDATE task_assignments SET task_id=?, employee_id=? WHERE assignment_id=?");
            $update->bind_param("iii", $task_id, $employee_id, $id);
            if ($update->execute()) {
                header("Location: view_task_assignment.php?id=$id&updated=1");
                exit;
            }
            $error = "Unable to update assignment.";
        }
    }
}

$tasks = $conn->query("SELECT task_id, task_title FROM event_tasks ORDER BY task_id DESC");
$employees = $conn->query("SELECT ep.employee_id,u.full_name,ep.designation FROM employee_profiles ep JOIN users u ON ep.user_id=u.user_id ORDER BY u.full_name");
$selectedTask = $_POST['task_id'] ?? $row['task_id'];
$selectedEmployee = $_POST['employee_id'] ?? $row['employee_id'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Edit Assignment</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.ta-page{padding:25px 30px}.ta-card{max-width:700px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.ta-field{margin:18px 0}.ta-field label{display:block;margin-bottom:7px;font-weight:600}
.ta-field select{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:6px}
.ta-btn{padding:11px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;text-decoration:none;cursor:pointer}
.ta-error{color:#dc2626}
</style></head><body><div class="admin-container">
<?php include 'includes/sidebar.php'; ?><main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="ta-page"><div class="ta-card"><h2>Edit Assignment</h2>
<?php if($error): ?><p class="ta-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
<div class="ta-field"><label>Task</label><select name="task_id" required>
<?php while($t=$tasks->fetch_assoc()): ?><option value="<?php echo (int)$t['task_id']; ?>" <?php echo $selectedTask==$t['task_id']?'selected':''; ?>>
<?php echo htmlspecialchars($t['task_title'].' (#'.$t['task_id'].')'); ?></option><?php endwhile; ?>
</select></div>
<div class="ta-field"><label>Employee</label><select name="employee_id" required>
<?php while($e=$employees->fetch_assoc()): ?><option value="<?php echo (int)$e['employee_id']; ?>" <?php echo $selectedEmployee==$e['employee_id']?'selected':''; ?>>
<?php echo htmlspecialchars($e['full_name'].' - '.($e['designation'] ?? 'Employee')); ?></option><?php endwhile; ?>
</select></div>
<button class="ta-btn">Update Assignment</button>
<a class="ta-btn" href="task_assignments.php">Cancel</a>
</form></div></section></main></div><?php include 'includes/footer.php'; ?></body></html>

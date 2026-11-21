<?php
include_once '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);

    if (!$task_id || !$employee_id) {
        $error = "Please select a valid task and employee.";
    } else {
        $check = $conn->prepare("SELECT assignment_id FROM task_assignments WHERE task_id=? AND employee_id=?");
        $check->bind_param("ii", $task_id, $employee_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "This employee is already assigned to this task.";
        } else {
            $stmt = $conn->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $task_id, $employee_id);

            if ($stmt->execute()) {
                header("Location: task_assignments.php?added=1");
                exit;
            }
            $error = "Unable to assign task.";
        }
    }
}

$tasks = $conn->query("SELECT task_id, task_title FROM event_tasks ORDER BY task_id DESC");
$employees = $conn->query("
    SELECT ep.employee_id, u.full_name, ep.designation
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id=u.user_id
    ORDER BY u.full_name
");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Assign Task</title>
<link rel="stylesheet" href="includes/css/admin.css">
<style>
.ta-form-page{padding:25px 30px}.ta-form-card{max-width:700px;background:#fff;padding:25px;border:1px solid #e2e8f0;border-radius:10px}
.ta-form-card h2{color:#1e293b}.ta-field{margin:18px 0}.ta-field label{display:block;margin-bottom:7px;font-weight:600;color:#475569}
.ta-field select{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:6px}
.ta-btn{padding:11px 18px;border:0;border-radius:6px;background:#2563eb;color:white;cursor:pointer;text-decoration:none}
.ta-cancel{background:#64748b}.ta-error{color:#dc2626;margin-bottom:15px}
</style></head><body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="ta-form-page"><div class="ta-form-card">
<h2>Assign Task to Employee</h2>
<?php if($error): ?><p class="ta-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="POST">
<div class="ta-field"><label>Select Task</label>
<select name="task_id" required><option value="">Choose Task</option>
<?php while($t=$tasks->fetch_assoc()): ?>
<option value="<?php echo (int)$t['task_id']; ?>" <?php echo (($_POST['task_id'] ?? '')==$t['task_id'])?'selected':''; ?>>
<?php echo htmlspecialchars($t['task_title'].' (Task #'.$t['task_id'].')'); ?>
</option><?php endwhile; ?></select></div>
<div class="ta-field"><label>Select Employee</label>
<select name="employee_id" required><option value="">Choose Employee</option>
<?php while($e=$employees->fetch_assoc()): ?>
<option value="<?php echo (int)$e['employee_id']; ?>" <?php echo (($_POST['employee_id'] ?? '')==$e['employee_id'])?'selected':''; ?>>
<?php echo htmlspecialchars($e['full_name'].' - '.($e['designation'] ?? 'Employee')); ?>
</option><?php endwhile; ?></select></div>
<button class="ta-btn" type="submit">Assign Task</button>
<a class="ta-btn ta-cancel" href="task_assignments.php">Cancel</a>
</form></div></section>
</main></div>
<?php include 'includes/footer.php'; ?></body></html>

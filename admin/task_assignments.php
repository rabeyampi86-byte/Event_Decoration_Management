
<?php
require_once __DIR__ . '/../db.php';

$search = trim($_GET['search'] ?? '');

$sql = "SELECT ta.assignment_id, ta.task_id, ta.employee_id, ta.assigned_at,
               et.task_title, u.full_name, ep.designation
        FROM task_assignments ta
        LEFT JOIN event_tasks et ON ta.task_id = et.task_id
        LEFT JOIN employee_profiles ep ON ta.employee_id = ep.employee_id
        LEFT JOIN users u ON ep.user_id = u.user_id";

if ($search !== '') {
    $sql .= " WHERE et.task_title LIKE ? OR u.full_name LIKE ?
              OR ta.task_id = ? OR ta.employee_id = ?";
}

$sql .= " ORDER BY ta.assignment_id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = "%$search%";
    $number = ctype_digit($search) ? (int)$search : 0;
    $stmt->bind_param("ssii", $like, $like, $number, $number);
}

$stmt->execute();
$result = $stmt->get_result();

$count = $conn->query("SELECT COUNT(*) total FROM task_assignments")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Task Assignments</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.ta-page{padding:25px 30px}.ta-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.ta-head h2{color:#1e293b;margin:0}.ta-card{background:#fff;padding:22px;border-radius:10px;border:1px solid #e2e8f0}
.ta-search{display:flex;gap:10px;margin-bottom:18px}.ta-search input{padding:10px;border:1px solid #e2e8f0;border-radius:6px;width:280px}
.ta-btn{display:inline-block;padding:10px 15px;border-radius:6px;background:#2563eb;color:#fff;text-decoration:none;border:0;cursor:pointer}
.ta-table{width:100%;border-collapse:collapse}.ta-table th,.ta-table td{padding:13px;text-align:left;border-bottom:1px solid #e2e8f0}
.ta-table th{background:#f8fafc;color:#475569}.ta-actions a{margin-right:9px;text-decoration:none;font-weight:600}
.ta-view{color:#2563eb}.ta-edit{color:#d97706}.ta-delete{color:#dc2626}
</style>
</head>
<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="ta-page">
<div class="ta-head">
<div><h2>Task Assignment Management</h2><p>Manage employee task assignments.</p></div>
<a class="ta-btn" href="add_task_assignment.php"><i class="fas fa-plus"></i> Assign Task</a>
</div>
<div class="ta-card">
<p><strong>Total Assignments:</strong> <?php echo (int)$count; ?></p>
<form class="ta-search" method="GET">
<input type="search" name="search" placeholder="Search task or employee..." value="<?php echo htmlspecialchars($search); ?>">
<button class="ta-btn">Search</button>
<a class="ta-btn" href="task_assignments.php">Reset</a>
</form>
<div style="overflow-x:auto">
<table class="ta-table">
<thead><tr><th>ID</th><th>Task</th><th>Employee</th><th>Designation</th><th>Assigned At</th><th>Actions</th></tr></thead>
<tbody>
<?php if($result->num_rows): while($row=$result->fetch_assoc()): ?>
<tr>
<td><?php echo (int)$row['assignment_id']; ?></td>
<td><?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></td>
<td><?php echo htmlspecialchars($row['full_name'] ?? 'Employee #'.$row['employee_id']); ?></td>
<td><?php echo htmlspecialchars($row['designation'] ?? '-'); ?></td>
<td><?php echo htmlspecialchars($row['assigned_at']); ?></td>
<td class="ta-actions">
<a class="ta-view" href="view_task_assignment.php?id=<?php echo (int)$row['assignment_id']; ?>">View</a>
<a class="ta-edit" href="edit_task_assignment.php?id=<?php echo (int)$row['assignment_id']; ?>">Edit</a>
<a class="ta-delete" href="delete_task_assignment.php?id=<?php echo (int)$row['assignment_id']; ?>">Delete</a>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="6">No assignments found.</td></tr>
<?php endif; ?>
</tbody></table></div>
</div></section>
</main></div>
<?php include 'includes/footer.php'; ?>
</body></html>

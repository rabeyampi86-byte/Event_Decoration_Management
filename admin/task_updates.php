
<?php
require_once __DIR__ . '/../db.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT tu.*, et.task_title
        FROM task_updates tu
        LEFT JOIN event_tasks et ON tu.task_id=et.task_id
        WHERE 1=1";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (et.task_title LIKE ? OR tu.update_note LIKE ? OR tu.task_id=?)";
    $like = "%$search%";
    $number = ctype_digit($search) ? (int)$search : 0;
    $params = [$like, $like, $number];
    $types = "ssi";
}

if (in_array($status, ['pending','in_progress','completed'], true)) {
    $sql .= " AND tu.status=?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY tu.update_id DESC";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$count = $conn->query("SELECT COUNT(*) total FROM task_updates")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Task Updates</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.tu-page{padding:25px 30px}.tu-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.tu-head h2{margin:0;color:#1e293b}.tu-card{background:#fff;padding:22px;border:1px solid #e2e8f0;border-radius:10px}
.tu-btn{display:inline-block;padding:10px 15px;border:0;border-radius:6px;background:#2563eb;color:#fff;text-decoration:none;cursor:pointer}
.tu-filters{display:flex;gap:10px;margin:18px 0;flex-wrap:wrap}.tu-filters input,.tu-filters select{padding:10px;border:1px solid #e2e8f0;border-radius:6px}
.tu-table{width:100%;border-collapse:collapse}.tu-table th,.tu-table td{padding:13px;text-align:left;border-bottom:1px solid #e2e8f0}
.tu-table th{background:#f8fafc;color:#475569}.tu-actions a{margin-right:9px;text-decoration:none;font-weight:600}
.tu-view{color:#2563eb}.tu-edit{color:#d97706}.tu-delete{color:#dc2626}
.tu-progress{width:110px;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;display:inline-block;margin-right:6px}
.tu-progress span{height:100%;display:block;background:#2563eb}
</style>
</head>
<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="tu-page">
<div class="tu-head">
<div><h2>Task Update Management</h2><p>Monitor task progress and status updates.</p></div>
<a class="tu-btn" href="add_task_update.php"><i class="fas fa-plus"></i> Add Task Update</a>
</div>
<div class="tu-card">
<p><strong>Total Updates:</strong> <?php echo (int)$count; ?></p>
<form method="GET" class="tu-filters">
<input type="search" name="search" placeholder="Search task or note..." value="<?php echo htmlspecialchars($search); ?>">
<select name="status">
<option value="">All Statuses</option>
<option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>
<option value="in_progress" <?php echo $status==='in_progress'?'selected':''; ?>>In Progress</option>
<option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
</select>
<button class="tu-btn">Filter</button>
<a class="tu-btn" href="task_updates.php">Reset</a>
</form>
<div style="overflow-x:auto">
<table class="tu-table">
<thead><tr><th>Update ID</th><th>Task</th><th>Progress</th><th>Status</th><th>Updated By</th><th>Created At</th><th>Actions</th></tr></thead>
<tbody>
<?php if($result->num_rows): while($row=$result->fetch_assoc()): ?>
<tr>
<td><?php echo (int)$row['update_id']; ?></td>
<td><?php echo htmlspecialchars($row['task_title'] ?? 'Task #'.$row['task_id']); ?></td>
<td><span class="tu-progress"><span style="width:<?php echo (int)$row['progress_percent']; ?>%"></span></span><?php echo (int)$row['progress_percent']; ?>%</td>
<td><?php echo htmlspecialchars(ucwords(str_replace('_',' ',$row['status']))); ?></td>
<td><?php echo $row['updated_by'] === null ? '-' : (int)$row['updated_by']; ?></td>
<td><?php echo htmlspecialchars($row['created_at']); ?></td>
<td class="tu-actions">
<a class="tu-view" href="view_task_update.php?id=<?php echo (int)$row['update_id']; ?>">View</a>
<a class="tu-edit" href="edit_task_update.php?id=<?php echo (int)$row['update_id']; ?>">Edit</a>
<a class="tu-delete" href="delete_task_update.php?id=<?php echo (int)$row['update_id']; ?>">Delete</a>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7">No task updates found.</td></tr>
<?php endif; ?>
</tbody></table></div>
</div></section>
</main></div>
<?php include 'includes/footer.php'; ?>
</body></html>


<?php
require_once __DIR__ . '/../db.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatuses = ['pending', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$sql = "SELECT * FROM event_tasks WHERE 1=1";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (task_title LIKE ? OR description LIKE ? OR CAST(booking_id AS CHAR) LIKE ?)";
    $term = "%{$search}%";
    $types .= "sss";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($status !== '') {
    $sql .= " AND status = ?";
    $types .= "s";
    $params[] = $status;
}

$sql .= " ORDER BY task_id DESC";

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$tasks = $stmt->get_result();

$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0
];

$result = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM event_tasks
    GROUP BY status
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = (int)$row['total'];
        }
    }
}

$stats['total'] = $stats['pending'] + $stats['in_progress'] + $stats['completed'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Event Task Management</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.task-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.task-header{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px}
.task-header h2{margin:0 0 6px;color:#1e293b}
.task-header p{margin:0;color:#64748b}
.task-add-btn{background:#2563eb;color:#fff;padding:11px 18px;border-radius:7px;text-decoration:none;font-weight:600;white-space:nowrap}
.task-add-btn:hover{background:#1d4ed8;color:#fff}
.task-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.task-stat-card{background:#fff;padding:20px;border:1px solid #e2e8f0;border-radius:10px}
.task-stat-card h3{font-size:14px;color:#64748b;margin:0 0 10px}
.task-stat-card strong{font-size:27px;color:#1e293b}
.task-card{background:#fff;padding:20px;border:1px solid #e2e8f0;border-radius:10px}
.task-filter{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.task-filter input,.task-filter select{padding:11px;border:1px solid #e2e8f0;border-radius:7px;min-width:180px}
.task-filter input{flex:1}
.task-filter button{padding:10px 18px;background:#2563eb;color:#fff;border:0;border-radius:7px;cursor:pointer}
.task-table{width:100%;border-collapse:collapse}
.task-table th,.task-table td{padding:13px;text-align:left;border-bottom:1px solid #e2e8f0;color:#475569}
.task-table th{background:#f8fafc;color:#1e293b;white-space:nowrap}
.task-title{font-weight:600;color:#1e293b}
.task-progress{width:120px;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:5px}
.task-progress span{display:block;height:100%;background:#2563eb;border-radius:10px}
.task-progress-text{font-size:12px;color:#64748b}
.task-status{display:inline-block;padding:5px 9px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}
.status-pending{background:#fef3c7;color:#92400e}
.status-in_progress{background:#dbeafe;color:#1d4ed8}
.status-completed{background:#dcfce7;color:#166534}
.task-actions{display:flex;gap:6px;flex-wrap:wrap}
.task-actions a{padding:7px 9px;border-radius:5px;color:#fff;text-decoration:none;font-size:12px;white-space:nowrap}
.task-view{background:#0d9488}
.task-edit{background:#2563eb}
.task-delete{background:#dc2626}
.task-empty{text-align:center;padding:25px;color:#64748b}
@media(max-width:1000px){.task-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
.task-page{padding:20px 15px}
.task-header{align-items:flex-start;flex-direction:column}
.task-card{overflow-x:auto}
.task-table{min-width:950px}
.task-filter input,.task-filter select{width:100%}
}
</style>
</head>

<body>
<div class="admin-container">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="task-page">

    <div class="task-header">
        <div>
            <h2>Event Task Management</h2>
            <p>Create, assign and monitor event tasks.</p>
        </div>

        <a href="add_event_task.php" class="task-add-btn">
            <i class="fas fa-plus"></i> Add New Task
        </a>
    </div>

    <div class="task-stats">
        <div class="task-stat-card">
            <h3>Total Tasks</h3>
            <strong><?php echo $stats['total']; ?></strong>
        </div>

        <div class="task-stat-card">
            <h3>Pending</h3>
            <strong><?php echo $stats['pending']; ?></strong>
        </div>

        <div class="task-stat-card">
            <h3>In Progress</h3>
            <strong><?php echo $stats['in_progress']; ?></strong>
        </div>

        <div class="task-stat-card">
            <h3>Completed</h3>
            <strong><?php echo $stats['completed']; ?></strong>
        </div>
    </div>

    <div class="task-card">

        <form method="GET" class="task-filter">
            <input type="search" name="search"
                   placeholder="Search task title, description or booking ID..."
                   value="<?php echo htmlspecialchars($search); ?>">

            <select name="status">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>

            <button type="submit"><i class="fas fa-search"></i> Filter</button>
        </form>

        <table class="task-table">
            <thead>
                <tr>
                    <th>Task ID</th>
                    <th>Booking ID</th>
                    <th>Task Title</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($tasks->num_rows > 0): ?>
                <?php while ($task = $tasks->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int)$task['task_id']; ?></td>

                    <td>#<?php echo (int)$task['booking_id']; ?></td>

                    <td>
                        <span class="task-title">
                            <?php echo htmlspecialchars($task['task_title']); ?>
                        </span>
                    </td>

                    <td>
                        <div class="task-progress">
                            <span style="width:<?php echo (int)$task['progress_percent']; ?>%"></span>
                        </div>
                        <span class="task-progress-text">
                            <?php echo (int)$task['progress_percent']; ?>%
                        </span>
                    </td>

                    <td>
                        <span class="task-status status-<?php echo htmlspecialchars($task['status']); ?>">
                            <?php
                            echo htmlspecialchars(
                                ucwords(str_replace('_', ' ', $task['status']))
                            );
                            ?>
                        </span>
                    </td>

                    <td><?php echo $task['start_date'] ? htmlspecialchars($task['start_date']) : '—'; ?></td>

                    <td><?php echo $task['due_date'] ? htmlspecialchars($task['due_date']) : '—'; ?></td>

                    <td>
                        <div class="task-actions">
                            <a class="task-view" href="view_event_task.php?task_id=<?php echo (int)$task['task_id']; ?>">
                                <i class="fas fa-eye"></i> View
                            </a>

                            <a class="task-edit" href="edit_event_task.php?task_id=<?php echo (int)$task['task_id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <a class="task-delete" href="delete_event_task.php?task_id=<?php echo (int)$task['task_id']; ?>">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="8" class="task-empty">No event tasks found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>
</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>


<?php
require_once '../db.php';

$error = '';

$task_title = '';
$description = '';
$booking_id = '';
$progress_percent = 0;
$status = 'pending';
$start_date = '';
$due_date = '';

$bookings = $conn->query("SELECT booking_id FROM bookings ORDER BY booking_id DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $task_title = trim($_POST['task_title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $progress_percent = filter_input(INPUT_POST, 'progress_percent', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? 'pending';
    $start_date = trim($_POST['start_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    $allowedStatuses = ['pending', 'in_progress', 'completed'];

    if (!$booking_id || $booking_id < 1) {
        $error = "Please select a valid booking.";
    } elseif ($task_title === '') {
        $error = "Task title is required.";
    } elseif (strlen($task_title) > 200) {
        $error = "Task title cannot exceed 200 characters.";
    } elseif ($progress_percent === false || $progress_percent === null || $progress_percent < 0 || $progress_percent > 100) {
        $error = "Progress must be between 0 and 100.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid task status.";
    } elseif ($start_date !== '' && !DateTime::createFromFormat('!Y-m-d', $start_date)) {
        $error = "Invalid start date.";
    } elseif ($due_date !== '' && !DateTime::createFromFormat('!Y-m-d', $due_date)) {
        $error = "Invalid due date.";
    } elseif ($start_date !== '' && $due_date !== '' && $due_date < $start_date) {
        $error = "Due date cannot be earlier than start date.";
    } else {

        $check = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ?");
        $check->bind_param("i", $booking_id);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $error = "Selected booking does not exist.";
        } else {

            if ($status === 'completed') {
                $progress_percent = 100;
            } elseif ($progress_percent === 100) {
                $status = 'completed';
            }

            $stmt = $conn->prepare("
                INSERT INTO event_tasks
                (booking_id, task_title, description, progress_percent, status, start_date, due_date)
                VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))
            ");

            $stmt->bind_param(
                "ississs",
                $booking_id,
                $task_title,
                $description,
                $progress_percent,
                $status,
                $start_date,
                $due_date
            );

            if ($stmt->execute()) {
                header("Location: event_tasks.php?added=1");
                exit;
            } else {
                $error = "Failed to create event task.";
            }
        }
    }
}

$bookingOptions = [];
if ($bookings) {
    while ($row = $bookings->fetch_assoc()) {
        $bookingOptions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Event Task</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.task-form-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.task-form-card{max-width:850px;background:#fff;padding:28px;border:1px solid #e2e8f0;border-radius:10px}
.task-form-card h2{color:#1e293b;margin-top:0}
.task-form-card>p{color:#64748b}
.task-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.form-group{margin-bottom:18px}
.form-group.full{grid-column:1/-1}
.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#334155}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box}
.form-group textarea{min-height:120px;resize:vertical}
.task-save-btn{background:#2563eb;color:#fff;padding:11px 20px;border:0;border-radius:7px;cursor:pointer;font-weight:600}
.task-save-btn:hover{background:#1d4ed8}
.task-cancel-btn{display:inline-block;margin-left:10px;padding:11px 18px;background:#e2e8f0;color:#334155;text-decoration:none;border-radius:7px}
.task-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:7px;margin-bottom:18px}
.task-hint{font-size:12px;color:#64748b;margin-top:6px}
@media(max-width:650px){.task-form-page{padding:20px 15px}.task-form-grid{grid-template-columns:1fr}.form-group.full{grid-column:auto}}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="task-form-page">
<div class="task-form-card">

    <h2><i class="fas fa-tasks"></i> Add New Event Task</h2>
    <p>Create a task and connect it with an existing booking.</p>

    <?php if ($error): ?>
        <div class="task-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (count($bookingOptions) === 0): ?>
        <div class="task-error">
            No bookings are available. Create a booking before adding an event task.
        </div>
    <?php else: ?>

    <form method="POST">
        <div class="task-form-grid">

            <div class="form-group">
                <label>Booking ID *</label>
                <select name="booking_id" required>
                    <option value="">Select Booking</option>
                    <?php foreach ($bookingOptions as $booking): ?>
                        <option value="<?php echo (int)$booking['booking_id']; ?>"
                            <?php echo (string)$booking_id === (string)$booking['booking_id'] ? 'selected' : ''; ?>>
                            Booking #<?php echo (int)$booking['booking_id']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Task Title *</label>
                <input type="text" name="task_title" maxlength="200"
                       value="<?php echo htmlspecialchars($task_title); ?>"
                       placeholder="e.g. Venue Decoration" required>
            </div>

            <div class="form-group full">
                <label>Description</label>
                <textarea name="description" placeholder="Describe the task..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="form-group">
                <label>Progress (%)</label>
                <input type="number" name="progress_percent" min="0" max="100"
                       value="<?php echo htmlspecialchars((string)$progress_percent); ?>" required>
                <div class="task-hint">Enter a value between 0 and 100.</div>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?php echo htmlspecialchars($due_date); ?>">
            </div>

        </div>

        <button type="submit" class="task-save-btn">
            <i class="fas fa-save"></i> Save Task
        </button>

        <a href="event_tasks.php" class="task-cancel-btn">Cancel</a>
    </form>

    <?php endif; ?>

</div>
</section>

</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

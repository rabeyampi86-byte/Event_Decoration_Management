
<?php
require_once __DIR__ . '/../db.php';

$team_member_id = filter_input(INPUT_GET, 'team_member_id', FILTER_VALIDATE_INT);

if (!$team_member_id || $team_member_id < 1) {
    header("Location: team_members.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM team_members
    WHERE team_member_id = ?
");
$stmt->bind_param("i", $team_member_id);
$stmt->execute();

$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    header("Location: team_members.php");
    exit;
}

$error = '';

$name = $member['name'];
$employee_id = $member['employee_id'];
$designation = $member['designation'];
$description = $member['description'];
$image_id = $member['image_id'];
$display_order = $member['display_order'];
$is_active = $member['is_active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $employee_id = $_POST['employee_id'] ?? '';
    $designation = trim($_POST['designation'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_id = $_POST['image_id'] ?? '';
    $display_order = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $employee_id = $employee_id === '' ? null : filter_var($employee_id, FILTER_VALIDATE_INT);
    $image_id = $image_id === '' ? null : filter_var($image_id, FILTER_VALIDATE_INT);

    if ($name === '') {
        $error = "Team member name is required.";
    } elseif ($display_order === false || $display_order < 0) {
        $error = "Please enter a valid display order.";
    } elseif ($employee_id === false || $image_id === false) {
        $error = "Invalid employee or image selection.";
    } else {

        if ($employee_id !== null) {
            $check = $conn->prepare("
                SELECT employee_id
                FROM employee_profiles
                WHERE employee_id = ?
            ");
            $check->bind_param("i", $employee_id);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                $error = "Selected employee does not exist.";
            }
        }

        if ($error === '' && $image_id !== null) {
            $check = $conn->prepare("
                SELECT media_id
                FROM media
                WHERE media_id = ?
            ");
            $check->bind_param("i", $image_id);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                $error = "Selected image does not exist.";
            }
        }

        if ($error === '') {

            $update = $conn->prepare("
                UPDATE team_members
                SET
                    employee_id = ?,
                    name = ?,
                    designation = ?,
                    description = ?,
                    image_id = ?,
                    display_order = ?,
                    is_active = ?
                WHERE team_member_id = ?
            ");

            $update->bind_param(
                "isssiiii",
                $employee_id,
                $name,
                $designation,
                $description,
                $image_id,
                $display_order,
                $is_active,
                $team_member_id
            );

            if ($update->execute()) {
                header("Location: view_team_member.php?team_member_id=" . $team_member_id . "&updated=1");
                exit;
            } else {
                $error = "Failed to update team member.";
            }
        }
    }
}

$employees = $conn->query("
    SELECT ep.employee_id, u.full_name, ep.designation
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id = u.user_id
    ORDER BY u.full_name ASC
");

$mediaList = $conn->query("
    SELECT media_id, file_name
    FROM media
    WHERE file_type LIKE 'image/%'
       OR file_type IS NULL
    ORDER BY media_id DESC
");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Team Member</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="includes/css/admin.css">

<style>
.team-form-page{padding:25px 30px;margin-top:20px}
.team-form-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:25px;max-width:850px}
.team-form-header{margin-bottom:22px}
.team-form-header h2{color:#1e293b;margin:0 0 7px;font-size:24px}
.team-form-header p{color:#64748b;margin:0;font-size:14px}
.team-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.team-form-group{margin-bottom:18px}
.team-form-group.full{grid-column:1/-1}
.team-form-group label{display:block;color:#334155;font-size:13px;font-weight:600;margin-bottom:8px}
.team-form-group input,.team-form-group select,.team-form-group textarea{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;outline:none;box-sizing:border-box}
.team-form-group input:focus,.team-form-group select:focus,.team-form-group textarea:focus{border-color:#2563eb}
.team-form-group textarea{min-height:120px;resize:vertical}
.team-form-check{display:flex;align-items:center;gap:9px;color:#475569;font-size:14px}
.team-form-check input{width:auto}
.team-form-actions{display:flex;gap:10px;margin-top:10px}
.team-save-btn,.team-cancel-btn{padding:11px 18px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;border:0;cursor:pointer}
.team-save-btn{background:#2563eb;color:#fff}
.team-save-btn:hover{background:#1d4ed8}
.team-cancel-btn{background:#f1f5f9;color:#475569}
.team-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:6px;margin-bottom:18px;font-size:14px}
@media(max-width:650px){.team-form-page{padding:20px 15px}.team-form-grid{grid-template-columns:1fr}.team-form-group.full{grid-column:auto}}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="team-form-page">
<div class="team-form-card">

<div class="team-form-header">
<h2><i class="fas fa-user-edit"></i> Edit Team Member</h2>
<p>Update team member information.</p>
</div>

<?php if ($error !== ''): ?>
<div class="team-error"><?php echo e($error); ?></div>
<?php endif; ?>

<form method="POST">

<div class="team-form-grid">

<div class="team-form-group full">
<label>Member Name *</label>
<input type="text" name="name" value="<?php echo e($name); ?>" required maxlength="150">
</div>

<div class="team-form-group">
<label>Employee (Optional)</label>
<select name="employee_id">
<option value="">Select Employee</option>
<?php if ($employees): while ($emp = $employees->fetch_assoc()): ?>
<option value="<?php echo (int)$emp['employee_id']; ?>"
<?php echo (string)$employee_id === (string)$emp['employee_id'] ? 'selected' : ''; ?>>
<?php echo e($emp['full_name']); ?>
<?php if (!empty($emp['designation'])): ?>
- <?php echo e($emp['designation']); ?>
<?php endif; ?>
</option>
<?php endwhile; endif; ?>
</select>
</div>

<div class="team-form-group">
<label>Designation</label>
<input type="text" name="designation" value="<?php echo e($designation); ?>" maxlength="120">
</div>

<div class="team-form-group full">
<label>Member Image</label>
<select name="image_id">
<option value="">No Image</option>
<?php if ($mediaList): while ($media = $mediaList->fetch_assoc()): ?>
<option value="<?php echo (int)$media['media_id']; ?>"
<?php echo (string)$image_id === (string)$media['media_id'] ? 'selected' : ''; ?>>
<?php echo e($media['file_name']); ?>
</option>
<?php endwhile; endif; ?>
</select>
</div>

<div class="team-form-group full">
<label>Description</label>
<textarea name="description"><?php echo e($description); ?></textarea>
</div>

<div class="team-form-group">
<label>Display Order</label>
<input type="number" name="display_order" min="0" value="<?php echo e($display_order); ?>">
</div>

<div class="team-form-group">
<label>Member Status</label>
<label class="team-form-check">
<input type="checkbox" name="is_active" value="1" <?php echo (int)$is_active === 1 ? 'checked' : ''; ?>>
Active Member
</label>
</div>

</div>

<div class="team-form-actions">
<button type="submit" class="team-save-btn">
<i class="fas fa-save"></i> Update Team Member
</button>
<a href="team_members.php" class="team-cancel-btn">Cancel</a>
</div>

</form>
</div>
</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

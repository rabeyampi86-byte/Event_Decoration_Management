
<?php
require_once __DIR__ . '/../db.php';

$team_member_id = filter_input(INPUT_GET, 'team_member_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_member_id = filter_input(INPUT_POST, 'team_member_id', FILTER_VALIDATE_INT);
}

if (!$team_member_id || $team_member_id < 1) {
    header("Location: team_members.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT team_member_id, name, designation
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $delete = $conn->prepare("
        DELETE FROM team_members
        WHERE team_member_id = ?
    ");

    $delete->bind_param("i", $team_member_id);

    if ($delete->execute()) {
        header("Location: team_members.php?deleted=1");
        exit;
    } else {
        $error = "Unable to delete team member. This record may be linked to another record.";
    }
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Team Member</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="includes/css/admin.css">

<style>
.team-delete-page{padding:25px 30px;margin-top:20px}
.team-delete-card{max-width:600px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:30px;text-align:center}
.team-delete-icon{width:65px;height:65px;margin:0 auto 18px;border-radius:50%;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:27px}
.team-delete-card h2{font-size:23px;color:#1e293b;margin:0 0 12px}
.team-delete-card p{font-size:14px;color:#64748b;line-height:1.7;margin-bottom:10px}
.team-delete-name{font-weight:700;color:#1e293b}
.team-delete-actions{display:flex;justify-content:center;gap:10px;margin-top:25px}
.team-confirm-delete,.team-cancel-delete{padding:11px 19px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;border:0;cursor:pointer}
.team-confirm-delete{background:#dc2626;color:#fff}
.team-confirm-delete:hover{background:#b91c1c}
.team-cancel-delete{background:#f1f5f9;color:#475569}
.team-delete-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:6px;margin-bottom:18px;font-size:14px}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="team-delete-page">
<div class="team-delete-card">

<div class="team-delete-icon">
<i class="fas fa-trash-alt"></i>
</div>

<h2>Delete Team Member?</h2>

<p>
Are you sure you want to delete
<span class="team-delete-name"><?php echo e($member['name']); ?></span>?
</p>

<p>This action will permanently remove this team member record.</p>
<p>The associated Media Library image will not be deleted.</p>

<?php if ($error !== ''): ?>
<div class="team-delete-error"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="team-delete-actions">

<form method="POST">
<input type="hidden" name="team_member_id" value="<?php echo (int)$team_member_id; ?>">

<button type="submit" class="team-confirm-delete">
<i class="fas fa-trash"></i> Yes, Delete
</button>
</form>

<a href="team_members.php" class="team-cancel-delete">
Cancel
</a>

</div>

</div>
</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

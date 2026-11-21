
<?php
require_once __DIR__ . '/../db.php';

$team_member_id = filter_input(INPUT_GET, 'team_member_id', FILTER_VALIDATE_INT);

if (!$team_member_id || $team_member_id < 1) {
    header("Location: team_members.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        tm.*,
        m.file_name,
        m.file_path,
        m.alt_text,
        u.full_name AS employee_name
    FROM team_members tm
    LEFT JOIN media m ON tm.image_id = m.media_id
    LEFT JOIN employee_profiles ep ON tm.employee_id = ep.employee_id
    LEFT JOIN users u ON ep.user_id = u.user_id
    WHERE tm.team_member_id = ?
");

$stmt->bind_param("i", $team_member_id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    header("Location: team_members.php");
    exit;
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
<title>View Team Member</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="includes/css/admin.css">

<style>
.team-view-page{padding:25px 30px;margin-top:20px}
.team-view-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;max-width:850px}
.team-view-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:25px}
.team-view-header h2{font-size:24px;color:#1e293b;margin:0}
.team-profile{text-align:center;padding:10px 0 25px;border-bottom:1px solid #e2e8f0;margin-bottom:22px}
.team-profile img,.team-profile-placeholder{width:115px;height:115px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0}
.team-profile-placeholder{display:inline-flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb;font-size:42px}
.team-profile h3{color:#1e293b;margin:13px 0 5px;font-size:21px}
.team-profile p{color:#64748b;margin:0}
.team-view-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.team-view-item label{display:block;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:7px}
.team-view-item p{margin:0;color:#1e293b;font-size:14px;line-height:1.6;overflow-wrap:anywhere}
.team-view-item.full{grid-column:1/-1}
.team-view-status{display:inline-block;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600}
.team-view-status.active{background:#dcfce7;color:#15803d}
.team-view-status.inactive{background:#fee2e2;color:#b91c1c}
.team-view-actions{display:flex;gap:10px;margin-top:28px}
.team-view-actions a{padding:10px 17px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600}
.team-edit-btn{background:#2563eb;color:#fff}
.team-back-btn{background:#f1f5f9;color:#475569}
@media(max-width:650px){.team-view-page{padding:20px 15px}.team-view-grid{grid-template-columns:1fr}.team-view-item.full{grid-column:auto}}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="team-view-page">
<div class="team-view-card">

<div class="team-view-header">
<h2><i class="fas fa-user"></i> Team Member Details</h2>
</div>

<div class="team-profile">

<?php if (!empty($member['file_path'])): ?>
<img src="../<?php echo e($member['file_path']); ?>"
     alt="<?php echo e($member['alt_text'] ?: $member['name']); ?>">
<?php else: ?>
<div class="team-profile-placeholder">
<i class="fas fa-user"></i>
</div>
<?php endif; ?>

<h3><?php echo e($member['name']); ?></h3>
<p><?php echo e($member['designation'] ?: 'No designation'); ?></p>

</div>

<div class="team-view-grid">

<div class="team-view-item">
<label>Team Member ID</label>
<p><?php echo (int)$member['team_member_id']; ?></p>
</div>

<div class="team-view-item">
<label>Employee ID</label>
<p><?php echo $member['employee_id'] ? (int)$member['employee_id'] : 'Not linked'; ?></p>
</div>

<div class="team-view-item">
<label>Linked Employee</label>
<p><?php echo e($member['employee_name'] ?: 'Not linked'); ?></p>
</div>

<div class="team-view-item">
<label>Image ID</label>
<p><?php echo $member['image_id'] ? (int)$member['image_id'] : 'No image selected'; ?></p>
</div>

<div class="team-view-item">
<label>Display Order</label>
<p><?php echo (int)$member['display_order']; ?></p>
</div>

<div class="team-view-item">
<label>Status</label>
<p>
<?php if ((int)$member['is_active'] === 1): ?>
<span class="team-view-status active">Active</span>
<?php else: ?>
<span class="team-view-status inactive">Inactive</span>
<?php endif; ?>
</p>
</div>

<div class="team-view-item full">
<label>Description</label>
<p><?php echo nl2br(e($member['description'] ?: 'No description available.')); ?></p>
</div>

<div class="team-view-item">
<label>Created At</label>
<p><?php echo e($member['created_at']); ?></p>
</div>

</div>

<div class="team-view-actions">
<a href="edit_team_member.php?team_member_id=<?php echo (int)$member['team_member_id']; ?>" class="team-edit-btn">
<i class="fas fa-edit"></i> Edit Member
</a>
<a href="team_members.php" class="team-back-btn">
<i class="fas fa-arrow-left"></i> Back
</a>
</div>

</div>
</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

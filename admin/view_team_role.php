
<?php
require_once __DIR__ . '/../db.php';

$role_id = filter_input(INPUT_GET, 'role_id', FILTER_VALIDATE_INT);

if (!$role_id || $role_id < 1) {
    header("Location: team_roles.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM team_roles WHERE role_id = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();

$role = $stmt->get_result()->fetch_assoc();

if (!$role) {
    header("Location: team_roles.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Team Role</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.role-view-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.role-view-card{max-width:750px;background:#fff;padding:28px;border:1px solid #e2e8f0;border-radius:10px}
.role-view-card h2{color:#1e293b;margin-top:0}
.role-detail{padding:17px 0;border-bottom:1px solid #e2e8f0}
.role-detail label{display:block;color:#64748b;font-size:13px;margin-bottom:7px}
.role-detail p{color:#1e293b;margin:0;line-height:1.7}
.role-view-actions{display:flex;gap:10px;margin-top:25px;flex-wrap:wrap}
.role-view-actions a{padding:11px 17px;border-radius:7px;text-decoration:none;color:#fff}
.role-edit-btn{background:#2563eb}
.role-back-btn{background:#64748b}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="role-view-page">
<div class="role-view-card">

    <h2><i class="fas fa-user-tag"></i> Team Role Details</h2>

    <div class="role-detail">
        <label>Role ID</label>
        <p><?php echo (int)$role['role_id']; ?></p>
    </div>

    <div class="role-detail">
        <label>Role Name</label>
        <p><?php echo htmlspecialchars($role['role_name']); ?></p>
    </div>

    <div class="role-detail">
        <label>Description</label>
        <p><?php echo nl2br(htmlspecialchars($role['description'] ?? 'Not provided')); ?></p>
    </div>

    <div class="role-view-actions">
        <a class="role-edit-btn"
           href="edit_team_role.php?role_id=<?php echo (int)$role['role_id']; ?>">
            <i class="fas fa-edit"></i> Edit Role
        </a>

        <a class="role-back-btn" href="team_roles.php">
            <i class="fas fa-arrow-left"></i> Back to Roles
        </a>
    </div>

</div>
</section>

</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

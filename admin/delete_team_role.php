
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $delete = $conn->prepare("DELETE FROM team_roles WHERE role_id = ?");
    $delete->bind_param("i", $role_id);

    if ($delete->execute()) {
        header("Location: team_roles.php?deleted=1");
        exit;
    } else {
        $error = "Unable to delete this role. It may be assigned to team members.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Team Role</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.role-delete-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.role-delete-card{max-width:600px;background:#fff;padding:30px;border:1px solid #e2e8f0;border-radius:10px;text-align:center}
.role-delete-icon{font-size:45px;color:#dc2626;margin-bottom:15px}
.role-delete-card h2{color:#1e293b}
.role-delete-card p{color:#64748b;line-height:1.7}
.role-delete-name{font-weight:700;color:#1e293b}
.role-delete-btn{background:#dc2626;color:#fff;padding:11px 20px;border:0;border-radius:7px;cursor:pointer;font-weight:600}
.role-delete-btn:hover{background:#b91c1c}
.role-cancel-btn{display:inline-block;margin-left:10px;padding:11px 18px;background:#e2e8f0;color:#334155;text-decoration:none;border-radius:7px}
.role-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:7px;margin-bottom:18px;text-align:left}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="role-delete-page">
<div class="role-delete-card">

    <div class="role-delete-icon">
        <i class="fas fa-trash-alt"></i>
    </div>

    <h2>Delete Team Role</h2>

    <p>
        Are you sure you want to delete this role?
        <br>
        <span class="role-delete-name">
            <?php echo htmlspecialchars($role['role_name']); ?>
        </span>
    </p>

    <?php if ($error): ?>
        <div class="role-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST"
          onsubmit="return confirm('Are you sure you want to delete this role?');">

        <button type="submit" class="role-delete-btn">
            <i class="fas fa-trash"></i> Yes, Delete
        </button>

        <a href="team_roles.php" class="role-cancel-btn">Cancel</a>

    </form>

</div>
</section>

</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

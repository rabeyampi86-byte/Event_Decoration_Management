
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
$role_name = $role['role_name'];
$description = $role['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role_name = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($role_name === '') {
        $error = "Role name is required.";
    } else {

        $check = $conn->prepare(
            "SELECT role_id FROM team_roles WHERE role_name = ? AND role_id != ?"
        );
        $check->bind_param("si", $role_name, $role_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Another role already uses this name.";
        } else {

            $update = $conn->prepare(
                "UPDATE team_roles SET role_name = ?, description = ? WHERE role_id = ?"
            );
            $update->bind_param("ssi", $role_name, $description, $role_id);

            if ($update->execute()) {
                header("Location: view_team_role.php?role_id=" . $role_id . "&updated=1");
                exit;
            } else {
                $error = "Failed to update team role.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Team Role</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.role-edit-page{padding:25px 30px;background:#f5f7fb;min-height:100vh}
.role-edit-card{max-width:750px;background:#fff;padding:28px;border:1px solid #e2e8f0;border-radius:10px}
.role-edit-card h2{color:#1e293b;margin-top:0}
.role-edit-card>p{color:#64748b}
.form-group{margin-bottom:20px}
.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#334155}
.form-group input,.form-group textarea{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box}
.form-group textarea{min-height:130px;resize:vertical}
.role-update-btn{background:#2563eb;color:#fff;padding:11px 20px;border:0;border-radius:7px;cursor:pointer;font-weight:600}
.role-update-btn:hover{background:#1d4ed8}
.role-cancel-btn{display:inline-block;margin-left:10px;padding:11px 18px;background:#e2e8f0;color:#334155;text-decoration:none;border-radius:7px}
.role-error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:7px;margin-bottom:18px}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="role-edit-page">
<div class="role-edit-card">

    <h2><i class="fas fa-edit"></i> Edit Team Role</h2>
    <p>Update the role information below.</p>

    <?php if ($error): ?>
        <div class="role-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Role Name *</label>
            <input type="text" name="role_name" maxlength="120"
                   value="<?php echo htmlspecialchars($role_name); ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <button type="submit" class="role-update-btn">
            <i class="fas fa-save"></i> Update Role
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

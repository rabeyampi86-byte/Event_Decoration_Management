
<?php
require_once __DIR__ . '/../db.php';

$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM team_roles";
if ($search !== '') {
    $sql .= " WHERE role_name LIKE ?";
}
$sql .= " ORDER BY role_id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $term = "%{$search}%";
    $stmt->bind_param("s", $term);
}

$stmt->execute();
$roles = $stmt->get_result();

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM team_roles");
$totalRoles = $totalResult->fetch_assoc()['total'];

$searchResult = $conn->query("SELECT COUNT(*) AS total FROM team_roles");
$allRoles = $searchResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Team Role Management</title>
<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.role-page{
    padding:25px 30px;
    background:#f5f7fb;
    min-height:100vh;
}
.role-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:25px;
}
.role-header h2{
    color:#1e293b;
    margin:0 0 6px;
}
.role-header p{
    color:#64748b;
    margin:0;
}
.role-add-btn{
    background:#2563eb;
    color:#fff;
    padding:11px 18px;
    border-radius:7px;
    text-decoration:none;
    font-weight:600;
}
.role-add-btn:hover{
    background:#1d4ed8;
    color:#fff;
}
.role-summary{
    background:#fff;
    padding:20px;
    border-radius:10px;
    margin-bottom:22px;
    border:1px solid #e2e8f0;
}
.role-summary h3{
    font-size:14px;
    color:#64748b;
    margin:0 0 8px;
}
.role-summary strong{
    font-size:26px;
    color:#1e293b;
}
.role-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:20px;
}
.role-filter{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}
.role-filter input{
    flex:1;
    padding:11px;
    border:1px solid #e2e8f0;
    border-radius:7px;
}
.role-filter button{
    padding:10px 18px;
    background:#2563eb;
    color:#fff;
    border:0;
    border-radius:7px;
    cursor:pointer;
}
.role-table{
    width:100%;
    border-collapse:collapse;
}
.role-table th,.role-table td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid #e2e8f0;
    color:#475569;
}
.role-table th{
    background:#f8fafc;
    color:#1e293b;
}
.role-table td strong{
    color:#1e293b;
}
.role-actions{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}
.role-actions a{
    padding:7px 10px;
    border-radius:5px;
    text-decoration:none;
    font-size:13px;
    color:#fff;
}
.role-view{
    background:#0d9488;
}
.role-edit{
    background:#2563eb;
}
.role-delete{
    background:#dc2626;
}
.role-empty{
    text-align:center;
    padding:25px;
    color:#64748b;
}
@media(max-width:768px){
.role-page{
    padding:20px 15px;
}
.role-header{
    align-items:flex-start;
    flex-direction:column;
}
.role-card{
    overflow-x:auto;
}
.role-table{
    min-width:750px;
}
}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="role-page">

    <div class="role-header">
        <div>
            <h2>Team Role Management</h2>
            <p>Manage your team's roles and responsibilities.</p>
        </div>

        <a href="add_team_role.php" class="role-add-btn">
            <i class="fas fa-plus"></i> Add Team Role
        </a>
    </div>

    <div class="role-summary">
        <h3>Total Team Roles</h3>
        <strong><?php echo (int)$totalRoles; ?></strong>
    </div>

    <div class="role-card">

        <form method="GET" class="role-filter">
            <input type="search" name="search"
                   placeholder="Search role name..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>

        <table class="role-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($roles->num_rows > 0): ?>
                <?php while ($role = $roles->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int)$role['role_id']; ?></td>

                    <td>
                        <strong><?php echo htmlspecialchars($role['role_name']); ?></strong>
                    </td>

                    <td>
                        <?php
                        $description = $role['description'] ?? '';
                        echo htmlspecialchars(
                            strlen($description) > 80
                            ? substr($description, 0, 80) . '...'
                            : $description
                        );
                        ?>
                    </td>

                    <td>
                        <div class="role-actions">
                            <a class="role-view"
                               href="view_team_role.php?role_id=<?php echo (int)$role['role_id']; ?>">
                                <i class="fas fa-eye"></i> View
                            </a>

                            <a class="role-edit"
                               href="edit_team_role.php?role_id=<?php echo (int)$role['role_id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <a class="role-delete"
                               href="delete_team_role.php?role_id=<?php echo (int)$role['role_id']; ?>">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="4" class="role-empty">No team roles found.</td>
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

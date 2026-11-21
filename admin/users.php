
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

// Admin Access Protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['users_csrf'])) {
    $_SESSION['users_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// --------------------------------------
// POST ACTIONS
// --------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['users_csrf'], $_POST['csrf_token'])
    ) {
        die('Invalid request. Please refresh the page.');
    }

    $action = $_POST['action'] ?? '';

    // ADD USER
    if ($action === 'add') {

        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer';

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 8 ||
            !in_array($role, ['admin', 'employee', 'customer'], true)
        ) {
            $message = 'Please provide valid information. Password must be at least 8 characters.';
            $messageType = 'error';

        } else {

            $conn->begin_transaction();

            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");

                $stmt->bind_param(
                    "sssss",
                    $name,
                    $email,
                    $passwordHash,
                    $phone,
                    $role
                );

                $stmt->execute();
                $newUserId = $conn->insert_id;
                $stmt->close();

                // Create customer profile when needed
                if ($role === 'customer') {
                    $profile = $conn->prepare("
                        INSERT INTO customers (user_id)
                        VALUES (?)
                    ");
                    $profile->bind_param("i", $newUserId);
                    $profile->execute();
                    $profile->close();
                }

                $conn->commit();

                $message = 'New user added successfully.';
                $messageType = 'success';

            } catch (mysqli_sql_exception $e) {
                $conn->rollback();

                $message = ($e->getCode() == 1062)
                    ? 'This email address is already registered.'
                    : 'Unable to add user. Please check the database constraints.';

                $messageType = 'error';
            }
        }
    }

    // UPDATE USER
    elseif ($action === 'update') {

        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';

        $allowedRoles = ['admin', 'employee', 'customer'];
        $allowedStatuses = ['active', 'inactive'];

        if (
            $userId <= 0 ||
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($role, $allowedRoles, true) ||
            !in_array($status, $allowedStatuses, true)
        ) {
            $message = 'Invalid user information.';
            $messageType = 'error';

        } elseif (
            $userId === (int)$_SESSION['admin_id'] &&
            ($role !== 'admin' || $status !== 'active')
        ) {
            $message = 'You cannot change your own Admin role or deactivate your account.';
            $messageType = 'error';

        } else {

            try {
                $stmt = $conn->prepare("
                    UPDATE users
                    SET full_name = ?,
                        email = ?,
                        phone = ?,
                        role = ?,
                        status = ?
                    WHERE user_id = ?
                ");

                $stmt->bind_param(
                    "sssssi",
                    $name,
                    $email,
                    $phone,
                    $role,
                    $status,
                    $userId
                );

                $stmt->execute();
                $stmt->close();

                // Ensure customer profile exists
                if ($role === 'customer') {
                    $check = $conn->prepare("
                        SELECT customer_id
                        FROM customers
                        WHERE user_id = ?
                        LIMIT 1
                    ");
                    $check->bind_param("i", $userId);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $check->close();

                    if (!$exists) {
                        $insert = $conn->prepare("
                            INSERT INTO customers (user_id)
                            VALUES (?)
                        ");
                        $insert->bind_param("i", $userId);
                        $insert->execute();
                        $insert->close();
                    }
                }

                $message = 'User information updated successfully.';
                $messageType = 'success';

            } catch (mysqli_sql_exception $e) {
                $message = ($e->getCode() == 1062)
                    ? 'This email address is already used by another account.'
                    : 'Unable to update user information.';

                $messageType = 'error';
            }
        }
    }

    // DELETE USER
    elseif ($action === 'delete') {

        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId === (int)$_SESSION['admin_id']) {
            $message = 'You cannot delete your own account.';
            $messageType = 'error';

        } else {
            try {
                $stmt = $conn->prepare("
                    DELETE FROM users
                    WHERE user_id = ?
                ");

                $stmt->bind_param("i", $userId);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = 'User deleted successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'User not found.';
                    $messageType = 'error';
                }

                $stmt->close();

            } catch (mysqli_sql_exception $e) {
                $message = 'This user is connected to other records. Delete is restricted. You can deactivate this account instead.';
                $messageType = 'error';
            }
        }
    }

    // REDIRECT AFTER POST
    $_SESSION['users_message'] = $message;
    $_SESSION['users_message_type'] = $messageType;

    header("Location: users.php");
    exit;
}

// --------------------------------------
// MESSAGE
// --------------------------------------
if (isset($_SESSION['users_message'])) {
    $message = $_SESSION['users_message'];
    $messageType = $_SESSION['users_message_type'] ?? 'success';

    unset($_SESSION['users_message'], $_SESSION['users_message_type']);
}

// --------------------------------------
// SEARCH AND FILTER
// --------------------------------------
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR CAST(user_id AS CHAR) LIKE ?)";
    $term = "%{$search}%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= 'ssss';
}

if (in_array($roleFilter, ['admin', 'employee', 'customer'], true)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --------------------------------------
// STATISTICS
// --------------------------------------
$stats = [
    'total' => 0,
    'admin' => 0,
    'employee' => 0,
    'customer' => 0,
    'active' => 0,
    'inactive' => 0
];

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(role = 'admin') AS admin,
        SUM(role = 'employee') AS employee,
        SUM(role = 'customer') AS customer,
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive
    FROM users
");

if ($statsQuery) {
    $stats = array_merge($stats, $statsQuery->fetch_assoc());
}

// --------------------------------------
// PAGINATION
// --------------------------------------
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$countSQL = "SELECT COUNT(*) AS total FROM users $whereSQL";
$countStmt = $conn->prepare($countSQL);

if ($params) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalUsers = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalUsers / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// --------------------------------------
// FETCH USERS
// --------------------------------------
$sql = "
    SELECT user_id, full_name, email, phone, role, status, created_at
    FROM users
    $whereSQL
    ORDER BY user_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$listTypes = $types . 'ii';
$listParams = array_merge($params, [$limit, $offset]);

$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();

$users = $stmt->get_result();

// --------------------------------------
// PAGE
// --------------------------------------
$pageTitle = 'Users Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | DecorEase Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="users-page">

            <div class="users-heading">
                <div>
                    <h1>Users Management</h1>
                    <p>Manage Admins, Employees and Customers</p>
                </div>

                <button type="button"
                        class="users-add-btn"
                        onclick="openAddUserModal()">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>

            <?php if ($message !== ''): ?>
                <div class="users-alert <?php echo e($messageType); ?>">
                    <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <!-- STATISTICS -->
            <div class="users-stats">

                <div class="users-stat-card">
                    <span>Total Users</span>
                    <strong><?php echo (int)$stats['total']; ?></strong>
                </div>

                <div class="users-stat-card">
                    <span>Admins</span>
                    <strong><?php echo (int)$stats['admin']; ?></strong>
                </div>

                <div class="users-stat-card">
                    <span>Employees</span>
                    <strong><?php echo (int)$stats['employee']; ?></strong>
                </div>

                <div class="users-stat-card">
                    <span>Customers</span>
                    <strong><?php echo (int)$stats['customer']; ?></strong>
                </div>

                <div class="users-stat-card">
                    <span>Active</span>
                    <strong><?php echo (int)$stats['active']; ?></strong>
                </div>

                <div class="users-stat-card">
                    <span>Inactive</span>
                    <strong><?php echo (int)$stats['inactive']; ?></strong>
                </div>

            </div>

            <!-- SEARCH AND FILTER -->
            <form method="GET" class="users-filter-form">

                <input type="text"
                       name="search"
                       placeholder="Search by name, email, phone or ID..."
                       value="<?php echo e($search); ?>">

                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="employee" <?php echo $roleFilter === 'employee' ? 'selected' : ''; ?>>Employee</option>
                    <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                </select>

                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>

                <button type="submit">
                    <i class="fas fa-search"></i> Search
                </button>

                <a href="users.php" class="users-reset-btn">Reset</a>

            </form>

            <!-- USERS TABLE -->
            <div class="users-table-container">

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Information</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($users->num_rows > 0): ?>

                        <?php while ($user = $users->fetch_assoc()): ?>

                            <tr>
                                <td>#<?php echo (int)$user['user_id']; ?></td>

                                <td>
                                    <strong><?php echo e($user['full_name']); ?></strong>
                                    <small><?php echo e($user['email']); ?></small>
                                </td>

                                <td><?php echo e($user['phone'] ?: 'N/A'); ?></td>

                                <td>
                                    <span class="users-role <?php echo e($user['role']); ?>">
                                        <?php echo ucfirst(e($user['role'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="users-status <?php echo e($user['status']); ?>">
                                        <?php echo ucfirst(e($user['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo $user['created_at']
                                        ? date('d M Y', strtotime($user['created_at']))
                                        : 'N/A';
                                    ?>
                                </td>

                                <td class="users-actions">

                                    <button type="button"
                                            class="users-edit-btn"
                                            onclick='openEditUserModal(<?php echo json_encode([
                                                "user_id" => $user["user_id"],
                                                "full_name" => $user["full_name"],
                                                "email" => $user["email"],
                                                "phone" => $user["phone"],
                                                "role" => $user["role"],
                                                "status" => $user["status"]
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <?php if ((int)$user['user_id'] !== (int)$_SESSION['admin_id']): ?>

                                        <form method="POST"
                                              class="users-delete-form"
                                              onsubmit="return confirm('Are you sure you want to delete this user?');">

                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo e($_SESSION['users_csrf']); ?>">

                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id"
                                                   value="<?php echo (int)$user['user_id']; ?>">

                                            <button type="submit" class="users-delete-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="users-empty">
                                No users found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>

                <div class="users-pagination">

                    <?php
                    $queryParams = $_GET;
                    ?>

                    <?php if ($page > 1): ?>
                        <?php $queryParams['page'] = $page - 1; ?>
                        <a href="?<?php echo e(http_build_query($queryParams)); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                    <?php if ($page < $totalPages): ?>
                        <?php $queryParams['page'] = $page + 1; ?>
                        <a href="?<?php echo e(http_build_query($queryParams)); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>
</div>


<!-- ADD / EDIT USER MODAL -->
<div class="users-modal" id="userModal">

    <div class="users-modal-content">

        <div class="users-modal-header">
            <h2 id="userModalTitle">Add New User</h2>

            <button type="button" onclick="closeUserModal()">
                &times;
            </button>
        </div>

        <form method="POST" id="userForm">

            <input type="hidden" name="csrf_token"
                   value="<?php echo e($_SESSION['users_csrf']); ?>">

            <input type="hidden" name="action" id="formAction" value="add">

            <input type="hidden" name="user_id" id="formUserId">

            <div class="users-form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" id="formName" required>
            </div>

            <div class="users-form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="formEmail" required>
            </div>

            <div class="users-form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" id="formPhone">
            </div>

            <div class="users-form-group" id="passwordGroup">
                <label>Password</label>
                <input type="password"
                       name="password"
                       id="formPassword"
                       minlength="8">
                <small>Minimum 8 characters.</small>
            </div>

            <div class="users-form-group">
                <label>User Role</label>
                <select name="role" id="formRole" required>
                    <option value="customer">Customer</option>
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="users-form-group" id="statusGroup" style="display:none;">
                <label>Account Status</label>
                <select name="status" id="formStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="users-modal-actions">
                <button type="button" class="users-cancel-btn"
                        onclick="closeUserModal()">Cancel</button>

                <button type="submit" class="users-save-btn">
                    Save User
                </button>
            </div>

        </form>

    </div>
</div>


<script>
function openAddUserModal() {
    document.getElementById('userForm').reset();

    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formUserId').value = '';

    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('formPassword').required = true;

    document.getElementById('statusGroup').style.display = 'none';

    document.getElementById('userModal').classList.add('show');
}

function openEditUserModal(user) {
    document.getElementById('userForm').reset();

    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('formAction').value = 'update';

    document.getElementById('formUserId').value = user.user_id;
    document.getElementById('formName').value = user.full_name;
    document.getElementById('formEmail').value = user.email;
    document.getElementById('formPhone').value = user.phone || '';
    document.getElementById('formRole').value = user.role;
    document.getElementById('formStatus').value = user.status;

    document.getElementById('passwordGroup').style.display = 'none';
    document.getElementById('formPassword').required = false;

    document.getElementById('statusGroup').style.display = 'block';

    document.getElementById('userModal').classList.add('show');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
}

document.getElementById('userModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeUserModal();
    }
});
</script>

<?php
$stmt->close();
include 'includes/footer.php';
?>
</body>
</html>

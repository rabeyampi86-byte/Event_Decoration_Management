
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['employees_csrf'])) {
    $_SESSION['employees_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

$availabilityOptions = ['available', 'busy', 'unavailable'];
$accountStatuses = ['active', 'inactive', 'suspended'];

// ======================================
// POST ACTIONS
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['employees_csrf'], $_POST['csrf_token'])
    ) {
        die('Invalid request. Please refresh the page.');
    }

    $action = $_POST['action'] ?? '';

    // ----------------------------------
    // ADD EMPLOYEE
    // ----------------------------------
    if ($action === 'add') {

        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        $designation = trim($_POST['designation'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $joiningDate = $_POST['joining_date'] ?? '';
        $salary = $_POST['salary'] ?? '';
        $availability = $_POST['availability_status'] ?? 'available';

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 8 ||
            !in_array($availability, $availabilityOptions, true) ||
            ($salary !== '' && (!is_numeric($salary) || $salary < 0)) ||
            ($joiningDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate))
        ) {
            $message = 'Please enter valid employee information. Password must contain at least 8 characters.';
            $messageType = 'error';

        } else {

            $conn->begin_transaction();

            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Create login account
                $stmt = $conn->prepare("
                    INSERT INTO users
                    (full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, 'employee', 'active')
                ");

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $email,
                    $passwordHash,
                    $phone
                );

                $stmt->execute();
                $userId = $conn->insert_id;
                $stmt->close();

                // Create employee profile
                $profile = $conn->prepare("
                    INSERT INTO employee_profiles
                    (
                        user_id,
                        designation,
                        specialization,
                        joining_date,
                        salary,
                        availability_status
                    )
                    VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)
                ");

                $salaryValue = $salary === '' ? '' : (string)$salary;

                $profile->bind_param(
                    "isssss",
                    $userId,
                    $designation,
                    $specialization,
                    $joiningDate,
                    $salaryValue,
                    $availability
                );

                $profile->execute();
                $profile->close();

                $conn->commit();

                $message = 'Employee added successfully.';
                $messageType = 'success';

            } catch (mysqli_sql_exception $ex) {

                $conn->rollback();

                $message = ($ex->getCode() == 1062)
                    ? 'This email address is already registered.'
                    : 'Unable to add employee. Please check the information.';

                $messageType = 'error';
            }
        }
    }

    // ----------------------------------
    // UPDATE EMPLOYEE
    // ----------------------------------
    elseif ($action === 'update') {

        $userId = (int)($_POST['user_id'] ?? 0);

        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $designation = trim($_POST['designation'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $joiningDate = $_POST['joining_date'] ?? '';
        $salary = $_POST['salary'] ?? '';
        $availability = $_POST['availability_status'] ?? '';
        $accountStatus = $_POST['account_status'] ?? '';

        if (
            $userId <= 0 ||
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($availability, $availabilityOptions, true) ||
            !in_array($accountStatus, $accountStatuses, true) ||
            ($salary !== '' && (!is_numeric($salary) || $salary < 0)) ||
            ($joiningDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate))
        ) {
            $message = 'Invalid employee information.';
            $messageType = 'error';

        } else {

            try {
                // Confirm this user is an employee
                $check = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE user_id = ? AND role = 'employee'
                    LIMIT 1
                ");

                $check->bind_param("i", $userId);
                $check->execute();

                $employeeExists = $check->get_result()->num_rows > 0;
                $check->close();

                if (!$employeeExists) {
                    throw new Exception('Employee account not found.');
                }

                $conn->begin_transaction();

                // Update user account
                $stmt = $conn->prepare("
                    UPDATE users
                    SET full_name = ?,
                        email = ?,
                        phone = ?,
                        status = ?
                    WHERE user_id = ? AND role = 'employee'
                ");

                $stmt->bind_param(
                    "ssssi",
                    $name,
                    $email,
                    $phone,
                    $accountStatus,
                    $userId
                );

                $stmt->execute();
                $stmt->close();

                // Update employee profile
                $profile = $conn->prepare("
                    UPDATE employee_profiles
                    SET designation = NULLIF(?, ''),
                        specialization = NULLIF(?, ''),
                        joining_date = NULLIF(?, ''),
                        salary = NULLIF(?, ''),
                        availability_status = ?
                    WHERE user_id = ?
                ");

                $salaryValue = $salary === '' ? '' : (string)$salary;

                $profile->bind_param(
                    "sssssi",
                    $designation,
                    $specialization,
                    $joiningDate,
                    $salaryValue,
                    $availability,
                    $userId
                );

                $profile->execute();

                // If profile does not exist, create it
                if ($profile->affected_rows === 0) {

                    $profileCheck = $conn->prepare("
                        SELECT employee_id
                        FROM employee_profiles
                        WHERE user_id = ?
                    ");

                    $profileCheck->bind_param("i", $userId);
                    $profileCheck->execute();

                    $profileExists = $profileCheck->get_result()->num_rows > 0;
                    $profileCheck->close();

                    if (!$profileExists) {
                        $profile->close();

                        $insert = $conn->prepare("
                            INSERT INTO employee_profiles
                            (
                                user_id,
                                designation,
                                specialization,
                                joining_date,
                                salary,
                                availability_status
                            )
                            VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)
                        ");

                        $insert->bind_param(
                            "isssss",
                            $userId,
                            $designation,
                            $specialization,
                            $joiningDate,
                            $salaryValue,
                            $availability
                        );

                        $insert->execute();
                        $insert->close();

                    } else {
                        $profile->close();
                    }

                } else {
                    $profile->close();
                }

                $conn->commit();

                $message = 'Employee information updated successfully.';
                $messageType = 'success';

            } catch (Exception $ex) {

                if ($conn->errno === 0 || $conn->connect_errno === 0) {
                    try {
                        $conn->rollback();
                    } catch (Throwable $ignored) {
                    }
                }

                $message = $ex instanceof mysqli_sql_exception &&
                           $ex->getCode() == 1062
                    ? 'This email address is already used.'
                    : 'Unable to update employee information.';

                $messageType = 'error';
            }
        }
    }

    $_SESSION['employees_message'] = $message;
    $_SESSION['employees_message_type'] = $messageType;

    header("Location: employees.php");
    exit;
}

// ======================================
// FLASH MESSAGE
// ======================================
if (isset($_SESSION['employees_message'])) {

    $message = $_SESSION['employees_message'];
    $messageType = $_SESSION['employees_message_type'] ?? 'success';

    unset($_SESSION['employees_message']);
    unset($_SESSION['employees_message_type']);
}

// ======================================
// SEARCH AND FILTER
// ======================================
$search = trim($_GET['search'] ?? '');
$availabilityFilter = $_GET['availability'] ?? '';
$accountFilter = $_GET['account_status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {

    $where[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
        OR ep.designation LIKE ?
        OR ep.specialization LIKE ?
        OR CAST(u.user_id AS CHAR) LIKE ?
    )";

    $term = "%{$search}%";

    for ($i = 0; $i < 6; $i++) {
        $params[] = $term;
    }

    $types .= 'ssssss';
}

if (in_array($availabilityFilter, $availabilityOptions, true)) {
    $where[] = "ep.availability_status = ?";
    $params[] = $availabilityFilter;
    $types .= 's';
}

if (in_array($accountFilter, $accountStatuses, true)) {
    $where[] = "u.status = ?";
    $params[] = $accountFilter;
    $types .= 's';
}

$whereSQL = $where ? 'AND ' . implode(' AND ', $where) : '';

// ======================================
// STATISTICS
// ======================================
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'suspended' => 0,
    'available' => 0,
    'busy' => 0,
    'unavailable' => 0
];

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(u.status = 'active') AS active,
        SUM(u.status = 'inactive') AS inactive,
        SUM(u.status = 'suspended') AS suspended,
        SUM(ep.availability_status = 'available') AS available,
        SUM(ep.availability_status = 'busy') AS busy,
        SUM(ep.availability_status = 'unavailable') AS unavailable
    FROM users u
    LEFT JOIN employee_profiles ep ON ep.user_id = u.user_id
    WHERE u.role = 'employee'
");

if ($statsQuery) {
    $stats = array_merge($stats, $statsQuery->fetch_assoc());
}

// ======================================
// PAGINATION
// ======================================
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$countSQL = "
    SELECT COUNT(*)
    FROM users u
    LEFT JOIN employee_profiles ep ON ep.user_id = u.user_id
    WHERE u.role = 'employee' $whereSQL
";

$countStmt = $conn->prepare($countSQL);

if ($params) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalEmployees = (int)$countStmt->get_result()->fetch_row()[0];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalEmployees / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// ======================================
// FETCH EMPLOYEES
// ======================================
$sql = "
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        u.status AS account_status,
        u.created_at,
        ep.employee_id,
        ep.designation,
        ep.specialization,
        ep.joining_date,
        ep.salary,
        ep.availability_status

    FROM users u

    LEFT JOIN employee_profiles ep
        ON ep.user_id = u.user_id

    WHERE u.role = 'employee' $whereSQL

    ORDER BY u.user_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$listTypes = $types . 'ii';
$listParams = array_merge($params, [$limit, $offset]);

$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();

$employees = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employees Management | DecorEase Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="employees-page">

            <!-- HEADING -->
            <div class="employees-heading">

                <div>
                    <h1>Employees Management</h1>
                    <p>Manage employee information and availability</p>
                </div>

                <button type="button"
                        class="employees-add-btn"
                        onclick="openAddEmployeeModal()">

                    <i class="fas fa-user-plus"></i>
                    Add New Employee

                </button>

            </div>

            <!-- MESSAGE -->
            <?php if ($message !== ''): ?>

                <div class="employees-alert <?php echo e($messageType); ?>">
                    <?php echo e($message); ?>
                </div>

            <?php endif; ?>

            <!-- STATISTICS -->
            <div class="employees-stats">

                <div class="employees-stat-card">
                    <span>Total Employees</span>
                    <strong><?php echo (int)$stats['total']; ?></strong>
                </div>

                <div class="employees-stat-card">
                    <span>Active Accounts</span>
                    <strong><?php echo (int)$stats['active']; ?></strong>
                </div>

                <div class="employees-stat-card">
                    <span>Inactive Accounts</span>
                    <strong><?php echo (int)$stats['inactive']; ?></strong>
                </div>

                <div class="employees-stat-card">
                    <span>Available</span>
                    <strong><?php echo (int)$stats['available']; ?></strong>
                </div>

                <div class="employees-stat-card">
                    <span>Busy</span>
                    <strong><?php echo (int)$stats['busy']; ?></strong>
                </div>

                <div class="employees-stat-card">
                    <span>Unavailable</span>
                    <strong><?php echo (int)$stats['unavailable']; ?></strong>
                </div>

            </div>

            <!-- SEARCH AND FILTER -->
            <form method="GET" class="employees-filter-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search name, email, phone or designation..."
                    value="<?php echo e($search); ?>"
                >

                <select name="availability">

                    <option value="">All Availability</option>

                    <?php foreach ($availabilityOptions as $option): ?>

                        <option value="<?php echo e($option); ?>"
                            <?php echo $availabilityFilter === $option ? 'selected' : ''; ?>>

                            <?php echo ucfirst($option); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="account_status">

                    <option value="">All Account Status</option>

                    <?php foreach ($accountStatuses as $status): ?>

                        <option value="<?php echo e($status); ?>"
                            <?php echo $accountFilter === $status ? 'selected' : ''; ?>>

                            <?php echo ucfirst($status); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit">
                    <i class="fas fa-search"></i> Search
                </button>

                <a href="employees.php" class="employees-reset-btn">
                    Reset
                </a>

            </form>

            <!-- EMPLOYEE TABLE -->
            <div class="employees-table-container">

                <table class="employees-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Information</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Specialization</th>
                            <th>Salary</th>
                            <th>Availability</th>
                            <th>Account</th>
                            <th>Joining Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($employees->num_rows > 0): ?>

                        <?php while ($employee = $employees->fetch_assoc()): ?>

                            <tr>

                                <td>#<?php echo (int)$employee['user_id']; ?></td>

                                <td>
                                    <strong><?php echo e($employee['full_name']); ?></strong>
                                    <small><?php echo e($employee['email']); ?></small>
                                </td>

                                <td><?php echo e($employee['phone'] ?: 'N/A'); ?></td>

                                <td><?php echo e($employee['designation'] ?: 'N/A'); ?></td>

                                <td><?php echo e($employee['specialization'] ?: 'N/A'); ?></td>

                                <td>
                                    <?php
                                    echo $employee['salary'] !== null
                                        ? '৳' . number_format((float)$employee['salary'], 2)
                                        : 'N/A';
                                    ?>
                                </td>

                                <td>
                                    <span class="employees-availability <?php echo e($employee['availability_status'] ?? 'unavailable'); ?>">
                                        <?php echo ucfirst(e($employee['availability_status'] ?? 'N/A')); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="employees-account-status <?php echo e($employee['account_status']); ?>">
                                        <?php echo ucfirst(e($employee['account_status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo $employee['joining_date']
                                        ? date('d M Y', strtotime($employee['joining_date']))
                                        : 'N/A';
                                    ?>
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="employees-edit-btn"
                                        title="Edit Employee"
                                        onclick='openEditEmployeeModal(<?php
                                            echo json_encode([
                                                "user_id" => $employee["user_id"],
                                                "full_name" => $employee["full_name"],
                                                "email" => $employee["email"],
                                                "phone" => $employee["phone"],
                                                "designation" => $employee["designation"],
                                                "specialization" => $employee["specialization"],
                                                "joining_date" => $employee["joining_date"],
                                                "salary" => $employee["salary"],
                                                "availability_status" => $employee["availability_status"] ?? "available",
                                                "account_status" => $employee["account_status"]
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                        ?>)'>

                                        <i class="fas fa-edit"></i>

                                    </button>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="employees-empty">
                                No employees found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>

                <div class="employees-pagination">

                    <?php $queryParams = $_GET; ?>

                    <?php if ($page > 1): ?>

                        <?php $queryParams['page'] = $page - 1; ?>

                        <a href="?<?php echo e(http_build_query($queryParams)); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>

                    <?php endif; ?>

                    <span>
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </span>

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


<!-- ADD / EDIT EMPLOYEE MODAL -->
<div class="employees-modal" id="employeeModal">

    <div class="employees-modal-content">

        <div class="employees-modal-header">

            <h2 id="employeeModalTitle">Add New Employee</h2>

            <button type="button" onclick="closeEmployeeModal()">
                &times;
            </button>

        </div>

        <form method="POST" id="employeeForm">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION['employees_csrf']); ?>"
            >

            <input type="hidden" name="action" id="employeeAction" value="add">

            <input type="hidden" name="user_id" id="employeeUserId">

            <div class="employees-form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="employeeName" required>
            </div>

            <div class="employees-form-group">
                <label>Email Address *</label>
                <input type="email" name="email" id="employeeEmail" required>
            </div>

            <div class="employees-form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" id="employeePhone">
            </div>

            <div class="employees-form-group" id="employeePasswordGroup">
                <label>Password *</label>
                <input type="password"
                       name="password"
                       id="employeePassword"
                       minlength="8">
                <small>Minimum 8 characters.</small>
            </div>

            <div class="employees-form-group">
                <label>Designation</label>
                <input type="text" name="designation" id="employeeDesignation">
            </div>

            <div class="employees-form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" id="employeeSpecialization">
            </div>

            <div class="employees-form-group">
                <label>Joining Date</label>
                <input type="date" name="joining_date" id="employeeJoiningDate">
            </div>

            <div class="employees-form-group">
                <label>Salary (BDT)</label>
                <input type="number"
                       name="salary"
                       id="employeeSalary"
                       min="0"
                       step="0.01">
            </div>

            <div class="employees-form-group">
                <label>Availability Status</label>

                <select name="availability_status" id="employeeAvailability">

                    <option value="available">Available</option>
                    <option value="busy">Busy</option>
                    <option value="unavailable">Unavailable</option>

                </select>
            </div>

            <div class="employees-form-group" id="employeeAccountGroup" style="display:none;">

                <label>Account Status</label>

                <select name="account_status" id="employeeAccountStatus">

                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>

                </select>

            </div>

            <div class="employees-modal-actions">

                <button type="button"
                        class="employees-cancel-btn"
                        onclick="closeEmployeeModal()">

                    Cancel

                </button>

                <button type="submit" class="employees-save-btn">
                    Save Employee
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openAddEmployeeModal() {

    document.getElementById('employeeForm').reset();

    document.getElementById('employeeModalTitle').textContent = 'Add New Employee';
    document.getElementById('employeeAction').value = 'add';
    document.getElementById('employeeUserId').value = '';

    document.getElementById('employeePasswordGroup').style.display = 'block';
    document.getElementById('employeePassword').required = true;

    document.getElementById('employeeAccountGroup').style.display = 'none';

    document.getElementById('employeeModal').classList.add('show');
}


function openEditEmployeeModal(employee) {

    document.getElementById('employeeForm').reset();

    document.getElementById('employeeModalTitle').textContent = 'Edit Employee';
    document.getElementById('employeeAction').value = 'update';

    document.getElementById('employeeUserId').value = employee.user_id;

    document.getElementById('employeeName').value = employee.full_name;
    document.getElementById('employeeEmail').value = employee.email;
    document.getElementById('employeePhone').value = employee.phone || '';

    document.getElementById('employeeDesignation').value = employee.designation || '';
    document.getElementById('employeeSpecialization').value = employee.specialization || '';

    document.getElementById('employeeJoiningDate').value = employee.joining_date || '';
    document.getElementById('employeeSalary').value = employee.salary || '';

    document.getElementById('employeeAvailability').value =
        employee.availability_status || 'available';

    document.getElementById('employeeAccountStatus').value =
        employee.account_status || 'active';

    document.getElementById('employeePasswordGroup').style.display = 'none';
    document.getElementById('employeePassword').required = false;

    document.getElementById('employeeAccountGroup').style.display = 'block';

    document.getElementById('employeeModal').classList.add('show');
}


function closeEmployeeModal() {

    document.getElementById('employeeModal').classList.remove('show');

}


document.getElementById('employeeModal').addEventListener('click', function(event) {

    if (event.target === this) {
        closeEmployeeModal();
    }

});

</script>

<?php
$stmt->close();
include 'includes/footer.php';
?>

</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../db.php';

/* ===============================
   Admin Authentication
================================ */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ===============================
   CSRF Protection
================================ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$success = '';
$error = '';

/* ===============================
   Helper Functions
================================ */
function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uploadEmployeeImage($file) {
    if (
        !isset($file) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Image size must not exceed 5 MB.");
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMime[$mime])) {
        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
    }

    $uploadDir = __DIR__ . '/../uploads/employees/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Unable to create upload directory.");
        }
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Unable to save uploaded image.");
    }

    return 'uploads/employees/' . $fileName;
}

function deleteEmployeeImage($path) {
    if (!$path) {
        return;
    }

    $prefix = 'uploads/employees/';

    if (strpos($path, $prefix) !== 0) {
        return;
    }

    $file = __DIR__ . '/../' . $path;

    if (is_file($file)) {
        unlink($file);
    }
}

/* ===============================
   POST Actions
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals($csrfToken, $_POST['csrf_token'])
        ) {
            throw new Exception("Invalid request. Please refresh the page.");
        }

        $action = $_POST['action'] ?? '';

        /* ===============================
           ADD EMPLOYEE
        ================================ */
        if ($action === 'add') {

            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $designation = trim($_POST['designation'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $joiningDate = $_POST['joining_date'] ?: null;
            $salary = $_POST['salary'] !== '' ? $_POST['salary'] : null;
            $availability = $_POST['availability_status'] ?? 'available';
            $accountStatus = $_POST['status'] ?? 'active';
            $bio = trim($_POST['bio'] ?? '');

            if (!$fullName || !$email || !$password || !$designation) {
                throw new Exception("Please fill in all required fields.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            if (!in_array($availability, ['available', 'busy', 'unavailable'], true)) {
                throw new Exception("Invalid availability status.");
            }

            if (!in_array($accountStatus, ['active', 'inactive', 'suspended'], true)) {
                throw new Exception("Invalid account status.");
            }

            $newImage = uploadEmployeeImage($_FILES['profile_image'] ?? null);

            $conn->begin_transaction();

            try {

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, 'employee', ?)
                ");

                $stmt->bind_param(
                    "sssss",
                    $fullName,
                    $email,
                    $passwordHash,
                    $phone,
                    $accountStatus
                );

                $stmt->execute();
                $userId = $conn->insert_id;

                $stmt = $conn->prepare("
                    INSERT INTO employee_profiles
                    (
                        user_id,
                        designation,
                        specialization,
                        profile_image,
                        bio,
                        joining_date,
                        salary,
                        availability_status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "isssssss",
                    $userId,
                    $designation,
                    $specialization,
                    $newImage,
                    $bio,
                    $joiningDate,
                    $salary,
                    $availability
                );

                $stmt->execute();

                $conn->commit();

                $success = "Employee added successfully.";

            } catch (Throwable $e) {
                $conn->rollback();

                if ($newImage) {
                    deleteEmployeeImage($newImage);
                }

                throw $e;
            }
        }

        /* ===============================
           UPDATE EMPLOYEE
        ================================ */
        elseif ($action === 'update') {

            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);

            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            $designation = trim($_POST['designation'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $joiningDate = $_POST['joining_date'] ?: null;
            $salary = $_POST['salary'] !== '' ? $_POST['salary'] : null;
            $availability = $_POST['availability_status'] ?? 'available';
            $accountStatus = $_POST['status'] ?? 'active';
            $bio = trim($_POST['bio'] ?? '');

            if (!$employeeId || !$userId || !$fullName || !$email || !$designation) {
                throw new Exception("Please fill in all required fields.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            if (!in_array($availability, ['available', 'busy', 'unavailable'], true)) {
                throw new Exception("Invalid availability status.");
            }

            if (!in_array($accountStatus, ['active', 'inactive', 'suspended'], true)) {
                throw new Exception("Invalid account status.");
            }

            $stmt = $conn->prepare("
                SELECT profile_image
                FROM employee_profiles
                WHERE employee_id = ? AND user_id = ?
            ");

            $stmt->bind_param("ii", $employeeId, $userId);
            $stmt->execute();

            $oldProfile = $stmt->get_result()->fetch_assoc();

            if (!$oldProfile) {
                throw new Exception("Employee record not found.");
            }

            $oldImage = $oldProfile['profile_image'];
            $newImage = uploadEmployeeImage($_FILES['profile_image'] ?? null);

            $conn->begin_transaction();

            try {

                if ($password !== '') {

                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, phone = ?,
                            password_hash = ?, status = ?
                        WHERE user_id = ? AND role = 'employee'
                    ");

                    $stmt->bind_param(
                        "sssssi",
                        $fullName,
                        $email,
                        $phone,
                        $passwordHash,
                        $accountStatus,
                        $userId
                    );

                } else {

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, phone = ?, status = ?
                        WHERE user_id = ? AND role = 'employee'
                    ");

                    $stmt->bind_param(
                        "ssssi",
                        $fullName,
                        $email,
                        $phone,
                        $accountStatus,
                        $userId
                    );
                }

                $stmt->execute();

                if ($newImage !== null) {

                    $stmt = $conn->prepare("
                        UPDATE employee_profiles
                        SET designation = ?,
                            specialization = ?,
                            profile_image = ?,
                            bio = ?,
                            joining_date = ?,
                            salary = ?,
                            availability_status = ?
                        WHERE employee_id = ? AND user_id = ?
                    ");

                    $stmt->bind_param(
                        "sssssssii",
                        $designation,
                        $specialization,
                        $newImage,
                        $bio,
                        $joiningDate,
                        $salary,
                        $availability,
                        $employeeId,
                        $userId
                    );

                } else {

                    $stmt = $conn->prepare("
                        UPDATE employee_profiles
                        SET designation = ?,
                            specialization = ?,
                            bio = ?,
                            joining_date = ?,
                            salary = ?,
                            availability_status = ?
                        WHERE employee_id = ? AND user_id = ?
                    ");

                    $stmt->bind_param(
                        "ssssssii",
                        $designation,
                        $specialization,
                        $bio,
                        $joiningDate,
                        $salary,
                        $availability,
                        $employeeId,
                        $userId
                    );
                }

                $stmt->execute();

                $conn->commit();

                if ($newImage !== null && $oldImage !== $newImage) {
                    deleteEmployeeImage($oldImage);
                }

                $success = "Employee updated successfully.";

            } catch (Throwable $e) {
                $conn->rollback();

                if ($newImage) {
                    deleteEmployeeImage($newImage);
                }

                throw $e;
            }
            
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

/* ===============================
   Search / Filter
================================ */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$availabilityFilter = $_GET['availability'] ?? '';

$where = ["u.role = 'employee'"];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR ep.designation LIKE ?)";
    $term = "%$search%";

    $params = array_merge($params, [$term, $term, $term, $term]);
    $types .= 'ssss';
}

if (in_array($statusFilter, ['active', 'inactive', 'suspended'], true)) {
    $where[] = "u.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (in_array($availabilityFilter, ['available', 'busy', 'unavailable'], true)) {
    $where[] = "ep.availability_status = ?";
    $params[] = $availabilityFilter;
    $types .= 's';
}

$whereSQL = implode(' AND ', $where);

/* ===============================
   Statistics
================================ */
$stats = [
    'total' => 0,
    'active' => 0,
    'available' => 0,
    'busy' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(u.status = 'active') AS active,
        SUM(ep.availability_status = 'available') AS available,
        SUM(ep.availability_status = 'busy') AS busy
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id = u.user_id
    WHERE u.role = 'employee'
");

if ($result) {
    $stats = $result->fetch_assoc();
}

/* ===============================
   Pagination
================================ */
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$countSQL = "
    SELECT COUNT(*) AS total
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id = u.user_id
    WHERE $whereSQL
";

$stmt = $conn->prepare($countSQL);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$totalRows = (int)$stmt->get_result()->fetch_assoc()['total'];

$totalPages = max(1, (int)ceil($totalRows / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

/* ===============================
   Employee List
================================ */
$listSQL = "
    SELECT
        ep.employee_id,
        ep.user_id,
        ep.designation,
        ep.specialization,
        ep.profile_image,
        ep.bio,
        ep.joining_date,
        ep.salary,
        ep.availability_status,
        u.full_name,
        u.email,
        u.phone,
        u.status
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id = u.user_id
    WHERE $whereSQL
    ORDER BY ep.employee_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($listSQL);

$listTypes = $types . 'ii';
$listParams = array_merge($params, [$limit, $offset]);

$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();

$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function pageUrl($page) {
    $query = $_GET;
    $query['page'] = $page;

    return '?' . http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Management | Admin</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.employee-page {
    padding: 25px;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.page-heading h2 {
    margin: 0;
    font-size: 25px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: #fff;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}

.stat-card h3 {
    font-size: 27px;
    margin: 8px 0;
}

.stat-card p {
    color: #777;
    margin: 0;
}

.employee-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.employee-toolbar input,
.employee-toolbar select,
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-size: 14px;
}

.employee-toolbar input {
    max-width: 280px;
}

.employee-toolbar select {
    width: auto;
    min-width: 150px;
}

.btn {
    border: 0;
    border-radius: 7px;
    padding: 11px 17px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.btn-primary {
    background: #6d314b;
    color: #fff;
}

.btn-secondary {
    background: #eee;
    color: #333;
}

.btn-edit {
    background: #f1e4ea;
    color: #6d314b;
}

.table-wrapper {
    background: #fff;
    border-radius: 12px;
    overflow-x: auto;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}

.employee-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

.employee-table th,
.employee-table td {
    padding: 14px 15px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 13px;
}

.employee-table th {
    background: #faf6f8;
    color: #5b3545;
}

.employee-photo {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    background: #f1e4ea;
}

.status-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    background: #eee;
}

.status-active,
.status-available {
    background: #e2f5e9;
    color: #237544;
}

.status-inactive,
.status-unavailable {
    background: #fbe8e8;
    color: #a33;
}

.status-busy {
    background: #fff1d9;
    color: #98630b;
}

.alert {
    padding: 13px 16px;
    margin-bottom: 18px;
    border-radius: 7px;
}

.alert-success {
    background: #e4f5e9;
    color: #236b3b;
}

.alert-error {
    background: #fbe8e8;
    color: #a33;
}

.pagination {
    display: flex;
    gap: 7px;
    justify-content: center;
    margin: 25px 0;
}

.pagination a {
    padding: 8px 13px;
    border-radius: 6px;
    background: #fff;
    color: #6d314b;
    text-decoration: none;
    border: 1px solid #eee;
}

.pagination a.current {
    background: #6d314b;
    color: #fff;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 9999;
    padding: 25px;
    overflow-y: auto;
}

.modal-content {
    background: #fff;
    max-width: 750px;
    margin: 30px auto;
    border-radius: 12px;
    padding: 25px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.modal-header h3 {
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 25px;
    cursor: pointer;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 17px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
    font-size: 13px;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group small {
    color: #777;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

.image-preview {
    width: 75px;
    height: 75px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 10px;
}

@media(max-width:900px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width:600px) {
    .employee-page {
        padding: 12px;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="employee-page">

            <div class="page-heading">
                <div>
                    <h2>Employee Management</h2>
                    <p>Manage your employees and their profiles.</p>
                </div>

                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Employee
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= clean($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= clean($error) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">

                <div class="stat-card">
                    <p>Total Employees</p>
                    <h3><?= (int)$stats['total'] ?></h3>
                </div>

                <div class="stat-card">
                    <p>Active Employees</p>
                    <h3><?= (int)$stats['active'] ?></h3>
                </div>

                <div class="stat-card">
                    <p>Available Employees</p>
                    <h3><?= (int)$stats['available'] ?></h3>
                </div>

                <div class="stat-card">
                    <p>Busy Employees</p>
                    <h3><?= (int)$stats['busy'] ?></h3>
                </div>

            </div>

            <!-- Search and Filters -->
            <form method="GET" class="employee-toolbar">

                <input
                    type="search"
                    name="search"
                    placeholder="Search name, email, phone..."
                    value="<?= clean($search) ?>"
                >

                <select name="status">
                    <option value="">All Account Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>

                <select name="availability">
                    <option value="">All Availability</option>
                    <option value="available" <?= $availabilityFilter === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="busy" <?= $availabilityFilter === 'busy' ? 'selected' : '' ?>>Busy</option>
                    <option value="unavailable" <?= $availabilityFilter === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>

                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> Search
                </button>

                <a href="employees.php" class="btn btn-secondary">
                    Reset
                </a>

            </form>

            <!-- Employee Table -->
            <div class="table-wrapper">

                <table class="employee-table">

                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Contact</th>
                            <th>Designation</th>
                            <th>Specialization</th>
                            <th>Joining Date</th>
                            <th>Salary</th>
                            <th>Availability</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($employees) > 0): ?>

                        <?php foreach ($employees as $employee): ?>

                            <tr>

                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">

                                        <?php if (!empty($employee['profile_image'])): ?>
                                            <img
                                                class="employee-photo"
                                                src="../<?= clean($employee['profile_image']) ?>"
                                                alt="Employee"
                                            >
                                        <?php else: ?>
                                            <div class="employee-photo"
                                                 style="display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>

                                        <strong><?= clean($employee['full_name']) ?></strong>

                                    </div>
                                </td>

                                <td>
                                    <?= clean($employee['email']) ?><br>
                                    <small><?= clean($employee['phone']) ?></small>
                                </td>

                                <td><?= clean($employee['designation']) ?></td>

                                <td><?= clean($employee['specialization']) ?></td>

                                <td><?= clean($employee['joining_date']) ?></td>

                                <td>
                                    <?= $employee['salary'] !== null
                                        ? number_format((float)$employee['salary'], 2)
                                        : '—' ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= clean($employee['availability_status']) ?>">
                                        <?= ucfirst(clean($employee['availability_status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= clean($employee['status']) ?>">
                                        <?= ucfirst(clean($employee['status'])) ?>
                                    </span>
                                </td>

                                <td>
    <div style="display:flex; gap:7px;">

        <!-- Edit Button -->
        <button
            type="button"
            class="btn btn-edit"
            onclick='openEditModal(<?= json_encode($employee, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'
        >
            <i class="fas fa-edit"></i> Edit
        </button>

        <!-- Delete Button -->
        <form action="delete_employee.php" method="POST"
              onsubmit="return confirm('Are you sure you want to delete this employee?');">

            <input type="hidden" name="employee_id"
                   value="<?= (int)$employee['employee_id'] ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= clean($csrfToken) ?>">

            <button type="submit" class="btn"
                    style="background:#fde8e8;color:#b42318;">
                <i class="fas fa-trash"></i> Delete
            </button>

        </form>

    </div>
</td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" style="text-align:center;padding:30px;">
                                No employees found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>

                <div class="pagination">

                    <?php if ($page > 1): ?>
                        <a href="<?= clean(pageUrl($page - 1)) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a
                            href="<?= clean(pageUrl($i)) ?>"
                            class="<?= $i === $page ? 'current' : '' ?>"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= clean(pageUrl($page + 1)) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

        <?php include 'includes/footer.php'; ?>

    </div>
</div>


<!-- ADD / EDIT EMPLOYEE MODAL -->
<div class="modal" id="employeeModal">

    <div class="modal-content">

        <div class="modal-header">
            <h3 id="modalTitle">Add New Employee</h3>
            <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="employee_id" id="employeeId">
            <input type="hidden" name="user_id" id="userId">

            <div class="form-grid">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="fullName" required>
                </div>

                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="phone">
                </div>

                <div class="form-group">
                    <label>Password <span id="passwordNote">*</span></label>
                    <input type="password" name="password" id="password">
                    <small id="passwordHelp">Required for a new employee.</small>
                </div>

                <div class="form-group">
                    <label>Designation *</label>
                    <input type="text" name="designation" id="designation" required>
                </div>

                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" name="specialization" id="specialization">
                </div>

                <div class="form-group">
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" id="joiningDate">
                </div>

                <div class="form-group">
                    <label>Salary</label>
                    <input type="number" name="salary" id="salary" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label>Availability</label>
                    <select name="availability_status" id="availability">
                        <option value="available">Available</option>
                        <option value="busy">Busy</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status" id="accountStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

            </div>

            <div class="form-group">
                <label>Profile Image</label>
                <div id="currentImagePreview"></div>
                <input
                    type="file"
                    name="profile_image"
                    id="profileImage"
                    accept=".jpg,.jpeg,.png,.webp"
                >
                <small>JPG, PNG or WEBP. Maximum size 5 MB.</small>
            </div>

            <div class="form-group">
                <label>Employee Bio</label>
                <textarea
                    name="bio"
                    id="bio"
                    placeholder="Write a short introduction about this employee..."
                ></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    Cancel
                </button>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Employee
                </button>
            </div>

        </form>

    </div>
</div>


<script>
const employeeModal = document.getElementById('employeeModal');

function openAddModal() {

    document.getElementById('modalTitle').textContent = 'Add New Employee';
    document.getElementById('formAction').value = 'add';

    document.getElementById('employeeId').value = '';
    document.getElementById('userId').value = '';

    document.getElementById('fullName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password').value = '';

    document.getElementById('designation').value = '';
    document.getElementById('specialization').value = '';
    document.getElementById('joiningDate').value = '';
    document.getElementById('salary').value = '';

    document.getElementById('availability').value = 'available';
    document.getElementById('accountStatus').value = 'active';

    document.getElementById('bio').value = '';
    document.getElementById('profileImage').value = '';

    document.getElementById('password').required = true;
    document.getElementById('passwordNote').textContent = '*';
    document.getElementById('passwordHelp').textContent = 'Required for a new employee.';

    document.getElementById('currentImagePreview').innerHTML = '';

    employeeModal.style.display = 'block';
}


function openEditModal(employee) {

    document.getElementById('modalTitle').textContent = 'Edit Employee';
    document.getElementById('formAction').value = 'update';

    document.getElementById('employeeId').value = employee.employee_id;
    document.getElementById('userId').value = employee.user_id;

    document.getElementById('fullName').value = employee.full_name || '';
    document.getElementById('email').value = employee.email || '';
    document.getElementById('phone').value = employee.phone || '';
    document.getElementById('password').value = '';

    document.getElementById('designation').value = employee.designation || '';
    document.getElementById('specialization').value = employee.specialization || '';
    document.getElementById('joiningDate').value = employee.joining_date || '';
    document.getElementById('salary').value = employee.salary || '';

    document.getElementById('availability').value =
        employee.availability_status || 'available';

    document.getElementById('accountStatus').value =
        employee.status || 'active';

    document.getElementById('bio').value = employee.bio || '';
    document.getElementById('profileImage').value = '';

    document.getElementById('password').required = false;
    document.getElementById('passwordNote').textContent = '(Optional)';
    document.getElementById('passwordHelp').textContent =
        'Leave blank to keep the current password.';

    const preview = document.getElementById('currentImagePreview');

    if (employee.profile_image) {
        preview.innerHTML =
            '<img class="image-preview" src="../' +
            encodeURI(employee.profile_image) +
            '" alt="Current profile image">';
    } else {
        preview.innerHTML = '';
    }

    employeeModal.style.display = 'block';
}


function closeModal() {
    employeeModal.style.display = 'none';
}

window.addEventListener('click', function(event) {
    if (event.target === employeeModal) {
        closeModal();
    }
});
</script>

</body>
</html>

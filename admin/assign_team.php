
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';

$pageTitle = "Assign Team";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$bookingId = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);

$error = '';
$success = '';

function teamEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================================
   Get Booking Information
========================================= */

$bookingStmt = $conn->prepare("
    SELECT
        booking_id,
        package_name_snapshot,
        event_date,
        start_time,
        end_time,
        status,
        event_location
    FROM bookings
    WHERE booking_id = ?
");

$bookingStmt->bind_param("i", $bookingId);
$bookingStmt->execute();

$booking = $bookingStmt->get_result()->fetch_assoc();
$bookingStmt->close();

if (!$booking) {
    die("Booking not found.");
}

/* =========================================
   Handle Team Assignment
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $token)) {

        $error = "Invalid request. Please try again.";

    } elseif ($booking['status'] !== 'confirmed') {

        $error = "Team can only be assigned to confirmed bookings.";

    } elseif (isset($_POST['assign_team'])) {

        $selectedEmployees = $_POST['employee_ids'] ?? [];

        if (!is_array($selectedEmployees) || empty($selectedEmployees)) {

            $error = "Please select at least one employee.";

        } else {

            $conn->begin_transaction();

            try {

                $assignedCount = 0;

                foreach ($selectedEmployees as $roleId => $employeeIds) {

                    $roleId = (int)$roleId;

                    if (!is_array($employeeIds)) {
                        continue;
                    }

                    /* Get Role */

                    $roleStmt = $conn->prepare("
                        SELECT role_name
                        FROM team_roles
                        WHERE role_id = ?
                    ");

                    $roleStmt->bind_param("i", $roleId);
                    $roleStmt->execute();

                    $role = $roleStmt->get_result()->fetch_assoc();
                    $roleStmt->close();

                    if (!$role) {
                        throw new Exception("Invalid employee role selected.");
                    }

                    $roleName = $role['role_name'];

                    foreach ($employeeIds as $employeeId) {

                        $employeeId = (int)$employeeId;

                        if ($employeeId < 1) {
                            continue;
                        }

                        /* Verify Employee and Role */

                        $employeeStmt = $conn->prepare("
                            SELECT
                                ep.employee_id,
                                ep.designation,
                                ep.availability_status,
                                u.full_name,
                                u.status AS user_status
                            FROM employee_profiles ep
                            INNER JOIN users u
                                ON ep.user_id = u.user_id
                            WHERE ep.employee_id = ?
                              AND u.role = 'employee'
                            LIMIT 1
                        ");

                        $employeeStmt->bind_param("i", $employeeId);
                        $employeeStmt->execute();

                        $employee = $employeeStmt->get_result()->fetch_assoc();
                        $employeeStmt->close();

                        if (!$employee) {
                            throw new Exception("Selected employee was not found.");
                        }

                        if ($employee['designation'] !== $roleName) {
                            throw new Exception(
                                teamEscape($employee['full_name'])
                                . " does not belong to the "
                                . teamEscape($roleName)
                                . " role."
                            );
                        }

                        if ($employee['user_status'] !== 'active') {
                            throw new Exception(
                                teamEscape($employee['full_name'])
                                . " has an inactive account."
                            );
                        }

                        if ($employee['availability_status'] === 'unavailable') {
                            throw new Exception(
                                teamEscape($employee['full_name'])
                                . " is currently unavailable."
                            );
                        }

                        /* Check Duplicate Assignment */

                        $duplicateStmt = $conn->prepare("
                            SELECT booking_staff_id
                            FROM booking_staff
                            WHERE booking_id = ?
                              AND employee_id = ?
                            LIMIT 1
                        ");

                        $duplicateStmt->bind_param(
                            "ii",
                            $bookingId,
                            $employeeId
                        );

                        $duplicateStmt->execute();

                        $duplicate = $duplicateStmt->get_result()->fetch_assoc();
                        $duplicateStmt->close();

                        if ($duplicate) {
                            throw new Exception(
                                teamEscape($employee['full_name'])
                                . " is already assigned to this booking."
                            );
                        }

                        /* Check Schedule Conflict */

                        $conflictStmt = $conn->prepare("
                            SELECT
                                b.booking_id,
                                b.package_name_snapshot,
                                b.start_time,
                                b.end_time
                            FROM booking_staff bs
                            INNER JOIN bookings b
                                ON bs.booking_id = b.booking_id
                            WHERE bs.employee_id = ?
                              AND bs.booking_id != ?
                              AND b.event_date = ?
                              AND b.status IN ('confirmed', 'in_progress')
                              AND b.start_time < ?
                              AND b.end_time > ?
                            LIMIT 1
                        ");

                        $eventDate = $booking['event_date'];
                        $startTime = $booking['start_time'];
                        $endTime = $booking['end_time'];

                        $conflictStmt->bind_param(
                            "iisss",
                            $employeeId,
                            $bookingId,
                            $eventDate,
                            $endTime,
                            $startTime
                        );

                        $conflictStmt->execute();

                        $conflict = $conflictStmt->get_result()->fetch_assoc();
                        $conflictStmt->close();

                        if ($conflict) {
                            throw new Exception(
                                teamEscape($employee['full_name'])
                                . " already has another booking (#"
                                . (int)$conflict['booking_id']
                                . ") during this event time."
                            );
                        }

                        /* Save Assignment */

                        $insertStmt = $conn->prepare("
                            INSERT INTO booking_staff
                                (booking_id, employee_id, assignment_role)
                            VALUES (?, ?, ?)
                        ");

                        $insertStmt->bind_param(
                            "iis",
                            $bookingId,
                            $employeeId,
                            $roleName
                        );

                        if (!$insertStmt->execute()) {
                            throw new Exception("Unable to assign employee.");
                        }

                        $insertStmt->close();

                        $assignedCount++;
                    }
                }

                if ($assignedCount === 0) {
                    throw new Exception("Please select at least one employee.");
                }

                $conn->commit();

                header(
                    "Location: assign_team.php?id="
                    . $bookingId
                    . "&assigned=1"
                );
                exit;

            } catch (Exception $e) {

                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* Remove Assigned Employee */

    elseif (isset($_POST['remove_assignment'])) {

        $assignmentId = (int)($_POST['booking_staff_id'] ?? 0);

        if ($assignmentId < 1) {

            $error = "Invalid assignment.";

        } else {

            $removeStmt = $conn->prepare("
                DELETE FROM booking_staff
                WHERE booking_staff_id = ?
                  AND booking_id = ?
            ");

            $removeStmt->bind_param(
                "ii",
                $assignmentId,
                $bookingId
            );

            if ($removeStmt->execute() && $removeStmt->affected_rows > 0) {

                header(
                    "Location: assign_team.php?id="
                    . $bookingId
                    . "&removed=1"
                );
                exit;

            } else {

                $error = "Unable to remove team member.";
            }

            $removeStmt->close();
        }
    }
}

/* =========================================
   Success Messages
========================================= */

if (isset($_GET['assigned'])) {
    $success = "Team members assigned successfully.";
}

if (isset($_GET['removed'])) {
    $success = "Team member removed successfully.";
}

/* =========================================
   Get Team Roles
========================================= */

$roles = [];

$roleResult = $conn->query("
    SELECT role_id, role_name
    FROM team_roles
    ORDER BY role_id ASC
");

if ($roleResult) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row;
    }
}

/* =========================================
   Get Employees
   Exclude employees already assigned
   to overlapping bookings
========================================= */

$employeesByRole = [];

$employeeStmt = $conn->prepare("
    SELECT
        ep.employee_id,
        ep.designation,
        ep.specialization,
        ep.availability_status,
        u.full_name,
        u.email,
        u.status AS user_status
    FROM employee_profiles ep
    INNER JOIN users u
        ON ep.user_id = u.user_id
    WHERE u.role = 'employee'
      AND u.status = 'active'
      AND ep.availability_status != 'unavailable'

      AND NOT EXISTS (
          SELECT 1
          FROM booking_staff bs
          INNER JOIN bookings b
              ON bs.booking_id = b.booking_id
          WHERE bs.employee_id = ep.employee_id
            AND bs.booking_id != ?
            AND b.event_date = ?
            AND b.status IN ('confirmed', 'in_progress')
            AND b.start_time < ?
            AND b.end_time > ?
      )

    ORDER BY u.full_name ASC
");

$employeeStmt->bind_param(
    "isss",
    $bookingId,
    $booking['event_date'],
    $booking['end_time'],
    $booking['start_time']
);

$employeeStmt->execute();

$employeeResult = $employeeStmt->get_result();

while ($row = $employeeResult->fetch_assoc()) {

    $designation = $row['designation'];

    if (!isset($employeesByRole[$designation])) {
        $employeesByRole[$designation] = [];
    }

    $employeesByRole[$designation][] = $row;
}

$employeeStmt->close();

/* =========================================
   Get Already Assigned Team
========================================= */

$assignedTeam = [];

$assignedStmt = $conn->prepare("
    SELECT
        bs.booking_staff_id,
        bs.employee_id,
        bs.assignment_role,
        bs.assigned_at,
        u.full_name,
        u.email,
        ep.designation
    FROM booking_staff bs
    INNER JOIN employee_profiles ep
        ON bs.employee_id = ep.employee_id
    INNER JOIN users u
        ON ep.user_id = u.user_id
    WHERE bs.booking_id = ?
    ORDER BY bs.assigned_at ASC
");

$assignedStmt->bind_param("i", $bookingId);
$assignedStmt->execute();

$assignedResult = $assignedStmt->get_result();

while ($row = $assignedResult->fetch_assoc()) {
    $assignedTeam[] = $row;
}

$assignedStmt->close();

/* =========================================
   Get Already Assigned Employee IDs
========================================= */

$assignedEmployeeIds = [];

foreach ($assignedTeam as $member) {
    $assignedEmployeeIds[] = (int)$member['employee_id'];
}

/* =========================================
   Format Booking Information
========================================= */

$formattedDate = date('d M Y', strtotime($booking['event_date']));
$formattedStart = date('h:i A', strtotime($booking['start_time']));
$formattedEnd = date('h:i A', strtotime($booking['end_time']));
?>

<?php include_once 'includes/sidebar.php'; ?>

<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .team-page {
            padding: 25px;
        }

        .team-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .team-heading h2 {
            margin: 0 0 6px;
            font-size: 25px;
        }

        .team-heading p {
            margin: 0;
            color: #777;
        }

        .team-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #f1f3f5;
            color: #333;
            text-decoration: none;
            border-radius: 7px;
        }

        .team-back-btn:hover {
            background: #e2e6ea;
        }

        .team-alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .team-alert.success {
            background: #e8f5e9;
            color: #256b31;
        }

        .team-alert.error {
            background: #ffebee;
            color: #c62828;
        }

        .team-booking-card,
        .team-form-card,
        .team-list-card {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .team-booking-card h3,
        .team-form-card h3,
        .team-list-card h3 {
            margin: 0 0 20px;
            font-size: 18px;
        }

        .team-booking-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .team-info-item span {
            display: block;
            color: #888;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .team-info-item strong {
            font-size: 15px;
            color: #333;
        }

        .team-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .team-role-box {
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            padding: 17px;
            background: #fff;
        }

        .team-role-box label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .team-role-box label i {
            color: #3977d5;
            margin-right: 7px;
        }

        .team-role-box select {
            width: 100%;
            min-height: 105px;
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
        }

        .team-role-box select:focus {
            outline: none;
            border-color: #3977d5;
        }

        .team-help-text {
            display: block;
            margin-top: 8px;
            color: #888;
            font-size: 12px;
        }

        .team-notice {
            padding: 13px 15px;
            background: #fff8e1;
            color: #8a6500;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.7;
        }

        .team-submit-btn {
            margin-top: 22px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            background: #2869c7;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .team-submit-btn:hover {
            background: #1d55a5;
        }

        .team-submit-btn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }

        .team-table-wrapper {
            overflow-x: auto;
        }

        .team-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .team-table th,
        .team-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .team-table th {
            background: #f8f9fb;
            color: #555;
            font-weight: 600;
        }

        .team-employee-name {
            font-weight: 600;
            color: #333;
        }

        .team-employee-email {
            display: block;
            color: #888;
            font-size: 12px;
            margin-top: 4px;
        }

        .team-role-badge {
            display: inline-block;
            padding: 6px 10px;
            background: #eaf1ff;
            color: #285bb0;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .team-remove-btn {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 5px;
            background: #ffebee;
            color: #d32f2f;
            cursor: pointer;
        }

        .team-remove-btn:hover {
            background: #d32f2f;
            color: #fff;
        }

        .team-empty {
            text-align: center;
            padding: 35px 15px !important;
            color: #888;
        }

        .team-empty i {
            font-size: 30px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .team-page {
                padding: 15px;
            }

            .team-booking-info,
            .team-form-grid {
                grid-template-columns: 1fr;
            }

            .team-booking-card,
            .team-form-card,
            .team-list-card {
                padding: 18px;
            }
        }
    </style>
</head>

<div class="main-content">

    <?php include_once 'includes/header.php'; ?>

    <main class="dashboard-content team-page">

        <!-- Page Heading -->

        <div class="team-heading">

            <div>
                <h2>Assign Event Team</h2>
                <p>Assign employees according to their roles.</p>
            </div>

            <a href="bookings.php" class="team-back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Bookings
            </a>

        </div>

        <!-- Alerts -->

        <?php if ($success !== ''): ?>
            <div class="team-alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo teamEscape($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="team-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Booking Information -->

        <div class="team-booking-card">

            <h3>
                <i class="fas fa-calendar-check"></i>
                Booking Information
            </h3>

            <div class="team-booking-info">

                <div class="team-info-item">
                    <span>Booking ID</span>
                    <strong>#<?php echo (int)$booking['booking_id']; ?></strong>
                </div>

                <div class="team-info-item">
                    <span>Package</span>
                    <strong><?php echo teamEscape($booking['package_name_snapshot']); ?></strong>
                </div>

                <div class="team-info-item">
                    <span>Event Date</span>
                    <strong><?php echo teamEscape($formattedDate); ?></strong>
                </div>

                <div class="team-info-item">
                    <span>Event Time</span>
                    <strong>
                        <?php echo teamEscape($formattedStart . " - " . $formattedEnd); ?>
                    </strong>
                </div>

                <div class="team-info-item">
                    <span>Event Location</span>
                    <strong>
                        <?php echo teamEscape($booking['event_location'] ?: 'Not specified'); ?>
                    </strong>
                </div>

                <div class="team-info-item">
                    <span>Booking Status</span>
                    <strong>
                        <?php echo teamEscape(ucwords(str_replace('_', ' ', $booking['status']))); ?>
                    </strong>
                </div>

            </div>

        </div>

        <?php if ($booking['status'] === 'confirmed'): ?>

            <!-- Assign Employees By Role -->

            <div class="team-form-card">

                <h3>
                    <i class="fas fa-user-plus"></i>
                    Assign Employees by Role
                </h3>

                <div class="team-notice">
                    <i class="fas fa-info-circle"></i>
                    প্রতিটি Role-এর জন্য আলাদা কর্মী নির্বাচন করো।
                    একই Role থেকে একাধিক কর্মী নির্বাচন করতে Ctrl চেপে নামগুলোতে ক্লিক করো।
                    Inactive, unavailable এবং একই সময়ে অন্য booking-এ assigned কর্মীদের
                    নির্বাচন করা যাবে না।
                </div>

                <form method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo teamEscape($csrfToken); ?>">

                    <input type="hidden"
                           name="booking_id"
                           value="<?php echo (int)$bookingId; ?>">

                    <div class="team-form-grid">

                        <?php foreach ($roles as $role): ?>

                            <?php
                            $roleName = $role['role_name'];
                            $roleEmployees = $employeesByRole[$roleName] ?? [];
                            ?>

                            <div class="team-role-box">

                                <label for="role_<?php echo (int)$role['role_id']; ?>">
                                    <i class="fas fa-user-tie"></i>
                                    <?php echo teamEscape($roleName); ?>
                                </label>

                                <select
                                    name="employee_ids[<?php echo (int)$role['role_id']; ?>][]"
                                    id="role_<?php echo (int)$role['role_id']; ?>"
                                    multiple
                                    size="4"
                                >

                                    <?php if (!empty($roleEmployees)): ?>

                                        <?php foreach ($roleEmployees as $employee): ?>

                                            <?php
                                            $isAssigned = in_array(
                                                (int)$employee['employee_id'],
                                                $assignedEmployeeIds,
                                                true
                                            );

                                            $isUnavailable =
                                                $employee['user_status'] !== 'active'
                                                || $employee['availability_status'] === 'unavailable';
                                            ?>

                                            <option
                                                value="<?php echo (int)$employee['employee_id']; ?>"
                                                <?php echo ($isAssigned || $isUnavailable) ? 'disabled' : ''; ?>
                                            >
                                                <?php
                                                echo teamEscape($employee['full_name']);

                                                if ($isAssigned) {
                                                    echo ' (Already Assigned)';
                                                } elseif ($employee['user_status'] !== 'active') {
                                                    echo ' (Inactive)';
                                                } elseif ($employee['availability_status'] === 'unavailable') {
                                                    echo ' (Unavailable)';
                                                }
                                                ?>
                                            </option>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <option disabled>
                                            No employee available for this role
                                        </option>

                                    <?php endif; ?>

                                </select>

                                <small class="team-help-text">
                                    <?php echo count($roleEmployees); ?> employee(s) found
                                    for this role.
                                </small>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button
                        type="submit"
                        name="assign_team"
                        class="team-submit-btn"
                        <?php echo empty($roles) ? 'disabled' : ''; ?>
                    >
                        <i class="fas fa-user-plus"></i>
                        Assign Selected Employees
                    </button>

                </form>

            </div>

        <?php else: ?>

            <div class="team-alert error">
                Team assignment is available only for confirmed bookings.
            </div>

        <?php endif; ?>

        <!-- Assigned Team List -->

        <div class="team-list-card">

            <h3>
                <i class="fas fa-users"></i>
                Assigned Team Members
                (<?php echo count($assignedTeam); ?>)
            </h3>

            <div class="team-table-wrapper">

                <table class="team-table">

                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Designation</th>
                            <th>Assigned Role</th>
                            <th>Assigned Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($assignedTeam)): ?>

                        <?php foreach ($assignedTeam as $member): ?>

                            <tr>

                                <td>
                                    <span class="team-employee-name">
                                        <?php echo teamEscape($member['full_name']); ?>
                                    </span>

                                    <span class="team-employee-email">
                                        <?php echo teamEscape($member['email']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo teamEscape($member['designation'] ?: 'N/A'); ?>
                                </td>

                                <td>
                                    <span class="team-role-badge">
                                        <?php echo teamEscape($member['assignment_role']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        'd M Y',
                                        strtotime($member['assigned_at'])
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($booking['status'] === 'confirmed'): ?>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Remove this employee from the booking?');"
                                        >

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?php echo teamEscape($csrfToken); ?>">

                                            <input type="hidden"
                                                   name="booking_id"
                                                   value="<?php echo (int)$bookingId; ?>">

                                            <input type="hidden"
                                                   name="booking_staff_id"
                                                   value="<?php echo (int)$member['booking_staff_id']; ?>">

                                            <button
                                                type="submit"
                                                name="remove_assignment"
                                                class="team-remove-btn"
                                                title="Remove Employee"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span>—</span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="team-empty">
                                <i class="fas fa-users"></i>
                                <p>No team members assigned yet.</p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <?php include_once 'includes/footer.php'; ?>

</div>

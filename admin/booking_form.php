<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../db.php';
$pageTitle = "Manage Booking";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = $bookingId && $bookingId > 0;

$allowedStatuses = [
    'pending',
    'quoted',
    'confirmed',
    'in_progress',
    'completed',
    'cancelled'
];

$error = '';

$booking = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'package_id' => '',
    'venue_id' => '',
    'package_name_snapshot' => '',
    'package_price_snapshot' => '',
    'package_description_snapshot' => '',
    'event_date' => '',
    'start_time' => '',
    'end_time' => '',
    'guest_count' => 1,
    'quoted_total' => '',
    'event_location' => '',
    'special_request' => '',
    'status' => 'pending'
];

// ========================================
// GET EXISTING BOOKING FOR EDIT
// ========================================

if ($isEdit) {

    $stmt = $conn->prepare("
        SELECT
            b.*,
            c.user_id,
            c.address,
            u.full_name,
            u.email,
            u.phone
        FROM bookings b
        LEFT JOIN customers c
            ON b.customer_id = c.customer_id
        LEFT JOIN users u
            ON c.user_id = u.user_id
        WHERE b.booking_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $existingBooking = $stmt->get_result()->fetch_assoc();

    if (!$existingBooking) {
        die("Booking not found.");
    }

    $booking = array_merge($booking, $existingBooking);
}

// ========================================
// HANDLE FORM SUBMISSION
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF verification
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        die("Invalid request. Please try again.");
    }

    // Customer information
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    // Package information
    $packageId = (int)($_POST['package_id'] ?? 0);
    $venueId = (int)($_POST['venue_id'] ?? 0);

    $customPackageName = trim($_POST['package_name_snapshot'] ?? '');
    $customPackagePrice = (float)($_POST['package_price_snapshot'] ?? 0);
    $customPackageDescription = trim($_POST['package_description_snapshot'] ?? '');

    // Event information
    $eventDate = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    $guestCount = (int)($_POST['guest_count'] ?? 1);

    $quotedTotalInput = trim($_POST['quoted_total'] ?? '');

    $quotedTotal = $quotedTotalInput !== ''
        ? (float)$quotedTotalInput
        : null;

    $eventLocation = trim($_POST['event_location'] ?? '');
    $specialRequest = trim($_POST['special_request'] ?? '');

    $status = $_POST['status'] ?? 'pending';

    // ========================================
    // VALIDATION
    // ========================================

    if (
        $fullName === '' ||
        $email === '' ||
        $phone === '' ||
        $eventDate === ''
    ) {
        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif ($guestCount < 1) {
        $error = "Guest count must be at least 1.";

    } elseif ($customPackagePrice < 0 || ($quotedTotal !== null && $quotedTotal < 0)) {
        $error = "Price cannot be negative.";

    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid booking status.";

    } elseif (!$isEdit && strlen($password) < 8) {
        $error = "Customer password must be at least 8 characters.";

    } elseif ($isEdit && $password !== '' && strlen($password) < 8) {
        $error = "New password must be at least 8 characters.";
    }

    // ========================================
    // GET SELECTED PACKAGE INFORMATION
    // ========================================

    $packageName = $customPackageName;
    $packagePrice = $customPackagePrice;
    $packageDescription = $customPackageDescription;

    if ($error === '' && $packageId > 0) {

        $packageStmt = $conn->prepare("
            SELECT
                package_name,
                base_price,
                description
            FROM packages
            WHERE package_id = ?
            LIMIT 1
        ");

        $packageStmt->bind_param("i", $packageId);
        $packageStmt->execute();

        $selectedPackage = $packageStmt->get_result()->fetch_assoc();

        if (!$selectedPackage) {
            $error = "Selected package was not found.";
        } else {
            $packageName = $selectedPackage['package_name'];
            $packagePrice = (float)$selectedPackage['base_price'];
            $packageDescription = $selectedPackage['description'] ?? '';
        }
    }

    if ($error === '' && $packageName === '') {
        $error = "Please select a package or enter a custom package name.";
    }

    // ========================================
    // SAVE BOOKING
    // ========================================

    if ($error === '') {

        try {

            $conn->begin_transaction();

            // --------------------------------
            // EDIT EXISTING CUSTOMER
            // --------------------------------

            if ($isEdit) {

                $userId = (int)$booking['user_id'];
                $customerId = (int)$booking['customer_id'];

                if ($userId < 1 || $customerId < 1) {
                    throw new Exception("Customer account was not found.");
                }

                // Check duplicate email
                $emailCheck = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE email = ?
                    AND user_id != ?
                    LIMIT 1
                ");

                $emailCheck->bind_param("si", $email, $userId);
                $emailCheck->execute();

                if ($emailCheck->get_result()->num_rows > 0) {
                    throw new Exception("This email address is already used by another account.");
                }

                // Update customer account
                if ($password !== '') {

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $userUpdate = $conn->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            email = ?,
                            phone = ?,
                            password_hash = ?
                        WHERE user_id = ?
                    ");

                    $userUpdate->bind_param(
                        "ssssi",
                        $fullName,
                        $email,
                        $phone,
                        $passwordHash,
                        $userId
                    );

                } else {

                    $userUpdate = $conn->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            email = ?,
                            phone = ?
                        WHERE user_id = ?
                    ");

                    $userUpdate->bind_param(
                        "sssi",
                        $fullName,
                        $email,
                        $phone,
                        $userId
                    );
                }

                $userUpdate->execute();

                // Update customer address
                $customerUpdate = $conn->prepare("
                    UPDATE customers
                    SET address = ?
                    WHERE customer_id = ?
                ");

                $customerUpdate->bind_param(
                    "si",
                    $address,
                    $customerId
                );

                $customerUpdate->execute();

            }

            // --------------------------------
            // CREATE NEW CUSTOMER
            // --------------------------------

            else {

                // Check whether email already exists
                $userCheck = $conn->prepare("
                    SELECT user_id, role
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");

                $userCheck->bind_param("s", $email);
                $userCheck->execute();

                $existingUser = $userCheck->get_result()->fetch_assoc();

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($existingUser) {

                    if ($existingUser['role'] !== 'customer') {
                        throw new Exception(
                            "This email belongs to a non-customer account."
                        );
                    }

                    $userId = (int)$existingUser['user_id'];

                    // Update existing customer account
                    $userUpdate = $conn->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            phone = ?,
                            password_hash = ?
                        WHERE user_id = ?
                    ");

                    $userUpdate->bind_param(
                        "sssi",
                        $fullName,
                        $phone,
                        $passwordHash,
                        $userId
                    );

                    $userUpdate->execute();

                } else {

                    // Create new customer user account
                    $userInsert = $conn->prepare("
                        INSERT INTO users (
                            full_name,
                            email,
                            password_hash,
                            phone,
                            role,
                            status
                        )
                        VALUES (?, ?, ?, ?, 'customer', 'active')
                    ");

                    $userInsert->bind_param(
                        "ssss",
                        $fullName,
                        $email,
                        $passwordHash,
                        $phone
                    );

                    $userInsert->execute();

                    $userId = $conn->insert_id;
                }

                // Find existing customer profile
                $customerCheck = $conn->prepare("
                    SELECT customer_id
                    FROM customers
                    WHERE user_id = ?
                    LIMIT 1
                ");

                $customerCheck->bind_param("i", $userId);
                $customerCheck->execute();

                $existingCustomer = $customerCheck
                    ->get_result()
                    ->fetch_assoc();

                if ($existingCustomer) {

                    $customerId = (int)$existingCustomer['customer_id'];

                    $customerUpdate = $conn->prepare("
                        UPDATE customers
                        SET address = ?
                        WHERE customer_id = ?
                    ");

                    $customerUpdate->bind_param(
                        "si",
                        $address,
                        $customerId
                    );

                    $customerUpdate->execute();

                } else {

                    // Create customer profile
                    $customerInsert = $conn->prepare("
                        INSERT INTO customers (
                            user_id,
                            address
                        )
                        VALUES (?, ?)
                    ");

                    $customerInsert->bind_param(
                        "is",
                        $userId,
                        $address
                    );

                    $customerInsert->execute();

                    $customerId = $conn->insert_id;
                }
            }

            // --------------------------------
            // PREPARE BOOKING VALUES
            // --------------------------------

            $packageIdValue = $packageId > 0
                ? $packageId
                : null;

            $venueIdValue = $venueId > 0
                ? $venueId
                : null;

            $startTimeValue = $startTime !== ''
                ? $startTime
                : null;

            $endTimeValue = $endTime !== ''
                ? $endTime
                : null;

            // --------------------------------
            // UPDATE EXISTING BOOKING
            // --------------------------------

            if ($isEdit) {

                $updateSql = "
                    UPDATE bookings SET
                        package_id = ?,
                        venue_id = ?,
                        package_name_snapshot = ?,
                        package_price_snapshot = ?,
                        package_description_snapshot = ?,
                        event_date = ?,
                        start_time = ?,
                        end_time = ?,
                        guest_count = ?,
                        quoted_total = ?,
                        event_location = ?,
                        special_request = ?,
                        status = ?
                    WHERE booking_id = ?
                ";

                $updateStmt = $conn->prepare($updateSql);

                $updateStmt->bind_param(
                    "iisdssssidsssi",
                    $packageIdValue,
                    $venueIdValue,
                    $packageName,
                    $packagePrice,
                    $packageDescription,
                    $eventDate,
                    $startTimeValue,
                    $endTimeValue,
                    $guestCount,
                    $quotedTotal,
                    $eventLocation,
                    $specialRequest,
                    $status,
                    $bookingId
                );

                $updateStmt->execute();

            }

            // --------------------------------
            // INSERT NEW BOOKING
            // --------------------------------

            else {

                $insertSql = "
                    INSERT INTO bookings (
                        customer_id,
                        package_id,
                        venue_id,
                        package_name_snapshot,
                        package_price_snapshot,
                        package_description_snapshot,
                        event_date,
                        start_time,
                        end_time,
                        guest_count,
                        quoted_total,
                        event_location,
                        special_request,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";

                $insertStmt = $conn->prepare($insertSql);

                $insertStmt->bind_param(
                    "iiisdssssidsss",
                    $customerId,
                    $packageIdValue,
                    $venueIdValue,
                    $packageName,
                    $packagePrice,
                    $packageDescription,
                    $eventDate,
                    $startTimeValue,
                    $endTimeValue,
                    $guestCount,
                    $quotedTotal,
                    $eventLocation,
                    $specialRequest,
                    $status
                );

                $insertStmt->execute();

                $bookingId = $conn->insert_id;
            }

            // --------------------------------
            // COMMIT
            // --------------------------------

            $conn->commit();

            header(
                "Location: booking_details.php?id=" .
                $bookingId . "&saved=1"
            );

            exit;

        } catch (Throwable $e) {

            $conn->rollback();

            $error = "Unable to save booking: " . $e->getMessage();
        }
    }

    // Preserve submitted form values
    foreach ($_POST as $key => $value) {
        if (array_key_exists($key, $booking)) {
            $booking[$key] = $value;
        }
    }
}

// ========================================
// GET PACKAGES AND VENUES
// ========================================

$packages = $conn->query("
    SELECT
        package_id,
        package_name,
        base_price,
        description
    FROM packages
    ORDER BY package_name ASC
");

$venues = $conn->query("
    SELECT
        venue_id,
        name,
        address,
        capacity
    FROM venues
    ORDER BY name ASC
");

// ========================================
// SAFE OUTPUT FUNCTION
// ========================================

function bookingFormValue($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<?php include_once 'includes/sidebar.php'; ?>

<div class="main-content">

<?php include_once 'includes/header.php'; ?>

<main class="dashboard-content">

    <!-- Page Heading -->
    <div class="page-heading booking-heading">

        <div>
            <h2>
                <?php echo $isEdit ? 'Edit Booking' : 'Add New Booking'; ?>
            </h2>

            <p>
                <?php echo $isEdit
                    ? 'Update customer and event information.'
                    : 'Create a new customer booking.'; ?>
            </p>
        </div>

        <a href="<?php echo $isEdit
            ? 'booking_details.php?id=' . (int)$bookingId
            : 'bookings.php'; ?>"
           class="booking-reset-btn">

            <i class="fas fa-arrow-left"></i> Back

        </a>

    </div>

    <!-- Error Message -->
    <?php if ($error !== ''): ?>

        <div class="details-alert error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo bookingFormValue($error); ?>
        </div>

    <?php endif; ?>

    <!-- Booking Form -->
    <form method="POST" class="booking-edit-form">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo bookingFormValue($csrfToken); ?>">

        <!-- CUSTOMER INFORMATION -->
        <section class="details-card">

            <div class="details-card-heading">
                <h3>
                    <i class="fas fa-user"></i>
                    Customer Information
                </h3>
            </div>

            <div class="booking-form-grid">

                <div class="booking-form-group">

                    <label>Customer Name *</label>

                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?php echo bookingFormValue($booking['full_name']); ?>"
                        placeholder="Enter customer name"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Email Address *</label>

                    <input
                        type="email"
                        name="email"
                        required
                        value="<?php echo bookingFormValue($booking['email']); ?>"
                        placeholder="Enter email address"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Phone Number *</label>

                    <input
                        type="text"
                        name="phone"
                        required
                        value="<?php echo bookingFormValue($booking['phone']); ?>"
                        placeholder="Enter phone number"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Customer Address</label>

                    <input
                        type="text"
                        name="address"
                        value="<?php echo bookingFormValue($booking['address']); ?>"
                        placeholder="Enter customer address"
                    >

                </div>

                <div class="booking-form-group full-width">

                    <label>
                        <?php echo $isEdit
                            ? 'New Password (Optional)'
                            : 'Account Password *'; ?>
                    </label>

                    <input
                        type="password"
                        name="password"
                        <?php echo $isEdit ? '' : 'required'; ?>
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="<?php echo $isEdit
                            ? 'Leave blank to keep current password'
                            : 'Enter customer password (minimum 8 characters)'; ?>"
                    >

                    <?php if ($isEdit): ?>
                        <small>Leave blank if you do not want to change the existing password.</small>
                    <?php endif; ?>

                </div>

            </div>

        </section>

        <!-- PACKAGE INFORMATION -->
        <section class="details-card">

            <div class="details-card-heading">
                <h3>
                    <i class="fas fa-box-open"></i>
                    Package Information
                </h3>
            </div>

            <div class="booking-form-grid">

                <div class="booking-form-group">

                    <label>Select Package</label>

                    <select name="package_id" id="package_id">

                        <option value="0">Custom / No Package</option>

                        <?php if ($packages): ?>

                            <?php while ($package = $packages->fetch_assoc()): ?>

                                <option
                                    value="<?php echo (int)$package['package_id']; ?>"
                                    data-name="<?php echo bookingFormValue($package['package_name']); ?>"
                                    data-price="<?php echo (float)$package['base_price']; ?>"
                                    data-description="<?php echo bookingFormValue($package['description']); ?>"
                                    <?php echo (int)$booking['package_id'] === (int)$package['package_id']
                                        ? 'selected'
                                        : ''; ?>
                                >

                                    <?php echo bookingFormValue($package['package_name']); ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="booking-form-group">

                    <label>Package Name *</label>

                    <input
                        type="text"
                        name="package_name_snapshot"
                        id="package_name_snapshot"
                        required
                        value="<?php echo bookingFormValue($booking['package_name_snapshot']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Package Price (৳) *</label>

                    <input
                        type="number"
                        name="package_price_snapshot"
                        id="package_price_snapshot"
                        min="0"
                        step="0.01"
                        required
                        value="<?php echo bookingFormValue($booking['package_price_snapshot']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Quoted Total (৳)</label>

                    <input
                        type="number"
                        name="quoted_total"
                        min="0"
                        step="0.01"
                        value="<?php echo bookingFormValue($booking['quoted_total']); ?>"
                        placeholder="Enter quoted total"
                    >

                </div>

                <div class="booking-form-group full-width">

                    <label>Package Description</label>

                    <textarea
                        name="package_description_snapshot"
                        id="package_description_snapshot"
                        rows="3"
                    ><?php echo bookingFormValue($booking['package_description_snapshot']); ?></textarea>

                </div>

            </div>

        </section>

        <!-- EVENT INFORMATION -->
        <section class="details-card">

            <div class="details-card-heading">
                <h3>
                    <i class="fas fa-calendar-alt"></i>
                    Event Information
                </h3>
            </div>

            <div class="booking-form-grid">

                <div class="booking-form-group">

                    <label>Event Date *</label>

                    <input
                        type="date"
                        name="event_date"
                        required
                        value="<?php echo bookingFormValue($booking['event_date']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Start Time</label>

                    <input
                        type="time"
                        name="start_time"
                        value="<?php echo bookingFormValue($booking['start_time']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>End Time</label>

                    <input
                        type="time"
                        name="end_time"
                        value="<?php echo bookingFormValue($booking['end_time']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Guest Count *</label>

                    <input
                        type="number"
                        name="guest_count"
                        min="1"
                        required
                        value="<?php echo bookingFormValue($booking['guest_count']); ?>"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Venue</label>

                    <select name="venue_id">

                        <option value="0">Not assigned</option>

                        <?php if ($venues): ?>

                            <?php while ($venue = $venues->fetch_assoc()): ?>

                                <option
                                    value="<?php echo (int)$venue['venue_id']; ?>"
                                    <?php echo (int)$booking['venue_id'] === (int)$venue['venue_id']
                                        ? 'selected'
                                        : ''; ?>
                                >

                                    <?php echo bookingFormValue($venue['name']); ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="booking-form-group">

                    <label>Event Location</label>

                    <input
                        type="text"
                        name="event_location"
                        value="<?php echo bookingFormValue($booking['event_location']); ?>"
                        placeholder="Enter event location"
                    >

                </div>

                <div class="booking-form-group">

                    <label>Booking Status</label>

                    <select name="status">

                        <?php foreach ($allowedStatuses as $item): ?>

                            <option
                                value="<?php echo $item; ?>"
                                <?php echo $booking['status'] === $item
                                    ? 'selected'
                                    : ''; ?>
                            >

                                <?php echo ucwords(str_replace('_', ' ', $item)); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="booking-form-group full-width">

                    <label>Special Request</label>

                    <textarea
                        name="special_request"
                        rows="4"
                        placeholder="Enter customer's special requirements..."
                    ><?php echo bookingFormValue($booking['special_request']); ?></textarea>

                </div>

            </div>

        </section>

        <!-- FORM ACTIONS -->
        <div class="booking-form-actions">

            <a
                href="<?php echo $isEdit
                    ? 'booking_details.php?id=' . (int)$bookingId
                    : 'bookings.php'; ?>"
                class="booking-cancel-btn"
            >
                Cancel
            </a>

            <button type="submit" class="booking-save-btn">

                <i class="fas fa-save"></i>

                <?php echo $isEdit
                    ? 'Update Booking'
                    : 'Save Booking'; ?>

            </button>

        </div>

    </form>

</main>

<?php include_once 'includes/footer.php'; ?>

</div>

<!-- PACKAGE AUTO-FILL -->
<script>

document.addEventListener('DOMContentLoaded', function () {

    const packageSelect = document.getElementById('package_id');

    const packageName = document.getElementById('package_name_snapshot');

    const packagePrice = document.getElementById('package_price_snapshot');

    const packageDescription = document.getElementById('package_description_snapshot');

    packageSelect.addEventListener('change', function () {

        const selectedOption = this.options[this.selectedIndex];

        if (this.value !== '0') {

            packageName.value = selectedOption.dataset.name || '';

            packagePrice.value = selectedOption.dataset.price || '';

            packageDescription.value = selectedOption.dataset.description || '';

        }

    });

});

</script>

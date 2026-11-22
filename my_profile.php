
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Customer Login Check
if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'customer'
) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$successMessage = '';
$errorMessage = '';

// Fetch Customer Profile
$stmt = $conn->prepare("
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        u.created_at,
        c.customer_id,
        c.address
    FROM users u
    LEFT JOIN customers c ON c.user_id = u.user_id
    WHERE u.user_id = ?
      AND u.role = 'customer'
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    die("Customer profile not found.");
}

if (empty($profile['customer_id'])) {
    die("Customer account is not properly configured.");
}

$customerId = (int) $profile['customer_id'];

// Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $token = $_POST['csrf_token'] ?? '';

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!hash_equals($csrfToken, $token)) {
        $errorMessage = "Invalid request. Please refresh the page and try again.";

    } elseif ($fullName === '' || $email === '') {
        $errorMessage = "Name and Email are required.";

    } elseif (mb_strlen($fullName) > 150) {
        $errorMessage = "Name cannot exceed 150 characters.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";

    } elseif (mb_strlen($email) > 191) {
        $errorMessage = "Email address is too long.";

    } elseif (mb_strlen($phone) > 30) {
        $errorMessage = "Phone number cannot exceed 30 characters.";

    } else {

        // Check duplicate email
        $emailStmt = $conn->prepare("
            SELECT user_id
            FROM users
            WHERE email = ?
              AND user_id != ?
            LIMIT 1
        ");

        $emailStmt->bind_param("si", $email, $userId);
        $emailStmt->execute();

        $emailResult = $emailStmt->get_result();
        $emailExists = $emailResult->num_rows > 0;

        $emailStmt->close();

        if ($emailExists) {
            $errorMessage = "This email address is already registered.";

        } else {

            try {
                $conn->begin_transaction();

                // Update users table
                $updateUser = $conn->prepare("
                    UPDATE users
                    SET full_name = ?,
                        email = ?,
                        phone = ?
                    WHERE user_id = ?
                      AND role = 'customer'
                ");

                $updateUser->bind_param(
                    "sssi",
                    $fullName,
                    $email,
                    $phone,
                    $userId
                );

                if (!$updateUser->execute()) {
                    throw new Exception("Unable to update account information.");
                }

                $updateUser->close();

                // Update customers table
                $updateCustomer = $conn->prepare("
                    UPDATE customers
                    SET address = ?
                    WHERE customer_id = ?
                      AND user_id = ?
                ");

                $updateCustomer->bind_param(
                    "sii",
                    $address,
                    $customerId,
                    $userId
                );

                if (!$updateCustomer->execute()) {
                    throw new Exception("Unable to update customer address.");
                }

                $updateCustomer->close();

                $conn->commit();

                // Update session name if used by the navbar
                $_SESSION['full_name'] = $fullName;

                $successMessage = "Your profile has been updated successfully.";

                // Refresh profile data
                $profile['full_name'] = $fullName;
                $profile['email'] = $email;
                $profile['phone'] = $phone;
                $profile['address'] = $address;

            } catch (Throwable $e) {

                $conn->rollback();

                $errorMessage = "Profile update failed. Please try again.";

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Profile | DecorEase</title>

    <?php include_once __DIR__ . '/includes/nav_style.php'; ?>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
    margin: 0;
    padding: 0;
    background: #f7f7f9;
    color: #333;
    font-family: Arial, sans-serif;
}

        .profile-page {
            max-width: 1050px;
            width: 92%;
            margin: 20px auto 70px;
        }

        .profile-heading {
            margin-bottom: 28px;
        }

        .profile-heading h1 {
            font-size: 30px;
            margin-bottom: 8px;
            color: #292929;
        }

        .profile-heading p {
            color: #777;
            font-size: 15px;
        }

        /* Profile Layout */

        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            align-items: start;
        }

        .profile-sidebar-card,
        .profile-form-card {
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 3px 18px rgba(0,0,0,0.05);
        }

        /* Profile Sidebar */

        .profile-sidebar-card {
            padding: 30px 22px;
            text-align: center;
        }

        .profile-avatar {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: #f5e9df;
            color: #a66b3f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 17px;
        }

        .profile-sidebar-card h3 {
            font-size: 19px;
            margin-bottom: 7px;
            overflow-wrap: anywhere;
        }

        .profile-sidebar-card p {
            color: #888;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .profile-role {
            display: inline-block;
            margin-top: 15px;
            background: #f8eee7;
            color: #925b35;
            padding: 7px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .profile-joined {
            border-top: 1px solid #eee;
            margin-top: 25px;
            padding-top: 20px;
            text-align: left;
        }

        .profile-joined span {
            display: block;
            font-size: 12px;
            color: #888;
            margin-bottom: 7px;
        }

        .profile-joined strong {
            font-size: 14px;
        }

        /* Form Card */

        .profile-form-card {
            padding: 30px;
        }

        .profile-form-heading {
            border-bottom: 1px solid #eee;
            padding-bottom: 18px;
            margin-bottom: 25px;
        }

        .profile-form-heading h2 {
            font-size: 21px;
            margin-bottom: 7px;
        }

        .profile-form-heading p {
            font-size: 13px;
            color: #888;
        }

        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 21px;
        }

        .profile-field {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .profile-field.full-width {
            grid-column: 1 / -1;
        }

        .profile-field label {
            font-size: 13px;
            font-weight: 600;
            color: #444;
        }

        .profile-field input,
        .profile-field textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
            transition: 0.2s;
            background: #fff;
        }

        .profile-field input:focus,
        .profile-field textarea:focus {
            border-color: #a66b3f;
            box-shadow: 0 0 0 3px rgba(166,107,63,0.08);
        }

        .profile-field textarea {
            min-height: 105px;
            resize: vertical;
        }

        .profile-field input[readonly] {
            background: #f5f5f5;
            color: #777;
            cursor: not-allowed;
        }

        .profile-field small {
            font-size: 11px;
            color: #999;
        }

        /* Buttons */

        .profile-form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 5px;
        }

        .profile-save-btn {
            border: none;
            background: #a66b3f;
            color: #fff;
            padding: 13px 23px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .profile-save-btn:hover {
            background: #87532f;
        }

        .profile-save-btn i {
            margin-right: 7px;
        }

        .profile-reset-btn {
            display: inline-flex;
            align-items: center;
            padding: 13px 20px;
            background: #f0f0f0;
            color: #555;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
        }

        .profile-reset-btn:hover {
            background: #e5e5e5;
        }

        /* Messages */

        .profile-alert {
            padding: 14px 17px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .profile-alert.success {
            background: #e6f6ec;
            color: #237b43;
        }

        .profile-alert.error {
            background: #ffeaea;
            color: #b52f2f;
        }

        .profile-alert i {
            margin-right: 8px;
        }

        @media (max-width: 800px) {

            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar-card {
                padding: 25px;
            }

            .profile-joined {
                text-align: center;
            }

        }

        @media (max-width: 550px) {

            .profile-form {
                grid-template-columns: 1fr;
            }

            .profile-field.full-width,
            .profile-form-actions {
                grid-column: 1;
            }

            .profile-form-card {
                padding: 20px;
            }

            .profile-form-actions {
                flex-direction: column;
            }

            .profile-save-btn,
            .profile-reset-btn {
                justify-content: center;
                width: 100%;
            }

        }

    </style>

</head>

<body>

<?php include_once __DIR__ . '/includes/navber.php'; ?>

<main class="profile-page">

    <div class="profile-heading">

        <h1>My Profile</h1>

        <p>Manage your personal information and account details.</p>

    </div>

    <?php if ($successMessage !== ''): ?>

        <div class="profile-alert success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div class="profile-alert error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>

    <?php endif; ?>

    <div class="profile-layout">

        <!-- Profile Summary -->
        <aside class="profile-sidebar-card">

            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>

            <h3>
                <?php echo htmlspecialchars($profile['full_name']); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($profile['email']); ?>
            </p>

            <span class="profile-role">
                <i class="fas fa-user-check"></i>
                Customer
            </span>

            <div class="profile-joined">

                <span>Member Since</span>

                <strong>
                    <?php
                    echo date(
                        'd F Y',
                        strtotime($profile['created_at'])
                    );
                    ?>
                </strong>

            </div>

        </aside>

        <!-- Edit Profile Form -->
        <section class="profile-form-card">

            <div class="profile-form-heading">

                <h2>Personal Information</h2>

                <p>Update your contact information and address below.</p>

            </div>

            <form method="POST" class="profile-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($csrfToken); ?>"
                >

                <div class="profile-field">

                    <label for="full_name">Full Name *</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo htmlspecialchars($profile['full_name']); ?>"
                        maxlength="150"
                        required
                    >

                </div>

                <div class="profile-field">

                    <label for="email">Email Address *</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($profile['email']); ?>"
                        maxlength="191"
                        required
                    >

                </div>

                <div class="profile-field">

                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"
                        maxlength="30"
                        placeholder="Enter your phone number"
                    >

                </div>

                <div class="profile-field">

                    <label for="user_id">Customer ID</label>

                    <input
                        type="text"
                        id="user_id"
                        value="<?php echo (int)$profile['user_id']; ?>"
                        readonly
                    >

                    <small>Customer ID cannot be changed.</small>

                </div>

                <div class="profile-field full-width">

                    <label for="address">Address</label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your full address"
                    ><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>

                </div>

                <div class="profile-form-actions">

                    <a href="my_profile.php" class="profile-reset-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="update_profile"
                        class="profile-save-btn"
                    >
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </section>

    </div>

</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>

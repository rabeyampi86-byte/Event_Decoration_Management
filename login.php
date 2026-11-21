
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Already logged-in user redirect
if (isset($_SESSION['user_id'], $_SESSION['role'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit;
    }

    if ($_SESSION['role'] === 'customer') {
        header("Location: index.php");
        exit;
    }

    if ($_SESSION['role'] === 'employee') {
        header("Location: employee/employee_dashboard.php");
        exit;
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $sql = "
            SELECT
                u.user_id,
                u.full_name,
                u.email,
                u.password_hash,
                u.phone,
                u.role,
                u.status,
                c.customer_id,
                ep.employee_id

            FROM users u

            LEFT JOIN customers c
                ON u.user_id = c.user_id

            LEFT JOIN employee_profiles ep
                ON u.user_id = ep.user_id

            WHERE u.email = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (
                    password_verify($password, $user['password_hash']) &&
                    strtolower($user['status']) === 'active'
                ) {

                    $role = strtolower($user['role']);

                    if (in_array($role, ['admin', 'customer', 'employee'], true)) {

                        session_regenerate_id(true);

                        // Common user session
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_phone'] = $user['phone'];
                        $_SESSION['role'] = $role;

                        // Admin session compatibility
                        if ($role === 'admin') {

                            $_SESSION['admin_id'] = $user['user_id'];
                            $_SESSION['adminName'] = $user['full_name'];
                            $_SESSION['adminEmail'] = $user['email'];

                            header("Location: admin/admin_dashboard.php");
                            exit;
                        }

                        // Customer redirect
                        if ($role === 'customer') {

                            $_SESSION['customer_id'] = $user['customer_id'];

                            header("Location: index.php");
                            exit;
                        }

                        // Employee redirect
                        if ($role === 'employee') {

                            $_SESSION['employee_id'] = $user['employee_id'];

                            header("Location: employee/employee_dashboard.php");
                            exit;
                        }

                    } else {
                        $error = "Your account has an invalid role.";
                    }

                } else {
                    $error = "Invalid email or password, or your account is inactive.";
                }

            } else {
                $error = "Invalid email or password.";
            }

            $stmt->close();

        } else {
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | DecorEase</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 45px 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-logo i {
            font-size: 42px;
            color: #c58b45;
            margin-bottom: 12px;
        }

        .login-logo h1 {
            font-size: 27px;
            color: #263238;
            margin-bottom: 8px;
        }

        .login-logo p {
            font-size: 14px;
            color: #777;
        }

        .login-heading {
            margin-bottom: 25px;
        }

        .login-heading h2 {
            font-size: 23px;
            color: #263238;
            margin-bottom: 7px;
        }

        .login-heading p {
            color: #777;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-wrapper input {
            width: 100%;
            height: 48px;
            padding: 0 43px;
            border: 1px solid #ddd;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .input-wrapper input:focus {
            border-color: #c58b45;
            box-shadow: 0 0 0 3px rgba(197, 139, 69, 0.12);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #888;
            cursor: pointer;
            font-size: 15px;
        }

        .login-button {
            width: 100%;
            height: 49px;
            border: none;
            border-radius: 7px;
            background: #c58b45;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-button:hover {
            background: #a97132;
        }

        .login-error {
            background: #fff0f0;
            color: #c62828;
            border: 1px solid #ffcaca;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .register-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #b77b35;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 22px;
        }

        .back-home a {
            color: #777;
            font-size: 13px;
            text-decoration: none;
        }

        .back-home a:hover {
            color: #b77b35;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 22px;
            }
        }
    </style>
</head>

<body>

<?php
// Existing frontend navbar
include 'includes/navber.php';
?>

<div class="login-wrapper">

    <div class="login-container">

        <div class="login-logo">
            <i class="fas fa-gem"></i>
            <h1>DecorEase</h1>
            <p>Event Decoration Management System</p>
        </div>

        <div class="login-heading">
            <h2>Welcome Back!</h2>
            <p>Please login to your account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="login-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="on">

            <div class="form-group">
                <label for="email">Email Address</label>

                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-label="Show password"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-button">
                Login <i class="fas fa-sign-in-alt"></i>
            </button>

        </form>

        <div class="register-link">
            Don't have an account?
            <a href="registration.php">Create Account</a>
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>

    </div>

</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {

        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';

        this.innerHTML = isPassword
            ? '<i class="fas fa-eye-slash"></i>'
            : '<i class="fas fa-eye"></i>';

        this.setAttribute(
            'aria-label',
            isPassword ? 'Hide password' : 'Show password'
        );
    });
</script>

</body>
</html>

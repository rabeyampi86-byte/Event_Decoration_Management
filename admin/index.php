
<?php
session_start();
include_once '../db.php';

// Already logged in admin
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {

        $stmt = $conn->prepare("
            SELECT user_id, full_name, email, password_hash, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            if (
                $admin['role'] === 'admin' &&
                $admin['status'] === 'active' &&
                password_verify($password, $admin['password_hash'])
            ) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['user_id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: admin_dashboard.php");
                exit;

            } else {
                $error = "Invalid email or password.";
            }

        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | DecorEase</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f7fb;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2563eb;
            color: #ffffff;
            border-radius: 12px;
            font-size: 25px;
        }

        .brand h1 {
            font-size: 27px;
            color: #1e293b;
            margin-bottom: 7px;
        }

        .brand p {
            color: #64748b;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
        }

        .input-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .input-box input {
            width: 100%;
            padding: 13px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
            color: #334155;
        }

        .input-box input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #1d4ed8;
        }

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 11px 13px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="brand">
        <div class="brand-icon">
            <i class="fas fa-calendar-check"></i>
        </div>

        <h1>DecorEase</h1>
        <p>Admin Panel Login</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="form-group">
            <label for="email">Email Address</label>

            <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your admin email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <div class="input-box">
                <i class="fas fa-lock"></i>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

                <i class="fas fa-eye toggle-password"
                   id="togglePassword"></i>
            </div>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt"></i>
            Login to Dashboard
        </button>

    </form>

    <div class="login-footer">
        &copy; <?php echo date("Y"); ?> DecorEase. All Rights Reserved.
    </div>

</div>

<script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");

    togglePassword.addEventListener("click", function () {
        const isPassword = passwordInput.type === "password";

        passwordInput.type = isPassword ? "text" : "password";

        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
    });
</script>

</body>
</html>

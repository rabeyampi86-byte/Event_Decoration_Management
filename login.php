
<?php
session_start();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . '/db.php';

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];

            $stmt->close();
            $conn->close();

            header("Location: index.php");
            exit;
        } else {
            $message = "Incorrect email or password.";
            $messageType = "error";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | DecorEase</title>

    <?php include __DIR__ . '/includes/nav_style.php'; ?>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff8f5;
            color: #333;
        }

        .login-container {
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 45px 20px;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .login-box h1 {
            text-align: center;
            color: #e76f51;
            margin: 0 0 10px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e76f51;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .remember-row input {
            accent-color: #e76f51;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #e76f51;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-btn:hover {
            background: #cf573b;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
            background: #fff0e9;
            color: #9b432d;
        }

        .register-link {
            text-align: center;
            margin-top: 22px;
        }

        .register-link a {
            color: #e76f51;
            text-decoration: none;
            font-weight: bold;
        }

        .login-footer {
            text-align: center;
            padding: 18px;
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 500px) {
            .login-box {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="login-container">
        <section class="login-box">

            <h1>Welcome Back!</h1>

            <p class="subtitle">
                Login to your DecorEase account.
            </p>

            <?php if ($message !== ""): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="remember-row">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                    >
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="login-btn">
                    Login
                </button>

            </form>

            <p class="register-link">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

        </section>
    </main>

    <footer class="login-footer">
        &copy; 2026 DecorEase. All rights reserved.
    </footer>

</body>
</html>



<?php
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once 'db.php';

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $message = "Please fill in all fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $messageType = "error";

    } else {
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "error";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $message = "Account created successfully! You can now login.";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | DecorEase</title>

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

        .register-container {
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 45px 20px;
        }

        .register-box {
            width: 100%;
            max-width: 480px;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .register-box h1 {
            text-align: center;
            color: #e76f51;
            margin: 0 0 10px;
        }

        .register-box .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 17px;
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

        .register-btn {
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

        .register-btn:hover {
            background: #cf573b;
        }

        .message {
            background: #fff0e9;
            color: #9b432d;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #e76f51;
            text-decoration: none;
            font-weight: bold;
        }

        .register-footer {
            text-align: center;
            padding: 18px;
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 500px) {
            .register-box {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navber.php'; ?>

    <main class="register-container">
        <section class="register-box">

            <h1>Create Account</h1>
            <p class="subtitle">
                Join DecorEase and plan your special event with us.
            </p>

           <?php if ($message !== ""): ?>
    <div class="message"
         style="<?php echo $messageType === 'success'
             ? 'background:#e8f7ed;color:#216e39;'
             : ''; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

            <form action="register.php" method="POST">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Enter password again"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit" class="register-btn">
                    Create Account
                </button>

            </form>

            <p class="login-link">
                Already have an account?
                <a href="login.php">Login</a>
            </p>

        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>

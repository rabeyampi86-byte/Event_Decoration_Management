
<?php
session_start();
include_once "../db.php";

// Already logged-in admin
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password_hash, phone, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

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

                $_SESSION['user_id'] = $admin['user_id'];
                $_SESSION['user_name'] = $admin['full_name'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_role'] = $admin['role'];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid admin email or password.";
            }

        } else {
            $error = "Invalid admin email or password.";
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
    <title>Admin Login | Event Decoration</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #24152f, #57283c);
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 35px rgba(0,0,0,.25);
        }

        .login-card h3 {
            color: #57283c;
            font-weight: 700;
        }

        .login-card p {
            color: #777;
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
        }

        .btn-login {
            background: #57283c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #b84a72;
            color: white;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="text-center mb-4">
        <h3>Admin Login</h3>
        <p>Event Decoration Management System</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Admin Email</label>
            <input type="email" name="email" class="form-control"
                   placeholder="Enter admin email" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn btn-login w-100">
            Login to Dashboard
        </button>

    </form>

</div>

</body>
</html>

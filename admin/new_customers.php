
<?php
include_once 'layout-start.php';

$message = '';
$messageType = '';

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $message = "Invalid request. Please try again.";
        $messageType = "error";

    } elseif (
        $fullName === '' ||
        $email === '' ||
        $phone === '' ||
        $address === '' ||
        $password === ''
    ) {

        $message = "Please fill in all fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";
        $messageType = "error";

    } else {

        try {

            /* Check Duplicate Email */
            $check = $conn->prepare("
                SELECT user_id FROM users WHERE email = ?
            ");

            $check->bind_param("s", $email);
            $check->execute();
            $existing = $check->get_result();

            if ($existing->num_rows > 0) {

                $message = "This email address is already registered.";
                $messageType = "error";

            } else {

                $conn->begin_transaction();

                /* Insert User */
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $role = 'customer';
                $status = 'active';

                $userStmt = $conn->prepare("
                    INSERT INTO users
                    (full_name, email, password_hash, phone, role, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $userStmt->bind_param(
                    "ssssss",
                    $fullName,
                    $email,
                    $hashedPassword,
                    $phone,
                    $role,
                    $status
                );

                $userStmt->execute();

                $userId = $conn->insert_id;

                /* Insert Customer Profile */
                $customerStmt = $conn->prepare("
                    INSERT INTO customers (user_id, address)
                    VALUES (?, ?)
                ");

                $customerStmt->bind_param(
                    "is",
                    $userId,
                    $address
                );

                $customerStmt->execute();

                $conn->commit();

                echo "<script>
                    window.location.href = 'customers.php?added=1';
                </script>";

            }

            $check->close();

        } catch (Throwable $e) {

            $conn->rollback();

            $message = "Unable to add customer. Please check the information and try again.";
            $messageType = "error";
        }
    }
}
?>

<style>
.customer-form-page {
    padding-bottom: 30px;
}

.customer-form-panel {
    background: #1e1422;
    border: 1px solid #292929;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
    max-width: 850px;
}

.customer-form-panel h3 {
    color: #f6f8fa;
    font-size: 18px;
    margin: 0 0 22px;
}

.customer-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.customer-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.customer-form-group.full-width {
    grid-column: 1 / -1;
}

.customer-form-group label {
    color: #d1d5db;
    font-size: 14px;
    font-weight: 500;
}

.customer-form-group input,
.customer-form-group textarea {
    width: 100%;
    padding: 12px 14px;
    background: #272626;
    border: 1px solid #383838;
    border-radius: 7px;
    color: #f5f5f5;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}

.customer-form-group input:focus,
.customer-form-group textarea:focus {
    border-color: #16a34a;
}

.customer-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.customer-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
}

.customer-submit-btn,
.customer-cancel-btn {
    padding: 11px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.customer-submit-btn {
    background: #16a34a;
    color: white;
}

.customer-cancel-btn {
    background: #be4e4e;
    color: #e5e7eb;
}

.customer-alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin-top: 18px;
    font-size: 14px;
}

.customer-alert.success {
    background: #12351f;
    color: #86efac;
}

.customer-alert.error {
    background: #351616;
    color: #fca5a5;
}

@media(max-width: 650px) {
    .customer-form-grid {
        grid-template-columns: 1fr;
    }

    .customer-form-group.full-width {
        grid-column: auto;
    }
}
</style>

<div class="customer-form-page">

    <div class="page-heading">
        <div>
            <h2>Add New Customer</h2>
            <p>Create a new customer account manually.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="customer-alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="customer-form-panel">

        <h3>Customer Information</h3>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <div class="customer-form-grid">

                <div class="customer-form-group">
                    <label>Full Name *</label>
                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter full name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="customer-form-group">
                    <label>Email Address *</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter email address"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="customer-form-group">
                    <label>Phone Number *</label>
                    <input
                        type="text"
                        name="phone"
                        placeholder="Enter phone number"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="customer-form-group">
                    <label>Password *</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        required
                    >
                </div>

                <div class="customer-form-group full-width">
                    <label>Address *</label>
                    <textarea
                        name="address"
                        placeholder="Enter customer address"
                        required
                    ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="customer-form-actions">

                <a href="customers.php" class="customer-cancel-btn">
                    Cancel
                </a>

                <button type="submit" class="customer-submit-btn">
                    Add Customer
                </button>

            </div>

        </form>

    </div>

</div>

<?php include 'layout-end.php'; ?>

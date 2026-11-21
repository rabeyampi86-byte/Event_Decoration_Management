<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: vendors.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM vendors
    WHERE vendor_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vendor) {
    header("Location: vendors.php?error=notfound");
    exit;
}

$error = '';

$vendor_name = $vendor['vendor_name'];
$service_type = $vendor['service_type'] ?? '';
$contact_person = $vendor['contact_person'] ?? '';
$phone = $vendor['phone'] ?? '';
$email = $vendor['email'] ?? '';
$address = $vendor['address'] ?? '';
$description = $vendor['description'] ?? '';
$status = $vendor['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vendor_name = trim($_POST['vendor_name'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($vendor_name === '') {
        $error = 'Vendor name is required.';
    } elseif (mb_strlen($vendor_name) > 180) {
        $error = 'Vendor name cannot exceed 180 characters.';
    } elseif (mb_strlen($service_type) > 120) {
        $error = 'Service type cannot exceed 120 characters.';
    } elseif (mb_strlen($contact_person) > 150) {
        $error = 'Contact person cannot exceed 150 characters.';
    } elseif (mb_strlen($phone) > 30) {
        $error = 'Phone cannot exceed 30 characters.';
    } elseif (mb_strlen($email) > 191) {
        $error = 'Email cannot exceed 191 characters.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive', 'pending'], true)) {
        $error = 'Invalid vendor status.';
    } else {

        $service_type = $service_type !== '' ? $service_type : null;
        $contact_person = $contact_person !== '' ? $contact_person : null;
        $phone = $phone !== '' ? $phone : null;
        $email = $email !== '' ? $email : null;
        $address = $address !== '' ? $address : null;
        $description = $description !== '' ? $description : null;

        $update = $conn->prepare("
            UPDATE vendors
            SET vendor_name = ?,
                service_type = ?,
                contact_person = ?,
                phone = ?,
                email = ?,
                address = ?,
                description = ?,
                status = ?
            WHERE vendor_id = ?
        ");

        $update->bind_param(
            "ssssssssi",
            $vendor_name,
            $service_type,
            $contact_person,
            $phone,
            $email,
            $address,
            $description,
            $status,
            $id
        );

        if ($update->execute()) {
            header("Location: vendors.php?success=updated");
            exit;
        } else {
            $error = 'Failed to update vendor.';
        }

        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vendor</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .form-container{max-width:850px;background:#fff;padding:25px;border-radius:10px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 20px}
        .form-group{margin-bottom:20px}
        .form-group.full-width{grid-column:1/-1}
        .form-group label{display:block;margin-bottom:8px;color:#334155;font-weight:600}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px}
        .form-group textarea{min-height:110px;resize:vertical}
        .submit-btn{background:#2563eb;color:#fff;border:0;padding:11px 20px;border-radius:6px;cursor:pointer}
        .back-btn{color:#475569;text-decoration:none;margin-left:12px}
        .error{color:#991b1b;background:#fee2e2;padding:10px;border-radius:6px;margin-bottom:15px}
        @media(max-width:650px){.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Edit Vendor</h2>

            <div class="form-container">

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">

                        <div class="form-group">
                            <label>Vendor Name *</label>
                            <input type="text" name="vendor_name" maxlength="180" required
                                   value="<?= htmlspecialchars($vendor_name); ?>">
                        </div>

                        <div class="form-group">
                            <label>Service Type</label>
                            <input type="text" name="service_type" maxlength="120"
                                   value="<?= htmlspecialchars($service_type); ?>">
                        </div>

                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" maxlength="150"
                                   value="<?= htmlspecialchars($contact_person); ?>">
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" maxlength="30"
                                   value="<?= htmlspecialchars($phone); ?>">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" maxlength="191"
                                   value="<?= htmlspecialchars($email); ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label>Address</label>
                            <textarea name="address"><?= htmlspecialchars($address); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="description"><?= htmlspecialchars($description); ?></textarea>
                        </div>

                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Update Vendor
                    </button>

                    <a href="vendors.php" class="back-btn">Cancel</a>
                </form>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
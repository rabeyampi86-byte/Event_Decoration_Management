<?php
require_once '../db.php';

$error = '';

$item_name = '';
$unit = '';
$total_quantity = '0';
$available_quantity = '0';
$replacement_cost = '0.00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item_name = trim($_POST['item_name'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $total_quantity = trim($_POST['total_quantity'] ?? '');
    $available_quantity = trim($_POST['available_quantity'] ?? '');
    $replacement_cost = trim($_POST['replacement_cost'] ?? '');

    if ($item_name === '' || $unit === '') {
        $error = 'Item name and unit are required.';
    } elseif (mb_strlen($item_name) > 200) {
        $error = 'Item name cannot exceed 200 characters.';
    } elseif (mb_strlen($unit) > 40) {
        $error = 'Unit cannot exceed 40 characters.';
    } elseif (
        !ctype_digit($total_quantity) ||
        !ctype_digit($available_quantity)
    ) {
        $error = 'Quantity must be a non-negative whole number.';
    } elseif ((float)$total_quantity > 4294967295 || (float)$available_quantity > 4294967295) {
        $error = 'Quantity value is too large.';
    } elseif ((int)$available_quantity > (int)$total_quantity) {
        $error = 'Available quantity cannot exceed total quantity.';
    } elseif (
        $replacement_cost === '' ||
        !is_numeric($replacement_cost) ||
        (float)$replacement_cost < 0 ||
        (float)$replacement_cost > 9999999999.99
    ) {
        $error = 'Enter a valid non-negative replacement cost.';
    } else {

        $total = (int)$total_quantity;
        $available = (int)$available_quantity;
        $cost = (float)$replacement_cost;

        $stmt = $conn->prepare("
            INSERT INTO inventory
            (item_name, unit, total_quantity, available_quantity, replacement_cost)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssiid",
            $item_name,
            $unit,
            $total,
            $available,
            $cost
        );

        if ($stmt->execute()) {
            header("Location: inventory.php?success=added");
            exit;
        } else {
            $error = 'Failed to add inventory item.';
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
    <title>Add Inventory Item</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .form-container{max-width:750px;background:#fff;padding:25px;border-radius:10px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:8px;color:#334155;font-weight:600}
        .form-group input{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px}
        .submit-btn{background:#2563eb;color:#fff;border:0;padding:11px 20px;border-radius:6px;cursor:pointer}
        .back-btn{color:#475569;text-decoration:none;margin-left:12px}
        .error{color:#991b1b;background:#fee2e2;padding:10px;border-radius:6px;margin-bottom:15px}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Add Inventory Item</h2>

            <div class="form-container">

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="item_name" maxlength="200" required
                               value="<?= htmlspecialchars($item_name); ?>">
                    </div>

                    <div class="form-group">
                        <label>Unit *</label>
                        <input type="text" name="unit" maxlength="40" required
                               placeholder="e.g. Piece, Set, Meter, Box"
                               value="<?= htmlspecialchars($unit); ?>">
                    </div>

                    <div class="form-group">
                        <label>Total Quantity *</label>
                        <input type="number" name="total_quantity" min="0" step="1" required
                               value="<?= htmlspecialchars($total_quantity); ?>">
                    </div>

                    <div class="form-group">
                        <label>Available Quantity *</label>
                        <input type="number" name="available_quantity" min="0" step="1" required
                               value="<?= htmlspecialchars($available_quantity); ?>">
                    </div>

                    <div class="form-group">
                        <label>Replacement Cost *</label>
                        <input type="number" name="replacement_cost" min="0" step="0.01" required
                               value="<?= htmlspecialchars($replacement_cost); ?>">
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Save Item
                    </button>

                    <a href="inventory.php" class="back-btn">Cancel</a>
                </form>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
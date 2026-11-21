<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: inventory.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM inventory WHERE inventory_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: inventory.php?error=notfound");
    exit;
}

function viewInventoryEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inventory Item</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .details-card{max-width:750px;background:#fff;padding:25px;border-radius:10px}
        .detail-row{display:flex;padding:14px 0;border-bottom:1px solid #e2e8f0}
        .detail-label{width:200px;color:#64748b;font-weight:600}
        .detail-value{flex:1;color:#1e293b}
        .back-btn{display:inline-block;margin-top:20px;background:#2563eb;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Inventory Item Details</h2>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Inventory ID</div>
                    <div class="detail-value"><?= (int)$item['inventory_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Item Name</div>
                    <div class="detail-value"><?= viewInventoryEscape($item['item_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Unit</div>
                    <div class="detail-value"><?= viewInventoryEscape($item['unit']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Total Quantity</div>
                    <div class="detail-value"><?= number_format((int)$item['total_quantity']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Available Quantity</div>
                    <div class="detail-value"><?= number_format((int)$item['available_quantity']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Used Quantity</div>
                    <div class="detail-value">
                        <?= number_format((int)$item['total_quantity'] - (int)$item['available_quantity']); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Replacement Cost</div>
                    <div class="detail-value"><?= number_format((float)$item['replacement_cost'], 2); ?></div>
                </div>

                <a href="inventory.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Inventory
                </a>

            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
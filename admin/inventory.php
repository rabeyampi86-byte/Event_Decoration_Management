<?php
require_once __DIR__ . '/../db.php';

function inventoryEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$result = $conn->query("
    SELECT *
    FROM inventory
    ORDER BY inventory_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .page-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px}
        .page-heading h2{color:#1e293b}
        .add-btn{background:#2563eb;color:white;padding:10px 16px;border-radius:6px;text-decoration:none}
        .table-container{background:#fff;padding:20px;border-radius:10px;overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:13px;text-align:left;border-bottom:1px solid #e2e8f0}
        th{background:#f8fafc;color:#475569}
        .action-links a{margin-right:10px;text-decoration:none}
        .view-link{color:#2563eb}.edit-link{color:#d97706}.delete-link{color:#dc2626}
        .empty-message{text-align:center;padding:25px;color:#64748b}
        .alert-success{color:#166534;background:#dcfce7;padding:10px;border-radius:6px;margin-bottom:15px}
        .alert-error{color:#991b1b;background:#fee2e2;padding:10px;border-radius:6px;margin-bottom:15px}
        .low-stock{color:#b45309;font-weight:600}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <h2><i class="fas fa-boxes-stacked"></i> Inventory Management</h2>
                <a href="add_inventory.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Inventory Item
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo 'Inventory item added successfully.';
                    elseif ($_GET['success'] === 'updated') echo 'Inventory item updated successfully.';
                    elseif ($_GET['success'] === 'deleted') echo 'Inventory item deleted successfully.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-error">Unable to complete the operation.</div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Unit</th>
                            <th>Total Quantity</th>
                            <th>Available Quantity</th>
                            <th>Replacement Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$item['inventory_id']; ?></td>
                                <td><?= inventoryEscape($item['item_name']); ?></td>
                                <td><?= inventoryEscape($item['unit']); ?></td>
                                <td><?= number_format((int)$item['total_quantity']); ?></td>
                                <td>
                                    <?php if ((int)$item['available_quantity'] <= 2): ?>
                                        <span class="low-stock">
                                            <?= number_format((int)$item['available_quantity']); ?>
                                        </span>
                                    <?php else: ?>
                                        <?= number_format((int)$item['available_quantity']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((float)$item['replacement_cost'], 2); ?></td>
                                <td class="action-links">
                                    <a class="view-link" title="View"
                                       href="view_inventory.php?id=<?= (int)$item['inventory_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a class="edit-link" title="Edit"
                                       href="edit_inventory.php?id=<?= (int)$item['inventory_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a class="delete-link" title="Delete"
                                       href="delete_inventory.php?id=<?= (int)$item['inventory_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this inventory item?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-message">No inventory items found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
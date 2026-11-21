<?php
require_once __DIR__ . '/../db.php';

function foodEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT f.*, m.file_name, m.file_path, m.alt_text
    FROM food_menu_items f
    LEFT JOIN media m ON f.image_id = m.media_id
    ORDER BY f.food_id DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu Items Management</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }

        .page-heading {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
        }

        .page-heading h2 { color:#1e293b; }

        .add-btn {
            background:#2563eb;
            color:white;
            padding:10px 16px;
            border-radius:6px;
            text-decoration:none;
        }

        .table-container {
            background:white;
            padding:20px;
            border-radius:10px;
            overflow-x:auto;
        }

        table {
            width:100%;
            border-collapse:collapse;
        }

        th, td {
            padding:13px;
            text-align:left;
            border-bottom:1px solid #e2e8f0;
        }

        th {
            background:#f8fafc;
            color:#475569;
        }

        .food-img {
            width:65px;
            height:55px;
            object-fit:cover;
            border-radius:6px;
        }

        .status {
            padding:5px 10px;
            border-radius:20px;
            font-size:12px;
        }

        .active {
            background:#dcfce7;
            color:#166534;
        }

        .inactive {
            background:#fee2e2;
            color:#991b1b;
        }

        .action-links a {
            margin-right:10px;
            text-decoration:none;
        }

        .view-link { color:#2563eb; }
        .edit-link { color:#d97706; }
        .delete-link { color:#dc2626; }

        .empty-message {
            text-align:center;
            padding:25px;
            color:#64748b;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <h2>
                    <i class="fas fa-utensils"></i>
                    Food Menu Items Management
                </h2>

                <a href="add_food_menu_item.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Food Item
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <p style="color:green;margin-bottom:15px;">
                    <?php
                    if ($_GET['success'] === 'added') echo 'Food item added successfully.';
                    elseif ($_GET['success'] === 'updated') echo 'Food item updated successfully.';
                    elseif ($_GET['success'] === 'deleted') echo 'Food item deleted successfully.';
                    ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <p style="color:red;margin-bottom:15px;">
                    Unable to complete the operation.
                </p>
            <?php endif; ?>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Food Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($food = $result->fetch_assoc()): ?>

                            <tr>

                                <td><?= (int)$food['food_id']; ?></td>

                                <td>
                                    <?php if (!empty($food['file_path'])): ?>

                                        <img
                                            class="food-img"
                                            src="../uploads/<?= foodEscape($food['file_path']); ?>"
                                            alt="<?= foodEscape($food['alt_text'] ?: $food['food_name']); ?>"
                                        >

                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>

                                <td><?= foodEscape($food['food_name']); ?></td>

                                <td>
                                    <?= foodEscape(
                                        strlen($food['description'] ?? '') > 60
                                        ? substr($food['description'], 0, 60) . '...'
                                        : ($food['description'] ?? '')
                                    ); ?>
                                </td>

                                <td>
                                    <?php if ($food['is_active']): ?>
                                        <span class="status active">Active</span>
                                    <?php else: ?>
                                        <span class="status inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td class="action-links">

                                    <a class="view-link"
                                       href="view_food_menu_item.php?id=<?= (int)$food['food_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a class="edit-link"
                                       href="edit_food_menu_item.php?id=<?= (int)$food['food_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a class="delete-link"
                                       href="delete_food_menu_item.php?id=<?= (int)$food['food_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this food item?');">
                                        <i class="fas fa-trash"></i>
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                No food menu items found.
                            </td>
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
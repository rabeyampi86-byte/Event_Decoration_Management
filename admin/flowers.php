<?php
require_once __DIR__ . '/../db.php';

function flowerEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT f.*, m.file_name, m.file_path, m.alt_text
    FROM flowers f
    LEFT JOIN media m ON f.image_id = m.media_id
    ORDER BY f.flower_id DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flowers Management</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-content { padding: 25px; }
        .page-heading {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:25px;
        }
        .page-heading h2 { color:#1e293b; }
        .add-btn {
            background:#2563eb; color:white; padding:10px 16px;
            border-radius:6px; text-decoration:none;
        }
        .table-container {
            background:white; padding:20px; border-radius:10px;
            overflow-x:auto;
        }
        table { width:100%; border-collapse:collapse; }
        th,td {
            padding:13px; text-align:left;
            border-bottom:1px solid #e2e8f0;
        }
        th { background:#f8fafc; color:#475569; }
        .flower-img {
            width:65px; height:55px; object-fit:cover;
            border-radius:6px;
        }
        .status {
            padding:5px 10px; border-radius:20px;
            font-size:12px;
        }
        .active { background:#dcfce7; color:#166534; }
        .inactive { background:#fee2e2; color:#991b1b; }
        .action-links a {
            margin-right:10px; text-decoration:none;
        }
        .view-link { color:#2563eb; }
        .edit-link { color:#d97706; }
        .delete-link { color:#dc2626; }
        .empty-message { text-align:center; padding:25px; color:#64748b; }
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <h2><i class="fas fa-spa"></i> Flowers Management</h2>
                <a href="add_flower.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add New Flower
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <p style="color:green;margin-bottom:15px;">
                    <?php
                    if ($_GET['success'] === 'added') echo 'Flower added successfully.';
                    elseif ($_GET['success'] === 'updated') echo 'Flower updated successfully.';
                    elseif ($_GET['success'] === 'deleted') echo 'Flower deleted successfully.';
                    ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <p style="color:red;margin-bottom:15px;">Unable to complete the operation.</p>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Flower Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($flower = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$flower['flower_id']; ?></td>

                                <td>
                                    <?php if (!empty($flower['file_path'])): ?>
                                        <img class="flower-img"
                                             src="../uploads/<?= flowerEscape($flower['file_path']); ?>"
                                             alt="<?= flowerEscape($flower['alt_text'] ?: $flower['flower_name']); ?>">
                                    <?php else: ?>
                                        <span>No Image</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= flowerEscape($flower['flower_name']); ?></td>

                                <td>
                                    <?= flowerEscape(
                                        strlen($flower['description'] ?? '') > 60
                                        ? substr($flower['description'], 0, 60) . '...'
                                        : ($flower['description'] ?? '')
                                    ); ?>
                                </td>

                                <td>
                                    <?php if ($flower['is_active']): ?>
                                        <span class="status active">Active</span>
                                    <?php else: ?>
                                        <span class="status inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td class="action-links">
                                    <a class="view-link" href="view_flower.php?id=<?= (int)$flower['flower_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a class="edit-link" href="edit_flower.php?id=<?= (int)$flower['flower_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a class="delete-link"
                                       href="delete_flower.php?id=<?= (int)$flower['flower_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this flower?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-message">No flowers found.</td>
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
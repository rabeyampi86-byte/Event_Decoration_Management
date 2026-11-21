<?php
require_once __DIR__ . '/../db.php';

function navEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$result = $conn->query("
    SELECT * FROM navigation_items
    ORDER BY sort_order ASC, nav_id ASC
");

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Menu Management</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }
        .page-heading {
            display:flex; justify-content:space-between;
            align-items:center; gap:15px; margin-bottom:22px;
        }
        .page-heading h2 { color:#1e293b; margin:0 0 5px; }
        .page-heading p { color:#64748b; margin:0; }
        .add-btn {
            background:#2563eb; color:white; padding:11px 17px;
            border-radius:7px; text-decoration:none;
        }
        .add-btn:hover { background:#1d4ed8; }
        .table-card {
            background:#fff; padding:20px; border-radius:10px;
            overflow-x:auto;
        }
        .nav-table {
            width:100%; border-collapse:collapse; min-width:750px;
        }
        .nav-table th, .nav-table td {
            padding:13px 12px; border-bottom:1px solid #e2e8f0;
            text-align:left; color:#475569;
        }
        .nav-table th { background:#f8fafc; color:#1e293b; }
        .status {
            padding:5px 10px; border-radius:20px;
            font-size:12px; font-weight:600;
        }
        .visible { background:#dcfce7; color:#166534; }
        .hidden { background:#fee2e2; color:#991b1b; }
        .actions { display:flex; gap:7px; }
        .actions a {
            width:32px; height:32px; display:inline-flex;
            align-items:center; justify-content:center;
            border-radius:5px; text-decoration:none; color:white;
        }
        .view-action { background:#0ea5e9; }
        .edit-action { background:#2563eb; }
        .delete-action { background:#dc2626; }
        .alert {
            padding:12px 15px; border-radius:7px; margin-bottom:18px;
        }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .url-text { overflow-wrap:anywhere; max-width:300px; }
    </style>
</head>

<body>
<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <div>
                    <h2>Navigation Menu</h2>
                    <p>Manage website navigation menu items.</p>
                </div>

                <a href="add_navigation.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Menu Item
                </a>
            </div>

            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">Menu item added successfully.</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">Menu item updated successfully.</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">Menu item deleted successfully.</div>
            <?php endif; ?>

            <?php if ($error === 'delete_failed'): ?>
                <div class="alert alert-error">Unable to delete menu item.</div>
            <?php endif; ?>

            <div class="table-card">
                <table class="nav-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Sort Order</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$item['nav_id']; ?></td>

                                <td>
                                    <strong><?= navEscape($item['label']); ?></strong>
                                </td>

                                <td class="url-text">
                                    <?= navEscape($item['url']); ?>
                                </td>

                                <td><?= (int)$item['sort_order']; ?></td>

                                <td>
                                    <?php if ((int)$item['is_visible'] === 1): ?>
                                        <span class="status visible">Visible</span>
                                    <?php else: ?>
                                        <span class="status hidden">Hidden</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a class="view-action"
                                           href="view_navigation.php?id=<?= (int)$item['nav_id']; ?>"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a class="edit-action"
                                           href="edit_navigation.php?id=<?= (int)$item['nav_id']; ?>"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a class="delete-action"
                                           href="delete_navigation.php?id=<?= (int)$item['nav_id']; ?>"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this menu item?');">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No navigation items found.
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
<?php
require_once __DIR__ . '/../db.php';

function areaEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$result = $conn->query("
    SELECT da.*, m.file_name, m.file_path, m.alt_text
    FROM decoration_areas da
    LEFT JOIN media m ON da.image_id = m.media_id
    ORDER BY da.area_id DESC
");

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decoration Areas</title>
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
            background:#2563eb; color:#fff; padding:11px 17px;
            border-radius:7px; text-decoration:none;
        }
        .add-btn:hover { background:#1d4ed8; }
        .table-card {
            background:#fff; padding:20px; border-radius:10px;
            overflow-x:auto;
        }
        .area-table { width:100%; border-collapse:collapse; min-width:800px; }
        .area-table th, .area-table td {
            padding:13px 12px; border-bottom:1px solid #e2e8f0;
            text-align:left; color:#475569;
        }
        .area-table th { background:#f8fafc; color:#1e293b; }
        .area-img {
            width:80px; height:55px; object-fit:cover;
            border-radius:6px;
        }
        .status {
            padding:5px 10px; border-radius:20px;
            font-size:12px; font-weight:600;
        }
        .active { background:#dcfce7; color:#166534; }
        .inactive { background:#fee2e2; color:#991b1b; }
        .actions { display:flex; gap:7px; }
        .actions a {
            width:32px; height:32px; display:inline-flex;
            align-items:center; justify-content:center;
            border-radius:5px; text-decoration:none; color:#fff;
        }
        .view-action { background:#0ea5e9; }
        .edit-action { background:#2563eb; }
        .delete-action { background:#dc2626; }
        .alert {
            padding:12px 15px; border-radius:7px; margin-bottom:18px;
        }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-error { background:#fee2e2; color:#991b1b; }
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
                    <h2>Decoration Areas</h2>
                    <p>Manage decoration areas and their information.</p>
                </div>

                <a href="add_decoration_area.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Decoration Area
                </a>
            </div>

            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">Decoration area added successfully.</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">Decoration area updated successfully.</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">Decoration area deleted successfully.</div>
            <?php endif; ?>

            <?php if ($error === 'delete_failed'): ?>
                <div class="alert alert-error">Unable to delete decoration area.</div>
            <?php endif; ?>

            <div class="table-card">
                <table class="area-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Area Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($area = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$area['area_id']; ?></td>

                                <td>
                                    <?php if (!empty($area['file_path'])): ?>
                                        <img class="area-img"
                                             src="../uploads/<?= areaEscape($area['file_path']); ?>"
                                             alt="<?= areaEscape($area['alt_text'] ?: $area['area_name']); ?>">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= areaEscape($area['area_name']); ?></strong>
                                </td>

                                <td>
                                    <?= areaEscape(mb_strimwidth($area['description'] ?? '', 0, 65, '...')); ?>
                                </td>

                                <td>
                                    <?php if ((int)$area['is_active'] === 1): ?>
                                        <span class="status active">Active</span>
                                    <?php else: ?>
                                        <span class="status inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a class="view-action"
                                           href="view_decoration_area.php?id=<?= (int)$area['area_id']; ?>"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a class="edit-action"
                                           href="edit_decoration_area.php?id=<?= (int)$area['area_id']; ?>"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a class="delete-action"
                                           href="delete_decoration_area.php?id=<?= (int)$area['area_id']; ?>"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this decoration area?');">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No decoration areas found.
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
<?php
require_once __DIR__ . '/../db.php';

function facilityEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT f.*, m.file_name, m.file_path, m.alt_text
    FROM facilities f
    LEFT JOIN media m ON f.image_id = m.media_id
    ORDER BY f.facility_id DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities Management</title>

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

        .facility-img {
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
                    <i class="fas fa-concierge-bell"></i>
                    Facilities Management
                </h2>

                <a href="add_facility.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Facility
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <p style="color:green;margin-bottom:15px;">
                    <?php
                    if ($_GET['success'] === 'added') echo 'Facility added successfully.';
                    elseif ($_GET['success'] === 'updated') echo 'Facility updated successfully.';
                    elseif ($_GET['success'] === 'deleted') echo 'Facility deleted successfully.';
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
                            <th>Facility Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($facility = $result->fetch_assoc()): ?>

                            <tr>

                                <td><?= (int)$facility['facility_id']; ?></td>

                                <td>
                                    <?php if (!empty($facility['file_path'])): ?>

                                        <img
                                            class="facility-img"
                                            src="../uploads/<?= facilityEscape($facility['file_path']); ?>"
                                            alt="<?= facilityEscape($facility['alt_text'] ?: $facility['facility_name']); ?>"
                                        >

                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>

                                <td><?= facilityEscape($facility['facility_name']); ?></td>

                                <td>
                                    <?= facilityEscape(
                                        strlen($facility['description'] ?? '') > 60
                                        ? substr($facility['description'], 0, 60) . '...'
                                        : ($facility['description'] ?? '')
                                    ); ?>
                                </td>

                                <td>
                                    <?php if ($facility['is_active']): ?>
                                        <span class="status active">Active</span>
                                    <?php else: ?>
                                        <span class="status inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td class="action-links">

                                    <a class="view-link"
                                       href="view_facility.php?id=<?= (int)$facility['facility_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a class="edit-link"
                                       href="edit_facility.php?id=<?= (int)$facility['facility_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a class="delete-link"
                                       href="delete_facility.php?id=<?= (int)$facility['facility_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this facility?');">
                                        <i class="fas fa-trash"></i>
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                No facilities found.
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
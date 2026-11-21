
<?php
require_once __DIR__ . '/../db.php';

function sectionEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Fetch all page sections */
$sql = "
    SELECT
        ps.*,
        p.page_title,
        m.file_name,
        m.file_path,
        m.alt_text
    FROM page_sections ps
    LEFT JOIN pages p
        ON ps.page_id = p.page_id
    LEFT JOIN media m
        ON ps.image_id = m.media_id
    ORDER BY ps.sort_order ASC, ps.section_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Page Sections | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .sections-content {
            padding: 25px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin: 0 0 6px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow-x: auto;
        }

        .sections-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        .sections-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .sections-table td {
            padding: 14px 15px;
            color: #475569;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .sections-table tr:last-child td {
            border-bottom: none;
        }

        .section-title {
            color: #1e293b;
            font-weight: 600;
        }

        .section-key {
            color: #64748b;
            font-size: 12px;
        }

        .section-image {
            width: 65px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .no-image {
            width: 65px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #94a3b8;
            border-radius: 6px;
            font-size: 18px;
        }

        .visibility {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .visible {
            background: #dcfce7;
            color: #166534;
        }

        .hidden {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .action-buttons a {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .edit-btn {
            background: #fef3c7;
            color: #b45309;
        }

        .delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        .empty-message {
            text-align: center;
            padding: 35px !important;
            color: #64748b;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
        }

        @media(max-width: 768px) {
            .sections-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="sections-content">

            <div class="page-heading">

                <div>
                    <h2>Page Sections</h2>
                    <p>Manage the content and sections of your website pages.</p>
                </div>

                <a href="add_page_section.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Add New Section
                </a>

            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') {
                        echo 'Section added successfully.';
                    } elseif ($_GET['success'] === 'updated') {
                        echo 'Section updated successfully.';
                    } elseif ($_GET['success'] === 'deleted') {
                        echo 'Section deleted successfully.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    Unable to complete the requested action.
                </div>
            <?php endif; ?>

            <div class="table-card">

                <table class="sections-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Page</th>
                            <th>Section</th>
                            <th>Heading</th>
                            <th>Image</th>
                            <th>Order</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($section = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= (int)$section['section_id']; ?>
                                </td>

                                <td>
                                    <?= sectionEscape($section['page_title'] ?? 'Unknown Page'); ?>
                                </td>

                                <td>
                                    <div class="section-title">
                                        <?= sectionEscape($section['section_title'] ?: 'Untitled Section'); ?>
                                    </div>

                                    <div class="section-key">
                                        <?= sectionEscape($section['section_key']); ?>
                                    </div>
                                </td>

                                <td>
                                    <?= sectionEscape($section['heading'] ?: '—'); ?>
                                </td>

                                <td>
                                    <?php if (!empty($section['file_path'])): ?>

                                        <img
                                            src="../uploads/<?= sectionEscape($section['file_path']); ?>"
                                            alt="<?= sectionEscape($section['alt_text'] ?? 'Section image'); ?>"
                                            class="section-image"
                                        >

                                    <?php else: ?>

                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= (int)$section['sort_order']; ?>
                                </td>

                                <td>

                                    <?php if ((int)$section['is_visible'] === 1): ?>

                                        <span class="visibility visible">
                                            Visible
                                        </span>

                                    <?php else: ?>

                                        <span class="visibility hidden">
                                            Hidden
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <a href="view_page_section.php?id=<?= (int)$section['section_id']; ?>"
                                           class="view-btn"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="edit_page_section.php?id=<?= (int)$section['section_id']; ?>"
                                           class="edit-btn"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="delete_page_section.php?id=<?= (int)$section['section_id']; ?>"
                                           class="delete-btn"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this section?');">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="empty-message">
                                No page sections found. Click "Add New Section" to create one.
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

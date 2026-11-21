
<?php
include_once '../db.php';

/* Fetch all pages */
$result = $conn->query("
    SELECT *
    FROM pages
    ORDER BY page_id DESC
");

function pageEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pages Management | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .pages-content {
            padding: 25px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .page-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin: 0 0 6px;
        }

        .page-heading p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .add-page-btn {
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
            transition: 0.2s;
        }

        .add-page-btn:hover {
            background: #1d4ed8;
        }

        .pages-table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow-x: auto;
        }

        .pages-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .pages-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .pages-table td {
            padding: 15px;
            color: #475569;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .pages-table tr:last-child td {
            border-bottom: none;
        }

        .page-title {
            color: #1e293b;
            font-weight: 600;
        }

        .page-slug {
            color: #64748b;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-published {
            background: #dcfce7;
            color: #166534;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
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

        .action-buttons a:hover {
            opacity: 0.75;
        }

        .empty-message {
            text-align: center;
            padding: 35px !important;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .pages-content {
                padding: 15px;
            }

            .page-heading h2 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- Header -->
        <?php include 'includes/header.php'; ?>

        <section class="pages-content">

            <!-- Page Heading -->
            <div class="page-heading">

                <div>
                    <h2>Pages Management</h2>
                    <p>Manage your website pages and their basic information.</p>
                </div>

                <a href="add_page.php" class="add-page-btn">
                    <i class="fas fa-plus"></i>
                    Add New Page
                </a>

            </div>

            <!-- Pages Table -->
            <div class="pages-table-card">

                <table class="pages-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Page Title</th>
                            <th>Page Slug</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($result && $result->num_rows > 0): ?>

                            <?php while ($page = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?= (int)$page['page_id']; ?>
                                    </td>

                                    <td>
                                        <span class="page-title">
                                            <?= pageEscape($page['page_title']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="page-slug">
                                            <?= pageEscape($page['page_slug']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status status-<?= pageEscape($page['status']); ?>">
                                            <?= ucfirst(pageEscape($page['status'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= pageEscape($page['updated_at']); ?>
                                    </td>

                                    <td>

                                        <div class="action-buttons">

                                            <a href="view_page.php?id=<?= (int)$page['page_id']; ?>"
                                               class="view-btn"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="edit_page.php?id=<?= (int)$page['page_id']; ?>"
                                               class="edit-btn"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a href="delete_page.php?id=<?= (int)$page['page_id']; ?>"
                                               class="delete-btn"
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this page?');">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty-message">
                                    No pages found. Click "Add New Page" to create your first page.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

</body>
</html>


<?php
require_once '../db.php';

$error = '';

function pageEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$page_title = '';
$page_slug = '';
$meta_title = '';
$meta_description = '';
$status = 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $page_title = trim($_POST['page_title'] ?? '');
    $page_slug = trim($_POST['page_slug'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'published';

    if ($page_title === '' || $page_slug === '') {
        $error = 'Page Title and Page Slug are required.';
    } elseif (!in_array($status, ['published', 'draft'], true)) {
        $error = 'Invalid page status.';
    } else {

        // Normalize slug
        $page_slug = strtolower($page_slug);
        $page_slug = preg_replace('/[^a-z0-9-]+/', '-', $page_slug);
        $page_slug = trim($page_slug, '-');

        // Check duplicate slug
        $check = $conn->prepare(
            "SELECT page_id FROM pages WHERE page_slug = ?"
        );
        $check->bind_param("s", $page_slug);
        $check->execute();
        $duplicate = $check->get_result();

        if ($duplicate->num_rows > 0) {
            $error = 'This page slug already exists.';
        } else {

            $stmt = $conn->prepare("
                INSERT INTO pages
                (page_title, page_slug, meta_title, meta_description, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $page_title,
                $page_slug,
                $meta_title,
                $meta_description,
                $status
            );

            if ($stmt->execute()) {
                header("Location: pages.php?success=added");
                exit;
            } else {
                $error = 'Unable to add page. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add New Page | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-form-content {
            padding: 25px;
        }

        .page-heading {
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

        .form-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
            max-width: 850px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            color: #334155;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        .form-hint {
            color: #64748b;
            font-size: 12px;
            margin-top: 6px;
        }

        .form-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .save-btn,
        .cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .save-btn {
            background: #2563eb;
            color: #fff;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            background: #f1f5f9;
            color: #475569;
        }

        @media (max-width: 768px) {
            .page-form-content {
                padding: 15px;
            }

            .form-card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-form-content">

            <div class="page-heading">
                <h2>Add New Page</h2>
                <p>Create a new page for your website.</p>
            </div>

            <div class="form-card">

                <?php if ($error !== ''): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= pageEscape($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label for="page_title">Page Title *</label>
                        <input
                            type="text"
                            id="page_title"
                            name="page_title"
                            maxlength="200"
                            value="<?= pageEscape($page_title); ?>"
                            placeholder="Enter page title"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="page_slug">Page Slug *</label>
                        <input
                            type="text"
                            id="page_slug"
                            name="page_slug"
                            maxlength="150"
                            value="<?= pageEscape($page_slug); ?>"
                            placeholder="e.g. about-us"
                            required
                        >
                        <p class="form-hint">
                            Use lowercase letters, numbers and hyphens.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input
                            type="text"
                            id="meta_title"
                            name="meta_title"
                            maxlength="255"
                            value="<?= pageEscape($meta_title); ?>"
                            placeholder="Enter SEO meta title"
                        >
                    </div>

                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            placeholder="Enter SEO meta description"
                        ><?= pageEscape($meta_description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Page Status *</label>
                        <select id="status" name="status" required>
                            <option value="published"
                                <?= $status === 'published' ? 'selected' : ''; ?>>
                                Published
                            </option>

                            <option value="draft"
                                <?= $status === 'draft' ? 'selected' : ''; ?>>
                                Draft
                            </option>
                        </select>
                    </div>

                    <div class="form-actions">

                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i>
                            Save Page
                        </button>

                        <a href="pages.php" class="cancel-btn">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

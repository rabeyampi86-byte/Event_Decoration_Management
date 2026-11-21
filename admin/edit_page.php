
<?php
require_once __DIR__ . '/../db.php';

function pageEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: pages.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM pages WHERE page_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$page = $result->fetch_assoc();

if (!$page) {
    header("Location: pages.php");
    exit;
}

$error = '';

$page_title = $page['page_title'];
$page_slug = $page['page_slug'];
$meta_title = $page['meta_title'] ?? '';
$meta_description = $page['meta_description'] ?? '';
$status = $page['status'] ?? 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $page_title = trim($_POST['page_title'] ?? '');
    $page_slug = trim($_POST['page_slug'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($page_title === '' || $page_slug === '') {
        $error = 'Page Title and Page Slug are required.';
    } elseif (!in_array($status, ['published', 'draft'], true)) {
        $error = 'Invalid page status.';
    } else {

        $page_slug = strtolower($page_slug);
        $page_slug = preg_replace('/[^a-z0-9-]+/', '-', $page_slug);
        $page_slug = trim($page_slug, '-');

        $check = $conn->prepare("
            SELECT page_id FROM pages
            WHERE page_slug = ? AND page_id != ?
        ");
        $check->bind_param("si", $page_slug, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'This page slug already exists.';
        } else {

            $update = $conn->prepare("
                UPDATE pages
                SET page_title = ?,
                    page_slug = ?,
                    meta_title = ?,
                    meta_description = ?,
                    status = ?
                WHERE page_id = ?
            ");

            $update->bind_param(
                "sssssi",
                $page_title,
                $page_slug,
                $meta_title,
                $meta_description,
                $status,
                $id
            );

            if ($update->execute()) {
                header("Location: pages.php?success=updated");
                exit;
            } else {
                $error = 'Unable to update page. Please try again.';
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
    <title>Edit Page | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .form-content { padding: 25px; }
        .page-heading { margin-bottom: 25px; }
        .page-heading h2 { color: #1e293b; font-size: 25px; margin: 0 0 6px; }
        .page-heading p { color: #64748b; margin: 0; font-size: 14px; }
        .form-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }
        .form-group { margin-bottom: 20px; }
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
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        .form-group textarea { resize: vertical; min-height: 110px; }
        .form-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-actions { display: flex; gap: 10px; margin-top: 25px; }
        .save-btn, .cancel-btn {
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
        .save-btn { background: #2563eb; color: #fff; }
        .save-btn:hover { background: #1d4ed8; }
        .cancel-btn { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="form-content">

            <div class="page-heading">
                <h2>Edit Page</h2>
                <p>Update the information of this website page.</p>
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
                        <input type="text" id="page_title" name="page_title"
                               maxlength="200" required
                               value="<?= pageEscape($page_title); ?>">
                    </div>

                    <div class="form-group">
                        <label for="page_slug">Page Slug *</label>
                        <input type="text" id="page_slug" name="page_slug"
                               maxlength="150" required
                               value="<?= pageEscape($page_slug); ?>">
                    </div>

                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" id="meta_title" name="meta_title"
                               maxlength="255"
                               value="<?= pageEscape($meta_title); ?>">
                    </div>

                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description"><?= pageEscape($meta_description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Page Status *</label>
                        <select id="status" name="status" required>
                            <option value="published" <?= $status === 'published' ? 'selected' : ''; ?>>
                                Published
                            </option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : ''; ?>>
                                Draft
                            </option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Update Page
                        </button>

                        <a href="pages.php" class="cancel-btn">Cancel</a>
                    </div>

                </form>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

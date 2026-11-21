
<?php
require_once __DIR__ . '/../db.php';

function sectionEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: page_sections.php");
    exit;
}

/* Fetch existing section */
$stmt = $conn->prepare("
    SELECT * FROM page_sections
    WHERE section_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

$section = $stmt->get_result()->fetch_assoc();

if (!$section) {
    header("Location: page_sections.php");
    exit;
}

/* Fetch pages and media */
$pages = $conn->query("
    SELECT page_id, page_title
    FROM pages
    ORDER BY page_title ASC
");

$mediaItems = $conn->query("
    SELECT media_id, file_name, file_path, alt_text
    FROM media
    ORDER BY media_id DESC
");

$error = '';

$page_id = (int)$section['page_id'];
$section_key = $section['section_key'];
$section_title = $section['section_title'] ?? '';
$heading = $section['heading'] ?? '';
$subheading = $section['subheading'] ?? '';
$content = $section['content'] ?? '';
$image_id = $section['image_id'];
$button_text = $section['button_text'] ?? '';
$button_link = $section['button_link'] ?? '';
$sort_order = (int)$section['sort_order'];
$is_visible = (int)$section['is_visible'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $page_id = (int)($_POST['page_id'] ?? 0);
    $section_key = trim($_POST['section_key'] ?? '');
    $section_title = trim($_POST['section_title'] ?? '');
    $heading = trim($_POST['heading'] ?? '');
    $subheading = trim($_POST['subheading'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_id = ($_POST['image_id'] ?? '') !== ''
        ? (int)$_POST['image_id']
        : null;
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;

    if ($page_id < 1 || $section_key === '') {
        $error = 'Please select a page and enter a Section Key.';
    } elseif (strlen($section_key) > 120) {
        $error = 'Section Key cannot exceed 120 characters.';
    } else {

        // Verify selected page
        $checkPage = $conn->prepare("
            SELECT page_id FROM pages WHERE page_id = ?
        ");
        $checkPage->bind_param("i", $page_id);
        $checkPage->execute();

        if (!$checkPage->get_result()->fetch_assoc()) {
            $error = 'Selected page does not exist.';
        }

        // Verify selected media
        if ($error === '' && $image_id !== null) {
            $checkMedia = $conn->prepare("
                SELECT media_id FROM media WHERE media_id = ?
            ");
            $checkMedia->bind_param("i", $image_id);
            $checkMedia->execute();

            if (!$checkMedia->get_result()->fetch_assoc()) {
                $error = 'Selected image does not exist.';
            }
        }

        if ($error === '') {

            $update = $conn->prepare("
                UPDATE page_sections
                SET
                    page_id = ?,
                    section_key = ?,
                    section_title = ?,
                    heading = ?,
                    subheading = ?,
                    content = ?,
                    image_id = ?,
                    button_text = ?,
                    button_link = ?,
                    sort_order = ?,
                    is_visible = ?
                WHERE section_id = ?
            ");

            $update->bind_param(
                "isssssissiii",
                $page_id,
                $section_key,
                $section_title,
                $heading,
                $subheading,
                $content,
                $image_id,
                $button_text,
                $button_link,
                $sort_order,
                $is_visible,
                $id
            );

            if ($update->execute()) {
                header("Location: page_sections.php?success=updated");
                exit;
            } else {
                $error = 'Unable to update section. Please try again.';
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
    <title>Edit Page Section | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .form-content { padding: 25px; }
        .page-heading { margin-bottom: 25px; }
        .page-heading h2 { color: #1e293b; font-size: 25px; margin: 0 0 6px; }
        .page-heading p { color: #64748b; margin: 0; font-size: 14px; }
        .form-card {
            max-width: 900px;
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
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        .current-image {
            width: 120px;
            height: 85px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-top: 10px;
        }
        .form-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .checkbox-group { display: flex; align-items: center; gap: 9px; }
        .checkbox-group input { width: auto; }
        .form-actions { display: flex; gap: 10px; margin-top: 25px; }
        .save-btn, .cancel-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .save-btn { background: #2563eb; color: #fff; }
        .save-btn:hover { background: #1d4ed8; }
        .cancel-btn { background: #f1f5f9; color: #475569; }
        @media(max-width:768px) {
            .form-content { padding: 15px; }
            .form-card { padding: 18px; }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="form-content">

            <div class="page-heading">
                <h2>Edit Page Section</h2>
                <p>Update the content and settings of this section.</p>
            </div>

            <div class="form-card">

                <?php if ($error !== ''): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= sectionEscape($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label for="page_id">Select Page *</label>
                        <select name="page_id" id="page_id" required>
                            <option value="">-- Select Page --</option>
                            <?php if ($pages): ?>
                                <?php while ($p = $pages->fetch_assoc()): ?>
                                    <option value="<?= (int)$p['page_id']; ?>"
                                        <?= $page_id === (int)$p['page_id'] ? 'selected' : ''; ?>>
                                        <?= sectionEscape($p['page_title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section_key">Section Key *</label>
                        <input type="text" name="section_key" id="section_key"
                               maxlength="120" required
                               value="<?= sectionEscape($section_key); ?>">
                    </div>

                    <div class="form-group">
                        <label for="section_title">Section Title</label>
                        <input type="text" name="section_title" id="section_title"
                               maxlength="255"
                               value="<?= sectionEscape($section_title); ?>">
                    </div>

                    <div class="form-group">
                        <label for="heading">Heading</label>
                        <input type="text" name="heading" id="heading"
                               maxlength="255"
                               value="<?= sectionEscape($heading); ?>">
                    </div>

                    <div class="form-group">
                        <label for="subheading">Subheading</label>
                        <input type="text" name="subheading" id="subheading"
                               maxlength="255"
                               value="<?= sectionEscape($subheading); ?>">
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea name="content" id="content"><?= sectionEscape($content); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image_id">Select Image (Media Library)</label>
                        <select name="image_id" id="image_id">
                            <option value="">-- No Image --</option>
                            <?php if ($mediaItems): ?>
                                <?php while ($m = $mediaItems->fetch_assoc()): ?>
                                    <option value="<?= (int)$m['media_id']; ?>"
                                        <?= (string)$image_id === (string)$m['media_id'] ? 'selected' : ''; ?>>
                                        <?= sectionEscape($m['file_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>

                        <?php if (!empty($section['image_id'])): ?>
                            <?php
                            $currentMedia = $conn->prepare("
                                SELECT file_path, alt_text
                                FROM media
                                WHERE media_id = ?
                            ");
                            $currentMedia->bind_param("i", $section['image_id']);
                            $currentMedia->execute();
                            $currentImage = $currentMedia->get_result()->fetch_assoc();
                            ?>

                            <?php if ($currentImage): ?>
                                <p class="form-hint">Current image:</p>
                                <img
                                    class="current-image"
                                    src="../uploads/<?= sectionEscape($currentImage['file_path']); ?>"
                                    alt="<?= sectionEscape($currentImage['alt_text'] ?? 'Current image'); ?>"
                                >
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="button_text">Button Text</label>
                        <input type="text" name="button_text" id="button_text"
                               maxlength="100"
                               value="<?= sectionEscape($button_text); ?>">
                    </div>

                    <div class="form-group">
                        <label for="button_link">Button Link</label>
                        <input type="text" name="button_link" id="button_link"
                               maxlength="500"
                               value="<?= sectionEscape($button_link); ?>">
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order"
                               value="<?= (int)$sort_order; ?>">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="is_visible" id="is_visible"
                               value="1" <?= $is_visible ? 'checked' : ''; ?>>
                        <label for="is_visible">Show this section on website</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Update Section
                        </button>

                        <a href="page_sections.php" class="cancel-btn">Cancel</a>
                    </div>

                </form>
            </div>
        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

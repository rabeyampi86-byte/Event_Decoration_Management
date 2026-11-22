
<?php
session_start();

include_once '../db.php';

// Admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get About Us page
$pageQuery = $conn->prepare("
    SELECT page_id
    FROM pages
    WHERE page_slug = 'about'
    LIMIT 1
");
$pageQuery->execute();
$pageResult = $pageQuery->get_result();
$page = $pageResult->fetch_assoc();

if (!$page) {
    die("About Us page not found.");
}

$pageId = (int)$page['page_id'];

$error = "";

// Save About sections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sectionIds = $_POST['section_id'] ?? [];
    $headings = $_POST['heading'] ?? [];
    $subheadings = $_POST['subheading'] ?? [];
    $contents = $_POST['content'] ?? [];
    $images = $_POST['image_id'] ?? [];
    $visibility = $_POST['is_visible'] ?? [];

    $update = $conn->prepare("
        UPDATE page_sections
        SET heading = ?,
            subheading = ?,
            content = ?,
            image_id = NULLIF(?, 0),
            is_visible = ?
        WHERE section_id = ?
        AND page_id = ?
    ");

    foreach ($sectionIds as $index => $sectionId) {

        $sectionId = (int)$sectionId;
        $heading = trim($headings[$index] ?? '');
        $subheading = trim($subheadings[$index] ?? '');
        $content = trim($contents[$index] ?? '');
        $imageId = (int)($images[$index] ?? 0);
        $isVisible = isset($visibility[$sectionId]) ? 1 : 0;

        $update->bind_param(
            "sssiiii",
            $heading,
            $subheading,
            $content,
            $imageId,
            $isVisible,
            $sectionId,
            $pageId
        );

        $update->execute();
    }

    header("Location: about.php?saved=1");
    exit;
}

// Get About sections
$sectionsQuery = $conn->prepare("
    SELECT *
    FROM page_sections
    WHERE page_id = ?
    ORDER BY sort_order ASC, section_id ASC
");
$sectionsQuery->bind_param("i", $pageId);
$sectionsQuery->execute();
$sections = $sectionsQuery->get_result();

// Get Media Library images
$mediaQuery = $conn->query("
    SELECT media_id, file_name, file_path
    FROM media
    WHERE file_type LIKE 'image/%'
    ORDER BY media_id DESC
");

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us Management | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .about-management {
            padding: 25px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            font-size: 25px;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #777;
        }

        .success-message {
            background: #e8f7ee;
            color: #187a42;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .section-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 23px;
            margin-bottom: 22px;
        }

        .section-card h3 {
            font-size: 19px;
            margin-bottom: 20px;
            padding-bottom: 13px;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .image-preview {
            margin-top: 12px;
        }

        .image-preview img {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 7px;
            border: 1px solid #ddd;
        }

        .visibility-option {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 15px;
        }

        .visibility-option input {
            width: auto;
        }

        .save-button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }

        .save-button:hover {
            background: #563797;
        }

        .empty-message {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="about-management">

            <div class="page-heading">
                <h2>About Us Management</h2>
                <p>Manage your company's introduction, mission and vision.</p>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="success-message">
                    <i class="fa-solid fa-circle-check"></i>
                    About Us information updated successfully.
                </div>
            <?php endif; ?>

            <?php if ($sections->num_rows > 0): ?>

                <form method="POST" action="">

                    <?php while ($section = $sections->fetch_assoc()): ?>

                        <div class="section-card">

                            <h3>
                                <i class="fa-solid fa-layer-group"></i>
                                <?= e($section['section_title']) ?>
                            </h3>

                            <input type="hidden"
                                   name="section_id[]"
                                   value="<?= (int)$section['section_id'] ?>">

                            <div class="form-group">
                                <label>Heading</label>
                                <input type="text"
                                       name="heading[]"
                                       value="<?= e($section['heading']) ?>">
                            </div>

                            <div class="form-group">
                                <label>Subheading</label>
                                <input type="text"
                                       name="subheading[]"
                                       value="<?= e($section['subheading']) ?>">
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="content[]"><?= e($section['content']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Select Image</label>

                                <select name="image_id[]">
                                    <option value="0">No Image</option>

                                    <?php
                                    $mediaQuery->data_seek(0);

                                    while ($media = $mediaQuery->fetch_assoc()):
                                    ?>
                                        <option
                                            value="<?= (int)$media['media_id'] ?>"
                                            <?= ((int)$section['image_id'] === (int)$media['media_id']) ? 'selected' : '' ?>
                                        >
                                            <?= e($media['file_name']) ?>
                                        </option>
                                    <?php endwhile; ?>

                                </select>

                                <?php if (!empty($section['image_id'])): ?>

                                    <?php
                                    $imageQuery = $conn->prepare("
                                        SELECT file_path
                                        FROM media
                                        WHERE media_id = ?
                                        LIMIT 1
                                    ");

                                    $imageQuery->bind_param(
                                        "i",
                                        $section['image_id']
                                    );

                                    $imageQuery->execute();
                                    $imageResult = $imageQuery->get_result();
                                    $image = $imageResult->fetch_assoc();
                                    ?>

                                    <?php if ($image): ?>
                                        <div class="image-preview">
                                            <img
                                                src="../<?= e($image['file_path']) ?>"
                                                alt="Section Image">
                                        </div>
                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>

                            <label class="visibility-option">
                                <input type="checkbox"
                                       name="is_visible[<?= (int)$section['section_id'] ?>]"
                                       value="1"
                                       <?= ((int)$section['is_visible'] === 1) ? 'checked' : '' ?>>

                                Show this section on website
                            </label>

                        </div>

                    <?php endwhile; ?>

                    <button type="submit" class="save-button">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>

                </form>

            <?php else: ?>

                <div class="empty-message">
                    No About Us sections found.
                </div>

            <?php endif; ?>

        </div>

        <?php include 'includes/footer.php'; ?>

    </div>

</div>

</body>
</html>

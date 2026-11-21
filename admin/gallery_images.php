
<?php
include_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function galleryImageEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

/* =========================
   Handle POST Requests
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ADD / UPDATE IMAGE */

    if ($action === 'save') {

        $galleryImageId = (int)($_POST['gallery_image_id'] ?? 0);
        $albumId = (int)($_POST['album_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $caption = trim($_POST['caption'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if ($albumId <= 0 || $imageId <= 0) {

            $error = 'Please select an album and an image.';

        } else {

            // Validate album
            $check = $conn->prepare("
                SELECT album_id
                FROM gallery_albums
                WHERE album_id = ?
            ");

            $check->bind_param("i", $albumId);
            $check->execute();

            $albumExists = $check->get_result()->fetch_assoc();
            $check->close();

            // Validate media image
            $check = $conn->prepare("
                SELECT media_id
                FROM media
                WHERE media_id = ?
            ");

            $check->bind_param("i", $imageId);
            $check->execute();

            $imageExists = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$albumExists) {

                $error = 'Selected album does not exist.';

            } elseif (!$imageExists) {

                $error = 'Selected media image does not exist.';

            } else {

                // Prevent duplicate image inside the same album
                $duplicate = $conn->prepare("
                    SELECT gallery_image_id
                    FROM gallery_images
                    WHERE album_id = ?
                    AND image_id = ?
                    AND gallery_image_id != ?
                ");

                $duplicate->bind_param(
                    "iii",
                    $albumId,
                    $imageId,
                    $galleryImageId
                );

                $duplicate->execute();
                $duplicateExists = $duplicate->get_result()->fetch_assoc();
                $duplicate->close();

                if ($duplicateExists) {

                    $error = 'This image already exists in the selected album.';

                } else {

                    $captionValue = ($caption === '') ? null : $caption;

                    if ($galleryImageId > 0) {

                        $stmt = $conn->prepare("
                            UPDATE gallery_images
                            SET album_id = ?,
                                image_id = ?,
                                caption = ?,
                                sort_order = ?,
                                is_visible = ?
                            WHERE gallery_image_id = ?
                        ");

                        $stmt->bind_param(
                            "iisiii",
                            $albumId,
                            $imageId,
                            $captionValue,
                            $sortOrder,
                            $isVisible,
                            $galleryImageId
                        );

                    } else {

                        $stmt = $conn->prepare("
                            INSERT INTO gallery_images
                            (
                                album_id,
                                image_id,
                                caption,
                                sort_order,
                                is_visible
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $stmt->bind_param(
                            "iisii",
                            $albumId,
                            $imageId,
                            $captionValue,
                            $sortOrder,
                            $isVisible
                        );
                    }

                    if ($stmt && $stmt->execute()) {

                        header("Location: gallery-images.php?album_id=" . $albumId . "&saved=1");
                        exit;

                    } else {

                        $error = 'Unable to save gallery image.';

                    }

                    if ($stmt) {
                        $stmt->close();
                    }
                }
            }
        }
    }

    /* DELETE IMAGE FROM GALLERY */

    elseif ($action === 'delete') {

        $galleryImageId = (int)($_POST['gallery_image_id'] ?? 0);
        $albumId = (int)($_POST['album_id'] ?? 0);

        $stmt = $conn->prepare("
            DELETE FROM gallery_images
            WHERE gallery_image_id = ?
        ");

        $stmt->bind_param("i", $galleryImageId);

        if ($stmt->execute()) {

            header("Location: gallery-images.php?album_id=" . $albumId . "&deleted=1");
            exit;

        } else {

            $error = 'Unable to remove gallery image.';

        }

        $stmt->close();
    }

    /* TOGGLE VISIBILITY */

    elseif ($action === 'toggle') {

        $galleryImageId = (int)($_POST['gallery_image_id'] ?? 0);
        $albumId = (int)($_POST['album_id'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE gallery_images
            SET is_visible = IF(is_visible = 1, 0, 1)
            WHERE gallery_image_id = ?
        ");

        $stmt->bind_param("i", $galleryImageId);

        if ($stmt->execute()) {

            header("Location: gallery-images.php?album_id=" . $albumId . "&updated=1");
            exit;

        } else {

            $error = 'Unable to update image visibility.';

        }

        $stmt->close();
    }
}

/* =========================
   Success Messages
========================= */

if (isset($_GET['saved'])) {
    $message = 'Gallery image saved successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Gallery image removed successfully.';
} elseif (isset($_GET['updated'])) {
    $message = 'Image visibility updated successfully.';
}

/* =========================
   Selected Album
========================= */

$selectedAlbumId = (int)($_GET['album_id'] ?? 0);
$selectedAlbum = null;

if ($selectedAlbumId > 0) {

    $stmt = $conn->prepare("
        SELECT *
        FROM gallery_albums
        WHERE album_id = ?
    ");

    $stmt->bind_param("i", $selectedAlbumId);
    $stmt->execute();

    $selectedAlbum = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$selectedAlbum) {
        $error = 'Selected album was not found.';
        $selectedAlbumId = 0;
    }
}

/* =========================
   Edit Gallery Image
========================= */

$editImage = null;

if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    $stmt = $conn->prepare("
        SELECT *
        FROM gallery_images
        WHERE gallery_image_id = ?
    ");

    $stmt->bind_param("i", $editId);
    $stmt->execute();

    $editImage = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editImage) {
        $selectedAlbumId = (int)$editImage['album_id'];

        $stmt = $conn->prepare("
            SELECT *
            FROM gallery_albums
            WHERE album_id = ?
        ");

        $stmt->bind_param("i", $selectedAlbumId);
        $stmt->execute();

        $selectedAlbum = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $error = 'Gallery image not found.';
    }
}

/* =========================
   Load Albums
========================= */

$albums = $conn->query("
    SELECT album_id, album_name, status
    FROM gallery_albums
    ORDER BY sort_order ASC, album_name ASC
");

/* =========================
   Load Media Library
========================= */

$mediaItems = $conn->query("
    SELECT media_id, file_name, file_path
    FROM media
    ORDER BY media_id DESC
");

/* =========================
   Image Statistics
========================= */

$stats = [
    'total' => 0,
    'visible' => 0,
    'hidden' => 0
];

$statsSql = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(is_visible = 1), 0) AS visible,
        COALESCE(SUM(is_visible = 0), 0) AS hidden
    FROM gallery_images
";

if ($selectedAlbumId > 0) {

    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(is_visible = 1), 0) AS visible,
            COALESCE(SUM(is_visible = 0), 0) AS hidden
        FROM gallery_images
        WHERE album_id = ?
    ");

    $stmt->bind_param("i", $selectedAlbumId);
    $stmt->execute();

    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} else {

    $statsResult = $conn->query($statsSql);

    if ($statsResult) {
        $stats = $statsResult->fetch_assoc();
    }
}

/* =========================
   Load Gallery Images
========================= */

if ($selectedAlbumId > 0) {

    $stmt = $conn->prepare("
        SELECT
            gi.*,
            a.album_name,
            m.file_name,
            m.file_path
        FROM gallery_images gi
        INNER JOIN gallery_albums a
            ON gi.album_id = a.album_id
        INNER JOIN media m
            ON gi.image_id = m.media_id
        WHERE gi.album_id = ?
        ORDER BY gi.sort_order ASC, gi.gallery_image_id DESC
    ");

    $stmt->bind_param("i", $selectedAlbumId);
    $stmt->execute();

    $galleryImages = $stmt->get_result();

} else {

    $galleryImages = $conn->query("
        SELECT
            gi.*,
            a.album_name,
            m.file_name,
            m.file_path
        FROM gallery_images gi
        INNER JOIN gallery_albums a
            ON gi.album_id = a.album_id
        INNER JOIN media m
            ON gi.image_id = m.media_id
        ORDER BY a.sort_order ASC, gi.sort_order ASC, gi.gallery_image_id DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gallery Images Management</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        .gallery-image-page {
            padding: 25px;
            background: #f5f7fb;
            min-height: 100vh;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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

        .content-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 25px;
        }

        .content-card h3 {
            color: #1e293b;
            font-size: 19px;
            margin: 0 0 22px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
        }

        .stat-card span {
            color: #64748b;
            font-size: 14px;
        }

        .stat-card h3 {
            color: #1e293b;
            font-size: 27px;
            margin: 10px 0 0;
        }

        .album-filter {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .album-filter .form-group {
            flex: 1;
            min-width: 220px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            outline: none;
            background: #fff;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 85px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .visibility-check {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #475569;
            font-size: 14px;
        }

        .visibility-check input {
            width: 16px;
            height: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-light {
            background: #eff6ff;
            color: #2563eb;
        }

        .alert {
            padding: 13px 16px;
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
            color: #991b1b;
        }

        .image-preview {
            display: none;
            width: 110px;
            height: 85px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .gallery-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .gallery-card-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
            background: #f1f5f9;
        }

        .gallery-card-body {
            padding: 15px;
        }

        .gallery-card-body h4 {
            color: #1e293b;
            margin: 0 0 7px;
            font-size: 15px;
        }

        .gallery-card-body p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 12px;
            min-height: 20px;
        }

        .gallery-card-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 13px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-visible {
            background: #dcfce7;
            color: #15803d;
        }

        .status-hidden {
            background: #f1f5f9;
            color: #64748b;
        }

        .gallery-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .gallery-actions form {
            margin: 0;
        }

        .gallery-actions .btn {
            padding: 8px 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 15px;
            color: #64748b;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 35px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        @media(max-width: 1000px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .gallery-image-page {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="gallery-image-page">

            <div class="page-heading">
                <div>
                    <h2>Gallery Images</h2>
                    <p>Manage images inside your event gallery albums.</p>
                </div>

                <a href="gallery-albums.php" class="btn btn-secondary">
                    <i class="fas fa-folder-open"></i>
                    Manage Albums
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo galleryImageEscape($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo galleryImageEscape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Album Filter -->

            <div class="content-card">

                <h3>Select Gallery Album</h3>

                <form method="GET" action="gallery-images.php" class="album-filter">

                    <div class="form-group">

                        <label>Choose Album</label>

                        <select name="album_id" required>

                            <option value="">Select an album</option>

                            <?php if ($albums): ?>
                                <?php while ($album = $albums->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo (int)$album['album_id']; ?>"
                                        <?php echo $selectedAlbumId === (int)$album['album_id'] ? 'selected' : ''; ?>>

                                        <?php
                                        echo galleryImageEscape($album['album_name']);

                                        if ($album['status'] === 'inactive') {
                                            echo ' (Inactive)';
                                        }
                                        ?>

                                    </option>

                                <?php endwhile; ?>
                            <?php endif; ?>

                        </select>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        View Album
                    </button>

                    <a href="gallery-images.php" class="btn btn-secondary">
                        Show All
                    </a>

                </form>

                <?php if ($selectedAlbum): ?>

                    <div style="color:#475569;font-size:14px;">
                        Selected Album:
                        <strong><?php echo galleryImageEscape($selectedAlbum['album_name']); ?></strong>
                    </div>

                <?php endif; ?>

            </div>

            <!-- Statistics -->

            <div class="stats-grid">

                <div class="stat-card">
                    <span>Total Gallery Images</span>
                    <h3><?php echo (int)$stats['total']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Visible Images</span>
                    <h3><?php echo (int)$stats['visible']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Hidden Images</span>
                    <h3><?php echo (int)$stats['hidden']; ?></h3>
                </div>

            </div>

            <!-- Add / Edit Image -->

            <div class="content-card">

                <h3>
                    <?php echo $editImage ? 'Edit Gallery Image' : 'Add Image to Gallery'; ?>
                </h3>

                <form method="POST" action="gallery-images.php">

                    <input type="hidden" name="action" value="save">

                    <input type="hidden"
                           name="gallery_image_id"
                           value="<?php echo (int)($editImage['gallery_image_id'] ?? 0); ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Select Album *</label>

                            <select name="album_id" required>

                                <option value="">Choose Album</option>

                                <?php
                                $albumOptions = $conn->query("
                                    SELECT album_id, album_name, status
                                    FROM gallery_albums
                                    ORDER BY sort_order ASC, album_name ASC
                                ");
                                ?>

                                <?php if ($albumOptions): ?>
                                    <?php while ($album = $albumOptions->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$album['album_id']; ?>"
                                            <?php echo (int)($editImage['album_id'] ?? $selectedAlbumId) === (int)$album['album_id'] ? 'selected' : ''; ?>>

                                            <?php
                                            echo galleryImageEscape($album['album_name']);

                                            if ($album['status'] === 'inactive') {
                                                echo ' (Inactive)';
                                            }
                                            ?>

                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Image from Media Library *</label>

                            <select name="image_id"
                                    id="mediaImageSelect"
                                    required
                                    onchange="previewGalleryImage()">

                                <option value="">Choose Image</option>

                                <?php if ($mediaItems): ?>
                                    <?php while ($media = $mediaItems->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$media['media_id']; ?>"
                                            data-image="<?php echo galleryImageEscape($media['file_path']); ?>"
                                            <?php echo (int)($editImage['image_id'] ?? 0) === (int)$media['media_id'] ? 'selected' : ''; ?>>

                                            <?php echo galleryImageEscape($media['file_name']); ?>

                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>

                            <img id="galleryImagePreview"
                                 class="image-preview"
                                 alt="Selected gallery image">

                        </div>

                        <div class="form-group full">
                            <label>Image Caption</label>

                            <textarea name="caption"
                                      maxlength="255"
                                      placeholder="Write a short image caption..."><?php echo galleryImageEscape($editImage['caption'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>

                            <input type="number"
                                   name="sort_order"
                                   value="<?php echo (int)($editImage['sort_order'] ?? 0); ?>">

                        </div>

                        <div class="form-group">

                            <label class="visibility-check">

                                <input type="checkbox"
                                       name="is_visible"
                                       value="1"
                                       <?php echo !isset($editImage) || (int)$editImage['is_visible'] === 1 ? 'checked' : ''; ?>>

                                Show this image on the website

                            </label>

                        </div>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>

                            <?php echo $editImage ? 'Update Image' : 'Add to Gallery'; ?>
                        </button>

                        <?php if ($editImage): ?>

                            <a href="gallery-images.php?album_id=<?php echo (int)$selectedAlbumId; ?>"
                               class="btn btn-secondary">
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

            <!-- Gallery Image Cards -->

            <div class="content-card">

                <h3>
                    <?php echo $selectedAlbum
                        ? 'Images in ' . galleryImageEscape($selectedAlbum['album_name'])
                        : 'All Gallery Images'; ?>
                </h3>

                <div class="gallery-grid">

                    <?php if ($galleryImages && $galleryImages->num_rows > 0): ?>

                        <?php while ($image = $galleryImages->fetch_assoc()): ?>

                            <div class="gallery-card">

                                <img
                                    src="<?php echo galleryImageEscape($image['file_path']); ?>"
                                    class="gallery-card-image"
                                    alt="<?php echo galleryImageEscape($image['caption'] ?: $image['file_name']); ?>">

                                <div class="gallery-card-body">

                                    <h4>
                                        <?php echo galleryImageEscape($image['file_name']); ?>
                                    </h4>

                                    <p>
                                        <?php echo galleryImageEscape($image['caption'] ?: 'No caption added'); ?>
                                    </p>

                                    <div style="font-size:12px;color:#64748b;margin-bottom:12px;">
                                        Album:
                                        <strong><?php echo galleryImageEscape($image['album_name']); ?></strong>
                                    </div>

                                    <div class="gallery-card-meta">

                                        <span>
                                            Order: <?php echo (int)$image['sort_order']; ?>
                                        </span>

                                        <?php if ((int)$image['is_visible'] === 1): ?>

                                            <span class="status status-visible">
                                                Visible
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-hidden">
                                                Hidden
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="gallery-actions">

                                        <a href="gallery-images.php?edit=<?php echo (int)$image['gallery_image_id']; ?>"
                                           class="btn btn-light"
                                           title="Edit">

                                            <i class="fas fa-edit"></i>
                                            Edit

                                        </a>

                                        <form method="POST" action="gallery-images.php">

                                            <input type="hidden"
                                                   name="action"
                                                   value="toggle">

                                            <input type="hidden"
                                                   name="gallery_image_id"
                                                   value="<?php echo (int)$image['gallery_image_id']; ?>">

                                            <input type="hidden"
                                                   name="album_id"
                                                   value="<?php echo (int)$image['album_id']; ?>">

                                            <button type="submit"
                                                    class="btn btn-light"
                                                    title="Change Visibility">

                                                <i class="fas fa-eye"></i>

                                            </button>

                                        </form>

                                        <form method="POST"
                                              action="gallery-images.php"
                                              onsubmit="return confirm('Remove this image from the gallery? The original media file will not be deleted.');">

                                            <input type="hidden"
                                                   name="action"
                                                   value="delete">

                                            <input type="hidden"
                                                   name="gallery_image_id"
                                                   value="<?php echo (int)$image['gallery_image_id']; ?>">

                                            <input type="hidden"
                                                   name="album_id"
                                                   value="<?php echo (int)$image['album_id']; ?>">

                                            <button type="submit"
                                                    class="btn btn-danger"
                                                    title="Remove Image">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="empty-state">

                            <i class="fas fa-images"></i>

                            <div>
                                No gallery images found.
                            </div>

                            <p>
                                Select an album and add images from your Media Library.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

<script>
function previewGalleryImage() {

    const select = document.getElementById('mediaImageSelect');
    const preview = document.getElementById('galleryImagePreview');

    const selectedOption = select.options[select.selectedIndex];
    const imagePath = selectedOption.getAttribute('data-image');

    if (imagePath) {
        preview.src = imagePath;
        preview.style.display = 'block';
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    previewGalleryImage();
});
</script>

</body>
</html>

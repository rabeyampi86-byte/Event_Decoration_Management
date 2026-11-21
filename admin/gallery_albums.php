
<?php
include_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function galleryAlbumEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

/* =========================
   Handle Form Submission
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ADD / UPDATE ALBUM */
    if ($action === 'save') {

        $albumId = (int)($_POST['album_id'] ?? 0);
        $albumName = trim($_POST['album_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $coverImageId = (int)($_POST['cover_image_id'] ?? 0);
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($albumName === '') {
            $error = 'Album name is required.';
        } elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = 'Invalid album status.';
        } else {

            // Validate selected category
            if ($categoryId > 0) {
                $check = $conn->prepare("
                    SELECT category_id
                    FROM event_categories
                    WHERE category_id = ?
                ");
                $check->bind_param("i", $categoryId);
                $check->execute();

                if (!$check->get_result()->fetch_assoc()) {
                    $error = 'Selected category does not exist.';
                }

                $check->close();
            }

            // Validate selected cover image
            if ($error === '' && $coverImageId > 0) {
                $check = $conn->prepare("
                    SELECT media_id
                    FROM media
                    WHERE media_id = ?
                ");
                $check->bind_param("i", $coverImageId);
                $check->execute();

                if (!$check->get_result()->fetch_assoc()) {
                    $error = 'Selected cover image does not exist.';
                }

                $check->close();
            }

            if ($error === '') {

                $descriptionValue = ($description === '') ? null : $description;
                $categoryValue = $categoryId;
                $coverValue = $coverImageId;

                if ($albumId > 0) {

                    $stmt = $conn->prepare("
                        UPDATE gallery_albums
                        SET album_name = ?,
                            description = ?,
                            category_id = NULLIF(?, 0),
                            cover_image_id = NULLIF(?, 0),
                            sort_order = ?,
                            status = ?
                        WHERE album_id = ?
                    ");

                    $stmt->bind_param(
                        "ssiiisi",
                        $albumName,
                        $descriptionValue,
                        $categoryValue,
                        $coverValue,
                        $sortOrder,
                        $status,
                        $albumId
                    );

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO gallery_albums
                        (
                            album_name,
                            description,
                            category_id,
                            cover_image_id,
                            sort_order,
                            status
                        )
                        VALUES (?, ?, NULLIF(?, 0), NULLIF(?, 0), ?, ?)
                    ");

                    $stmt->bind_param(
                        "ssiiis",
                        $albumName,
                        $descriptionValue,
                        $categoryValue,
                        $coverValue,
                        $sortOrder,
                        $status
                    );
                }

                if ($stmt && $stmt->execute()) {
                    header("Location: gallery-albums.php?saved=1");
                    exit;
                } else {
                    $error = 'Unable to save album. Please check the database.';
                }

                if ($stmt) {
                    $stmt->close();
                }
            }
        }
    }

    /* DELETE ALBUM */
    elseif ($action === 'delete') {

        $albumId = (int)($_POST['album_id'] ?? 0);

        if ($albumId <= 0) {
            $error = 'Invalid album selected.';
        } else {

            // Do not delete an album that still contains gallery images.
            $check = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM gallery_images
                WHERE album_id = ?
            ");

            $check->bind_param("i", $albumId);
            $check->execute();

            $imageCount = (int)$check->get_result()->fetch_assoc()['total'];
            $check->close();

            if ($imageCount > 0) {

                $error = 'This album contains ' . $imageCount .
                         ' image(s). Remove or move those images before deleting the album.';

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM gallery_albums
                    WHERE album_id = ?
                ");

                $stmt->bind_param("i", $albumId);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    header("Location: gallery-albums.php?deleted=1");
                    exit;
                } else {
                    $error = 'Unable to delete album.';
                }

                $stmt->close();
            }
        }
    }

    /* CHANGE ALBUM STATUS */
    elseif ($action === 'toggle_status') {

        $albumId = (int)($_POST['album_id'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE gallery_albums
            SET status = IF(status = 'active', 'inactive', 'active')
            WHERE album_id = ?
        ");

        $stmt->bind_param("i", $albumId);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            header("Location: gallery-albums.php?updated=1");
            exit;
        } else {
            $error = 'Unable to update album status.';
        }

        $stmt->close();
    }
}

/* =========================
   Success Messages
========================= */

if (isset($_GET['saved'])) {
    $message = 'Album saved successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Album deleted successfully.';
} elseif (isset($_GET['updated'])) {
    $message = 'Album status updated successfully.';
}

/* =========================
   Load Album for Editing
========================= */

$editAlbum = null;

if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    $stmt = $conn->prepare("
        SELECT *
        FROM gallery_albums
        WHERE album_id = ?
    ");

    $stmt->bind_param("i", $editId);
    $stmt->execute();

    $editAlbum = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$editAlbum) {
        $error = 'Album not found.';
    }
}

/* =========================
   Load Categories
========================= */

$categories = $conn->query("
    SELECT category_id, name, status
    FROM event_categories
    ORDER BY sort_order ASC, name ASC
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
   Album Statistics
========================= */

$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

$statsResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'active'), 0) AS active,
        COALESCE(SUM(status = 'inactive'), 0) AS inactive
    FROM gallery_albums
");

if ($statsResult) {
    $stats = $statsResult->fetch_assoc();
}

/* =========================
   Load Albums
========================= */

$albums = $conn->query("
    SELECT
        a.*,
        c.name AS category_name,
        m.file_name AS cover_file_name,
        m.file_path AS cover_file_path,
        (
            SELECT COUNT(*)
            FROM gallery_images gi
            WHERE gi.album_id = a.album_id
        ) AS image_count
    FROM gallery_albums a
    LEFT JOIN event_categories c
        ON a.category_id = c.category_id
    LEFT JOIN media m
        ON a.cover_image_id = m.media_id
    ORDER BY a.sort_order ASC, a.album_id DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gallery Albums Management</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        .gallery-page {
            padding: 25px;
            background: #f5f7fb;
            min-height: 100vh;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 22px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
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

        .content-card {
            background: #fff;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .content-card h3 {
            color: #1e293b;
            font-size: 19px;
            margin: 0 0 22px;
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
            font-family: inherit;
            font-size: 14px;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
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

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .album-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .album-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .album-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
            vertical-align: middle;
        }

        .album-cover {
            width: 65px;
            height: 48px;
            object-fit: cover;
            border-radius: 7px;
            background: #f1f5f9;
        }

        .no-cover {
            width: 65px;
            height: 48px;
            border-radius: 7px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-buttons form {
            margin: 0;
        }

        .action-buttons .btn {
            padding: 8px 10px;
        }

        .cover-preview {
            display: none;
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
        }

        .album-description {
            max-width: 220px;
            line-height: 1.5;
        }

        @media(max-width: 768px) {
            .gallery-page {
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
        }
    </style>

</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="gallery-page">

            <div class="page-heading">
                <div>
                    <h2>Gallery Albums</h2>
                    <p>Create and manage event gallery albums.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo galleryAlbumEscape($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo galleryAlbumEscape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->

            <div class="stats-grid">

                <div class="stat-card">
                    <span>Total Albums</span>
                    <h3><?php echo (int)$stats['total']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Active Albums</span>
                    <h3><?php echo (int)$stats['active']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Inactive Albums</span>
                    <h3><?php echo (int)$stats['inactive']; ?></h3>
                </div>

            </div>

            <!-- Add / Edit Album -->

            <div class="content-card">

                <h3>
                    <?php echo $editAlbum ? 'Edit Album' : 'Add New Album'; ?>
                </h3>

                <form method="POST" action="gallery-albums.php">

                    <input type="hidden" name="action" value="save">

                    <input type="hidden"
                           name="album_id"
                           value="<?php echo (int)($editAlbum['album_id'] ?? 0); ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Album Name *</label>

                            <input type="text"
                                   name="album_name"
                                   maxlength="180"
                                   required
                                   value="<?php echo galleryAlbumEscape($editAlbum['album_name'] ?? ''); ?>"
                                   placeholder="Enter album name">
                        </div>

                        <div class="form-group">
                            <label>Event Category</label>

                            <select name="category_id">

                                <option value="0">Select Category</option>

                                <?php if ($categories): ?>
                                    <?php while ($category = $categories->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$category['category_id']; ?>"
                                            <?php echo (int)($editAlbum['category_id'] ?? 0) === (int)$category['category_id'] ? 'selected' : ''; ?>>

                                            <?php
                                            echo galleryAlbumEscape($category['name']);

                                            if ($category['status'] === 'inactive') {
                                                echo ' (Inactive)';
                                            }
                                            ?>

                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>
                        </div>

                        <div class="form-group full">
                            <label>Description</label>

                            <textarea name="description"
                                      placeholder="Write a short album description..."><?php echo galleryAlbumEscape($editAlbum['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Cover Image</label>

                            <select name="cover_image_id"
                                    id="coverImageSelect"
                                    onchange="previewCoverImage()">

                                <option value="0">No Cover Image</option>

                                <?php if ($mediaItems): ?>
                                    <?php while ($media = $mediaItems->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$media['media_id']; ?>"
                                            data-image="<?php echo galleryAlbumEscape($media['file_path']); ?>"
                                            <?php echo (int)($editAlbum['cover_image_id'] ?? 0) === (int)$media['media_id'] ? 'selected' : ''; ?>>

                                            <?php echo galleryAlbumEscape($media['file_name']); ?>

                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>

                            <img id="coverPreview"
                                 class="cover-preview"
                                 alt="Album cover preview">
                        </div>

                        <div class="form-group">
                            <label>Sort Order</label>

                            <input type="number"
                                   name="sort_order"
                                   value="<?php echo (int)($editAlbum['sort_order'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>

                            <select name="status">

                                <option value="active"
                                    <?php echo ($editAlbum['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>
                                    Active
                                </option>

                                <option value="inactive"
                                    <?php echo ($editAlbum['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                    Inactive
                                </option>

                            </select>
                        </div>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>

                            <?php echo $editAlbum ? 'Update Album' : 'Save Album'; ?>
                        </button>

                        <?php if ($editAlbum): ?>

                            <a href="gallery-albums.php" class="btn btn-secondary">
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

            <!-- Album List -->

            <div class="content-card">

                <h3>All Albums</h3>

                <div class="table-wrapper">

                    <table class="album-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cover</th>
                                <th>Album Name</th>
                                <th>Category</th>
                                <th>Images</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($albums && $albums->num_rows > 0): ?>

                            <?php while ($album = $albums->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$album['album_id']; ?>
                                    </td>

                                    <td>

                                        <?php if (!empty($album['cover_file_path'])): ?>

                                            <img
                                                src="<?php echo galleryAlbumEscape($album['cover_file_path']); ?>"
                                                class="album-cover"
                                                alt="Album cover">

                                        <?php else: ?>

                                            <div class="no-cover">
                                                <i class="fas fa-images"></i>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo galleryAlbumEscape($album['album_name']); ?>
                                        </strong>

                                        <?php if (!empty($album['description'])): ?>
                                            <div class="album-description"
                                                 style="font-size:12px;color:#64748b;margin-top:5px;">

                                                <?php
                                                $descriptionText = $album['description'];

                                                echo galleryAlbumEscape(
                                                    strlen($descriptionText) > 65
                                                        ? substr($descriptionText, 0, 65) . '...'
                                                        : $descriptionText
                                                );
                                                ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo galleryAlbumEscape($album['category_name'] ?: 'Not assigned'); ?>
                                    </td>

                                    <td>
                                        <?php echo (int)$album['image_count']; ?>
                                    </td>

                                    <td>
                                        <?php echo (int)$album['sort_order']; ?>
                                    </td>

                                    <td>

                                        <?php if ($album['status'] === 'active'): ?>

                                            <span class="status status-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php echo date('d M Y', strtotime($album['created_at'])); ?>
                                    </td>

                                    <td>

                                        <div class="action-buttons">

                                            <a href="gallery-images.php?album_id=<?php echo (int)$album['album_id']; ?>"
                                               class="btn btn-light"
                                               title="Manage Images">

                                                <i class="fas fa-images"></i>

                                            </a>

                                            <a href="gallery-albums.php?edit=<?php echo (int)$album['album_id']; ?>"
                                               class="btn btn-light"
                                               title="Edit">

                                                <i class="fas fa-edit"></i>

                                            </a>

                                            <form method="POST" action="gallery-albums.php">

                                                <input type="hidden"
                                                       name="action"
                                                       value="toggle_status">

                                                <input type="hidden"
                                                       name="album_id"
                                                       value="<?php echo (int)$album['album_id']; ?>">

                                                <button type="submit"
                                                        class="btn btn-light"
                                                        title="Change Status">

                                                    <i class="fas fa-toggle-on"></i>

                                                </button>

                                            </form>

                                            <form method="POST"
                                                  action="gallery-albums.php"
                                                  onsubmit="return confirm('Delete this album? Albums containing images cannot be deleted.');">

                                                <input type="hidden"
                                                       name="action"
                                                       value="delete">

                                                <input type="hidden"
                                                       name="album_id"
                                                       value="<?php echo (int)$album['album_id']; ?>">

                                                <button type="submit"
                                                        class="btn btn-danger"
                                                        title="Delete">

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" style="text-align:center;padding:30px;">
                                    No albums found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

<script>
function previewCoverImage() {

    const select = document.getElementById('coverImageSelect');
    const preview = document.getElementById('coverPreview');

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
    previewCoverImage();
});
</script>

</body>
</html>

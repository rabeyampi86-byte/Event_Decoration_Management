
<?php
require_once __DIR__ . '/../db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$mediaId = (int)($_GET['media_id'] ?? 0);

if ($mediaId <= 0) {
    header('Location: media.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM media WHERE media_id = ?");
$stmt->bind_param("i", $mediaId);
$stmt->execute();
$result = $stmt->get_result();
$media = $result->fetch_assoc();
$stmt->close();

if (!$media) {
    header('Location: media.php');
    exit;
}

$errors = [];

$fileName = pathinfo($media['file_name'], PATHINFO_FILENAME);
$altText = $media['alt_text'] ?? '';

$uploadDir = __DIR__ . '/../uploads/media/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fileName = trim($_POST['file_name'] ?? '');
    $altText = trim($_POST['alt_text'] ?? '');

    if ($fileName === '') {
        $errors[] = 'Please enter a file name.';
    }

    $newPath = $media['file_path'];
    $newType = $media['file_type'];
    $newStoredName = $media['file_name'];
    $newUploadedFile = null;

    $hasNewFile = isset($_FILES['media_file']) &&
                  $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($hasNewFile) {

        $file = $_FILES['media_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The new image could not be uploaded.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image size must not exceed 5 MB.';
        } else {

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!isset($allowedTypes[$mimeType])) {
                $errors[] = 'Only JPG, PNG and WEBP images are allowed.';
            } else {
                $extension = $allowedTypes[$mimeType];

                $newStoredName = $fileName . '.' . $extension;
                $newPath = 'media/' . bin2hex(random_bytes(16)) . '.' . $extension;
                $newType = $mimeType;
                $newUploadedFile = $uploadDir . basename($newPath);
            }
        }
    }

    if (!$errors) {

        if ($hasNewFile) {

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $newUploadedFile)) {
                $errors[] = 'Failed to save the new image.';
            }
        }

        if (!$errors) {

            $stmt = $conn->prepare("
                UPDATE media
                SET file_name = ?, file_path = ?, file_type = ?, alt_text = ?
                WHERE media_id = ?
            ");

            if (!$stmt) {

                $errors[] = 'Database query could not be prepared.';

            } else {

                $stmt->bind_param(
                    "ssssi",
                    $newStoredName,
                    $newPath,
                    $newType,
                    $altText,
                    $mediaId
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    if ($hasNewFile) {

                        $oldFile = realpath(__DIR__ . '/../uploads/' . $media['file_path']);
                        $mediaDirectory = realpath($uploadDir);

                        if (
                            $oldFile &&
                            $mediaDirectory &&
                            strpos($oldFile, $mediaDirectory . DIRECTORY_SEPARATOR) === 0 &&
                            is_file($oldFile)
                        ) {
                            unlink($oldFile);
                        }
                    }

                    header('Location: view_media.php?media_id=' . $mediaId . '&updated=success');
                    exit;

                } else {

                    $errors[] = 'Failed to update media information.';
                    $stmt->close();

                    if ($hasNewFile && is_file($newUploadedFile)) {
                        unlink($newUploadedFile);
                    }
                }
            }

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                // Statement already closed after execution.
            }
        }
    }
}

$currentImage = '../uploads/' . $media['file_path'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Media | Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .edit-media-page {
            padding: 25px;
        }

        .edit-heading {
            margin-bottom: 25px;
        }

        .edit-heading h2 {
            color: #1e293b;
            margin: 0 0 7px;
        }

        .edit-heading p {
            color: #64748b;
            margin: 0;
        }

        .edit-media-card {
            max-width: 750px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .current-image {
            width: 200px;
            height: 155px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 22px;
            background: #f1f5f9;
        }

        .current-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 95px;
            resize: vertical;
        }

        .file-hint {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-top: 7px;
        }

        .new-preview {
            display: none;
            width: 200px;
            height: 155px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .new-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .error-box {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="edit-media-page">

            <div class="edit-heading">
                <h2>Edit Media</h2>
                <p>Update image information or replace the image.</p>
            </div>

            <div class="edit-media-card">

                <?php if ($errors): ?>
                    <div class="error-box">
                        <strong>Please fix the following:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <label style="display:block;font-weight:600;margin-bottom:10px;color:#334155;">
                    Current Image
                </label>

                <?php if (strpos((string)$media['file_type'], 'image/') === 0): ?>
                    <div class="current-image">
                        <img src="<?php echo e($currentImage); ?>"
                             alt="<?php echo e($media['alt_text']); ?>">
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="file_name">Image Name *</label>

                        <input
                            type="text"
                            id="file_name"
                            name="file_name"
                            value="<?php echo e($fileName); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="media_file">Replace Image (Optional)</label>

                        <input
                            type="file"
                            id="media_file"
                            name="media_file"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <span class="file-hint">
                            JPG, PNG or WEBP. Maximum size: 5 MB.
                            Leave empty to keep the current image.
                        </span>

                        <div class="new-preview" id="newPreview">
                            <img id="previewImage" src="" alt="New image preview">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="alt_text">Alt Text</label>

                        <textarea
                            id="alt_text"
                            name="alt_text"
                        ><?php echo e($altText); ?></textarea>
                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Update Media
                        </button>

                        <a
                            href="view_media.php?media_id=<?php echo $mediaId; ?>"
                            class="btn-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('media_file').addEventListener('change', function () {

    const file = this.files[0];
    const preview = document.getElementById('newPreview');
    const previewImage = document.getElementById('previewImage');

    if (file && file.type.startsWith('image/')) {
        previewImage.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        previewImage.src = '';
        preview.style.display = 'none';
    }
});
</script>

</body>
</html>

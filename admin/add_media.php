
<?php
require_once '../db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = '';

$fileName = '';
$altText = '';

$uploadDir = __DIR__ . '/../uploads/media/';
$databasePath = 'media/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fileName = trim($_POST['file_name'] ?? '');
    $altText = trim($_POST['alt_text'] ?? '');

    if ($fileName === '') {
        $errors[] = 'Please enter a file name.';
    }

    if (!isset($_FILES['media_file']) ||
        $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {

        $errors[] = 'Please select an image to upload.';

    } else {

        $file = $_FILES['media_file'];

        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $errors[] = 'Image size must not exceed 5 MB.';
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Only JPG, PNG and WEBP images are allowed.';
        }

        if (!$errors) {

            $extension = $allowedTypes[$mimeType];

            $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;

            $destination = $uploadDir . $newFileName;

            $relativePath = $databasePath . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {

                $stmt = $conn->prepare("
                    INSERT INTO media
                    (file_name, file_path, file_type, alt_text)
                    VALUES (?, ?, ?, ?)
                ");

                if (!$stmt) {

                    $errors[] = 'Database query could not be prepared.';

                    if (is_file($destination)) {
                        unlink($destination);
                    }

                } else {

                    $storedFileName = $fileName . '.' . $extension;

                    $stmt->bind_param(
                        "ssss",
                        $storedFileName,
                        $relativePath,
                        $mimeType,
                        $altText
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        header('Location: media.php?upload=success');
                        exit;

                    } else {

                        $errors[] = 'Failed to save media information.';

                        if (is_file($destination)) {
                            unlink($destination);
                        }

                        $stmt->close();
                    }
                }

            } else {
                $errors[] = 'Image upload failed. Please check folder permissions.';
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

    <title>Upload Media | Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .upload-page {
            padding: 25px;
        }

        .upload-heading {
            margin-bottom: 25px;
        }

        .upload-heading h2 {
            color: #1e293b;
            margin: 0 0 7px;
        }

        .upload-heading p {
            color: #64748b;
            margin: 0;
        }

        .upload-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
            max-width: 750px;
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
            resize: vertical;
            min-height: 95px;
        }

        .file-hint {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-top: 7px;
        }

        .image-preview {
            display: none;
            margin-top: 15px;
            width: 180px;
            height: 140px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 11px 19px;
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

        @media(max-width: 600px) {
            .upload-page {
                padding: 15px;
            }

            .upload-card {
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

        <section class="upload-page">

            <div class="upload-heading">
                <h2>Upload Media</h2>
                <p>Add a new image to your website media library.</p>
            </div>

            <div class="upload-card">

                <?php if (!empty($errors)): ?>

                    <div class="error-box">
                        <strong>Please fix the following:</strong>

                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="file_name">Image Name *</label>

                        <input
                            type="text"
                            id="file_name"
                            name="file_name"
                            placeholder="Enter image name"
                            value="<?php echo e($fileName); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="media_file">Select Image *</label>

                        <input
                            type="file"
                            id="media_file"
                            name="media_file"
                            accept=".jpg,.jpeg,.png,.webp"
                            required
                        >

                        <span class="file-hint">
                            Allowed formats: JPG, JPEG, PNG, WEBP. Maximum size: 5 MB.
                        </span>

                        <div class="image-preview" id="imagePreview">
                            <img id="previewImage" src="" alt="Image Preview">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="alt_text">Alt Text</label>

                        <textarea
                            id="alt_text"
                            name="alt_text"
                            placeholder="Describe the image for accessibility and SEO..."
                        ><?php echo e($altText); ?></textarea>
                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>

                        <a href="media.php" class="btn-secondary">
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
    const preview = document.getElementById('imagePreview');
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

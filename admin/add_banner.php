<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

function bannerEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

$title = '';
$subtitle = '';
$button_text = '';
$button_link = '';
$sort_order = 0;
$is_active = 1;
$selected_image = '';

/* =====================================
   Fetch Media Library Images
===================================== */

$mediaImages = [];

$mediaQuery = $conn->query("
    SELECT media_id, file_name, file_path, alt_text
    FROM media
    ORDER BY media_id DESC
");

if ($mediaQuery) {
    while ($row = $mediaQuery->fetch_assoc()) {
        $mediaImages[] = $row;
    }
}

/* =====================================
   Add Banner
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $selected_image = !empty($_POST['image_id'])
        ? (int)$_POST['image_id']
        : null;

    $uploadedFile = isset($_FILES['banner_image'])
        ? $_FILES['banner_image']
        : null;

    $hasNewUpload = (
        $uploadedFile &&
        $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE
    );

    $uploadedPath = null;
    $newMediaId = null;

    try {

        // Both image options cannot be used together
        if ($hasNewUpload && $selected_image !== null) {
            throw new Exception(
                "Select an image from Media Library OR upload a new image, not both."
            );
        }

        // Validate selected Media Library image
        if ($selected_image !== null) {

            $checkImage = $conn->prepare("
                SELECT media_id
                FROM media
                WHERE media_id = ?
                LIMIT 1
            ");

            $checkImage->bind_param("i", $selected_image);
            $checkImage->execute();

            $imageResult = $checkImage->get_result();

            if ($imageResult->num_rows === 0) {
                throw new Exception("Selected Media Library image was not found.");
            }

            $checkImage->close();
        }

        // Upload a new image
        if ($hasNewUpload) {

            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Image upload failed. Please try again.");
            }

            // Maximum file size: 5 MB
            if ($uploadedFile['size'] > 5 * 1024 * 1024) {
                throw new Exception("Image size must not exceed 5 MB.");
            }

            // Validate actual image MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($uploadedFile['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mimeType])) {
                throw new Exception(
                    "Only JPG, JPEG, PNG and WEBP images are allowed."
                );
            }

            // Create upload directory if it does not exist
            $uploadDirectory = __DIR__ . '/../uploads/banners/';

            if (!is_dir($uploadDirectory)) {
                if (!mkdir($uploadDirectory, 0755, true)) {
                    throw new Exception("Could not create the banner upload folder.");
                }
            }

            if (!is_writable($uploadDirectory)) {
                throw new Exception("Banner upload folder is not writable.");
            }

            // Generate a unique file name
            $extension = $allowedTypes[$mimeType];
            $newFileName = 'banner_' . bin2hex(random_bytes(16)) . '.' . $extension;

            $absolutePath = $uploadDirectory . $newFileName;

            // Store project-root-relative path in database
            $uploadedPath = 'uploads/banners/' . $newFileName;

            if (!move_uploaded_file($uploadedFile['tmp_name'], $absolutePath)) {
                throw new Exception("Could not save the uploaded image.");
            }

            // Insert image information into Media Library
            $conn->begin_transaction();

            $fileName = $uploadedFile['name'];
            $fileType = $mimeType;
            $altText = $title !== '' ? $title : 'Banner Image';
            $uploadedBy = (int)$_SESSION['admin_id'];

            $mediaStmt = $conn->prepare("
                INSERT INTO media
                (file_name, file_path, file_type, alt_text, uploaded_by)
                VALUES (?, ?, ?, ?, ?)
            ");

            $mediaStmt->bind_param(
                "ssssi",
                $fileName,
                $uploadedPath,
                $fileType,
                $altText,
                $uploadedBy
            );

            if (!$mediaStmt->execute()) {
                throw new Exception("Could not add image to Media Library.");
            }

            $newMediaId = $conn->insert_id;
            $mediaStmt->close();

            $selected_image = $newMediaId;
        }

        // Insert Banner record
        $stmt = $conn->prepare("
            INSERT INTO banners
            (
                title,
                subtitle,
                image_id,
                button_text,
                button_link,
                sort_order,
                is_active
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssissii",
            $title,
            $subtitle,
            $selected_image,
            $button_text,
            $button_link,
            $sort_order,
            $is_active
        );

        if (!$stmt->execute()) {
            throw new Exception("Could not add the banner.");
        }

        $stmt->close();

        // Commit transaction if a new image was uploaded
        if ($hasNewUpload) {
            $conn->commit();
        }

        header("Location: banners.php?success=added");
        exit;

    } catch (Exception $e) {

        // Roll back database changes if a new image was uploaded
        if ($hasNewUpload) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                // Ignore rollback errors
            }

            if ($uploadedPath !== null) {
                $fileToDelete = __DIR__ . '/../' . $uploadedPath;

                if (file_exists($fileToDelete)) {
                    unlink($fileToDelete);
                }
            }
        }

        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Banner | Admin Panel</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content {
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

        .back-btn {
            background: #64748b;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #475569;
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
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
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
            background: #fff;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .image-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .image-section h3 {
            color: #1e293b;
            font-size: 16px;
            margin: 0 0 15px;
        }

        .or-divider {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            margin: 18px 0;
        }

        .help-text {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-top: 7px;
        }

        .status-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .status-option input {
            width: 17px;
            height: 17px;
        }

        .status-option label {
            color: #334155;
            font-size: 14px;
        }

        .submit-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .submit-btn:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        @media(max-width: 650px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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

        <section class="page-content">

            <div class="page-heading">
                <div>
                    <h2>Add New Banner</h2>
                    <p>Create a new homepage banner.</p>
                </div>

                <a href="banners.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Banners
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= bannerEscape($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-card">

                <form method="POST"
                      action=""
                      enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="title">Banner Title</label>

                        <input type="text"
                               id="title"
                               name="title"
                               maxlength="255"
                               value="<?= bannerEscape($title); ?>"
                               placeholder="Enter banner title">
                    </div>

                    <div class="form-group">
                        <label for="subtitle">Banner Subtitle</label>

                        <textarea id="subtitle"
                                  name="subtitle"
                                  placeholder="Enter banner description"><?= bannerEscape($subtitle); ?></textarea>
                    </div>

                    <!-- IMAGE SELECTION -->
                    <div class="image-section">

                        <h3>
                            <i class="fas fa-images"></i>
                            Banner Image
                        </h3>

                        <div class="form-group">
                            <label for="image_id">
                                Select from Media Library
                            </label>

                            <select name="image_id" id="image_id">

                                <option value="">
                                    -- Choose an Existing Image --
                                </option>

                                <?php foreach ($mediaImages as $image): ?>

                                    <option
                                        value="<?= (int)$image['media_id']; ?>"
                                        <?= (
                                            (string)$selected_image ===
                                            (string)$image['media_id']
                                        ) ? 'selected' : ''; ?>
                                    >
                                        <?= bannerEscape($image['file_name']); ?>
                                        (ID: <?= (int)$image['media_id']; ?>)
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <span class="help-text">
                                Choose an image that has already been uploaded.
                            </span>
                        </div>

                        <div class="or-divider">
                            — OR —
                        </div>

                        <div class="form-group">
                            <label for="banner_image">
                                Upload a New Image
                            </label>

                            <input type="file"
                                   name="banner_image"
                                   id="banner_image"
                                   accept=".jpg,.jpeg,.png,.webp">

                            <span class="help-text">
                                Allowed formats: JPG, JPEG, PNG, WEBP.
                                Maximum size: 5 MB.
                            </span>
                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label for="button_text">Button Text</label>

                            <input type="text"
                                   id="button_text"
                                   name="button_text"
                                   maxlength="100"
                                   value="<?= bannerEscape($button_text); ?>"
                                   placeholder="e.g. Explore Packages">
                        </div>

                        <div class="form-group">
                            <label for="button_link">Button Link</label>

                            <input type="text"
                                   id="button_link"
                                   name="button_link"
                                   maxlength="500"
                                   value="<?= bannerEscape($button_link); ?>"
                                   placeholder="e.g. packages.php">
                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label for="sort_order">Display Order</label>

                            <input type="number"
                                   id="sort_order"
                                   name="sort_order"
                                   min="0"
                                   value="<?= (int)$sort_order; ?>">
                        </div>

                    </div>

                    <div class="status-option">
                        <input type="checkbox"
                               name="is_active"
                               id="is_active"
                               value="1"
                               <?= $is_active ? 'checked' : ''; ?>>

                        <label for="is_active">
                            Active Banner
                        </label>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Save Banner
                    </button>

                </form>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
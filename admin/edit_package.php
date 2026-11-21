
<?php
session_start();

include_once '../db.php';

/* Admin Login Check */
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

/* Helper Function */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Get Package ID */
$package_id = (int)($_GET['id'] ?? $_POST['package_id'] ?? 0);

if ($package_id <= 0) {
    header("Location: packages.php");
    exit;
}

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Fetch Existing Package */
$stmt = $conn->prepare("
    SELECT
        p.*,
        m.file_path AS image_path,
        m.alt_text AS image_alt
    FROM packages p
    LEFT JOIN media m ON p.image_id = m.media_id
    WHERE p.package_id = ?
");

$stmt->bind_param("i", $package_id);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();
$stmt->close();

if (!$package) {
    header("Location: packages.php");
    exit;
}

/* Get Categories */
$categories = [];

$categoryQuery = $conn->query("
    SELECT category_id, name
    FROM event_categories
    WHERE status = 'active'
       OR category_id = " . (int)$package['category_id'] . "
    ORDER BY sort_order ASC, name ASC
");

if ($categoryQuery) {
    while ($row = $categoryQuery->fetch_assoc()) {
        $categories[] = $row;
    }
}

$error = '';

/* Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $category_id = (int)($_POST['category_id'] ?? 0);
        $package_name = trim($_POST['package_name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $base_price = (float)($_POST['base_price'] ?? 0);
        $guest_capacity = (int)($_POST['guest_capacity'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'draft';

        /* Validation */
        if (
            $category_id <= 0 ||
            $package_name === '' ||
            $slug === '' ||
            $base_price < 0 ||
            $guest_capacity < 0
        ) {
            $error = "Please fill in all required fields correctly.";
        } elseif (!in_array($status, ['active', 'inactive', 'draft'], true)) {
            $error = "Invalid package status.";
        }

        /* Check Category */
        if (!$error) {
            $checkCategory = $conn->prepare("
                SELECT category_id
                FROM event_categories
                WHERE category_id = ?
                  AND (status = 'active' OR category_id = ?)
            ");

            $checkCategory->bind_param(
                "ii",
                $category_id,
                $category_id
            );

            $checkCategory->execute();
            $categoryExists = $checkCategory->get_result()->num_rows > 0;
            $checkCategory->close();

            if (!$categoryExists) {
                $error = "Please select a valid category.";
            }
        }

        /* Check Duplicate Slug */
        if (!$error) {
            $checkSlug = $conn->prepare("
                SELECT package_id
                FROM packages
                WHERE slug = ? AND package_id != ?
            ");

            $checkSlug->bind_param("si", $slug, $package_id);
            $checkSlug->execute();

            $slugExists = $checkSlug->get_result()->num_rows > 0;
            $checkSlug->close();

            if ($slugExists) {
                $error = "This slug already exists. Please use another slug.";
            }
        }

        if (!$error) {

            $uploadedFilePath = null;
            $newMediaId = null;

            // Keep existing image unless a new image is uploaded.
            $imageId = $package['image_id'];

            try {

                /* New Image Upload */
                if (
                    isset($_FILES['package_image']) &&
                    $_FILES['package_image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES['package_image']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Image upload failed.");
                    }

                    if ($_FILES['package_image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception("Image size must be 5 MB or less.");
                    }

                    $tmpFile = $_FILES['package_image']['tmp_name'];
                    $imageInfo = getimagesize($tmpFile);

                    if ($imageInfo === false) {
                        throw new Exception("Please upload a valid image.");
                    }

                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    $mimeType = $imageInfo['mime'];

                    if (!isset($allowedTypes[$mimeType])) {
                        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
                    }

                    $extension = $allowedTypes[$mimeType];

                    $uploadDirectory = __DIR__ . '/../uploads/packages/';

                    if (!is_dir($uploadDirectory)) {
                        if (!mkdir($uploadDirectory, 0755, true)) {
                            throw new Exception("Could not create upload directory.");
                        }
                    }

                    $fileName = 'package_' . bin2hex(random_bytes(12)) . '.' . $extension;
                    $destination = $uploadDirectory . $fileName;

                    if (!move_uploaded_file($tmpFile, $destination)) {
                        throw new Exception("Could not save the uploaded image.");
                    }

                    $uploadedFilePath = 'uploads/packages/' . $fileName;

                    /* Insert New Media Record */
                    $originalName = basename($_FILES['package_image']['name']);
                    $altText = $package_name . ' Package Image';
                    $fileType = $mimeType;
                    $uploadedBy = (int)$_SESSION['admin_id'];

                    $mediaStmt = $conn->prepare("
                        INSERT INTO media
                        (file_name, file_path, file_type, alt_text, uploaded_by)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $mediaStmt->bind_param(
                        "ssssi",
                        $originalName,
                        $uploadedFilePath,
                        $fileType,
                        $altText,
                        $uploadedBy
                    );

                    if (!$mediaStmt->execute()) {
                        throw new Exception("Could not save image information.");
                    }

                    $newMediaId = $conn->insert_id;
                    $imageId = $newMediaId;

                    $mediaStmt->close();
                }

                /* Update Package */
                $updateStmt = $conn->prepare("
                    UPDATE packages SET
                        category_id = ?,
                        package_name = ?,
                        slug = ?,
                        short_description = ?,
                        description = ?,
                        base_price = ?,
                        guest_capacity = ?,
                        image_id = ?,
                        is_featured = ?,
                        status = ?
                    WHERE package_id = ?
                ");

                $updateStmt->bind_param(
                    "issssdiiisi",
                    $category_id,
                    $package_name,
                    $slug,
                    $short_description,
                    $description,
                    $base_price,
                    $guest_capacity,
                    $imageId,
                    $is_featured,
                    $status,
                    $package_id
                );

                if (!$updateStmt->execute()) {
                    throw new Exception("Could not update package information.");
                }

                $updateStmt->close();

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: packages.php?success=updated");
                exit;

            } catch (Exception $ex) {

                /* Clean Up New Upload If Update Failed */
                if ($uploadedFilePath !== null) {
                    $fullPath = __DIR__ . '/../' . $uploadedFilePath;

                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                if ($newMediaId !== null) {
                    $deleteMedia = $conn->prepare("
                        DELETE FROM media WHERE media_id = ?
                    ");

                    $deleteMedia->bind_param("i", $newMediaId);
                    $deleteMedia->execute();
                    $deleteMedia->close();
                }

                $error = $ex->getMessage();
            }
        }
    }
}

/* Display Form Values */
$formCategory = $_POST['category_id'] ?? $package['category_id'];
$formName = $_POST['package_name'] ?? $package['package_name'];
$formSlug = $_POST['slug'] ?? $package['slug'];
$formShortDescription = $_POST['short_description'] ?? $package['short_description'];
$formDescription = $_POST['description'] ?? $package['description'];
$formPrice = $_POST['base_price'] ?? $package['base_price'];
$formCapacity = $_POST['guest_capacity'] ?? $package['guest_capacity'];
$formFeatured = isset($_POST['is_featured'])
    ? true
    : ($_SERVER['REQUEST_METHOD'] === 'POST' ? false : (bool)$package['is_featured']);
$formStatus = $_POST['status'] ?? $package['status'];

$currentImage = $package['image_path'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Package | DecorEase</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper add-package-page">

            <div class="add-package-heading">
                <div>
                    <h2>Edit Package</h2>
                    <p>Update package information and image.</p>
                </div>

                <a href="packages.php" class="package-back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Packages
                </a>
            </div>

            <?php if ($error): ?>
                <div class="packages-alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST"
                  enctype="multipart/form-data"
                  class="add-package-form">

                <input type="hidden" name="csrf_token"
                       value="<?php echo e($_SESSION['csrf_token']); ?>">

                <input type="hidden" name="package_id"
                       value="<?php echo (int)$package_id; ?>">

                <div class="package-form-section">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>

                    <div class="package-form-grid">

                        <div class="package-form-group">
                            <label>Package Category <span>*</span></label>

                            <select name="category_id" required>
                                <option value="">Select Category</option>

                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['category_id']; ?>"
                                        <?php echo ((int)$formCategory === (int)$category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="package-form-group">
                            <label>Package Name <span>*</span></label>
                            <input type="text" name="package_name"
                                   value="<?php echo e($formName); ?>" required>
                        </div>

                        <div class="package-form-group">
                            <label>Slug <span>*</span></label>
                            <input type="text" name="slug"
                                   value="<?php echo e($formSlug); ?>" required>
                        </div>

                        <div class="package-form-group">
                            <label>Guest Capacity</label>
                            <input type="number" name="guest_capacity"
                                   min="0" value="<?php echo e($formCapacity); ?>">
                        </div>

                        <div class="package-form-group full-width">
                            <label>Short Description</label>
                            <textarea name="short_description" rows="3"><?php
                                echo e($formShortDescription);
                            ?></textarea>
                        </div>

                        <div class="package-form-group full-width">
                            <label>Full Description</label>
                            <textarea name="description" rows="6"><?php
                                echo e($formDescription);
                            ?></textarea>
                        </div>

                    </div>
                </div>

                <div class="package-form-section">
                    <h3><i class="fas fa-dollar-sign"></i> Pricing & Settings</h3>

                    <div class="package-form-grid">

                        <div class="package-form-group">
                            <label>Base Price (BDT) <span>*</span></label>
                            <input type="number" name="base_price"
                                   min="0" step="0.01"
                                   value="<?php echo e($formPrice); ?>" required>
                        </div>

                        <div class="package-form-group">
                            <label>Package Status</label>
                            <select name="status">
                                <option value="draft" <?php echo $formStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="active" <?php echo $formStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $formStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="package-form-group">
                            <label>Featured Package</label>

                            <label class="package-checkbox-label">
                                <input type="checkbox" name="is_featured" value="1"
                                    <?php echo $formFeatured ? 'checked' : ''; ?>>
                                Show this package as featured
                            </label>
                        </div>

                    </div>
                </div>

                <div class="package-form-section">
                    <h3><i class="fas fa-image"></i> Package Image</h3>

                    <?php if ($currentImage): ?>
                        <div class="edit-package-current-image">
                            <p>Current Image</p>
                            <img src="../<?php echo e($currentImage); ?>"
                                 alt="<?php echo e($package['image_alt'] ?? $package['package_name']); ?>">
                        </div>
                    <?php else: ?>
                        <p class="edit-package-no-image">No image uploaded for this package.</p>
                    <?php endif; ?>

                    <div class="package-form-group">
                        <label>Upload New Image (Optional)</label>

                        <input type="file" name="package_image"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                        <small>
                            Allowed formats: JPG, PNG, WEBP. Maximum size: 5 MB.
                            Leave empty to keep the current image.
                        </small>
                    </div>
                </div>

                <div class="package-form-actions">
                    <a href="packages.php" class="package-cancel-btn">Cancel</a>

                    <button type="submit" class="package-save-btn">
                        <i class="fas fa-save"></i> Update Package
                    </button>
                </div>

            </form>

        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

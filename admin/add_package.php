
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

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

/* Get Categories */
$categories = [];
$categoryQuery = $conn->query("
    SELECT category_id, name
    FROM event_categories
    WHERE status = 'active'
    ORDER BY sort_order ASC, name ASC
");

if ($categoryQuery) {
    while ($row = $categoryQuery->fetch_assoc()) {
        $categories[] = $row;
    }
}

/* Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
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
        } else {

            /* Check Category */
            $checkCategory = $conn->prepare("
                SELECT category_id
                FROM event_categories
                WHERE category_id = ? AND status = 'active'
            ");
            $checkCategory->bind_param("i", $category_id);
            $checkCategory->execute();
            $categoryExists = $checkCategory->get_result()->num_rows > 0;
            $checkCategory->close();

            if (!$categoryExists) {
                $error = "Please select a valid category.";
            }

            /* Check Slug */
            if (!$error) {
                $checkSlug = $conn->prepare("
                    SELECT package_id
                    FROM packages
                    WHERE slug = ?
                ");
                $checkSlug->bind_param("s", $slug);
                $checkSlug->execute();
                $slugExists = $checkSlug->get_result()->num_rows > 0;
                $checkSlug->close();

                if ($slugExists) {
                    $error = "This slug already exists. Please use another slug.";
                }
            }

            if (!$error) {

                $uploadedFilePath = null;
                $mediaId = null;

                try {

                    /* Image Upload */
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

                        /* Insert Media Record */
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

                        $mediaId = $conn->insert_id;
                        $mediaStmt->close();
                    }

                    /* Insert Package */
                    $packageStmt = $conn->prepare("
                        INSERT INTO packages
                        (
                            category_id,
                            package_name,
                            slug,
                            short_description,
                            description,
                            base_price,
                            guest_capacity,
                            image_id,
                            is_featured,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $packageStmt->bind_param(
                        "issssdiiis",
                        $category_id,
                        $package_name,
                        $slug,
                        $short_description,
                        $description,
                        $base_price,
                        $guest_capacity,
                        $mediaId,
                        $is_featured,
                        $status
                    );

                    if (!$packageStmt->execute()) {
                        throw new Exception("Could not save package information.");
                    }

                    $packageStmt->close();

                    $success = "Package added successfully!";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Redirect after successful insertion
                    header("Location: packages.php?success=added");
                    exit;

                } catch (Exception $ex) {

                    /* Remove Uploaded File If Saving Failed */
                    if ($uploadedFilePath !== null) {
                        $fullPath = __DIR__ . '/../' . $uploadedFilePath;

                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }

                    /* Remove Media Record If Package Insert Failed */
                    if ($mediaId !== null) {
                        $deleteMedia = $conn->prepare("
                            DELETE FROM media WHERE media_id = ?
                        ");
                        $deleteMedia->bind_param("i", $mediaId);
                        $deleteMedia->execute();
                        $deleteMedia->close();
                    }

                    $error = $ex->getMessage();
                }
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
    <title>Add Package | DecorEase</title>

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
                    <h2>Add New Package</h2>
                    <p>Create a new decoration package for your customers.</p>
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

                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo e($_SESSION['csrf_token']); ?>">

                <div class="package-form-section">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>

                    <div class="package-form-grid">

                        <div class="package-form-group">
                            <label>Package Category <span>*</span></label>

                            <select name="category_id" required>
                                <option value="">Select Category</option>

                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['category_id']; ?>"
                                        <?php echo (($_POST['category_id'] ?? '') == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="package-form-group">
                            <label>Package Name <span>*</span></label>
                            <input type="text"
                                   name="package_name"
                                   value="<?php echo e($_POST['package_name'] ?? ''); ?>"
                                   placeholder="Example: Premium Wedding Package"
                                   required>
                        </div>

                        <div class="package-form-group">
                            <label>Slug <span>*</span></label>
                            <input type="text"
                                   name="slug"
                                   value="<?php echo e($_POST['slug'] ?? ''); ?>"
                                   placeholder="premium-wedding-package"
                                   required>
                        </div>

                        <div class="package-form-group">
                            <label>Guest Capacity</label>
                            <input type="number"
                                   name="guest_capacity"
                                   min="0"
                                   value="<?php echo e($_POST['guest_capacity'] ?? ''); ?>"
                                   placeholder="Example: 500">
                        </div>

                        <div class="package-form-group full-width">
                            <label>Short Description</label>
                            <textarea name="short_description"
                                      rows="3"
                                      placeholder="Write a short description..."><?php echo e($_POST['short_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="package-form-group full-width">
                            <label>Full Description</label>
                            <textarea name="description"
                                      rows="6"
                                      placeholder="Write complete package details..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                        </div>

                    </div>
                </div>

                <div class="package-form-section">
                    <h3><i class="fas fa-dollar-sign"></i> Pricing & Settings</h3>

                    <div class="package-form-grid">

                        <div class="package-form-group">
                            <label>Base Price (BDT) <span>*</span></label>
                            <input type="number"
                                   name="base_price"
                                   min="0"
                                   step="0.01"
                                   value="<?php echo e($_POST['base_price'] ?? ''); ?>"
                                   placeholder="Example: 50000"
                                   required>
                        </div>

                        <div class="package-form-group">
                            <label>Package Status</label>
                            <select name="status">
                                <option value="draft">Draft</option>
                                <option value="active"
                                    <?php echo (($_POST['status'] ?? '') === 'active') ? 'selected' : ''; ?>>
                                    Active
                                </option>
                                <option value="inactive"
                                    <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>

                        <div class="package-form-group package-featured-group">
                            <label>Featured Package</label>
                            <label class="package-checkbox-label">
                                <input type="checkbox"
                                       name="is_featured"
                                       value="1"
                                       <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                Show this package as featured
                            </label>
                        </div>

                    </div>
                </div>

                <div class="package-form-section">
                    <h3><i class="fas fa-image"></i> Package Image</h3>

                    <div class="package-form-group">
                        <label>Upload Package Image</label>
                        <input type="file"
                               name="package_image"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                        <small>Allowed formats: JPG, PNG, WEBP. Maximum size: 5 MB.</small>
                    </div>
                </div>

                <div class="package-form-actions">
                    <a href="packages.php" class="package-cancel-btn">Cancel</a>

                    <button type="submit" class="package-save-btn">
                        <i class="fas fa-save"></i> Save Package
                    </button>
                </div>

            </form>

        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

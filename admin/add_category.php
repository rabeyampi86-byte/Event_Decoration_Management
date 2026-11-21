<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

$name = '';
$slug = '';
$description = '';
$sortOrder = 0;
$status = 'active';

/* Save Category */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($name === '' || $slug === '') {
            $error = "Category name and slug are required.";
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $error = "Slug must contain lowercase English letters, numbers and hyphens.";
        } elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = "Invalid category status.";
        } elseif ($sortOrder < 0) {
            $error = "Sort order cannot be negative.";
        } else {

            /* Check Duplicate */
            $check = $conn->prepare(
                "SELECT category_id
                 FROM event_categories
                 WHERE name = ? OR slug = ?
                 LIMIT 1"
            );

            $check->bind_param("ss", $name, $slug);
            $check->execute();

            $exists = $check->get_result()->num_rows > 0;

            $check->close();

            if ($exists) {
                $error = "A category with this name or slug already exists.";
            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO event_categories
                    (name, slug, description, sort_order, status)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssis",
                    $name,
                    $slug,
                    $description,
                    $sortOrder,
                    $status
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    header("Location: event_categories.php?added=1");
                    exit;
                } else {
                    $error = "Unable to save category. Please try again.";
                }

                $stmt->close();
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

    <title>Add Event Category | Admin Dashboard</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper add-category-page">

            <div class="page-heading">
                <div>
                    <h1>Add Event Category</h1>
                    <p>Create a new event category for your website.</p>
                </div>

                <a href="event_categories.php" class="ac-back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Categories
                </a>
            </div>

            <?php if ($error): ?>
                <div class="ec-alert ec-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="ac-form-card">

                <div class="ac-card-heading">
                    <h2>
                        <i class="fas fa-tags"></i>
                        Category Information
                    </h2>
                    <p>Fill in the details below to create a category.</p>
                </div>

                <form method="POST" action="add_category.php">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="ac-form-group">
                        <label for="name">
                            Category Name <span>*</span>
                        </label>

                        <input type="text"
                               id="name"
                               name="name"
                               value="<?= e($name) ?>"
                               placeholder="Example: Wedding"
                               required>
                    </div>

                    <div class="ac-form-group">
                        <label for="slug">
                            Slug <span>*</span>
                        </label>

                        <input type="text"
                               id="slug"
                               name="slug"
                               value="<?= e($slug) ?>"
                               placeholder="Example: wedding"
                               required>

                        <small>
                            Use lowercase English letters, numbers and hyphens.
                        </small>
                    </div>

                    <div class="ac-form-group">
                        <label for="description">Description</label>

                        <textarea id="description"
                                  name="description"
                                  rows="5"
                                  placeholder="Write a short description of this category..."><?= e($description) ?></textarea>
                    </div>

                    <div class="ac-form-row">

                        <div class="ac-form-group">
                            <label for="sort_order">Sort Order</label>

                            <input type="number"
                                   id="sort_order"
                                   name="sort_order"
                                   min="0"
                                   value="<?= e($sortOrder) ?>">
                        </div>

                        <div class="ac-form-group">
                            <label for="status">Status</label>

                            <select id="status" name="status">
                                <option value="active"
                                    <?= $status === 'active' ? 'selected' : '' ?>>
                                    Active
                                </option>

                                <option value="inactive"
                                    <?= $status === 'inactive' ? 'selected' : '' ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="ac-form-actions">

                        <button type="submit" class="ac-save-btn">
                            <i class="fas fa-save"></i>
                            Save Category
                        </button>

                        <a href="event_categories.php" class="ac-cancel-btn">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
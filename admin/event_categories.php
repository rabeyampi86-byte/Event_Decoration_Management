<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

/* Helper */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';

/* POST Actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $action = $_POST['action'] ?? '';

        /* Add Category */
        if ($action === 'add') {

            $name = trim($_POST['name'] ?? '');
            $slug = strtolower(trim($_POST['slug'] ?? ''));
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            if ($name === '' || $slug === '') {
                $error = "Category name and slug are required.";
            } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $error = "Slug must contain only lowercase English letters, numbers and hyphens.";
            } elseif (!in_array($status, ['active', 'inactive'], true)) {
                $error = "Invalid category status.";
            } else {

                $check = $conn->prepare(
                    "SELECT category_id FROM event_categories WHERE slug = ? OR name = ? LIMIT 1"
                );
                $check->bind_param("ss", $slug, $name);
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
                        $success = "Event category added successfully.";
                    } else {
                        $error = "Unable to add category. Please try again.";
                    }

                    $stmt->close();
                }
            }
        }

        /* Update Category */
        elseif ($action === 'update') {

            $categoryId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = strtolower(trim($_POST['slug'] ?? ''));
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            if ($categoryId <= 0 || $name === '' || $slug === '') {
                $error = "Please provide all required information.";
            } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $error = "Slug must contain only lowercase English letters, numbers and hyphens.";
            } elseif (!in_array($status, ['active', 'inactive'], true)) {
                $error = "Invalid category status.";
            } else {

                $check = $conn->prepare(
                    "SELECT category_id
                     FROM event_categories
                     WHERE (slug = ? OR name = ?) AND category_id != ?
                     LIMIT 1"
                );

                $check->bind_param("ssi", $slug, $name, $categoryId);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                $check->close();

                if ($exists) {
                    $error = "Another category already uses this name or slug.";
                } else {

                    $stmt = $conn->prepare(
                        "UPDATE event_categories
                         SET name = ?, slug = ?, description = ?,
                             sort_order = ?, status = ?
                         WHERE category_id = ?"
                    );

                    $stmt->bind_param(
                        "sssisi",
                        $name,
                        $slug,
                        $description,
                        $sortOrder,
                        $status,
                        $categoryId
                    );

                    if ($stmt->execute()) {
                        $success = "Event category updated successfully.";
                    } else {
                        $error = "Unable to update category. Please try again.";
                    }

                    $stmt->close();
                }
            }
        }

        /* Change Status */
        elseif ($action === 'toggle_status') {

            $categoryId = (int)($_POST['category_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';

            if (
                $categoryId <= 0 ||
                !in_array($newStatus, ['active', 'inactive'], true)
            ) {
                $error = "Invalid category status request.";
            } else {

                $stmt = $conn->prepare(
                    "UPDATE event_categories SET status = ? WHERE category_id = ?"
                );

                $stmt->bind_param("si", $newStatus, $categoryId);

                if ($stmt->execute()) {
                    $success = "Category status updated successfully.";
                } else {
                    $error = "Unable to update category status.";
                }

                $stmt->close();
            }
        }
    }
}

/* Edit Record */
$editCategory = null;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    if ($editId > 0) {
        $stmt = $conn->prepare(
            "SELECT category_id, name, slug, description, sort_order, status
             FROM event_categories
             WHERE category_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $editId);
        $stmt->execute();

        $result = $stmt->get_result();
        $editCategory = $result->fetch_assoc();

        $stmt->close();
    }
}

/* Search and Filter */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(name LIKE ? OR slug LIKE ? OR description LIKE ?)";
    $keyword = "%{$search}%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Statistics */
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

$statsQuery = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive
     FROM event_categories"
);

if ($statsQuery && $statsRow = $statsQuery->fetch_assoc()) {
    $stats['total'] = (int)$statsRow['total'];
    $stats['active'] = (int)$statsRow['active'];
    $stats['inactive'] = (int)$statsRow['inactive'];
}

/* Pagination */
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$countSQL = "SELECT COUNT(*) AS total FROM event_categories $whereSQL";
$countStmt = $conn->prepare($countSQL);

if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

/* Category List */
$listSQL = "
    SELECT category_id, name, slug, description, sort_order, status, created_at
    FROM event_categories
    $whereSQL
    ORDER BY sort_order ASC, category_id DESC
    LIMIT ? OFFSET ?
";

$listStmt = $conn->prepare($listSQL);

$listTypes = $types . "ii";
$listParams = $params;
$listParams[] = $limit;
$listParams[] = $offset;

$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();

$categories = $listStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Categories | Admin Dashboard</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper event-categories-page">

            <div class="page-heading">
                <div>
                    <h1>Event Categories</h1>
                    <p>Manage event categories displayed on your website.</p>
                </div>

                <a href="add_category.php" class="ec-add-btn">
                    <i class="fas fa-plus"></i>
                    Add Category
                </a>
            </div>

            <?php if ($success): ?>
                <div class="ec-alert ec-success">
                    <i class="fas fa-check-circle"></i>
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="ec-alert ec-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="ec-stats">

                <div class="ec-stat-card">
                    <div class="ec-stat-icon total">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <span>Total Categories</span>
                        <h2><?= $stats['total'] ?></h2>
                    </div>
                </div>

                <div class="ec-stat-card">
                    <div class="ec-stat-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <span>Active Categories</span>
                        <h2><?= $stats['active'] ?></h2>
                    </div>
                </div>

                <div class="ec-stat-card">
                    <div class="ec-stat-icon inactive">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div>
                        <span>Inactive Categories</span>
                        <h2><?= $stats['inactive'] ?></h2>
                    </div>
                </div>

            </div>

            <div class="ec-content-grid">


                <!-- Category List -->
                <section class="ec-panel ec-list-panel">

                    <div class="ec-panel-heading">
                        <h2><i class="fas fa-list"></i> Category List</h2>
                        <span class="ec-result-count">
                            <?= $totalRows ?> Records
                        </span>
                    </div>

                    <form method="GET" class="ec-filter-form">

                        <div class="ec-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search"
                                   placeholder="Search category..."
                                   value="<?= e($search) ?>">
                        </div>

                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active"
                                <?= $statusFilter === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive"
                                <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>

                        <button type="submit" class="ec-filter-btn">
                            Filter
                        </button>

                        <a href="event_categories.php" class="ec-clear-btn">
                            Clear
                        </a>

                    </form>

                    <div class="ec-table-wrapper">
                        <table class="ec-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Slug</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($categories->num_rows > 0): ?>

                                <?php while ($category = $categories->fetch_assoc()): ?>

                                    <tr>
                                        <td>#<?= (int)$category['category_id'] ?></td>

                                        <td>
                                            <div class="ec-category-name">
                                                <strong><?= e($category['name']) ?></strong>

                                                <?php if (!empty($category['description'])): ?>
                                                    <small><?= e($category['description']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="ec-slug">
                                                <?= e($category['slug']) ?>
                                            </span>
                                        </td>

                                        <td><?= (int)$category['sort_order'] ?></td>

                                        <td>
                                            <span class="ec-status <?= e($category['status']) ?>">
                                                <?= ucfirst(e($category['status'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= $category['created_at']
                                                ? date('d M Y', strtotime($category['created_at']))
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <div class="ec-action-buttons">

                                                <a href="event_categories.php?edit=<?= (int)$category['category_id'] ?>"
                                                   class="ec-edit-btn"
                                                   title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <form method="POST"
                                                      onsubmit="return confirm('Are you sure you want to change this category status?');">

                                                    <input type="hidden" name="csrf_token"
                                                           value="<?= e($_SESSION['csrf_token']) ?>">

                                                    <input type="hidden" name="action"
                                                           value="toggle_status">

                                                    <input type="hidden" name="category_id"
                                                           value="<?= (int)$category['category_id'] ?>">

                                                    <input type="hidden" name="new_status"
                                                           value="<?= $category['status'] === 'active' ? 'inactive' : 'active' ?>">

                                                    <button type="submit"
                                                            class="ec-toggle-btn <?= $category['status'] === 'active' ? 'make-inactive' : 'make-active' ?>"
                                                            title="<?= $category['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="fas <?= $category['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                    </button>

                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="ec-empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <p>No event categories found.</p>
                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>

                        <div class="ec-pagination">

                            <?php
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $baseQuery = http_build_query($queryParams);
                            $baseUrl = 'event_categories.php?' . ($baseQuery ? $baseQuery . '&' : '');
                            ?>

                            <?php if ($page > 1): ?>
                                <a href="<?= e($baseUrl . 'page=' . ($page - 1)) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <span>
                                Page <?= $page ?> of <?= $totalPages ?>
                            </span>

                            <?php if ($page < $totalPages): ?>
                                <a href="<?= e($baseUrl . 'page=' . ($page + 1)) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </section>

            </div>

        </div>

    </main>

</div>

<?php
$listStmt->close();
include 'includes/footer.php';
?>

</body>
</html>
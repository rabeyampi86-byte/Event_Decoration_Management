<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

/* Helper Functions */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function packageImageUrl($path)
{
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (preg_match('~^(https?://|/)~i', $path)) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';

/* Delete Package */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $packageId = (int)($_POST['package_id'] ?? 0);

        if ($packageId <= 0) {
            $error = "Invalid package selected.";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM packages WHERE package_id = ?"
                );

                $stmt->bind_param("i", $packageId);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $success = "Package deleted successfully.";
                } else {
                    $error = "Package not found.";
                }

                $stmt->close();

            } catch (Throwable $e) {
                $error = "This package cannot be deleted because it is linked to other records.";
            }
        }
    }
}
/* Update Package Status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $packageId = (int)($_POST['package_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if (
            $packageId <= 0 ||
            !in_array($newStatus, ['active', 'inactive'], true)
        ) {
            $error = "Invalid package status request.";
        } else {

            $stmt = $conn->prepare(
                "UPDATE packages SET status = ? WHERE package_id = ?"
            );

            $stmt->bind_param("si", $newStatus, $packageId);

            if ($stmt->execute()) {
                $success = "Package status updated successfully.";
            } else {
                $error = "Unable to update package status.";
            }

            $stmt->close();
        }
    }
}
/* Search and Filters */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = (int)($_GET['category'] ?? 0);

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(p.package_name LIKE ? OR p.slug LIKE ? OR p.short_description LIKE ? OR ec.name LIKE ?)";

    $keyword = "%{$search}%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= "ssss";
}

if (in_array($statusFilter, ['active', 'inactive', 'draft'], true)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
    $types .= "i";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Statistics */
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'draft' => 0
];

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive,
        SUM(status = 'draft') AS draft
    FROM packages
");

if ($statsQuery && $row = $statsQuery->fetch_assoc()) {
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = (int)$row['inactive'];
    $stats['draft'] = (int)$row['draft'];
}

/* Category Options */
$categoryResult = $conn->query("
    SELECT category_id, name
    FROM event_categories
    ORDER BY name ASC
");

/* Pagination */
$limit = 9;
$page = max(1, (int)($_GET['page'] ?? 1));

$countSQL = "
    SELECT COUNT(*) AS total
    FROM packages p
    LEFT JOIN event_categories ec
        ON p.category_id = ec.category_id
    $whereSQL
";

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

/* Package List */
$listSQL = "
    SELECT
        p.package_id,
        p.category_id,
        p.package_name,
        p.slug,
        p.short_description,
        p.description,
        p.base_price,
        p.guest_capacity,
        p.image_id,
        p.is_featured,
        p.status,
        p.created_at,
        ec.name AS category_name,
        m.file_path AS image_path,
        m.alt_text AS image_alt
    FROM packages p
    LEFT JOIN event_categories ec
        ON p.category_id = ec.category_id
    LEFT JOIN media m
        ON p.image_id = m.media_id
    $whereSQL
    ORDER BY p.created_at DESC, p.package_id DESC
    LIMIT ? OFFSET ?
";

$listStmt = $conn->prepare($listSQL);

$listTypes = $types . "ii";
$listParams = $params;
$listParams[] = $limit;
$listParams[] = $offset;

$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();

$packages = $listStmt->get_result();

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Packages Management | Admin Dashboard</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper packages-page">

            <!-- Page Heading -->
            <div class="packages-heading">

                <div>
                    <h1>Packages Management</h1>
                    <p>Manage your decoration packages and their details.</p>
                </div>

                <a href="add_package.php" class="packages-add-btn">
                    <i class="fas fa-plus"></i>
                    Add Package
                </a>

            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="packages-alert success">
                    <i class="fas fa-check-circle"></i>
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="packages-alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="packages-stats">

                <div class="packages-stat-card">
                    <div class="packages-stat-icon total">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <span>Total Packages</span>
                        <h2><?= $stats['total'] ?></h2>
                    </div>
                </div>

                <div class="packages-stat-card">
                    <div class="packages-stat-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <span>Active</span>
                        <h2><?= $stats['active'] ?></h2>
                    </div>
                </div>

                <div class="packages-stat-card">
                    <div class="packages-stat-icon inactive">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div>
                        <span>Inactive</span>
                        <h2><?= $stats['inactive'] ?></h2>
                    </div>
                </div>

                <div class="packages-stat-card">
                    <div class="packages-stat-icon draft">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <span>Draft</span>
                        <h2><?= $stats['draft'] ?></h2>
                    </div>
                </div>

            </div>

            <!-- Filters -->
            <div class="packages-filter-panel">

                <form method="GET" class="packages-filter-form">

                    <div class="packages-search">
                        <i class="fas fa-search"></i>

                        <input type="text"
                               name="search"
                               placeholder="Search package..."
                               value="<?= e($search) ?>">
                    </div>

                    <select name="category">
                        <option value="0">All Categories</option>

                        <?php if ($categoryResult): ?>
                            <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                                <option value="<?= (int)$cat['category_id'] ?>"
                                    <?= $categoryFilter === (int)$cat['category_id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>

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

                        <option value="draft"
                            <?= $statusFilter === 'draft' ? 'selected' : '' ?>>
                            Draft
                        </option>
                    </select>

                    <button type="submit" class="packages-filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                    <a href="packages.php" class="packages-clear-btn">
                        Clear
                    </a>

                </form>

            </div>

            <!-- Package Cards -->
            <div class="packages-section-heading">
                <h2>All Packages</h2>
                <span><?= $totalRows ?> Packages Found</span>
            </div>

            <div class="packages-card-grid">

                <?php if ($packages->num_rows > 0): ?>

                    <?php while ($package = $packages->fetch_assoc()): ?>

                        <?php
                        $imageUrl = packageImageUrl($package['image_path'] ?? '');
                        ?>

                        <article class="package-admin-card">

                            <!-- Image -->
                            <div class="package-card-image">

                                <?php if ($imageUrl !== ''): ?>

                                    <img src="<?= e($imageUrl) ?>"
                                         alt="<?= e($package['image_alt'] ?: $package['package_name']) ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                    <div class="package-no-image" style="display:none;">
                                        <i class="fas fa-image"></i>
                                        <span>Image unavailable</span>
                                    </div>

                                <?php else: ?>

                                    <div class="package-no-image">
                                        <i class="fas fa-image"></i>
                                        <span>No Image Available</span>
                                    </div>

                                <?php endif; ?>

                                <?php if ((int)$package['is_featured'] === 1): ?>
                                    <span class="package-featured">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>

                                <span class="package-status <?= e($package['status']) ?>">
                                    <?= ucfirst(e($package['status'])) ?>
                                </span>

                            </div>

                            <!-- Package Information -->
                            <div class="package-card-body">

                                <span class="package-category">
                                    <?= e($package['category_name'] ?? 'Uncategorized') ?>
                                </span>

                                <h3><?= e($package['package_name']) ?></h3>

                                <p class="package-short-description">
                                    <?= e($package['short_description'] ?: 'No short description available.') ?>
                                </p>

                                <div class="package-card-info">

                                    <div>
                                        <i class="fas fa-users"></i>
                                        <span>
                                            <?= number_format((int)$package['guest_capacity']) ?>
                                            Guests
                                        </span>
                                    </div>

                                    <div class="package-price">
                                        <span>৳</span>
                                        <?= number_format((float)$package['base_price'], 2) ?>
                                    </div>

                                </div>

                                <div class="package-card-actions">

                                    <a href="edit_package.php?id=<?= (int)$package['package_id'] ?>"
                                       class="package-edit-btn">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>

                                    <form method="POST"
                                          class="package-delete-form"
                                          onsubmit="return confirm('Are you sure you want to delete this package? Related package details may also be deleted.');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="package_id"
                                               value="<?= (int)$package['package_id'] ?>">

                                        <button type="submit" class="package-delete-btn">
                                            <i class="fas fa-trash-alt"></i>
                                            Delete
                                        </button>

                                    </form>

                                    <form method="POST" class="package-status-form">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="update_status">

                                        <input type="hidden"
                                               name="package_id"
                                               value="<?= (int)$package['package_id'] ?>">

                                        <input type="hidden"
                                               name="new_status"
                                               value="<?= $package['status'] === 'active' ? 'inactive' : 'active' ?>">

                                    </form>

                                </div>

                                <div class="package-card-footer">

                                    <form method="POST"
                                          onsubmit="return confirm('Change this package status?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="update_status">

                                        <input type="hidden"
                                               name="package_id"
                                               value="<?= (int)$package['package_id'] ?>">

                                        <input type="hidden"
                                               name="new_status"
                                               value="<?= $package['status'] === 'active' ? 'inactive' : 'active' ?>">

                                        <button type="submit"
                                                class="package-status-btn <?= $package['status'] === 'active' ? 'make-inactive' : 'make-active' ?>">

                                            <i class="fas <?= $package['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>

                                            <?= $package['status'] === 'active'
                                                ? 'Make Inactive'
                                                : 'Make Active' ?>

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="packages-empty">
                        <i class="fas fa-box-open"></i>
                        <h3>No Packages Found</h3>
                        <p>Try changing your search or add a new package.</p>

                        <a href="add_package.php" class="packages-add-btn">
                            <i class="fas fa-plus"></i>
                            Add Package
                        </a>
                    </div>

                <?php endif; ?>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>

                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);

                $baseQuery = http_build_query($queryParams);
                $baseUrl = 'packages.php?' . ($baseQuery ? $baseQuery . '&' : '');
                ?>

                <div class="packages-pagination">

                    <?php if ($page > 1): ?>
                        <a href="<?= e($baseUrl . 'page=' . ($page - 1)) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <span>Page <?= $page ?> of <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e($baseUrl . 'page=' . ($page + 1)) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<?php
$listStmt->close();
include 'includes/footer.php';
?>

</body>
</html>
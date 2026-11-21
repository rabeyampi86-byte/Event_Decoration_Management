
<?php
session_start();
include_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$searchLike = '%' . $search . '%';

/* =========================================
   PACKAGE DETAILS STATISTICS
========================================= */

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total_packages,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_details pd
             WHERE pd.package_id = p.package_id)
        ), 0) AS total_overview,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_team_roles ptr
             WHERE ptr.package_id = p.package_id)
        ), 0) AS total_team_roles,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_flower_options pfo
             WHERE pfo.package_id = p.package_id)
        ), 0) AS total_flowers,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_decoration_areas pda
             WHERE pda.package_id = p.package_id)
        ), 0) AS total_areas,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_lighting pl
             WHERE pl.package_id = p.package_id)
        ), 0) AS total_lighting,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_food_menus pfm
             WHERE pfm.package_id = p.package_id)
        ), 0) AS total_food,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_vendors pv
             WHERE pv.package_id = p.package_id)
        ), 0) AS total_vendors,
        COALESCE(SUM(
            (SELECT COUNT(*) FROM package_facilities pfa
             WHERE pfa.package_id = p.package_id)
        ), 0) AS total_facilities
    FROM packages p
");

$stats = $statsQuery ? $statsQuery->fetch_assoc() : [];

/* =========================================
   FETCH PACKAGES WITH DETAIL COUNTS
========================================= */

$sql = "
    SELECT
        p.package_id,
        p.package_name,
        p.status,
        p.base_price,

        (SELECT COUNT(*) FROM package_details pd
         WHERE pd.package_id = p.package_id) AS overview_count,

        (SELECT COUNT(*) FROM package_team_roles ptr
         WHERE ptr.package_id = p.package_id) AS team_count,

        (SELECT COUNT(*) FROM package_flower_options pfo
         WHERE pfo.package_id = p.package_id) AS flower_count,

        (SELECT COUNT(*) FROM package_decoration_areas pda
         WHERE pda.package_id = p.package_id) AS area_count,

        (SELECT COUNT(*) FROM package_lighting pl
         WHERE pl.package_id = p.package_id) AS lighting_count,

        (SELECT COUNT(*) FROM package_food_menus pfm
         WHERE pfm.package_id = p.package_id) AS food_count,

        (SELECT COUNT(*) FROM package_vendors pv
         WHERE pv.package_id = p.package_id) AS vendor_count,

        (SELECT COUNT(*) FROM package_facilities pfa
         WHERE pfa.package_id = p.package_id) AS facility_count

    FROM packages p
    WHERE p.package_name LIKE ?
    ORDER BY p.package_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $searchLike);
$stmt->execute();
$result = $stmt->get_result();

$packages = [];

while ($row = $result->fetch_assoc()) {
    $packages[] = $row;
}

$stmt->close();

$totalPackages = (int)($stats['total_packages'] ?? 0);
$totalOverview = (int)($stats['total_overview'] ?? 0);
$totalTeam = (int)($stats['total_team_roles'] ?? 0);
$totalFlowers = (int)($stats['total_flowers'] ?? 0);

$totalResources = (int)(
    ($stats['total_areas'] ?? 0) +
    ($stats['total_lighting'] ?? 0) +
    ($stats['total_food'] ?? 0) +
    ($stats['total_vendors'] ?? 0) +
    ($stats['total_facilities'] ?? 0)
);

include 'includes/sidebar.php';
include 'includes/header.php';
?>

<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<div class="main-content package-details-page">

    <!-- PAGE HEADER -->
    <div class="pd-page-header">
        <div>
            <span class="pd-eyebrow">EVENT & PACKAGE MANAGEMENT</span>
            <h1>Package Details</h1>
            <p>Manage all information, teams, decoration options and services for each package.</p>
        </div>

        <a href="add_package_details.php" class="pd-primary-btn">
            <i class="fas fa-plus"></i>
            Add Package Details
        </a>
    </div>

    <!-- STATISTICS -->
    <div class="pd-summary-grid">

        <div class="pd-summary-card">
            <div class="pd-summary-icon burgundy">
                <i class="fas fa-box-open"></i>
            </div>
            <div>
                <span>Total Packages</span>
                <h3><?= $totalPackages ?></h3>
            </div>
        </div>

        <div class="pd-summary-card">
            <div class="pd-summary-icon gold">
                <i class="fas fa-file-alt"></i>
            </div>
            <div>
                <span>Package Overviews</span>
                <h3><?= $totalOverview ?></h3>
            </div>
        </div>

        <div class="pd-summary-card">
            <div class="pd-summary-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <span>Team Assignments</span>
                <h3><?= $totalTeam ?></h3>
            </div>
        </div>

        <div class="pd-summary-card">
            <div class="pd-summary-icon green">
                <i class="fas fa-spa"></i>
            </div>
            <div>
                <span>Flower Options</span>
                <h3><?= $totalFlowers ?></h3>
            </div>
        </div>

        <div class="pd-summary-card">
            <div class="pd-summary-icon purple">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <span>Other Package Options</span>
                <h3><?= $totalResources ?></h3>
            </div>
        </div>

    </div>

    <!-- PACKAGE MANAGEMENT -->
    <div class="pd-management-card">

        <div class="pd-section-heading">
            <div>
                <h2>Package-wise Details</h2>
                <p>Choose a package to manage its complete details.</p>
            </div>

            <span class="pd-record-count">
                <?= count($packages) ?> packages shown
            </span>
        </div>

        <!-- SEARCH -->
        <form method="GET" class="pd-search-form">

            <div class="pd-search-box">
                <i class="fas fa-search"></i>

                <input
                    type="search"
                    name="search"
                    placeholder="Search package by name..."
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <button type="submit" class="pd-search-btn">
                Search
            </button>

            <a href="package_details.php" class="pd-reset-btn">
                Reset
            </a>

        </form>

        <!-- TABLE -->
        <div class="pd-table-responsive">

            <table class="pd-management-table">

                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Overview</th>
                        <th>Photography & Decoration Team</th>
                        <th>Flowers</th>
                        <th>Decoration Areas</th>
                        <th>Lighting</th>
                        <th>Food Menu</th>
                        <th>Partners</th>
                        <th>Facilities</th>
                        <th>Manage</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($packages)): ?>

                    <?php foreach ($packages as $package): ?>

                        <tr>

                            <!-- PACKAGE -->
                            <td>
                                <div class="pd-package-name">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $package['package_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= number_format(
                                            (float)$package['base_price'],
                                            2
                                        ) ?>
                                    </span>

                                    <small class="pd-status <?= htmlspecialchars($package['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($package['status'])) ?>
                                    </small>

                                </div>
                            </td>

                            <!-- OVERVIEW -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['overview_count'] ?>
                                </span>
                            </td>

                            <!-- TEAM -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['team_count'] ?>
                                </span>
                            </td>

                            <!-- FLOWERS -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['flower_count'] ?>
                                </span>
                            </td>

                            <!-- DECORATION AREAS -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['area_count'] ?>
                                </span>
                            </td>

                            <!-- LIGHTING -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['lighting_count'] ?>
                                </span>
                            </td>

                            <!-- FOOD -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['food_count'] ?>
                                </span>
                            </td>

                            <!-- PARTNERS -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['vendor_count'] ?>
                                </span>
                            </td>

                            <!-- FACILITIES -->
                            <td>
                                <span class="pd-count">
                                    <?= (int)$package['facility_count'] ?>
                                </span>
                            </td>

                            <!-- MANAGE ACTIONS -->
                            <td>
                                <div class="pd-action-buttons">

                                    <!-- MANAGE DETAILS -->
                                    <a
                                        href="add_package_details.php?package_id=<?= (int)$package['package_id'] ?>"
                                        class="pd-manage-btn"
                                    >
                                        <i class="fas fa-sliders-h"></i>
                                        Manage Details
                                    </a>

                                    <!-- EDIT -->
                                    <a
                                        href="edit_package_details.php?package_id=<?= (int)$package['package_id'] ?>"
                                        class="pd-manage-btn"
                                    >
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>

                                    <!-- DELETE ALL DETAILS -->
                                    <form
                                        method="POST"
                                        action="delete_package_details.php"
                                        onsubmit="return confirm('Are you sure you want to delete ALL details of this package? This cannot be undone.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="package_id"
                                            value="<?= (int)$package['package_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete_all"
                                        >

                                        <button
                                            type="submit"
                                            class="pd-delete-btn"
                                        >
                                            <i class="fas fa-trash"></i>
                                            Delete All
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10" class="pd-no-data">

                            <i class="fas fa-box-open"></i>

                            <h3>No Packages Found</h3>

                            <p>
                                <?= $search !== ''
                                    ? 'Try another package name.'
                                    : 'Create a package first, then add its details.' ?>
                            </p>

                            <a href="packages.php" class="pd-secondary-btn">
                                View Packages
                            </a>

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>

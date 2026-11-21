
<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================
   SEARCH
========================================= */

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$allowedStatuses = ['active', 'inactive'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

/* =========================================
   VENUE STATISTICS
========================================= */

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total_venues,
        SUM(status = 'active') AS active_venues,
        SUM(status = 'inactive') AS inactive_venues,
        SUM(capacity) AS total_capacity
    FROM venues
");

$stats = $statsQuery ? $statsQuery->fetch_assoc() : [];

$totalVenues = (int)($stats['total_venues'] ?? 0);
$activeVenues = (int)($stats['active_venues'] ?? 0);
$inactiveVenues = (int)($stats['inactive_venues'] ?? 0);
$totalCapacity = (int)($stats['total_capacity'] ?? 0);

/* =========================================
   FETCH VENUES
========================================= */

$sql = "
    SELECT
        venue_id,
        name,
        address,
        capacity,
        contact_phone,
        status
    FROM venues
    WHERE name LIKE ?
";

$params = ['%' . $search . '%'];
$types = 's';

if ($statusFilter !== '') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= " ORDER BY venue_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

$venues = [];

while ($row = $result->fetch_assoc()) {
    $venues[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Event Venues | DecorEase Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="venue-page">

            <!-- PAGE HEADER -->
            <div class="venue-page-header">

                <div>
                    <span class="venue-eyebrow">
                        EVENT & VENUE MANAGEMENT
                    </span>

                    <h1>Event Venues</h1>

                    <p>
                        Manage event locations, venue capacity and contact information.
                    </p>
                </div>

                <a href="add_venue.php" class="venue-primary-btn">
                    <i class="fas fa-plus"></i>
                    Add New Venue
                </a>

            </div>

            <!-- STATISTICS -->
            <div class="venue-summary-grid">

                <div class="venue-summary-card">
                    <div class="venue-summary-icon blue">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>

                    <div>
                        <span>Total Venues</span>
                        <h3><?= $totalVenues ?></h3>
                    </div>
                </div>

                <div class="venue-summary-card">
                    <div class="venue-summary-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <div>
                        <span>Active Venues</span>
                        <h3><?= $activeVenues ?></h3>
                    </div>
                </div>

                <div class="venue-summary-card">
                    <div class="venue-summary-icon orange">
                        <i class="fas fa-pause-circle"></i>
                    </div>

                    <div>
                        <span>Inactive Venues</span>
                        <h3><?= $inactiveVenues ?></h3>
                    </div>
                </div>

                <div class="venue-summary-card">
                    <div class="venue-summary-icon purple">
                        <i class="fas fa-users"></i>
                    </div>

                    <div>
                        <span>Total Capacity</span>
                        <h3><?= number_format($totalCapacity) ?></h3>
                    </div>
                </div>

            </div>

            <!-- VENUE MANAGEMENT -->
            <div class="venue-management-card">

                <div class="venue-section-heading">

                    <div>
                        <h2>Venue List</h2>
                        <p>View and manage all registered event venues.</p>
                    </div>

                    <span class="venue-record-count">
                        <?= count($venues) ?> venues shown
                    </span>

                </div>

                <!-- SEARCH AND FILTER -->
                <form method="GET" class="venue-filter-form">

                    <div class="venue-search-box">
                        <i class="fas fa-search"></i>

                        <input
                            type="search"
                            name="search"
                            placeholder="Search venue by name..."
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <select name="status" class="venue-status-filter">
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

                    <button type="submit" class="venue-search-btn">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>

                    <a href="venues.php" class="venue-reset-btn">
                        Reset
                    </a>

                </form>

                <!-- VENUE TABLE -->
                <div class="venue-table-responsive">

                    <table class="venue-table">

                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Address</th>
                                <th>Capacity</th>
                                <th>Contact Phone</th>
                                <th>Status</th>
                                <th>Manage</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($venues)): ?>

                            <?php foreach ($venues as $venue): ?>

                                <tr>

                                    <td>
                                        <div class="venue-name-cell">

                                            <div class="venue-row-icon">
                                                <i class="fas fa-building"></i>
                                            </div>

                                            <div>
                                                <strong>
                                                    <?= htmlspecialchars($venue['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <small>
                                                    Venue ID: #<?= (int)$venue['venue_id'] ?>
                                                </small>
                                            </div>

                                        </div>
                                    </td>

                                    <td>
                                        <div class="venue-address-cell">
                                            <i class="fas fa-map-marker-alt"></i>

                                            <span>
                                                <?= !empty($venue['address'])
                                                    ? nl2br(htmlspecialchars($venue['address'], ENT_QUOTES, 'UTF-8'))
                                                    : 'Not provided' ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="venue-capacity">
                                            <i class="fas fa-users"></i>

                                            <?= $venue['capacity'] !== null
                                                ? number_format((int)$venue['capacity'])
                                                : 'Not specified' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="venue-phone">
                                            <i class="fas fa-phone-alt"></i>

                                            <?= !empty($venue['contact_phone'])
                                                ? htmlspecialchars($venue['contact_phone'], ENT_QUOTES, 'UTF-8')
                                                : 'Not provided' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="venue-status-badge <?= htmlspecialchars($venue['status'] ?? 'inactive', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-circle"></i>

                                            <?= htmlspecialchars(
                                                ucfirst($venue['status'] ?? 'inactive'),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="venue-action-buttons">

                                            <a href="edit_venue.php?venue_id=<?php echo $venue['venue_id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="delete_venue.php"
                                                onsubmit="return confirm('Are you sure you want to delete this venue?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="venue_id"
                                                    value="<?= (int)$venue['venue_id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="venue-delete-btn"
                                                    title="Delete Venue"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>

                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="venue-no-data">

                                    <i class="fas fa-map-marked-alt"></i>

                                    <h3>No Venues Found</h3>

                                    <p>
                                        <?= $search !== '' || $statusFilter !== ''
                                            ? 'Try changing your search or filter.'
                                            : 'No venues have been added yet. Add your first venue to get started.' ?>
                                    </p>

                                    <a href="add_venue.php" class="venue-secondary-btn">
                                        <i class="fas fa-plus"></i>
                                        Add New Venue
                                    </a>

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

</body>
</html>

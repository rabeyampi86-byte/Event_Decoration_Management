<?php
require_once __DIR__ . '/../db.php';

function vendorEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatuses = ['active', 'inactive', 'pending'];

$sql = "SELECT * FROM vendors WHERE 1=1";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (vendor_name LIKE ? OR service_type LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $term = "%{$search}%";
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND status = ?";
    $types .= 's';
    $params[] = $status;
}

$sql .= " ORDER BY vendor_id DESC";

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors Management</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .page-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px}
        .page-heading h2{color:#1e293b}
        .add-btn{background:#2563eb;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;white-space:nowrap}
        .filter-form{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
        .filter-form input,.filter-form select{padding:10px;border:1px solid #cbd5e1;border-radius:6px;min-width:180px}
        .filter-btn{background:#2563eb;color:#fff;border:0;padding:10px 16px;border-radius:6px;cursor:pointer}
        .reset-btn{color:#475569;text-decoration:none;padding:10px}
        .table-container{background:#fff;padding:20px;border-radius:10px;overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:13px;text-align:left;border-bottom:1px solid #e2e8f0;vertical-align:middle}
        th{background:#f8fafc;color:#475569;white-space:nowrap}
        .status{padding:5px 10px;border-radius:20px;font-size:12px;text-transform:capitalize}
        .active{background:#dcfce7;color:#166534}
        .inactive{background:#fee2e2;color:#991b1b}
        .pending{background:#fef3c7;color:#92400e}
        .action-links a{margin-right:10px;text-decoration:none}
        .view-link{color:#2563eb}.edit-link{color:#d97706}.delete-link{color:#dc2626}
        .empty-message{text-align:center;padding:25px;color:#64748b}
        .alert-success{color:#166534;background:#dcfce7;padding:10px;border-radius:6px;margin-bottom:15px}
        .alert-error{color:#991b1b;background:#fee2e2;padding:10px;border-radius:6px;margin-bottom:15px}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <h2><i class="fas fa-truck-field"></i> Vendors Management</h2>
                <a href="add_vendor.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Vendor
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo 'Vendor added successfully.';
                    elseif ($_GET['success'] === 'updated') echo 'Vendor updated successfully.';
                    elseif ($_GET['success'] === 'deleted') echo 'Vendor deleted successfully.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-error">
                    Unable to complete the operation.
                </div>
            <?php endif; ?>

            <form method="GET" class="filter-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Search vendor, service, phone..."
                    value="<?= vendorEscape($search); ?>"
                >

                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>

                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i> Search
                </button>

                <a href="vendors.php" class="reset-btn">Reset</a>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vendor Name</th>
                            <th>Service Type</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($vendor = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$vendor['vendor_id']; ?></td>
                                <td><?= vendorEscape($vendor['vendor_name']); ?></td>
                                <td><?= vendorEscape($vendor['service_type'] ?: '—'); ?></td>
                                <td><?= vendorEscape($vendor['contact_person'] ?: '—'); ?></td>
                                <td><?= vendorEscape($vendor['phone'] ?: '—'); ?></td>
                                <td><?= vendorEscape($vendor['email'] ?: '—'); ?></td>
                                <td>
                                    <span class="status <?= vendorEscape($vendor['status']); ?>">
                                        <?= vendorEscape($vendor['status']); ?>
                                    </span>
                                </td>
                                <td class="action-links">
                                    <a class="view-link" title="View"
                                       href="view_vendor.php?id=<?= (int)$vendor['vendor_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a class="edit-link" title="Edit"
                                       href="edit_vendor.php?id=<?= (int)$vendor['vendor_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a class="delete-link" title="Delete"
                                       href="delete_vendor.php?id=<?= (int)$vendor['vendor_id']; ?>"
                                       onclick="return confirm('Are you sure you want to delete this vendor?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-message">No vendors found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
</body>
</html>

<?php
require_once __DIR__ . '/../db.php';

// ==============================
// Search and Filter
// ==============================

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(
        tm.name LIKE ?
        OR tm.designation LIKE ?
        OR CAST(tm.employee_id AS CHAR) LIKE ?
    )";

    $keyword = "%{$search}%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= 'sss';
}

if ($status === 'active') {
    $where[] = "tm.is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "tm.is_active = 0";
}

$whereSQL = !empty($where)
    ? "WHERE " . implode(" AND ", $where)
    : "";

// ==============================
// Statistics
// ==============================

$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1) AS active,
        SUM(is_active = 0) AS inactive
    FROM team_members
");

if ($result && $row = $result->fetch_assoc()) {
    $stats['total'] = (int) $row['total'];
    $stats['active'] = (int) $row['active'];
    $stats['inactive'] = (int) $row['inactive'];
}

// ==============================
// Fetch Team Members
// ==============================

$sql = "
    SELECT
        tm.team_member_id,
        tm.employee_id,
        tm.name,
        tm.designation,
        tm.description,
        tm.image_id,
        tm.display_order,
        tm.is_active,
        tm.created_at,
        m.file_name,
        m.file_path,
        m.alt_text
    FROM team_members tm
    LEFT JOIN media m
        ON tm.image_id = m.media_id
    $whereSQL
    ORDER BY tm.display_order ASC, tm.team_member_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query preparation failed.");
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$members = $stmt->get_result();

// ==============================
// Helper
// ==============================

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Team Members | Admin Dashboard</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet"
          href="includes/css/admin.css">

    <style>
        .team-page {
            padding: 25px 30px 35px;
            margin-top: 20px;
        }

        .team-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .team-page-header h2 {
            color: #1e293b;
            font-size: 25px;
            margin: 0 0 7px;
        }

        .team-page-header p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .team-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .team-add-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .team-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .team-stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .team-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #eff6ff;
            color: #2563eb;
        }

        .team-stat-card h3 {
            margin: 0 0 5px;
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .team-stat-card strong {
            font-size: 24px;
            color: #1e293b;
        }

        .team-management-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .team-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .team-filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .team-filter input,
        .team-filter select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            background: #fff;
        }

        .team-filter input {
            min-width: 240px;
        }

        .team-filter input:focus,
        .team-filter select:focus {
            border-color: #2563eb;
        }

        .team-filter button {
            border: none;
            padding: 10px 16px;
            background: #2563eb;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .team-reset-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 13px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
        }

        .team-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .team-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .team-table th {
            background: #f8fafc;
            color: #475569;
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .team-table td {
            padding: 14px 16px;
            color: #475569;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .team-member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .team-member-avatar {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            object-fit: cover;
            background: #eff6ff;
            border: 1px solid #e2e8f0;
        }

        .team-avatar-placeholder {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .team-member-name {
            color: #1e293b;
            font-weight: 600;
        }

        .team-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .team-status.active {
            background: #dcfce7;
            color: #15803d;
        }

        .team-status.inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .team-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .team-action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .team-view {
            background: #eff6ff;
            color: #2563eb;
        }

        .team-edit {
            background: #fef3c7;
            color: #b45309;
        }

        .team-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .team-empty {
            text-align: center;
            padding: 45px 20px !important;
            color: #64748b !important;
        }

        .team-empty i {
            display: block;
            font-size: 35px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        @media(max-width: 768px) {
            .team-page {
                padding: 20px 15px;
            }

            .team-stats {
                grid-template-columns: 1fr;
            }

            .team-filter,
            .team-filter-form {
                width: 100%;
            }

            .team-filter input {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="team-page">

            <!-- Page Header -->

            <div class="team-page-header">
                <div>
                    <h2>Team Member Management</h2>
                    <p>Manage your company team members and their information.</p>
                </div>

                <a href="add_team_member.php" class="team-add-btn">
                    <i class="fas fa-plus"></i>
                    Add Team Member
                </a>
            </div>

            <!-- Statistics -->

            <div class="team-stats">

                <div class="team-stat-card">
                    <div class="team-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3>Total Team Members</h3>
                        <strong><?php echo $stats['total']; ?></strong>
                    </div>
                </div>

                <div class="team-stat-card">
                    <div class="team-stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <h3>Active Members</h3>
                        <strong><?php echo $stats['active']; ?></strong>
                    </div>
                </div>

                <div class="team-stat-card">
                    <div class="team-stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div>
                        <h3>Inactive Members</h3>
                        <strong><?php echo $stats['inactive']; ?></strong>
                    </div>
                </div>

            </div>

            <!-- Team Management -->

            <div class="team-management-card">

                <div class="team-filter">

                    <form method="GET" class="team-filter-form">

                        <input
                            type="search"
                            name="search"
                            placeholder="Search name, designation or employee ID..."
                            value="<?php echo e($search); ?>"
                        >

                        <select name="status">
                            <option value="">All Members</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>

                        <button type="submit">
                            <i class="fas fa-search"></i>
                            Search
                        </button>

                        <a href="team_members.php" class="team-reset-btn">
                            Reset
                        </a>

                    </form>

                </div>

                <div class="team-table-wrapper">

                    <table class="team-table">

                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Employee ID</th>
                                <th>Designation</th>
                                <th>Display Order</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($members && $members->num_rows > 0): ?>

                            <?php while ($member = $members->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <div class="team-member-info">

                                            <?php if (!empty($member['file_path'])): ?>

    <img
        src="../<?php echo e($member['file_path']); ?>"
        alt="<?php echo e($member['alt_text'] ?: $member['name']); ?>"
        class="team-member-avatar"
    >

<?php else: ?>

    <div class="team-avatar-placeholder">
        <i class="fas fa-user"></i>
    </div>

<?php endif; ?>

                                            <span class="team-member-name">
                                                <?php echo e($member['name']); ?>
                                            </span>

                                        </div>
                                    </td>

                                    <td>
                                        <?php echo $member['employee_id']
                                            ? (int)$member['employee_id']
                                            : '—'; ?>
                                    </td>

                                    <td>
                                        <?php echo e($member['designation'] ?: '—'); ?>
                                    </td>

                                    <td>
                                        <?php echo (int)$member['display_order']; ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$member['is_active'] === 1): ?>
                                            <span class="team-status active">Active</span>
                                        <?php else: ?>
                                            <span class="team-status inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo $member['created_at']
                                            ? date('d M Y', strtotime($member['created_at']))
                                            : '—';
                                        ?>
                                    </td>

                                    <td>
                                        <div class="team-actions">

                                            <a
                                                href="view_team_member.php?team_member_id=<?php echo (int)$member['team_member_id']; ?>"
                                                class="team-action-btn team-view"
                                                title="View"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a
                                                href="edit_team_member.php?team_member_id=<?php echo (int)$member['team_member_id']; ?>"
                                                class="team-action-btn team-edit"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a
                                                href="delete_team_member.php?team_member_id=<?php echo (int)$member['team_member_id']; ?>"
                                                class="team-action-btn team-delete"
                                                title="Delete"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="team-empty">
                                    <i class="fas fa-users"></i>
                                    No team members found.
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

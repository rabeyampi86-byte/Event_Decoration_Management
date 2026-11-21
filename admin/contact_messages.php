
<?php
require_once __DIR__ . '/../db.php';

// ===============================
// Search and Filter
// ===============================

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatus = ['unread', 'read', 'replied'];

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(
        name LIKE ?
        OR email LIKE ?
        OR subject LIKE ?
        OR message LIKE ?
    )";

    $keyword = "%{$search}%";

    $params = array_merge($params, [
        $keyword, $keyword, $keyword, $keyword
    ]);

    $types .= 'ssss';
}

if (in_array($status, $allowedStatus, true)) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql = "SELECT * FROM contact_messages";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$messages = $stmt->get_result();

// ===============================
// Summary Statistics
// ===============================

$stats = [
    'total' => 0,
    'unread' => 0,
    'read' => 0,
    'replied' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'unread') AS unread,
        SUM(status = 'read') AS read_count,
        SUM(status = 'replied') AS replied
    FROM contact_messages
");

if ($result && $row = $result->fetch_assoc()) {
    $stats['total'] = (int)$row['total'];
    $stats['unread'] = (int)$row['unread'];
    $stats['read'] = (int)$row['read_count'];
    $stats['replied'] = (int)$row['replied'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Message Management</title>

    <link rel="stylesheet"
          href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .contact-page {
            padding: 25px 30px 35px;
            color: #475569;
        }

        .contact-page * {
            box-sizing: border-box;
        }

        .contact-header {
            margin-bottom: 25px;
        }

        .contact-header h2 {
            margin: 0 0 7px;
            color: #1e293b;
            font-size: 25px;
        }

        .contact-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .contact-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .contact-stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .contact-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .contact-stat-icon.blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .contact-stat-icon.orange {
            background: #ffedd5;
            color: #ea580c;
        }

        .contact-stat-icon.green {
            background: #dcfce7;
            color: #16a34a;
        }

        .contact-stat-icon.purple {
            background: #ede9fe;
            color: #7c3aed;
        }

        .contact-stat-card h3 {
            margin: 0 0 5px;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .contact-stat-card strong {
            color: #1e293b;
            font-size: 23px;
        }

        .contact-management-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .contact-card-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .contact-card-header h3 {
            margin: 0;
            color: #1e293b;
            font-size: 17px;
        }

        .contact-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .contact-filter-form input,
        .contact-filter-form select {
            height: 42px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
        }

        .contact-filter-form input {
            flex: 1;
            min-width: 200px;
        }

        .contact-filter-form select {
            min-width: 150px;
            background: #fff;
        }

        .contact-filter-form button {
            height: 42px;
            padding: 0 18px;
            border: none;
            border-radius: 6px;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .contact-filter-form button:hover {
            background: #1d4ed8;
        }

        .contact-reset-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
            height: 42px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
        }

        .contact-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .contact-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        .contact-table th {
            padding: 14px 16px;
            background: #f8fafc;
            color: #475569;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .contact-table td {
            padding: 15px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #475569;
        }

        .contact-table tr:hover {
            background: #f8fafc;
        }

        .contact-name {
            color: #1e293b;
            font-weight: 600;
        }

        .contact-subject {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .contact-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-unread {
            background: #ffedd5;
            color: #c2410c;
        }

        .status-read {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-replied {
            background: #dcfce7;
            color: #15803d;
        }

        .contact-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .contact-view-btn {
            background: #dbeafe;
            color: #2563eb;
        }

        .contact-delete-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        .contact-empty {
            padding: 35px !important;
            text-align: center;
            color: #64748b !important;
        }

        @media (max-width: 1000px) {
            .contact-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .contact-page {
                padding: 20px 15px;
            }

            .contact-stats {
                grid-template-columns: 1fr;
            }
        }

.contact-add-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 18px;
    background: #2563eb;
    color: #fff;
    text-decoration: none;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 600;
    transition: .2s;
}

.contact-add-btn:hover {
    background: #1d4ed8;
    color: #fff;
}

    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="contact-page">

            
            <div class="contact-header" style="display:flex; justify-content:space-between; 
            align-items:center; gap:15px; flex-wrap:wrap;">

                <div>
                    <h2>Contact Message Management</h2>
                    <p>View and manage messages submitted by customers.</p>
                </div>

                <a href="add_contact_message.php" class="contact-add-btn">
                    <i class="fas fa-plus"></i>
                    Add Contact
                </a>

            </div>


            <!-- Summary Cards -->

            <div class="contact-stats">

                <div class="contact-stat-card">
                    <div class="contact-stat-icon blue">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h3>Total Messages</h3>
                        <strong><?php echo $stats['total']; ?></strong>
                    </div>
                </div>

                <div class="contact-stat-card">
                    <div class="contact-stat-icon orange">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div>
                        <h3>Unread Messages</h3>
                        <strong><?php echo $stats['unread']; ?></strong>
                    </div>
                </div>

                <div class="contact-stat-card">
                    <div class="contact-stat-icon blue">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div>
                        <h3>Read Messages</h3>
                        <strong><?php echo $stats['read']; ?></strong>
                    </div>
                </div>

                <div class="contact-stat-card">
                    <div class="contact-stat-icon green">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div>
                        <h3>Replied Messages</h3>
                        <strong><?php echo $stats['replied']; ?></strong>
                    </div>
                </div>

            </div>

            <!-- Message Table -->

            <div class="contact-management-card">

                <div class="contact-card-header">

                    <h3>All Contact Messages</h3>

                    <form method="GET" class="contact-filter-form">

                        <input
                            type="text"
                            name="search"
                            placeholder="Search name, email, subject or message..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                        <select name="status">
                            <option value="">All Status</option>

                            <option value="unread"
                                <?php echo $status === 'unread' ? 'selected' : ''; ?>>
                                Unread
                            </option>

                            <option value="read"
                                <?php echo $status === 'read' ? 'selected' : ''; ?>>
                                Read
                            </option>

                            <option value="replied"
                                <?php echo $status === 'replied' ? 'selected' : ''; ?>>
                                Replied
                            </option>
                        </select>

                        <button type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>

                        <a href="contact_messages.php" class="contact-reset-btn">
                            Reset
                        </a>

                    </form>

                </div>

                <div class="contact-table-wrapper">

                    <table class="contact-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sender</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Received At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($messages->num_rows > 0): ?>

                            <?php while ($message = $messages->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$message['message_id']; ?>
                                    </td>

                                    <td class="contact-name">
                                        <?php echo htmlspecialchars($message['name']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($message['email']); ?>
                                    </td>

                                    <td>
                                        <div class="contact-subject">
                                            <?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($message['phone'] ?: 'N/A'); ?>
                                    </td>

                                    <td>
                                        <span class="contact-status status-<?php echo htmlspecialchars($message['status'] ?? 'unread'); ?>">
                                            <?php echo htmlspecialchars($message['status'] ?? 'unread'); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo date('d M Y, h:i A', strtotime($message['created_at'])); ?>
                                    </td>

                                    <td>
                                        <div class="contact-actions">

                                            


                                            <!-- View -->
                                            <a
                                                href="view_contact_message.php?message_id=<?php echo (int)$message['message_id']; ?>"
                                                class="contact-action-btn contact-view-btn"
                                                title="View Message">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <!-- Edit -->
                                            <a
                                                href="edit_contact_message.php?message_id=<?php echo (int)$message['message_id']; ?>"
                                                class="contact-action-btn"
                                                style="background:#ede9fe;color:#7c3aed;"
                                                title="Edit Message">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Delete -->
                                            <a
                                                href="delete_contact_message.php?message_id=<?php echo (int)$message['message_id']; ?>"
                                                class="contact-action-btn contact-delete-btn"
                                                title="Delete Message">
                                                <i class="fas fa-trash"></i>
                                            </a>




                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="contact-empty">
                                    <i class="fas fa-inbox"></i>
                                    <br><br>
                                    No contact messages found.
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

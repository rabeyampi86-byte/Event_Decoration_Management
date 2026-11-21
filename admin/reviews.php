
<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatuses = ['pending', 'approved', 'rejected'];

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(CAST(review_id AS CHAR) LIKE ? OR CAST(booking_id AS CHAR) LIKE ? OR CAST(customer_id AS CHAR) LIKE ?)";
    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types .= 'sss';
}

if (in_array($status, $allowedStatuses, true)) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereSQL = !empty($where)
    ? "WHERE " . implode(" AND ", $where)
    : "";

/* =========================
   Review Statistics
========================= */

$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
    FROM reviews
");

if ($statsQuery && $statsRow = $statsQuery->fetch_assoc()) {
    foreach ($stats as $key => $value) {
        $stats[$key] = (int)($statsRow[$key] ?? 0);
    }
}

/* =========================
   Fetch Reviews
========================= */

$sql = "
    SELECT
        review_id,
        booking_id,
        customer_id,
        rating,
        comment,
        status,
        created_at
    FROM reviews
    $whereSQL
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$reviews = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review Management</title>

    <link rel="stylesheet" href="includes/css/admin.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .review-page {
            padding: 25px 30px 35px;
            margin-top: 20px;
            color: #1e293b;
        }

        .review-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .review-page-header h2 {
            margin: 0 0 6px;
            font-size: 24px;
        }

        .review-page-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .review-add-btn {
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
            white-space: nowrap;
        }

        .review-add-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .review-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .review-stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 20px;
        }

        .review-stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .review-stat-card .stat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 8px;
            font-size: 17px;
        }

        .review-stat-card h3 {
            margin: 0;
            font-size: 25px;
            color: #1e293b;
        }

        .review-stat-card p {
            margin: 6px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        .review-management-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            overflow: hidden;
        }

        .review-card-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .review-card-header h3 {
            margin: 0;
            font-size: 17px;
        }

        .review-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 17px;
        }

        .review-filter-form input,
        .review-filter-form select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 13px;
            color: #334155;
            outline: none;
        }

        .review-filter-form input {
            flex: 1;
            min-width: 200px;
        }

        .review-filter-form input:focus,
        .review-filter-form select:focus {
            border-color: #2563eb;
        }

        .review-filter-btn {
            padding: 10px 17px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .review-reset-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background: #f1f5f9;
            color: #334155;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
        }

        .review-table-wrapper {
            overflow-x: auto;
        }

        .review-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .review-table th {
            padding: 14px 16px;
            background: #f8fafc;
            color: #475569;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .review-table td {
            padding: 15px 16px;
            border-top: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
            vertical-align: middle;
        }

        .review-table tr:hover {
            background: #fafcff;
        }

        .review-comment {
            max-width: 230px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .review-stars {
            color: #f59e0b;
            white-space: nowrap;
            font-size: 13px;
        }

        .review-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .review-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .review-status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .review-status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .review-actions {
            display: flex;
            gap: 7px;
            white-space: nowrap;
        }

        .review-action-btn {
            width: 31px;
            height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
        }

        .review-view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .review-edit-btn {
            background: #f1f5f9;
            color: #475569;
        }

        .review-delete-btn {
            background: #fef2f2;
            color: #dc2626;
        }

        .review-action-btn:hover {
            opacity: .75;
        }

        .review-empty {
            text-align: center;
            padding: 45px 20px !important;
            color: #64748b !important;
        }

        .review-empty i {
            display: block;
            font-size: 32px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        @media (max-width: 1000px) {
            .review-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .review-page {
                padding: 20px 15px;
            }

            .review-page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .review-stats-grid {
                grid-template-columns: 1fr;
            }

            .review-card-header {
                padding: 15px;
            }

            .review-filter-form {
                flex-direction: column;
            }

            .review-filter-form input,
            .review-filter-form select {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="review-page">

            <div class="review-page-header">
                <div>
                    <h2>Review Management</h2>
                    <p>Manage customer reviews, ratings and feedback.</p>
                </div>

                <a href="add_review.php" class="review-add-btn">
                    <i class="fas fa-plus"></i>
                    Add Review
                </a>
            </div>

            <!-- Statistics -->

            <div class="review-stats-grid">

                <div class="review-stat-card">
                    <div class="stat-top">
                        <div>
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total Reviews</p>
                        </div>

                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                </div>

                <div class="review-stat-card">
                    <div class="stat-top">
                        <div>
                            <h3><?php echo $stats['pending']; ?></h3>
                            <p>Pending Reviews</p>
                        </div>

                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <div class="review-stat-card">
                    <div class="stat-top">
                        <div>
                            <h3><?php echo $stats['approved']; ?></h3>
                            <p>Approved Reviews</p>
                        </div>

                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="review-stat-card">
                    <div class="stat-top">
                        <div>
                            <h3><?php echo $stats['rejected']; ?></h3>
                            <p>Rejected Reviews</p>
                        </div>

                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Review Table -->

            <div class="review-management-card">

                <div class="review-card-header">

                    <h3>All Customer Reviews</h3>

                    <form method="GET" class="review-filter-form">

                        <input
                            type="text"
                            name="search"
                            placeholder="Search Review ID, Booking ID or Customer ID..."
                            value="<?php echo e($search); ?>"
                        >

                        <select name="status">
                            <option value="">All Status</option>

                            <option value="pending"
                                <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                                Pending
                            </option>

                            <option value="approved"
                                <?php echo $status === 'approved' ? 'selected' : ''; ?>>
                                Approved
                            </option>

                            <option value="rejected"
                                <?php echo $status === 'rejected' ? 'selected' : ''; ?>>
                                Rejected
                            </option>
                        </select>

                        <button type="submit" class="review-filter-btn">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>

                        <a href="reviews.php" class="review-reset-btn">
                            Reset
                        </a>

                    </form>

                </div>

                <div class="review-table-wrapper">

                    <table class="review-table">

                        <thead>
                            <tr>
                                <th>Review ID</th>
                                <th>Booking ID</th>
                                <th>Customer ID</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($reviews->num_rows > 0): ?>

                            <?php while ($review = $reviews->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$review['review_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$review['booking_id']; ?>
                                    </td>

                                    <td>
                                        #<?php echo (int)$review['customer_id']; ?>
                                    </td>

                                    <td>
                                        <span class="review-stars">
                                            <?php
                                            $rating = (int)$review['rating'];
                                            echo str_repeat('★', $rating);
                                            echo str_repeat('☆', 5 - $rating);
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="review-comment"
                                             title="<?php echo e($review['comment']); ?>">
                                            <?php
                                            echo $review['comment'] !== null &&
                                                 $review['comment'] !== ''
                                                ? e($review['comment'])
                                                : 'No comment';
                                            ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="review-status <?php echo e($review['status']); ?>">
                                            <?php echo e($review['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        echo e(date(
                                            'd M Y',
                                            strtotime($review['created_at'])
                                        ));
                                        ?>
                                    </td>

                                    <td>
                                        <div class="review-actions">

                                            <a href="view_review.php?review_id=<?php echo (int)$review['review_id']; ?>"
                                               class="review-action-btn review-view-btn"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="edit_review.php?review_id=<?php echo (int)$review['review_id']; ?>"
                                               class="review-action-btn review-edit-btn"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a href="delete_review.php?review_id=<?php echo (int)$review['review_id']; ?>"
                                               class="review-action-btn review-delete-btn"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="review-empty">
                                    <i class="fas fa-comments"></i>
                                    No reviews found.
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

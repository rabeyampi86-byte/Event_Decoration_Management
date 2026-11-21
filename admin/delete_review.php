
<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$review_id = filter_input(INPUT_GET, 'review_id', FILTER_VALIDATE_INT);

if (!$review_id) {
    header("Location: reviews.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT review_id, booking_id, customer_id, rating, comment, status, created_at
    FROM reviews
    WHERE review_id = ?
");

$stmt->bind_param("i", $review_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) {
    header("Location: reviews.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    $stmt = $conn->prepare("
        DELETE FROM reviews
        WHERE review_id = ?
    ");

    $stmt->bind_param("i", $review_id);

    if ($stmt->execute()) {
        $stmt->close();

        header("Location: reviews.php?success=deleted");
        exit;
    } else {
        $error = "Failed to delete review. Please try again.";
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Review</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.review-delete-page{padding:25px 30px 35px;margin-top:20px;color:#1e293b}
.review-delete-header{margin-bottom:25px}
.review-delete-header h2{margin:0 0 7px;font-size:24px}
.review-delete-header p{margin:0;color:#64748b;font-size:14px}
.review-delete-card{max-width:650px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:30px;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.review-delete-icon{width:55px;height:55px;display:flex;align-items:center;justify-content:center;background:#fef2f2;color:#dc2626;border-radius:50%;font-size:23px;margin-bottom:18px}
.review-delete-card h3{margin:0 0 10px;font-size:20px}
.review-delete-card p{color:#64748b;font-size:14px;line-height:1.6}
.review-delete-info{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;margin:22px 0}
.review-delete-info div{display:flex;justify-content:space-between;gap:15px;padding:8px 0;font-size:14px}
.review-delete-info span{color:#64748b}
.review-delete-info strong{color:#1e293b}
.review-delete-error{padding:12px 15px;margin-bottom:20px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:7px;font-size:14px}
.review-delete-actions{display:flex;gap:12px;margin-top:22px}
.review-confirm-btn,.review-cancel-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 19px;border:0;border-radius:7px;text-decoration:none;font-size:14px;font-weight:600;cursor:pointer}
.review-confirm-btn{background:#dc2626;color:#fff}
.review-confirm-btn:hover{background:#b91c1c}
.review-cancel-btn{background:#f1f5f9;color:#334155}
.review-cancel-btn:hover{background:#e2e8f0}
@media(max-width:600px){.review-delete-page{padding:20px 15px}.review-delete-card{padding:20px}.review-delete-info div{flex-direction:column;gap:4px}}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="review-delete-page">

    <div class="review-delete-header">
        <h2>Delete Review</h2>
        <p>Review the information before deleting this record.</p>
    </div>

    <div class="review-delete-card">

        <div class="review-delete-icon">
            <i class="fas fa-trash-alt"></i>
        </div>

        <h3>Are you sure you want to delete this review?</h3>

        <p>
            This action will permanently remove this review record.
            This operation cannot be undone.
        </p>

        <?php if ($error !== ''): ?>
            <div class="review-delete-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="review-delete-info">

            <div>
                <span>Review ID</span>
                <strong>#<?php echo (int)$review['review_id']; ?></strong>
            </div>

            <div>
                <span>Booking ID</span>
                <strong>#<?php echo (int)$review['booking_id']; ?></strong>
            </div>

            <div>
                <span>Customer ID</span>
                <strong>#<?php echo (int)$review['customer_id']; ?></strong>
            </div>

            <div>
                <span>Rating</span>
                <strong><?php echo (int)$review['rating']; ?>/5 Stars</strong>
            </div>

            <div>
                <span>Status</span>
                <strong><?php echo htmlspecialchars($review['status']); ?></strong>
            </div>

        </div>

        <form method="POST">

            <div class="review-delete-actions">

                <button
                    type="submit"
                    name="confirm_delete"
                    value="1"
                    class="review-confirm-btn"
                    onclick="return confirm('Are you sure you want to permanently delete this review?');"
                >
                    <i class="fas fa-trash"></i>
                    Yes, Delete Review
                </button>

                <a href="reviews.php" class="review-cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

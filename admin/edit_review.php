
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
    SELECT *
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

$rating = (string)$review['rating'];
$comment = $review['comment'] ?? '';
$status = $review['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating = trim($_POST['rating'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (
        !ctype_digit($rating) ||
        (int)$rating < 1 ||
        (int)$rating > 5
    ) {
        $error = "Rating must be between 1 and 5.";
    } elseif (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $error = "Please select a valid review status.";
    } else {

        $rating_value = (int)$rating;

        $stmt = $conn->prepare("
            UPDATE reviews
            SET rating = ?,
                comment = ?,
                status = ?
            WHERE review_id = ?
        ");

        $stmt->bind_param(
            "issi",
            $rating_value,
            $comment,
            $status,
            $review_id
        );

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: reviews.php?success=updated");
            exit;
        } else {
            $error = "Failed to update review.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Review</title>

<link rel="stylesheet" href="includes/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.review-form-page{padding:25px 30px 35px;margin-top:20px;color:#1e293b}
.review-form-header{margin-bottom:25px}
.review-form-header h2{margin:0 0 7px;font-size:24px}
.review-form-header p{margin:0;color:#64748b;font-size:14px}
.review-form-card{max-width:850px;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}
.review-form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
.review-form-group{display:flex;flex-direction:column;gap:8px}
.review-form-group.full{grid-column:1/-1}
.review-form-group label{font-size:14px;font-weight:600;color:#334155}
.review-form-group input,.review-form-group select,.review-form-group textarea{width:100%;padding:12px 13px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none;color:#1e293b}
.review-form-group input:disabled{background:#f8fafc;color:#64748b}
.review-form-group textarea{min-height:130px;resize:vertical}
.review-form-group input:focus,.review-form-group select:focus,.review-form-group textarea:focus{border-color:#2563eb}
.review-error{padding:12px 15px;margin-bottom:20px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:7px;font-size:14px}
.review-form-actions{display:flex;gap:12px;margin-top:25px}
.review-submit-btn,.review-cancel-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 19px;border:0;border-radius:7px;text-decoration:none;font-size:14px;font-weight:600;cursor:pointer}
.review-submit-btn{background:#2563eb;color:#fff}
.review-submit-btn:hover{background:#1d4ed8}
.review-cancel-btn{background:#f1f5f9;color:#334155}
.review-cancel-btn:hover{background:#e2e8f0}
@media(max-width:650px){.review-form-page{padding:20px 15px}.review-form-card{padding:18px}.review-form-grid{grid-template-columns:1fr}.review-form-group.full{grid-column:auto}}
</style>
</head>

<body>
<div class="admin-container">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="review-form-page">

    <div class="review-form-header">
        <h2>Edit Review</h2>
        <p>Update rating, comment and review status.</p>
    </div>

    <div class="review-form-card">

        <?php if ($error !== ''): ?>
            <div class="review-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="review-form-grid">

                <div class="review-form-group">
                    <label>Booking ID</label>
                    <input type="text" value="#<?php echo (int)$review['booking_id']; ?>" disabled>
                </div>

                <div class="review-form-group">
                    <label>Customer ID</label>
                    <input type="text" value="#<?php echo (int)$review['customer_id']; ?>" disabled>
                </div>

                <div class="review-form-group">
                    <label for="rating">Rating *</label>
                    <select name="rating" id="rating" required>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (string)$rating === (string)$i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="review-form-group">
                    <label for="status">Review Status *</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="review-form-group full">
                    <label for="comment">Customer Comment</label>
                    <textarea name="comment" id="comment" placeholder="Enter customer feedback..."><?php echo htmlspecialchars($comment); ?></textarea>
                </div>

            </div>

            <div class="review-form-actions">
                <button type="submit" class="review-submit-btn">
                    <i class="fas fa-save"></i> Update Review
                </button>

                <a href="reviews.php" class="review-cancel-btn">Cancel</a>
            </div>

        </form>
    </div>

</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

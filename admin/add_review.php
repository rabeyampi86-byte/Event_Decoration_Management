
<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$booking_id = '';
$rating = '5';
$comment = '';
$status = 'pending';

/* Load bookings and their customer IDs */
$bookings = $conn->query("
    SELECT booking_id, customer_id
    FROM bookings
    ORDER BY booking_id DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $booking_id = trim($_POST['booking_id'] ?? '');
    $rating = trim($_POST['rating'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');

    if (
        !ctype_digit($booking_id) ||
        !ctype_digit($rating) ||
        (int)$rating < 1 ||
        (int)$rating > 5 ||
        !in_array($status, ['pending', 'approved', 'rejected'], true)
    ) {
        $error = "Please provide valid review information.";
    } else {

        // Get the customer belonging to this booking
        $check = $conn->prepare("
            SELECT customer_id
            FROM bookings
            WHERE booking_id = ?
        ");

        $check->bind_param("i", $booking_id);
        $check->execute();
        $booking = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$booking) {
            $error = "Selected booking does not exist.";
        } else {

            $customer_id = (int)$booking['customer_id'];
            $rating_value = (int)$rating;
            $comment_value = trim($comment);

            $stmt = $conn->prepare("
                INSERT INTO reviews
                (booking_id, customer_id, rating, comment, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iiiss",
                $booking_id,
                $customer_id,
                $rating_value,
                $comment_value,
                $status
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: reviews.php?success=added");
                exit;
            } else {
                $error = "Failed to add review.";
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Review</title>

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
.review-form-group textarea{min-height:120px;resize:vertical}
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
        <h2>Add New Review</h2>
        <p>Create a customer review for an existing booking.</p>
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
                    <label for="booking_id">Booking ID *</label>
                    <select name="booking_id" id="booking_id" required>
                        <option value="">Select Booking</option>
                        <?php if ($bookings): ?>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <option
                                    value="<?php echo (int)$b['booking_id']; ?>"
                                    data-customer="<?php echo (int)$b['customer_id']; ?>"
                                    <?php echo (string)$booking_id === (string)$b['booking_id'] ? 'selected' : ''; ?>
                                >
                                    Booking #<?php echo (int)$b['booking_id']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="review-form-group">
                    <label for="customer_id">Customer ID</label>
                    <input type="text" id="customer_id" readonly placeholder="Select a booking">
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
                    <label for="status">Status *</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="review-form-group full">
                    <label for="comment">Review Comment</label>
                    <textarea name="comment" id="comment" placeholder="Write customer feedback..."><?php echo htmlspecialchars($comment); ?></textarea>
                </div>

            </div>

            <div class="review-form-actions">
                <button type="submit" class="review-submit-btn">
                    <i class="fas fa-save"></i> Save Review
                </button>

                <a href="reviews.php" class="review-cancel-btn">Cancel</a>
            </div>

        </form>
    </div>
</section>
</main>
</div>

<?php include 'includes/footer.php'; ?>

<script>
const bookingSelect = document.getElementById('booking_id');
const customerInput = document.getElementById('customer_id');

function updateCustomerId() {
    const option = bookingSelect.options[bookingSelect.selectedIndex];
    customerInput.value = option ? (option.dataset.customer || '') : '';
}

bookingSelect.addEventListener('change', updateCustomerId);
updateCustomerId();
</script>

</body>
</html>

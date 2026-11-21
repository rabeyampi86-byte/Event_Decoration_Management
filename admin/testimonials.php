
<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function testimonialEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

/* =========================
   Handle POST Requests
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ADD / UPDATE */
    if ($action === 'save') {

        $testimonialId = (int)($_POST['testimonial_id'] ?? 0);
        $customerName = trim($_POST['customer_name'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $review = trim($_POST['review'] ?? '');
        $rating = (int)($_POST['rating'] ?? 5);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if ($customerName === '' || $review === '') {
            $error = 'Customer name and review are required.';
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Rating must be between 1 and 5.';
        } else {

            $designationValue = ($designation === '') ? null : $designation;

            if ($testimonialId > 0) {

                $stmt = $conn->prepare("
                    UPDATE testimonials
                    SET customer_name = ?,
                        designation = ?,
                        review = ?,
                        rating = ?,
                        image_id = NULLIF(?, 0),
                        is_visible = ?
                    WHERE testimonial_id = ?
                ");

                $stmt->bind_param(
                    "sssiiii",
                    $customerName,
                    $designationValue,
                    $review,
                    $rating,
                    $imageId,
                    $isVisible,
                    $testimonialId
                );

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO testimonials
                    (customer_name, designation, review, rating, image_id, is_visible)
                    VALUES (?, ?, ?, ?, NULLIF(?, 0), ?)
                ");

                $stmt->bind_param(
                    "sssiii",
                    $customerName,
                    $designationValue,
                    $review,
                    $rating,
                    $imageId,
                    $isVisible
                );
            }

            if ($stmt && $stmt->execute()) {
                header("Location: testimonials.php?success=1");
                exit;
            } else {
                $error = 'Unable to save testimonial.';
            }
        }
    }

    /* DELETE */
    if ($action === 'delete') {

        $testimonialId = (int)($_POST['testimonial_id'] ?? 0);

        $stmt = $conn->prepare("
            DELETE FROM testimonials
            WHERE testimonial_id = ?
        ");

        $stmt->bind_param("i", $testimonialId);

        if ($stmt->execute()) {
            header("Location: testimonials.php?deleted=1");
            exit;
        } else {
            $error = 'Unable to delete testimonial.';
        }
    }

    /* TOGGLE VISIBILITY */
    if ($action === 'toggle') {

        $testimonialId = (int)($_POST['testimonial_id'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE testimonials
            SET is_visible = IF(is_visible = 1, 0, 1)
            WHERE testimonial_id = ?
        ");

        $stmt->bind_param("i", $testimonialId);

        if ($stmt->execute()) {
            header("Location: testimonials.php?updated=1");
            exit;
        } else {
            $error = 'Unable to update visibility.';
        }
    }
}

/* =========================
   Success Messages
========================= */

if (isset($_GET['success'])) {
    $message = 'Testimonial saved successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Testimonial deleted successfully.';
} elseif (isset($_GET['updated'])) {
    $message = 'Visibility updated successfully.';
}

/* =========================
   Edit Data
========================= */

$editTestimonial = null;

if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    $stmt = $conn->prepare("
        SELECT *
        FROM testimonials
        WHERE testimonial_id = ?
    ");

    $stmt->bind_param("i", $editId);
    $stmt->execute();

    $editTestimonial = $stmt->get_result()->fetch_assoc();
}

/* =========================
   Media Library
========================= */

$mediaResult = $conn->query("
    SELECT media_id, file_name, file_path
    FROM media
    ORDER BY media_id DESC
");

/* =========================
   Statistics
========================= */

$stats = [
    'total' => 0,
    'visible' => 0,
    'hidden' => 0
];

$statsResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_visible = 1) AS visible,
        SUM(is_visible = 0) AS hidden
    FROM testimonials
");

if ($statsResult) {
    $stats = $statsResult->fetch_assoc();
}

/* =========================
   Testimonials List
========================= */

$testimonials = $conn->query("
    SELECT
        t.*,
        m.file_path,
        m.file_name
    FROM testimonials t
    LEFT JOIN media m ON t.image_id = m.media_id
    ORDER BY t.testimonial_id DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Testimonials Management</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        .testimonial-page {
            padding: 25px;
            background: #f5f7fb;
            min-height: 100vh;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .page-heading h2 {
            color: #1e293b;
            font-size: 25px;
            margin: 0 0 6px;
        }

        .page-heading p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 22px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .stat-card span {
            color: #64748b;
            font-size: 14px;
        }

        .stat-card h3 {
            color: #1e293b;
            font-size: 27px;
            margin: 10px 0 0;
        }

        .content-card {
            background: #fff;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .content-card h3 {
            color: #1e293b;
            margin: 0 0 22px;
            font-size: 19px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 115px;
            resize: vertical;
        }

        .visibility-check {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #475569;
            font-size: 14px;
        }

        .visibility-check input {
            width: 16px;
            height: 16px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 17px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-status {
            background: #eff6ff;
            color: #2563eb;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .testimonial-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .testimonial-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .testimonial-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 13px;
            vertical-align: middle;
        }

        .customer-image {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 50%;
            background: #f1f5f9;
        }

        .no-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        .stars {
            color: #f59e0b;
            white-space: nowrap;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-visible {
            background: #dcfce7;
            color: #15803d;
        }

        .status-hidden {
            background: #f1f5f9;
            color: #64748b;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .action-buttons form {
            margin: 0;
        }

        .action-buttons .btn {
            padding: 8px 10px;
        }

        .review-text {
            max-width: 280px;
            line-height: 1.5;
        }

        .media-preview {
            display: none;
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
        }

        @media(max-width: 768px) {
            .testimonial-page {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="testimonial-page">

            <div class="page-heading">
                <div>
                    <h2>Testimonials</h2>
                    <p>Manage customer reviews and testimonials.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo testimonialEscape($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo testimonialEscape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->

            <div class="stats-grid">

                <div class="stat-card">
                    <span>Total Testimonials</span>
                    <h3><?php echo (int)$stats['total']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Visible Testimonials</span>
                    <h3><?php echo (int)$stats['visible']; ?></h3>
                </div>

                <div class="stat-card">
                    <span>Hidden Testimonials</span>
                    <h3><?php echo (int)$stats['hidden']; ?></h3>
                </div>

            </div>

            <!-- Add / Edit Form -->

            <div class="content-card">

                <h3>
                    <?php echo $editTestimonial ? 'Edit Testimonial' : 'Add New Testimonial'; ?>
                </h3>

                <form method="POST" action="testimonials.php">

                    <input type="hidden" name="action" value="save">

                    <input type="hidden"
                           name="testimonial_id"
                           value="<?php echo (int)($editTestimonial['testimonial_id'] ?? 0); ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Customer Name *</label>

                            <input type="text"
                                   name="customer_name"
                                   maxlength="150"
                                   required
                                   value="<?php echo testimonialEscape($editTestimonial['customer_name'] ?? ''); ?>"
                                   placeholder="Enter customer name">
                        </div>

                        <div class="form-group">
                            <label>Designation</label>

                            <input type="text"
                                   name="designation"
                                   maxlength="120"
                                   value="<?php echo testimonialEscape($editTestimonial['designation'] ?? ''); ?>"
                                   placeholder="Example: Corporate Client">
                        </div>

                        <div class="form-group full">
                            <label>Customer Review *</label>

                            <textarea name="review"
                                      required
                                      placeholder="Write customer review..."><?php echo testimonialEscape($editTestimonial['review'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Rating</label>

                            <select name="rating">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <option value="<?php echo $i; ?>"
                                        <?php echo (int)($editTestimonial['rating'] ?? 5) === $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>

                                <?php endfor; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Customer Image</label>

                            <select name="image_id" id="imageSelect"
                                    onchange="previewMediaImage()">

                                <option value="0">No Image</option>

                                <?php if ($mediaResult): ?>
                                    <?php while ($media = $mediaResult->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo (int)$media['media_id']; ?>"
                                            data-image="<?php echo testimonialEscape($media['file_path']); ?>"
                                            <?php echo (int)($editTestimonial['image_id'] ?? 0) === (int)$media['media_id'] ? 'selected' : ''; ?>>

                                            <?php echo testimonialEscape($media['file_name']); ?>

                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>

                            <img id="mediaPreview"
                                 class="media-preview"
                                 alt="Selected customer image">
                        </div>

                        <div class="form-group full">

                            <label class="visibility-check">
                                <input type="checkbox"
                                       name="is_visible"
                                       value="1"
                                       <?php echo !isset($editTestimonial) || (int)$editTestimonial['is_visible'] === 1 ? 'checked' : ''; ?>>

                                Show this testimonial on the website
                            </label>

                        </div>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>

                            <?php echo $editTestimonial ? 'Update Testimonial' : 'Save Testimonial'; ?>
                        </button>

                        <?php if ($editTestimonial): ?>

                            <a href="testimonials.php" class="btn btn-secondary">
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

            <!-- Testimonials Table -->

            <div class="content-card">

                <h3>All Testimonials</h3>

                <div class="table-wrapper">

                    <table class="testimonial-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Review</th>
                                <th>Rating</th>
                                <th>Visibility</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($testimonials && $testimonials->num_rows > 0): ?>

                            <?php while ($row = $testimonials->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?php echo (int)$row['testimonial_id']; ?>
                                    </td>

                                    <td>

                                        <div style="display:flex;align-items:center;gap:10px;">

                                            <?php if (!empty($row['file_path'])): ?>

                                                <img class="customer-image"
                                                     src="<?php echo testimonialEscape($row['file_path']); ?>"
                                                     alt="Customer">

                                            <?php else: ?>

                                                <div class="no-image">
                                                    <i class="fas fa-user"></i>
                                                </div>

                                            <?php endif; ?>

                                            <div>
                                                <strong><?php echo testimonialEscape($row['customer_name']); ?></strong>

                                                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                                    <?php echo testimonialEscape($row['designation'] ?: 'Customer'); ?>
                                                </div>
                                            </div>

                                        </div>

                                    </td>

                                    <td>
                                        <div class="review-text">
                                            <?php
                                            $reviewText = $row['review'];
                                            echo testimonialEscape(
                                                strlen($reviewText) > 100
                                                    ? substr($reviewText, 0, 100) . '...'
                                                    : $reviewText
                                            );
                                            ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="stars">
                                            <?php
                                            echo str_repeat('★', (int)$row['rating']);
                                            echo str_repeat('☆', 5 - (int)$row['rating']);
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int)$row['is_visible'] === 1): ?>

                                            <span class="status status-visible">
                                                Visible
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-hidden">
                                                Hidden
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                    </td>

                                    <td>

                                        <div class="action-buttons">

                                            <a href="testimonials.php?edit=<?php echo (int)$row['testimonial_id']; ?>"
                                               class="btn btn-status"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form method="POST"
                                                  action="testimonials.php">

                                                <input type="hidden" name="action" value="toggle">

                                                <input type="hidden"
                                                       name="testimonial_id"
                                                       value="<?php echo (int)$row['testimonial_id']; ?>">

                                                <button type="submit"
                                                        class="btn btn-status"
                                                        title="Change Visibility">

                                                    <i class="fas fa-eye"></i>

                                                </button>

                                            </form>

                                            <form method="POST"
                                                  action="testimonials.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this testimonial?');">

                                                <input type="hidden" name="action" value="delete">

                                                <input type="hidden"
                                                       name="testimonial_id"
                                                       value="<?php echo (int)$row['testimonial_id']; ?>">

                                                <button type="submit"
                                                        class="btn btn-danger"
                                                        title="Delete">

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" style="text-align:center;padding:30px;">
                                    No testimonials found.
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

<script>
function previewMediaImage() {

    const select = document.getElementById('imageSelect');
    const preview = document.getElementById('mediaPreview');

    const selectedOption = select.options[select.selectedIndex];
    const imagePath = selectedOption.getAttribute('data-image');

    if (imagePath) {
        preview.src = imagePath;
        preview.style.display = 'block';
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    previewMediaImage();
});
</script>

</body>
</html>

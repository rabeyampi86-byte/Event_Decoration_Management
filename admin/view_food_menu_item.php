<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: food_menu_items.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("
    SELECT f.*, m.file_name, m.file_path, m.alt_text
    FROM food_menu_items f
    LEFT JOIN media m ON f.image_id = m.media_id
    WHERE f.food_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$food = $result->fetch_assoc();

$stmt->close();

if (!$food) {
    header("Location: food_menu_items.php?error=notfound");
    exit;
}

function viewFoodEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Food Menu Item</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }

        .details-card {
            max-width:750px;
            background:white;
            padding:25px;
            border-radius:10px;
        }

        .detail-row {
            display:flex;
            padding:14px 0;
            border-bottom:1px solid #e2e8f0;
        }

        .detail-label {
            width:180px;
            color:#64748b;
            font-weight:600;
        }

        .detail-value {
            flex:1;
            color:#1e293b;
        }

        .food-image {
            max-width:250px;
            max-height:200px;
            object-fit:cover;
            border-radius:8px;
        }

        .back-btn {
            display:inline-block;
            margin-top:20px;
            background:#2563eb;
            color:white;
            padding:10px 16px;
            text-decoration:none;
            border-radius:6px;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <h2 style="margin-bottom:20px;">Food Item Details</h2>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Food ID</div>
                    <div class="detail-value">
                        <?= (int)$food['food_id']; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Food Name</div>
                    <div class="detail-value">
                        <?= viewFoodEscape($food['food_name']); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">
                        <?= nl2br(viewFoodEscape($food['description'])); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image</div>
                    <div class="detail-value">

                        <?php if (!empty($food['file_path'])): ?>

                            <img
                                class="food-image"
                                src="../uploads/<?= viewFoodEscape($food['file_path']); ?>"
                                alt="<?= viewFoodEscape($food['alt_text'] ?: $food['food_name']); ?>"
                            >

                        <?php else: ?>

                            No image selected.

                        <?php endif; ?>

                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image ID</div>
                    <div class="detail-value">
                        <?= $food['image_id'] !== null ? (int)$food['image_id'] : 'Not selected'; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?= $food['is_active'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>

                <a href="food_menu_items.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Food Menu Items
                </a>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
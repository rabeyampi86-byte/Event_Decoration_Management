<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: flowers.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("
    SELECT f.*, m.file_name, m.file_path, m.alt_text
    FROM flowers f
    LEFT JOIN media m ON f.image_id = m.media_id
    WHERE f.flower_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$flower = $result->fetch_assoc();
$stmt->close();

if (!$flower) {
    header("Location: flowers.php?error=notfound");
    exit;
}

function viewFlowerEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Flower</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-content { padding:25px; }
        .details-card {
            max-width:750px; background:white; padding:25px;
            border-radius:10px;
        }
        .detail-row {
            display:flex; padding:14px 0;
            border-bottom:1px solid #e2e8f0;
        }
        .detail-label { width:180px; color:#64748b; font-weight:600; }
        .detail-value { flex:1; color:#1e293b; }
        .flower-image {
            max-width:250px; max-height:200px;
            object-fit:cover; border-radius:8px;
        }
        .back-btn {
            display:inline-block; margin-top:20px;
            background:#2563eb; color:white; padding:10px 16px;
            text-decoration:none; border-radius:6px;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Flower Details</h2>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Flower ID</div>
                    <div class="detail-value"><?= (int)$flower['flower_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Flower Name</div>
                    <div class="detail-value"><?= viewFlowerEscape($flower['flower_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">
                        <?= nl2br(viewFlowerEscape($flower['description'])); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image</div>
                    <div class="detail-value">
                        <?php if (!empty($flower['file_path'])): ?>
                            <img class="flower-image"
                                 src="../uploads/<?= viewFlowerEscape($flower['file_path']); ?>"
                                 alt="<?= viewFlowerEscape($flower['alt_text'] ?: $flower['flower_name']); ?>">
                        <?php else: ?>
                            No image selected.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image ID</div>
                    <div class="detail-value">
                        <?= $flower['image_id'] !== null ? (int)$flower['image_id'] : 'Not selected'; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?= $flower['is_active'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>

                <a href="flowers.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Flowers
                </a>

            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
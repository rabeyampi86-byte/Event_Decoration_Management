<?php
require_once __DIR__ . '/../db.php';

function bannerEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: banners.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT b.*, m.file_name, m.file_path, m.alt_text
    FROM banners b
    LEFT JOIN media m ON b.image_id = m.media_id
    WHERE b.banner_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();
$stmt->close();

if (!$banner) {
    header("Location: banners.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Banner</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-content { padding:25px; }
        .details-card { background:#fff; padding:25px; border-radius:10px; max-width:850px; }
        .details-card h2 { color:#1e293b; margin-top:0; }
        .detail-row { padding:13px 0; border-bottom:1px solid #e2e8f0; display:grid; grid-template-columns:180px 1fr; gap:15px; }
        .detail-label { font-weight:600; color:#475569; }
        .detail-value { color:#1e293b; overflow-wrap:anywhere; }
        .banner-preview { width:100%; max-width:500px; max-height:280px; object-fit:cover; border-radius:8px; }
        .btn { display:inline-block; padding:10px 16px; border-radius:6px; text-decoration:none; margin-top:20px; margin-right:8px; }
        .btn-edit { background:#2563eb; color:#fff; }
        .btn-back { background:#e2e8f0; color:#334155; }
        @media(max-width:600px) { .detail-row { grid-template-columns:1fr; gap:5px; } }
    </style>
</head>
<body>
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <div class="details-card">
                <h2><i class="fas fa-image"></i> Banner Details</h2>

                <div class="detail-row">
                    <div class="detail-label">Banner ID</div>
                    <div class="detail-value"><?= (int)$banner['banner_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Title</div>
                    <div class="detail-value"><?= bannerEscape($banner['title']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Subtitle</div>
                    <div class="detail-value"><?= nl2br(bannerEscape($banner['subtitle'])); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image</div>
                    <div class="detail-value">
                        <?php if (!empty($banner['file_path'])): ?>
                            <img class="banner-preview"
                                 src="../uploads/<?= bannerEscape($banner['file_path']); ?>"
                                 alt="<?= bannerEscape($banner['alt_text'] ?: $banner['title']); ?>">
                            <p><?= bannerEscape($banner['file_name']); ?></p>
                        <?php else: ?>
                            No image selected.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Button Text</div>
                    <div class="detail-value"><?= bannerEscape($banner['button_text']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Button Link</div>
                    <div class="detail-value"><?= bannerEscape($banner['button_link']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Sort Order</div>
                    <div class="detail-value"><?= (int)$banner['sort_order']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?= (int)$banner['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created At</div>
                    <div class="detail-value"><?= bannerEscape($banner['created_at']); ?></div>
                </div>

                <a href="edit_banner.php?id=<?= (int)$banner['banner_id']; ?>" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Banner
                </a>

                <a href="banners.php" class="btn btn-back">Back to Banners</a>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
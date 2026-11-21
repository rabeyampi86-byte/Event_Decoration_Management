<?php
require_once __DIR__ . '/../db.php';

function areaEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: decoration_areas.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT da.*, m.file_name, m.file_path, m.alt_text
    FROM decoration_areas da
    LEFT JOIN media m ON da.image_id = m.media_id
    WHERE da.area_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$area = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$area) {
    header("Location: decoration_areas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Decoration Area</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }
        .details-card {
            background:#fff; padding:25px;
            border-radius:10px; max-width:850px;
        }
        .details-card h2 { color:#1e293b; margin-top:0; }
        .detail-row {
            padding:14px 0; border-bottom:1px solid #e2e8f0;
            display:grid; grid-template-columns:180px 1fr; gap:15px;
        }
        .detail-label { font-weight:600; color:#475569; }
        .detail-value { color:#1e293b; overflow-wrap:anywhere; }
        .area-preview {
            width:100%; max-width:450px; max-height:280px;
            object-fit:cover; border-radius:8px;
        }
        .btn {
            display:inline-block; padding:10px 16px;
            border-radius:6px; text-decoration:none;
            margin-top:20px; margin-right:8px;
        }
        .btn-edit { background:#2563eb; color:#fff; }
        .btn-back { background:#e2e8f0; color:#334155; }
        @media(max-width:600px) {
            .detail-row { grid-template-columns:1fr; gap:5px; }
        }
    </style>
</head>

<body>
<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="details-card">

                <h2><i class="fas fa-map-marked-alt"></i> Decoration Area Details</h2>

                <div class="detail-row">
                    <div class="detail-label">Area ID</div>
                    <div class="detail-value"><?= (int)$area['area_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Area Name</div>
                    <div class="detail-value"><?= areaEscape($area['area_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">
                        <?= nl2br(areaEscape($area['description'])); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image</div>
                    <div class="detail-value">

                        <?php if (!empty($area['file_path'])): ?>
                            <img class="area-preview"
                                 src="../uploads/<?= areaEscape($area['file_path']); ?>"
                                 alt="<?= areaEscape($area['alt_text'] ?: $area['area_name']); ?>">
                            <p><?= areaEscape($area['file_name']); ?></p>
                        <?php else: ?>
                            No image selected.
                        <?php endif; ?>

                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?= (int)$area['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>

                <a href="edit_decoration_area.php?id=<?= (int)$area['area_id']; ?>"
                   class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Area
                </a>

                <a href="decoration_areas.php" class="btn btn-back">
                    Back to Decoration Areas
                </a>

            </div>

        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
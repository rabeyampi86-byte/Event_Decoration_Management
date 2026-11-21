<?php
require_once __DIR__ . '/../db.php';

function navEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: navigation.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM navigation_items
    WHERE nav_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: navigation.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Navigation Item</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }
        .details-card {
            background:#fff; padding:25px;
            border-radius:10px; max-width:800px;
        }
        .details-card h2 { color:#1e293b; margin-top:0; }
        .detail-row {
            padding:14px 0; border-bottom:1px solid #e2e8f0;
            display:grid; grid-template-columns:180px 1fr; gap:15px;
        }
        .detail-label { font-weight:600; color:#475569; }
        .detail-value { color:#1e293b; overflow-wrap:anywhere; }
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

                <h2><i class="fas fa-list"></i> Navigation Item Details</h2>

                <div class="detail-row">
                    <div class="detail-label">Navigation ID</div>
                    <div class="detail-value"><?= (int)$item['nav_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Label</div>
                    <div class="detail-value"><?= navEscape($item['label']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">URL</div>
                    <div class="detail-value"><?= navEscape($item['url']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Sort Order</div>
                    <div class="detail-value"><?= (int)$item['sort_order']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Visibility</div>
                    <div class="detail-value">
                        <?= (int)$item['is_visible'] === 1 ? 'Visible' : 'Hidden'; ?>
                    </div>
                </div>

                <a href="edit_navigation.php?id=<?= (int)$item['nav_id']; ?>"
                   class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Item
                </a>

                <a href="navigation.php" class="btn btn-back">
                    Back to Navigation
                </a>

            </div>

        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
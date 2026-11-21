<?php
require_once '../db.php';

function bannerEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$title = '';
$subtitle = '';
$image_id = '';
$button_text = '';
$button_link = '';
$sort_order = 0;
$is_active = 1;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $image_id = $_POST['image_id'] ?? '';
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

    $image_id = ($image_id === '') ? null : (int)$image_id;

    if ($image_id !== null) {
        $check = $conn->prepare("SELECT media_id FROM media WHERE media_id = ?");
        $check->bind_param("i", $image_id);
        $check->execute();
        $imageResult = $check->get_result();

        if ($imageResult->num_rows === 0) {
            $error = "Selected media image was not found.";
        }

        $check->close();
    }

    if ($error === '') {
        $stmt = $conn->prepare("
            INSERT INTO banners
            (title, subtitle, image_id, button_text, button_link, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssissii",
            $title,
            $subtitle,
            $image_id,
            $button_text,
            $button_link,
            $sort_order,
            $is_active
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: banners.php?success=added");
            exit;
        } else {
            $error = "Failed to add banner: " . $stmt->error;
        }

        $stmt->close();
    }
}

$mediaResult = $conn->query("
    SELECT media_id, file_name, file_path
    FROM media
    ORDER BY media_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Banner</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-content { padding:25px; }
        .form-card { background:#fff; padding:25px; border-radius:10px; max-width:850px; }
        .form-card h2 { color:#1e293b; margin-top:0; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; margin-bottom:7px; color:#334155; font-weight:600; }
        .form-group input, .form-group textarea, .form-group select {
            width:100%; padding:11px 12px; border:1px solid #cbd5e1;
            border-radius:6px; font-size:14px; box-sizing:border-box;
        }
        .form-group textarea { min-height:110px; resize:vertical; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .form-actions { display:flex; gap:10px; margin-top:22px; }
        .btn { border:0; padding:11px 18px; border-radius:6px; cursor:pointer; text-decoration:none; font-size:14px; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { background:#e2e8f0; color:#334155; }
        .alert-error { background:#fee2e2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:18px; }
        @media(max-width:650px) { .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Banner</h2>

                <?php if ($error): ?>
                    <div class="alert-error"><?= bannerEscape($error); ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Banner Title</label>
                        <input type="text" name="title" maxlength="255"
                               value="<?= bannerEscape($title); ?>">
                    </div>

                    <div class="form-group">
                        <label>Subtitle</label>
                        <textarea name="subtitle"><?= bannerEscape($subtitle); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Select Banner Image</label>
                        <select name="image_id">
                            <option value="">-- Select Image from Media Library --</option>
                            <?php if ($mediaResult): ?>
                                <?php while ($media = $mediaResult->fetch_assoc()): ?>
                                    <option value="<?= (int)$media['media_id']; ?>"
                                        <?= ((string)$image_id === (string)$media['media_id']) ? 'selected' : ''; ?>>
                                        <?= bannerEscape($media['file_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Button Text</label>
                            <input type="text" name="button_text" maxlength="100"
                                   value="<?= bannerEscape($button_text); ?>">
                        </div>

                        <div class="form-group">
                            <label>Button Link</label>
                            <input type="text" name="button_link" maxlength="500"
                                   placeholder="e.g. packages.php"
                                   value="<?= bannerEscape($button_link); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order"
                                   value="<?= (int)$sort_order; ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_active">
                                <option value="1" <?= $is_active === 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?= $is_active === 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Banner
                        </button>
                        <a href="banners.php" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
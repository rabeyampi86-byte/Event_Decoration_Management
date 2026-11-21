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
    SELECT * FROM decoration_areas
    WHERE area_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$area = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$area) {
    header("Location: decoration_areas.php");
    exit;
}

$area_name = $area['area_name'];
$description = $area['description'] ?? '';
$image_id = $area['image_id'] ?? '';
$is_active = (int)$area['is_active'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $area_name = trim($_POST['area_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_id = $_POST['image_id'] ?? '';
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

    $image_id = ($image_id === '') ? null : (int)$image_id;

    if ($area_name === '') {
        $error = "Area name is required.";
    } elseif (mb_strlen($area_name) > 150) {
        $error = "Area name cannot exceed 150 characters.";
    }

    if ($error === '' && $image_id !== null) {
        $check = $conn->prepare("SELECT media_id FROM media WHERE media_id = ?");
        $check->bind_param("i", $image_id);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $error = "Selected media image was not found.";
        }

        $check->close();
    }

    if ($error === '') {

        $update = $conn->prepare("
            UPDATE decoration_areas
            SET area_name = ?,
                description = ?,
                image_id = ?,
                is_active = ?
            WHERE area_id = ?
        ");

        $update->bind_param(
            "ssiii",
            $area_name,
            $description,
            $image_id,
            $is_active,
            $id
        );

        if ($update->execute()) {
            $update->close();
            header("Location: decoration_areas.php?success=updated");
            exit;
        } else {
            $error = "Failed to update decoration area: " . $update->error;
        }

        $update->close();
    }
}

$mediaResult = $conn->query("
    SELECT media_id, file_name
    FROM media
    ORDER BY media_id DESC
");

$currentImage = null;

if (!empty($image_id)) {
    $imageStmt = $conn->prepare("
        SELECT file_path, alt_text
        FROM media
        WHERE media_id = ?
    ");

    $imageStmt->bind_param("i", $image_id);
    $imageStmt->execute();

    $currentImage = $imageStmt->get_result()->fetch_assoc();
    $imageStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Decoration Area</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }
        .form-card {
            background:#fff; padding:25px;
            border-radius:10px; max-width:850px;
        }
        .form-card h2 { color:#1e293b; margin-top:0; }
        .form-group { margin-bottom:18px; }
        .form-group label {
            display:block; margin-bottom:7px;
            color:#334155; font-weight:600;
        }
        .form-group input, .form-group textarea, .form-group select {
            width:100%; padding:11px 12px;
            border:1px solid #cbd5e1; border-radius:6px;
            font-size:14px; box-sizing:border-box;
        }
        .form-group textarea { min-height:130px; resize:vertical; }
        .form-actions { display:flex; gap:10px; margin-top:22px; }
        .btn {
            border:0; padding:11px 18px; border-radius:6px;
            cursor:pointer; text-decoration:none; font-size:14px;
        }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { background:#e2e8f0; color:#334155; }
        .alert-error {
            background:#fee2e2; color:#991b1b;
            padding:12px; border-radius:6px; margin-bottom:18px;
        }
        .current-image {
            width:180px; height:110px; object-fit:cover;
            border-radius:7px; margin-top:10px;
        }
    </style>
</head>

<body>
<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="form-card">

                <h2><i class="fas fa-edit"></i> Edit Decoration Area</h2>

                <?php if ($error): ?>
                    <div class="alert-error"><?= areaEscape($error); ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Area Name *</label>
                        <input type="text" name="area_name" maxlength="150"
                               value="<?= areaEscape($area_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?= areaEscape($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Select Image from Media Library</label>

                        <select name="image_id">
                            <option value="">-- No Image --</option>

                            <?php if ($mediaResult): ?>
                                <?php while ($media = $mediaResult->fetch_assoc()): ?>
                                    <option value="<?= (int)$media['media_id']; ?>"
                                        <?= ((string)$image_id === (string)$media['media_id']) ? 'selected' : ''; ?>>
                                        <?= areaEscape($media['file_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </select>

                        <?php if (!empty($currentImage['file_path'])): ?>
                            <br>
                            <img class="current-image"
                                 src="../uploads/<?= areaEscape($currentImage['file_path']); ?>"
                                 alt="<?= areaEscape($currentImage['alt_text']); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active">
                            <option value="1" <?= $is_active === 1 ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="0" <?= $is_active === 0 ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Decoration Area
                        </button>

                        <a href="decoration_areas.php" class="btn btn-secondary">
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
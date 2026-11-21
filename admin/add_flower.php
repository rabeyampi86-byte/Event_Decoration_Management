<?php
require_once '../db.php';

$error = '';
$flower_name = '';
$description = '';
$image_id = '';
$is_active = 1;

$mediaResult = $conn->query("
    SELECT media_id, file_name, file_path
    FROM media
    ORDER BY media_id DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $flower_name = trim($_POST['flower_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_id = $_POST['image_id'] ?? '';
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

    if ($flower_name === '') {
        $error = 'Flower name is required.';
    } elseif (!in_array($is_active, [0, 1], true)) {
        $error = 'Invalid status selected.';
    } else {

        $image_id = ($image_id !== '') ? (int)$image_id : null;

        if ($image_id !== null) {
            $check = $conn->prepare("SELECT media_id FROM media WHERE media_id = ?");
            $check->bind_param("i", $image_id);
            $check->execute();
            $mediaCheck = $check->get_result();

            if ($mediaCheck->num_rows === 0) {
                $error = 'Selected image was not found.';
            }
            $check->close();
        }

        if ($error === '') {
            $stmt = $conn->prepare("
                INSERT INTO flowers
                (flower_name, description, image_id, is_active)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssii",
                $flower_name,
                $description,
                $image_id,
                $is_active
            );

            if ($stmt->execute()) {
                header("Location: flowers.php?success=added");
                exit;
            } else {
                $error = 'Failed to add flower.';
            }

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
    <title>Add Flower</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-content { padding:25px; }
        .form-container {
            max-width:750px; background:white; padding:25px;
            border-radius:10px;
        }
        .form-group { margin-bottom:20px; }
        .form-group label {
            display:block; margin-bottom:8px;
            color:#334155; font-weight:600;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width:100%; padding:11px; border:1px solid #cbd5e1;
            border-radius:6px; font-size:14px;
        }
        .form-group textarea { min-height:120px; resize:vertical; }
        .submit-btn {
            background:#2563eb; color:white; border:0;
            padding:11px 20px; border-radius:6px; cursor:pointer;
        }
        .back-btn {
            color:#475569; text-decoration:none; margin-left:12px;
        }
        .error {
            color:#b91c1c; background:#fee2e2;
            padding:10px; border-radius:6px; margin-bottom:15px;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Add New Flower</h2>

            <div class="form-container">

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Flower Name *</label>
                        <input type="text" name="flower_name"
                               maxlength="120" required
                               value="<?= htmlspecialchars($flower_name); ?>">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Select Image</label>
                        <select name="image_id">
                            <option value="">No Image</option>
                            <?php if ($mediaResult): ?>
                                <?php while ($media = $mediaResult->fetch_assoc()): ?>
                                    <option value="<?= (int)$media['media_id']; ?>"
                                        <?= (string)$image_id === (string)$media['media_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($media['file_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active">
                            <option value="1" <?= $is_active === 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?= $is_active === 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Save Flower
                    </button>

                    <a href="flowers.php" class="back-btn">Cancel</a>

                </form>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
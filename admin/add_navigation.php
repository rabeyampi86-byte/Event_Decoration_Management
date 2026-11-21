<?php
require_once '../db.php';

function navEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$label = '';
$url = '';
$sort_order = 0;
$is_visible = 1;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $label = trim($_POST['label'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_visible = isset($_POST['is_visible'])
        ? (int)$_POST['is_visible']
        : 0;

    if ($label === '' || $url === '') {
        $error = "Label and URL are required.";
    } elseif (strlen($label) > 100) {
        $error = "Label cannot exceed 100 characters.";
    } elseif (strlen($url) > 500) {
        $error = "URL cannot exceed 500 characters.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO navigation_items
            (label, url, sort_order, is_visible)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssii",
            $label,
            $url,
            $sort_order,
            $is_visible
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: navigation.php?success=added");
            exit;
        } else {
            $error = "Failed to add menu item: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Navigation Item</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content { padding:25px; }
        .form-card {
            background:#fff; padding:25px;
            border-radius:10px; max-width:800px;
        }
        .form-card h2 { color:#1e293b; margin-top:0; }
        .form-group { margin-bottom:18px; }
        .form-group label {
            display:block; margin-bottom:7px;
            color:#334155; font-weight:600;
        }
        .form-group input, .form-group select {
            width:100%; padding:11px 12px;
            border:1px solid #cbd5e1; border-radius:6px;
            font-size:14px; box-sizing:border-box;
        }
        .form-row {
            display:grid; grid-template-columns:1fr 1fr; gap:18px;
        }
        .form-actions {
            display:flex; gap:10px; margin-top:22px;
        }
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
        .form-help { color:#64748b; font-size:12px; margin-top:6px; }
        @media(max-width:650px) {
            .form-row { grid-template-columns:1fr; }
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

                <h2><i class="fas fa-plus-circle"></i> Add Menu Item</h2>

                <?php if ($error): ?>
                    <div class="alert-error"><?= navEscape($error); ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Menu Label *</label>
                        <input type="text" name="label" maxlength="100"
                               value="<?= navEscape($label); ?>"
                               placeholder="e.g. Home, About, Services"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Menu URL *</label>
                        <input type="text" name="url" maxlength="500"
                               value="<?= navEscape($url); ?>"
                               placeholder="e.g. index.php or packages.php"
                               required>
                        <div class="form-help">
                            Enter the website page address for this menu item.
                        </div>
                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order"
                                   value="<?= (int)$sort_order; ?>">
                            <div class="form-help">
                                Smaller numbers appear first.
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Visibility</label>
                            <select name="is_visible">
                                <option value="1" <?= $is_visible === 1 ? 'selected' : ''; ?>>
                                    Visible
                                </option>
                                <option value="0" <?= $is_visible === 0 ? 'selected' : ''; ?>>
                                    Hidden
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Menu Item
                        </button>

                        <a href="navigation.php" class="btn btn-secondary">
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
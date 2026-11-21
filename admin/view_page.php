
<?php
require_once __DIR__ . '/../db.php';

function pageEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: pages.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM pages WHERE page_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$page = $result->fetch_assoc();

if (!$page) {
    header("Location: pages.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Page | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .view-content { padding: 25px; }
        .page-heading { margin-bottom: 25px; }
        .page-heading h2 { color: #1e293b; font-size: 25px; margin: 0 0 6px; }
        .page-heading p { color: #64748b; margin: 0; font-size: 14px; }
        .details-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-size: 14px; font-weight: 600; }
        .detail-value { color: #1e293b; font-size: 14px; overflow-wrap: anywhere; }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-published { background: #dcfce7; color: #166534; }
        .status-draft { background: #f1f5f9; color: #475569; }
        .back-btn, .edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 17px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
        }
        .back-btn { background: #f1f5f9; color: #475569; }
        .edit-btn { background: #2563eb; color: #fff; }
        @media(max-width: 600px) {
            .detail-row { grid-template-columns: 1fr; gap: 6px; }
        }
    </style>
</head>
<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="view-content">

            <div class="page-heading">
                <h2>Page Details</h2>
                <p>View the information of this website page.</p>
            </div>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Page ID</div>
                    <div class="detail-value"><?= (int)$page['page_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Page Title</div>
                    <div class="detail-value"><?= pageEscape($page['page_title']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Page Slug</div>
                    <div class="detail-value"><?= pageEscape($page['page_slug']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Meta Title</div>
                    <div class="detail-value"><?= pageEscape($page['meta_title'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Meta Description</div>
                    <div class="detail-value"><?= nl2br(pageEscape($page['meta_description'] ?: 'Not set')); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status status-<?= pageEscape($page['status']); ?>">
                            <?= ucfirst(pageEscape($page['status'])); ?>
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value"><?= pageEscape($page['updated_at']); ?></div>
                </div>

                <a href="pages.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

                <a href="edit_page.php?id=<?= (int)$page['page_id']; ?>" class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Page
                </a>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

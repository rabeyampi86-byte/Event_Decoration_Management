
<?php
require_once __DIR__ . '/../db.php';

function sectionEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: page_sections.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT
        ps.*,
        p.page_title,
        m.file_name,
        m.file_path,
        m.alt_text
    FROM page_sections ps
    LEFT JOIN pages p ON ps.page_id = p.page_id
    LEFT JOIN media m ON ps.image_id = m.media_id
    WHERE ps.section_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$section = $stmt->get_result()->fetch_assoc();

if (!$section) {
    header("Location: page_sections.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Page Section | Admin Dashboard</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .view-content { padding: 25px; }
        .page-heading { margin-bottom: 25px; }
        .page-heading h2 { color: #1e293b; font-size: 25px; margin: 0 0 6px; }
        .page-heading p { color: #64748b; margin: 0; font-size: 14px; }
        .details-card {
            max-width: 900px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-size: 14px; font-weight: 600; }
        .detail-value { color: #1e293b; font-size: 14px; overflow-wrap: anywhere; }
        .section-image {
            max-width: 220px;
            max-height: 160px;
            object-fit: contain;
            border-radius: 7px;
            border: 1px solid #e2e8f0;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .visible { background: #dcfce7; color: #166534; }
        .hidden { background: #fee2e2; color: #b91c1c; }
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
        @media(max-width:600px) {
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
                <h2>Page Section Details</h2>
                <p>View the complete information of this section.</p>
            </div>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Section ID</div>
                    <div class="detail-value"><?= (int)$section['section_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Page</div>
                    <div class="detail-value"><?= sectionEscape($section['page_title'] ?? 'Unknown Page'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Section Key</div>
                    <div class="detail-value"><?= sectionEscape($section['section_key']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Section Title</div>
                    <div class="detail-value"><?= sectionEscape($section['section_title'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Heading</div>
                    <div class="detail-value"><?= sectionEscape($section['heading'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Subheading</div>
                    <div class="detail-value"><?= sectionEscape($section['subheading'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Content</div>
                    <div class="detail-value"><?= nl2br(sectionEscape($section['content'] ?: 'Not set')); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Image</div>
                    <div class="detail-value">
                        <?php if (!empty($section['file_path'])): ?>
                            <img
                                src="../uploads/<?= sectionEscape($section['file_path']); ?>"
                                alt="<?= sectionEscape($section['alt_text'] ?? 'Section image'); ?>"
                                class="section-image"
                            >
                            <p><?= sectionEscape($section['file_name']); ?></p>
                        <?php else: ?>
                            No image selected
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Button Text</div>
                    <div class="detail-value"><?= sectionEscape($section['button_text'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Button Link</div>
                    <div class="detail-value"><?= sectionEscape($section['button_link'] ?: 'Not set'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Sort Order</div>
                    <div class="detail-value"><?= (int)$section['sort_order']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Visibility</div>
                    <div class="detail-value">
                        <?php if ((int)$section['is_visible'] === 1): ?>
                            <span class="status visible">Visible</span>
                        <?php else: ?>
                            <span class="status hidden">Hidden</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created At</div>
                    <div class="detail-value"><?= sectionEscape($section['created_at']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value"><?= sectionEscape($section['updated_at']); ?></div>
                </div>

                <a href="page_sections.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

                <a href="edit_page_section.php?id=<?= (int)$section['section_id']; ?>" class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Section
                </a>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

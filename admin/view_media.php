
<?php
require_once __DIR__ . '/../db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$mediaId = (int)($_GET['media_id'] ?? 0);

if ($mediaId <= 0) {
    header('Location: media.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT media_id, file_name, file_path, file_type,
           alt_text, uploaded_by, created_at
    FROM media
    WHERE media_id = ?
");

$stmt->bind_param("i", $mediaId);
$stmt->execute();

$result = $stmt->get_result();
$media = $result->fetch_assoc();
$stmt->close();

if (!$media) {
    header('Location: media.php?error=not_found');
    exit;
}

$isImage = strpos((string)$media['file_type'], 'image/') === 0;
$imageUrl = '../uploads/' . $media['file_path'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Media | Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .view-media-page {
            padding: 25px;
        }

        .view-heading {
            margin-bottom: 25px;
        }

        .view-heading h2 {
            color: #1e293b;
            margin: 0 0 7px;
        }

        .view-heading p {
            color: #64748b;
            margin: 0;
        }

        .media-detail-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 30px;
            max-width: 1050px;
        }

        .media-large-preview {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            min-height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .media-large-preview img {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
        }

        .media-large-preview i {
            font-size: 70px;
            color: #94a3b8;
        }

        .media-details h3 {
            color: #1e293b;
            font-size: 21px;
            margin: 0 0 22px;
            overflow-wrap: anywhere;
        }

        .detail-item {
            padding: 13px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item label {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .detail-item p {
            color: #334155;
            margin: 0;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .media-detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 25px;
        }

        .media-detail-actions a {
            padding: 10px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .edit-media-btn {
            background: #2563eb;
            color: white;
        }

        .delete-media-btn {
            background: #fef2f2;
            color: #dc2626;
        }

        .back-media-btn {
            background: #f1f5f9;
            color: #475569;
        }

        @media(max-width: 750px) {
            .media-detail-card {
                grid-template-columns: 1fr;
            }

            .view-media-page {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="view-media-page">

            <div class="view-heading">
                <h2>Media Details</h2>
                <p>View uploaded media information.</p>
            </div>

            <div class="media-detail-card">

                <div class="media-large-preview">

                    <?php if ($isImage): ?>

                        <img
                            src="<?php echo e($imageUrl); ?>"
                            alt="<?php echo e($media['alt_text'] ?: $media['file_name']); ?>"
                        >

                    <?php else: ?>

                        <i class="fas fa-file"></i>

                    <?php endif; ?>

                </div>

                <div class="media-details">

                    <h3><?php echo e($media['file_name']); ?></h3>

                    <div class="detail-item">
                        <label>Media ID</label>
                        <p><?php echo (int)$media['media_id']; ?></p>
                    </div>

                    <div class="detail-item">
                        <label>File Name</label>
                        <p><?php echo e($media['file_name']); ?></p>
                    </div>

                    <div class="detail-item">
                        <label>File Type</label>
                        <p><?php echo e($media['file_type'] ?: 'Unknown'); ?></p>
                    </div>

                    <div class="detail-item">
                        <label>File Path</label>
                        <p><?php echo e($media['file_path']); ?></p>
                    </div>

                    <div class="detail-item">
                        <label>Alt Text</label>
                        <p>
                            <?php echo e($media['alt_text'] ?: 'Not provided'); ?>
                        </p>
                    </div>

                    <div class="detail-item">
                        <label>Uploaded By</label>
                        <p>
                            <?php echo $media['uploaded_by'] !== null
                                ? (int)$media['uploaded_by']
                                : 'Not recorded'; ?>
                        </p>
                    </div>

                    <div class="detail-item">
                        <label>Upload Date</label>
                        <p><?php echo e($media['created_at']); ?></p>
                    </div>

                    <div class="media-detail-actions">

                        <a
                            href="edit_media.php?media_id=<?php echo (int)$media['media_id']; ?>"
                            class="edit-media-btn"
                        >
                            <i class="fas fa-edit"></i> Edit
                        </a>

                        <a
                            href="delete_media.php?media_id=<?php echo (int)$media['media_id']; ?>"
                            class="delete-media-btn"
                        >
                            <i class="fas fa-trash"></i> Delete
                        </a>

                        <a href="media.php" class="back-media-btn">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

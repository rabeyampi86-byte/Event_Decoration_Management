
<?php
require_once __DIR__ . '/../db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$mediaId = (int)($_GET['media_id'] ?? $_POST['media_id'] ?? 0);

if ($mediaId <= 0) {
    header('Location: media.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT media_id, file_name, file_path, file_type
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $conn->prepare("
        DELETE FROM media
        WHERE media_id = ?
    ");

    if (!$stmt) {

        $error = 'Unable to prepare delete query.';

    } else {

        $stmt->bind_param("i", $mediaId);

        if ($stmt->execute()) {

            $stmt->close();

            $filePath = realpath(
                __DIR__ . '/../uploads/' . $media['file_path']
            );

            $mediaDirectory = realpath(
                __DIR__ . '/../uploads/media/'
            );

            if (
                $filePath &&
                $mediaDirectory &&
                strpos(
                    $filePath,
                    $mediaDirectory . DIRECTORY_SEPARATOR
                ) === 0 &&
                is_file($filePath)
            ) {
                unlink($filePath);
            }

            header('Location: media.php?deleted=success');
            exit;

        } else {

            $error = 'Unable to delete this media. It may be used by other website content.';

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

    <title>Delete Media | Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .delete-media-page {
            padding: 25px;
        }

        .delete-card {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }

        .delete-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
        }

        .delete-card h2 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        .delete-card p {
            color: #64748b;
            line-height: 1.6;
        }

        .delete-file-name {
            display: block;
            color: #1e293b;
            font-weight: 600;
            margin: 15px 0;
            overflow-wrap: anywhere;
        }

        .delete-warning {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 7px;
            font-size: 13px;
            margin: 20px 0;
        }

        .delete-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .delete-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .delete-actions button,
        .delete-actions a {
            padding: 11px 19px;
            border-radius: 7px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .confirm-delete {
            background: #dc2626;
            color: white;
        }

        .confirm-delete:hover {
            background: #b91c1c;
        }

        .cancel-delete {
            background: #f1f5f9;
            color: #475569;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="delete-media-page">

            <div class="delete-card">

                <div class="delete-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h2>Delete Media</h2>

                <p>
                    Are you sure you want to delete this media file?
                </p>

                <span class="delete-file-name">
                    <?php echo e($media['file_name']); ?>
                </span>

                <?php if ($error): ?>
                    <div class="delete-error">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <div class="delete-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    This action cannot be undone. The uploaded file will also be deleted.
                </div>

                <form method="POST">

                    <input
                        type="hidden"
                        name="media_id"
                        value="<?php echo $mediaId; ?>"
                    >

                    <div class="delete-actions">

                        <button type="submit" class="confirm-delete">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>

                        <a
                            href="view_media.php?media_id=<?php echo $mediaId; ?>"
                            class="cancel-delete"
                        >
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

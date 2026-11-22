
<?php
require_once __DIR__ . '/../db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['search'] ?? '');
$fileType = trim($_GET['type'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(file_name LIKE ? OR alt_text LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $types .= 'ss';
}

if ($fileType !== '') {
    $where[] = "file_type LIKE ?";
    $params[] = $fileType . '%';
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT media_id, file_name, file_path, file_type,
           alt_text, uploaded_by, created_at
    FROM media
    $whereSQL
    ORDER BY media_id DESC
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$mediaItems = [];
while ($row = $result->fetch_assoc()) {
    $mediaItems[] = $row;
}
$stmt->close();

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM media");
$totalMedia = (int)($totalResult->fetch_assoc()['total'] ?? 0);

$imageResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM media
    WHERE file_type LIKE 'image/%'
");
$totalImages = (int)($imageResult->fetch_assoc()['total'] ?? 0);

$otherMedia = $totalMedia - $totalImages;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library | Admin</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .media-page {
            padding: 25px;
        }

        .media-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .media-heading h2 {
            color: #1e293b;
            margin: 0 0 6px;
        }

        .media-heading p {
            color: #64748b;
            margin: 0;
        }

        .media-upload-btn {
            background: #2563eb;
            color: white;
            padding: 11px 17px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 600;
        }

        .media-upload-btn:hover {
            background: #1d4ed8;
        }

        .media-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .media-stat {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
        }

        .media-stat span {
            color: #64748b;
            font-size: 14px;
        }

        .media-stat h3 {
            color: #1e293b;
            font-size: 27px;
            margin: 9px 0 0;
        }

        .media-toolbar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .media-toolbar input,
        .media-toolbar select {
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .media-toolbar input {
            flex: 1;
            min-width: 200px;
        }

        .media-toolbar button {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 7px;
            padding: 10px 19px;
            cursor: pointer;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 20px;
        }

        .media-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .media-preview {
            height: 165px;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-preview i {
            font-size: 45px;
            color: #94a3b8;
        }

        .media-info {
            padding: 14px;
        }

        .media-info h4 {
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 7px;
            overflow-wrap: anywhere;
        }

        .media-info p {
            color: #64748b;
            font-size: 12px;
            margin: 0 0 10px;
        }

        .media-actions {
            display: flex;
            gap: 8px;
        }

        .media-actions a {
            flex: 1;
            text-align: center;
            padding: 8px 5px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .media-view {
            background: #eff6ff;
            color: #2563eb;
        }

        .media-delete {
            background: #fef2f2;
            color: #dc2626;
        }

        .media-empty {
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 55px 20px;
            text-align: center;
            color: #64748b;
            grid-column: 1 / -1;
        }

        .media-empty i {
            font-size: 38px;
            margin-bottom: 12px;
        }

        @media(max-width: 700px) {
            .media-stats {
                grid-template-columns: 1fr;
            }

            .media-page {
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

        <section class="media-page">

            <div class="media-heading">
                <div>
                    <h2>Media Library</h2>
                    <p>Manage and organize your website media files.</p>
                </div>

                <a href="add_media.php" class="media-upload-btn">
                    <i class="fas fa-upload"></i> Upload Media
                </a>
            </div>

            <div class="media-stats">

                <div class="media-stat">
                    <span>Total Media Files</span>
                    <h3><?php echo $totalMedia; ?></h3>
                </div>

                <div class="media-stat">
                    <span>Images</span>
                    <h3><?php echo $totalImages; ?></h3>
                </div>

                <div class="media-stat">
                    <span>Other Files</span>
                    <h3><?php echo $otherMedia; ?></h3>
                </div>

            </div>

            <form method="GET" class="media-toolbar">

                <input
                    type="search"
                    name="search"
                    placeholder="Search file name or alt text..."
                    value="<?php echo e($search); ?>"
                >

                <select name="type">
                    <option value="">All File Types</option>
                    <option value="image/" <?php echo $fileType === 'image/' ? 'selected' : ''; ?>>
                        Images
                    </option>
                    <option value="video/" <?php echo $fileType === 'video/' ? 'selected' : ''; ?>>
                        Videos
                    </option>
                    <option value="application/" <?php echo $fileType === 'application/' ? 'selected' : ''; ?>>
                        Documents
                    </option>
                </select>

                <button type="submit">
                    <i class="fas fa-search"></i> Search
                </button>

            </form>

            <div class="media-grid">

                <?php if (empty($mediaItems)): ?>

                    <div class="media-empty">
                        <i class="fas fa-photo-film"></i>
                        <h3>No Media Found</h3>
                        <p>Upload media files to see them here.</p>
                    </div>

                <?php else: ?>

                    <?php foreach ($mediaItems as $media): ?>

                        <div class="media-card">

                            <div class="media-preview">

                                <?php if (strpos((string)$media['file_type'], 'image/') === 0): ?>

                                    <?php
$imagePath = ltrim($media['file_path'], '/');

if (strpos($imagePath, 'uploads/') === 0) {
    $imageUrl = '../' . $imagePath;
} elseif (strpos($imagePath, 'media/') === 0) {
    $imageUrl = '../uploads/' . $imagePath;
} else {
    $imageUrl = '../' . $imagePath;
}
?>

<img
    src="<?php echo e($imageUrl); ?>"
    alt="<?php echo e($media['alt_text'] ?: $media['file_name']); ?>"
>
>

                                <?php else: ?>

                                    <i class="fas fa-file"></i>

                                <?php endif; ?>

                            </div>

                            <div class="media-info">

                                <h4><?php echo e($media['file_name']); ?></h4>

                                <p>
                                    <?php echo e($media['file_type'] ?: 'Unknown type'); ?>
                                </p>

                                <div class="media-actions">

                                    <a
                                        href="view_media.php?media_id=<?php echo (int)$media['media_id']; ?>"
                                        class="media-view"
                                    >
                                        <i class="fas fa-eye"></i> View
                                    </a>

                                    <a
                                        href="delete_media.php?media_id=<?php echo (int)$media['media_id']; ?>"
                                        class="media-delete"
                                    >
                                        <i class="fas fa-trash"></i> Delete
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

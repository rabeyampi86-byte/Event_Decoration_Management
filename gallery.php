
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'db.php';

$page_title = "Our Gallery";

function galleryEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Get active gallery albums
$albums = [];

$sql = "
    SELECT
        ga.album_id,
        ga.album_name,
        ga.description,
        ga.cover_image_id,
        ga.sort_order,
        ec.name AS category_name,
        m.file_path AS cover_path,
        m.alt_text AS cover_alt
    FROM gallery_albums ga
    LEFT JOIN event_categories ec
        ON ga.category_id = ec.category_id
    LEFT JOIN media m
        ON ga.cover_image_id = m.media_id
    WHERE ga.status = 'active'
    ORDER BY ga.sort_order ASC, ga.album_id DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $albums[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo galleryEscape($page_title); ?> | DecoEase</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <?php include_once __DIR__ . '/includes/nav_style.php'; ?>

    <style>
        .gallery-page {
            background: #fffaf7;
            min-height: 100vh;
            padding-bottom: 70px;
        }

        .gallery-hero {
            background: linear-gradient(135deg, #57283c, #b84a72);
            padding: 85px 20px;
            text-align: center;
            color: #fff;
        }

        .gallery-hero h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .gallery-hero p {
            max-width: 650px;
            margin: auto;
            font-size: 16px;
            line-height: 1.8;
            color: #fcecf2;
        }

        .gallery-container {
            max-width: 1200px;
            width: 92%;
            margin: 55px auto;
        }

        .gallery-section {
            margin-bottom: 65px;
            scroll-margin-top: 100px;
        }

        .gallery-section-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .gallery-section-heading h2 {
            color: #57283c;
            font-size: 29px;
            margin-bottom: 10px;
        }

        .gallery-section-heading p {
            color: #765568;
            font-size: 15px;
            line-height: 1.7;
        }

        .gallery-category {
            display: inline-block;
            background: #fff0f4;
            color: #b84a72;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(87, 40, 60, 0.10);
            cursor: pointer;
            aspect-ratio: 4 / 3;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.08);
        }

        .gallery-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 35px 15px 15px;
            color: #fff;
            background: linear-gradient(transparent, rgba(35, 15, 25, 0.8));
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-caption {
            opacity: 1;
        }

        .gallery-cover {
            margin-bottom: 22px;
            border-radius: 14px;
            overflow: hidden;
            max-height: 300px;
        }

        .gallery-cover img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }

        .gallery-empty {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 14px;
            color: #765568;
            box-shadow: 0 5px 20px rgba(87, 40, 60, 0.07);
        }

        .gallery-empty h3 {
            color: #57283c;
            margin-bottom: 10px;
        }

        /* Image Lightbox */
        .gallery-lightbox {
            display: none;
            position: fixed;
            z-index: 99999;
            inset: 0;
            background: rgba(20, 10, 15, 0.93);
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .gallery-lightbox.active {
            display: flex;
        }

        .gallery-lightbox img {
            max-width: 90%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 38px;
            cursor: pointer;
            background: none;
            border: none;
        }

        @media (max-width: 768px) {
            .gallery-hero {
                padding: 60px 20px;
            }

            .gallery-hero h1 {
                font-size: 32px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }

            .gallery-section-heading h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-hero h1 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<?php include_once __DIR__ . '/includes/navber.php'; ?>

<div class="gallery-page">

    <!-- Gallery Hero -->
    <section class="gallery-hero">
        <h1>Our Event Gallery</h1>
        <p>
            Explore our collection of beautifully decorated events.
            From weddings and birthdays to corporate celebrations,
            discover the moments we have helped make unforgettable.
        </p>
    </section>

    <div class="gallery-container">

        <?php if (!empty($albums)): ?>

            <?php foreach ($albums as $album): ?>

                <?php
                $albumId = (int)$album['album_id'];

                // Get visible images of this album
                $imageSql = "
                    SELECT
                        gi.gallery_image_id,
                        gi.caption,
                        gi.sort_order,
                        m.file_path,
                        m.alt_text,
                        m.file_name
                    FROM gallery_images gi
                    INNER JOIN media m
                        ON gi.image_id = m.media_id
                    WHERE gi.album_id = ?
                      AND gi.is_visible = 1
                    ORDER BY gi.sort_order ASC, gi.gallery_image_id DESC
                ";

                $imageStmt = $conn->prepare($imageSql);
                $albumImages = [];

                if ($imageStmt) {
                    $imageStmt->bind_param("i", $albumId);
                    $imageStmt->execute();

                    $imageResult = $imageStmt->get_result();

                    while ($image = $imageResult->fetch_assoc()) {
                        $albumImages[] = $image;
                    }

                    $imageStmt->close();
                }

                // Skip albums without visible images
                if (empty($albumImages)) {
                    continue;
                }

                // Use selected cover image, otherwise first gallery image
                $coverPath = $album['cover_path'];

                if (empty($coverPath)) {
                    $coverPath = $albumImages[0]['file_path'];
                }
                ?>

                <section class="gallery-section"
                         id="album-<?php echo $albumId; ?>">

                    <div class="gallery-section-heading">

                        <?php if (!empty($album['category_name'])): ?>
                            <span class="gallery-category">
                                <?php echo galleryEscape($album['category_name']); ?>
                            </span>
                        <?php endif; ?>

                        <h2>
                            <?php echo galleryEscape($album['album_name']); ?>
                        </h2>

                        <?php if (!empty($album['description'])): ?>
                            <p>
                                <?php echo nl2br(galleryEscape($album['description'])); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- Album Cover -->
                    <div class="gallery-cover">
                        <img
                            src="<?php echo galleryEscape($coverPath); ?>"
                            alt="<?php echo galleryEscape($album['cover_alt'] ?: $album['album_name']); ?>"
                            loading="lazy"
                        >
                    </div>

                    <!-- Album Images -->
                    <div class="gallery-grid">

                        <?php foreach ($albumImages as $image): ?>

                            <div class="gallery-item"
                                 onclick="openGalleryImage(this)">

                                <img
                                    src="<?php echo galleryEscape($image['file_path']); ?>"
                                    alt="<?php echo galleryEscape($image['alt_text'] ?: $album['album_name']); ?>"
                                    loading="lazy"
                                >

                                <?php if (!empty($image['caption'])): ?>
                                    <div class="gallery-caption">
                                        <?php echo galleryEscape($image['caption']); ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="gallery-empty">
                <h3>Gallery Coming Soon</h3>
                <p>
                    Our event gallery is being updated.
                    Please visit us again soon.
                </p>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- Image Lightbox -->
<div class="gallery-lightbox" id="galleryLightbox"
     onclick="closeGalleryImage(event)">

    <button class="lightbox-close"
            type="button"
            onclick="closeGalleryImage(event)">
        &times;
    </button>

    <img src="" id="lightboxImage" alt="Gallery Preview">

</div>

<script>
function openGalleryImage(element) {
    const image = element.querySelector('img');

    if (!image) return;

    document.getElementById('lightboxImage').src = image.src;
    document.getElementById('galleryLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeGalleryImage(event) {
    if (
        event.target.id === 'galleryLightbox' ||
        event.target.classList.contains('lightbox-close')
    ) {
        document.getElementById('galleryLightbox').classList.remove('active');
        document.getElementById('lightboxImage').src = '';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('galleryLightbox').classList.remove('active');
        document.getElementById('lightboxImage').src = '';
        document.body.style.overflow = '';
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>

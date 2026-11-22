<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* =====================================
   HERO BANNER
===================================== */

$hero = null;

$heroQuery = $conn->query("
    SELECT
        b.banner_id,
        b.title,
        b.subtitle,
        b.button_text,
        b.button_link,
        m.file_path,
        m.alt_text
    FROM banners b
    LEFT JOIN media m
        ON b.image_id = m.media_id
    WHERE b.is_active = 1
    ORDER BY b.sort_order ASC, b.banner_id DESC
    LIMIT 1
");

if ($heroQuery) {
    $hero = $heroQuery->fetch_assoc();
} else {
    error_log('Banner query error: ' . $conn->error);
}

// Fallback content if no active banner exists
$heroTitle = !empty($hero['title'])
    ? $hero['title']
    : 'Beautiful Spaces. Unforgettable Moments.';

$heroSubtitle = !empty($hero['subtitle'])
    ? $hero['subtitle']
    : 'From dream weddings to intimate family celebrations, we transform your special occasions into extraordinary experiences with elegant and creative decoration.';

$heroImage = !empty($hero['file_path'])
    ? $hero['file_path']
    : 'images/wed1.webp';

$heroButtonText = !empty($hero['button_text'])
    ? $hero['button_text']
    : 'Explore Our Packages';

$heroButtonLink = !empty($hero['button_link'])
    ? $hero['button_link']
    : 'packages.php';


/* =====================================
   EVENT CATEGORIES
===================================== */

$events = [];

$eventSql = "
    SELECT
        ec.category_id,
        ec.name,
        ec.slug,
        ec.description,
        m.file_path,
        m.alt_text
    FROM event_categories ec
    LEFT JOIN media m
        ON ec.image_id = m.media_id
    WHERE ec.status = 'active'
    ORDER BY ec.sort_order ASC, ec.category_id ASC
";

$eventResult = $conn->query($eventSql);

if ($eventResult) {

    // Fallback images for categories without an uploaded image
    $fallbackImages = [
        'wedding' => 'images/wd.jpg',
        'engagement' => 'images/en4.jpg',
        'holud' => 'images/h2.jpg',
        'anniversary' => 'images/an2.jpg',
        'birthday' => 'images/h3.jpg',
        'akika' => 'images/ak1.jpg',
        'corporate event' => 'images/cor5.jpg',
        'custom event' => 'images/cs3.jpg'
    ];

    $eventIcons = [
        'wedding' => '💍',
        'engagement' => '💎',
        'holud' => '🌼',
        'anniversary' => '❤️',
        'birthday' => '🎂',
        'akika' => '🌸',
        'corporate event' => '🏢',
        'custom event' => '✨'
    ];

    while ($row = $eventResult->fetch_assoc()) {

        $categoryName = strtolower(trim($row['name']));

        $row['image'] = !empty($row['file_path'])
            ? $row['file_path']
            : ($fallbackImages[$categoryName] ?? 'images/wd.jpg');

        $row['icon'] = $eventIcons[$categoryName] ?? '✨';

        $row['sub'] = !empty($row['description'])
            ? $row['description']
            : 'Explore our creative decoration ideas';

        $events[] = $row;
    }

} else {
    error_log('Event category query error: ' . $conn->error);
}


/* =====================================
   FEATURED PACKAGES
===================================== */

$packageSql = "
    SELECT
        p.package_id,
        p.package_name,
        p.description,
        p.short_description,
        p.base_price,
        ec.name AS type_name,
        m.file_path,
        m.alt_text
    FROM packages p
    INNER JOIN event_categories ec
        ON p.category_id = ec.category_id
    LEFT JOIN media m
        ON p.image_id = m.media_id
    WHERE p.status = 'active'
      AND ec.status = 'active'
    ORDER BY p.is_featured DESC, p.package_id DESC
    LIMIT 6
";

$packages = $conn->query($packageSql);

if (!$packages) {
    error_log('Package query error: ' . $conn->error);
}
/* =====================================
   About Us Section
===================================== */

$aboutSections = [];

$aboutSql = "
    SELECT
        ps.section_key,
        ps.section_title,
        ps.heading,
        ps.subheading,
        ps.content,
        ps.image_id,
        ps.is_visible,
        m.file_path
    FROM pages p
    INNER JOIN page_sections ps
        ON p.page_id = ps.page_id
    LEFT JOIN media m
        ON ps.image_id = m.media_id
    WHERE p.page_slug = 'about'
    AND ps.is_visible = 1
    ORDER BY ps.sort_order ASC
";

$aboutResult = $conn->query($aboutSql);

if ($aboutResult) {
    while ($row = $aboutResult->fetch_assoc()) {
        $aboutSections[$row['section_key']] = $row;
    }
}

$aboutIntro = $aboutSections['about_intro'] ?? null;
$aboutMission = $aboutSections['mission'] ?? null;
$aboutVision = $aboutSections['vision'] ?? null;

$aboutImage = !empty($aboutIntro['file_path'])
    ? $aboutIntro['file_path']
    : 'images/mg.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DecorEase | Beautiful Events, Beautiful Memories</title>

    <?php include 'includes/nav_style.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --plum: #6d314b;
            --deep: #53263a;
            --pink: #c0547b;
            --rose: #e7a2b8;
            --cream: #fffaf7;
            --light: #fff0f4;
            --gold: #d6b47a;
            --text: #45343b;
            --muted: #83747a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.7;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: 86%;
            max-width: 1280px;
            margin: auto;
        }

        .serif {
            font-family: 'Playfair Display', serif;
        }

        /* HERO */

        .hero {
            min-height: 650px;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            background:
                linear-gradient(
                    90deg,
                    rgba(39, 16, 29, .92) 0%,
                    rgba(57, 23, 41, .78) 45%,
                    rgba(57, 23, 41, .12) 100%
                ),
                url('images/wed1.webp')
                center/cover no-repeat;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 50%;
            right: -120px;
            bottom: -180px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            width: 86%;
            max-width: 1280px;
            margin: auto;
            padding: 100px 0;
            color: white;
        }

        .hero-label {
            display: inline-block;
            color: #f1c9d6;
            border: 1px solid rgba(255,255,255,.4);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .hero h1 {
            max-width: 720px;
            font-family: 'Playfair Display', serif;
            font-size: clamp(42px, 5.5vw, 72px);
            line-height: 1.12;
            margin-bottom: 24px;
            font-weight: 700;
        }

        .hero h1 span {
            color: #f0b9ca;
        }

        .hero p {
            max-width: 570px;
            font-size: 16px;
            color: #f2e8ec;
            margin-bottom: 35px;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-outline {
            display: inline-block;
            padding: 14px 27px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            transition: .3s;
        }

        .btn-primary {
            background: var(--pink);
            color: white;
        }

        .btn-primary:hover {
            background: #96385d;
            color: white;
            transform: translateY(-3px);
        }

        .btn-outline {
            border: 1px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--plum);
        }

        .hero-note {
            margin-top: 55px;
            display: flex;
            gap: 35px;
            flex-wrap: wrap;
            color: #f3dfe6;
            font-size: 13px;
        }

        .hero-note span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* SECTION COMMON */

        .section {
            padding: 90px 0;
        }

        .section-heading {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 45px;
        }

        .eyebrow {
            display: block;
            color: var(--pink);
            font-size: 12px;
            letter-spacing: 3px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .section-heading h2 {
            font-family: 'Playfair Display', serif;
            color: var(--plum);
            font-size: clamp(30px, 4vw, 43px);
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        /* INTRO STRIP */

        .intro-strip {
            background: white;
            padding: 27px 0;
            box-shadow: 0 8px 30px rgba(60,20,40,.05);
        }

        .intro-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
        }

        .intro-item {
            padding: 10px 20px;
            border-right: 1px solid #f0e2e7;
        }

        .intro-item:last-child {
            border: none;
        }

        .intro-item strong {
            display: block;
            color: var(--plum);
            font-size: 16px;
        }

        .intro-item small {
            color: var(--muted);
            font-size: 12px;
        }

        /* EVENT CATEGORY */

        .events-section {
            background: var(--cream);
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .event-card {
            position: relative;
            height: 300px;
            border-radius: 9px;
            overflow: hidden;
            background: #e9dce1;
            box-shadow: 0 8px 25px rgba(60,20,40,.09);
            transition: .35s;
        }

        .event-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .6s;
        }

        .event-card:hover img {
            transform: scale(1.09);
        }

        .event-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(35,14,26,.92),
                rgba(35,14,26,.05) 75%
            );
        }

        .event-info {
            position: absolute;
            z-index: 2;
            bottom: 22px;
            left: 22px;
            right: 15px;
            color: white;
        }

        .event-info .event-icon {
            font-size: 23px;
            margin-bottom: 5px;
            display: block;
        }

        .event-info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 2px;
        }

        .event-info p {
            font-size: 12px;
            color: #f4dfe7;
        }

        /* PACKAGES */

        .packages-section {
            background: #f8edf1;
        }

        .package-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .package-card {
            background: white;
            overflow: hidden;
            border-radius: 9px;
            box-shadow: 0 8px 28px rgba(60,20,40,.07);
            transition: .3s;
        }

        .package-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(60,20,40,.13);
        }

        .package-top {
            height: 165px;
            background:
                linear-gradient(135deg, #e9b7c8, #8e4967);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 58px;
        }

        .package-details {
            padding: 25px;
        }

        .package-category {
            display: inline-block;
            font-size: 10px;
            letter-spacing: 1px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--pink);
            background: #fff0f4;
            padding: 5px 11px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .package-details h3 {
            color: var(--plum);
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .package-details p {
            color: var(--muted);
            font-size: 13px;
            min-height: 45px;
            margin-bottom: 18px;
        }

        .package-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f2e7eb;
            padding-top: 17px;
            gap: 10px;
        }

        .price {
            color: var(--plum);
            font-size: 19px;
            font-weight: 800;
        }

        .book-btn {
            background: var(--pink);
            color: white;
            padding: 10px 17px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 4px;
            transition: .3s;
        }

        .book-btn:hover {
            background: var(--plum);
            color: white;
        }

        .empty-packages {
            grid-column: 1 / -1;
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            color: var(--muted);
        }

        .view-all {
            text-align: center;
            margin-top: 40px;
        }

        /* ABOUT */

        .about-section {
            background: white;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 65px;
            align-items: center;
        }

        .about-image {
            position: relative;
            min-height: 440px;
        }

        .about-image img {
            width: 88%;
            height: 410px;
            object-fit: cover;
            border-radius: 8px;
        }

        .about-image::after {
            content: "";
            position: absolute;
            width: 65%;
            height: 75%;
            right: 0;
            bottom: -15px;
            border: 3px solid var(--rose);
            border-radius: 8px;
            z-index: 0;
        }

        .about-image img {
            position: relative;
            z-index: 1;
        }

        .about-content .eyebrow {
            text-align: left;
        }

        .about-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 40px;
            color: var(--plum);
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .about-content p {
            color: var(--muted);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .about-points {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }

        .about-point {
            color: var(--plum);
            font-size: 13px;
            font-weight: 700;
        }

        .about-point span {
            color: var(--pink);
            margin-right: 7px;
        }

        /* PROCESS */

        .process-section {
            background: #fff4f6;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .process-card {
            text-align: center;
            padding: 35px 25px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(60,20,40,.04);
        }

        .process-number {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            background: var(--plum);
            color: white;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
        }

        .process-card h3 {
            color: var(--plum);
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            margin-bottom: 10px;
        }

        .process-card p {
            color: var(--muted);
            font-size: 13px;
        }

        /* CTA */

        .booking-section {
            position: relative;
            padding: 85px 20px;
            text-align: center;
            color: white;
            background:
                linear-gradient(rgba(49,19,35,.89), rgba(49,19,35,.91)),
                url('images/an2.jpg')
                center/cover;
        }

        .booking-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(30px, 4vw, 45px);
            margin-bottom: 15px;
        }

        .booking-section p {
            color: #eadbe1;
            margin-bottom: 28px;
        }


        /* RESPONSIVE */

        @media(max-width: 1000px) {
            .event-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .package-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .about-grid {
                gap: 35px;
            }
        }

        @media(max-width: 700px) {
            .container,
            .hero-content {
                width: 90%;
            }

            .hero {
                min-height: 570px;
                background-position: 60% center;
            }

            .hero h1 {
                font-size: 42px;
            }

            .hero p {
                font-size: 14px;
            }

            .hero-note {
                gap: 13px;
                flex-direction: column;
                margin-top: 35px;
            }

            .intro-grid {
                grid-template-columns: 1fr;
            }

            .intro-item {
                border-right: none;
                border-bottom: 1px solid #f0e2e7;
            }

            .intro-item:last-child {
                border-bottom: none;
            }

            .section {
                padding: 65px 0;
            }

            .event-grid,
            .package-container,
            .process-grid,
            .about-grid {
                grid-template-columns: 1fr;
            }

            .event-card {
                height: 320px;
            }

            .about-image {
                min-height: 320px;
            }

            .about-image img {
                height: 300px;
            }

            .about-content h2 {
                font-size: 32px;
            }

            .about-points {
                grid-template-columns: 1fr;
            }

            .package-details p {
                min-height: auto;
            }
        }

        @media(max-width: 400px) {
            .hero h1 {
                font-size: 35px;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .event-card {
                height: 270px;
            }
        }
        .package-top img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>

<body>

<?php include 'includes/navber.php'; ?>

<main>

    <!-- HERO -->
    <!-- HERO -->
<section
    class="hero"
    id="home"
    style="background-image:
        linear-gradient(
            90deg,
            rgba(39, 16, 29, .92) 0%,
            rgba(57, 23, 41, .78) 45%,
            rgba(57, 23, 41, .12) 100%
        ),
        url('<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8'); ?>');
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;"
>

    <div class="hero-content">

        <span class="hero-label">
            Creating Moments That Last Forever
        </span>

        <h1 class="serif">
            <?= htmlspecialchars($heroTitle); ?>
        </h1>

        <p>
            <?= htmlspecialchars($heroSubtitle); ?>
        </p>

        <div class="hero-buttons">

            <a
                href="<?= htmlspecialchars($heroButtonLink, ENT_QUOTES, 'UTF-8'); ?>"
                class="btn-primary"
            >
                <?= htmlspecialchars($heroButtonText); ?> &nbsp; →
            </a>

            <a href="contact.php" class="btn-outline">
                Plan Your Event
            </a>

        </div>

        <div class="hero-note">
            <span>✦ Elegant Event Styling</span>
            <span>✦ Creative Decoration</span>
            <span>✦ Customized Themes</span>
        </div>

    </div>

</section>


    <!-- EVENT CATEGORIES -->
    <!-- EVENT CATEGORIES -->
<section class="section events-section" id="services">

    <div class="container">

        <div class="section-heading">

            <span class="eyebrow">What We Create</span>

            <h2>Every Celebration Deserves Something Beautiful</h2>

            <p>
                Discover decoration ideas for every occasion.
                Choose your event and let us help you create
                a setting worth remembering.
            </p>

        </div>

        <div class="event-grid">

            <?php if (!empty($events)): ?>

                <?php foreach ($events as $event): ?>

                    <a
                        href="packages.php?category=<?= urlencode($event['slug']); ?>"
                        class="event-card"
                    >

                        <img
                            src="<?= htmlspecialchars($event['image'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?= htmlspecialchars($event['name']); ?>"
                            loading="lazy"
                        >

                        <div class="event-info">

                            <span class="event-icon">
                                <?= $event['icon']; ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($event['name']); ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($event['sub']); ?>
                            </p>

                        </div>

                    </a>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-packages">
                    <h3>No Event Categories Available</h3>
                    <p>Please check back later.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


    <!-- FEATURED PACKAGES -->
    <!-- FEATURED PACKAGES -->
<section class="section packages-section" id="packages">

    <div class="container">

        <div class="section-heading">

            <span class="eyebrow">Our Collection</span>

            <h2>Featured Decoration Packages</h2>

            <p>
                Explore our available decoration packages
                and find an option that matches your celebration.
            </p>

        </div>

        <div class="package-container">

            <?php if ($packages && $packages->num_rows > 0): ?>

                <?php while ($package = $packages->fetch_assoc()): ?>

                    <?php
                    $eventName = strtolower($package['type_name']);
                    $icon = '✨';

                    if (strpos($eventName, 'wedding') !== false) {
                        $icon = '💐';
                    } elseif (strpos($eventName, 'birthday') !== false) {
                        $icon = '🎂';
                    } elseif (strpos($eventName, 'holud') !== false) {
                        $icon = '🌼';
                    } elseif (strpos($eventName, 'akika') !== false) {
                        $icon = '🌸';
                    } elseif (strpos($eventName, 'corporate') !== false) {
                        $icon = '🏢';
                    } elseif (strpos($eventName, 'engagement') !== false) {
                        $icon = '💍';
                    } elseif (strpos($eventName, 'anniversary') !== false) {
                        $icon = '❤️';
                    }
                    ?>

                    <div class="package-card">

                        <div class="package-top">

                            <?php if (!empty($package['file_path'])): ?>

                                <img
                                    src="<?= htmlspecialchars($package['file_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?= htmlspecialchars($package['alt_text'] ?: $package['package_name']); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <?= $icon; ?>

                            <?php endif; ?>

                        </div>

                        <div class="package-details">

                            <span class="package-category">
                                <?= htmlspecialchars($package['type_name']); ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($package['package_name']); ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $package['short_description']
                                    ?: $package['description']
                                    ?: 'Explore this decoration package.'
                                ); ?>
                            </p>

                            <div class="package-bottom">

                                <div class="price">
                                    ৳<?= number_format((float)$package['base_price'], 2); ?>
                                </div>

                                <a
                                    href="package_details.php?id=<?= (int)$package['package_id']; ?>"
                                    class="book-btn"
                                >
                                    Book Now →
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-packages">
                    <h3>No Packages Available Yet</h3>
                    <p>
                        Please check back later for our decoration packages.
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <div class="view-all">

            <a href="packages.php" class="btn-primary">
                View All Packages →
            </a>

        </div>

    </div>

</section>


    <!-- ABOUT -->
    
<
<!-- ABOUT SECTION -->
<section class="section about-section">

    <div class="container about-grid">

        <!-- About Image -->
        <div class="about-image">
            <img
                src="<?= htmlspecialchars($aboutImage) ?>"
                alt="Elegant event decoration"
            >
        </div>

        <!-- About Content -->
        <div class="about-content">

            <?php if ($aboutIntro): ?>

                <span class="eyebrow">
                    <?= htmlspecialchars($aboutIntro['subheading'] ?: $aboutIntro['section_title']) ?>
                </span>

                <h2>
                    <?= htmlspecialchars($aboutIntro['heading']) ?>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($aboutIntro['content'])) ?>
                </p>

            <?php endif; ?>

            <!-- Mission and Vision -->
            <div class="about-points">

                <?php if ($aboutMission): ?>
                    <div class="about-point">
                        <span>
                            <i class="fa-solid fa-bullseye"></i>
                        </span>

                        <div>
                            <strong>
                                <?= htmlspecialchars($aboutMission['heading']) ?>
                            </strong>

                            <p>
                                <?= nl2br(htmlspecialchars($aboutMission['content'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>


                <?php if ($aboutVision): ?>
                    <div class="about-point">
                        <span>
                            <i class="fa-solid fa-eye"></i>
                        </span>

                        <div>
                            <strong>
                                <?= htmlspecialchars($aboutVision['heading']) ?>
                            </strong>

                            <p>
                                <?= nl2br(htmlspecialchars($aboutVision['content'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <a href="services.php" class="btn-primary">
                Discover Our Services →
            </a>

        </div>

    </div>

</section>




    <!-- HOW IT WORKS -->
    <section class="section process-section">

        <div class="container">

            <div class="section-heading">
                <span class="eyebrow">Simple & Convenient</span>

                <h2>How to Plan Your Event</h2>

                <p>
                    Your celebration begins with a simple idea.
                    Follow these steps to start planning.
                </p>
            </div>

            <div class="process-grid">

                <div class="process-card">

                    <div class="process-number">01</div>

                    <h3>Explore</h3>

                    <p>
                        Browse our event services and available
                        decoration packages.
                    </p>

                </div>

                <div class="process-card">

                    <div class="process-number">02</div>

                    <h3>Choose</h3>

                    <p>
                        Select a suitable package or contact us
                        about your customized event idea.
                    </p>

                </div>

                <div class="process-card">

                    <div class="process-number">03</div>

                    <h3>Book</h3>

                    <p>
                        Submit your event details and begin
                        your booking process.
                    </p>

                </div>

            </div>

        </div>
    </section>


    <!-- BOOKING CTA -->
    <section class="booking-section">

        <h2 class="serif">
            Let's Make Your Special Day Beautiful
        </h2>

        <p>
            Share your ideas, choose your style and start
            planning a celebration to remember.
        </p>

        <a href="login.php" class="btn-primary">
            Start Your Planning →
        </a>

    </section>

</main>


<?php include 'includes/footer.php'; ?>

</body>
</html>

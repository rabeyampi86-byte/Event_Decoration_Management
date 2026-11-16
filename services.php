<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'db.php';
$services = [
    [
        'title' => 'Wedding Decoration',
        'subtitle' => 'Elegant celebrations',
        'description' => 'Create your dream wedding with beautiful stage decoration, floral arrangements and elegant venue styling.',
        'image' => 'images/wed7.png',
        'link' => './wedding_details.php'
    ],
    [
        'title' => 'Engagement Decoration',
        'subtitle' => 'A beautiful beginning',
        'description' => 'Celebrate your special beginning with a charming engagement stage and attractive decorations.',
        'image' => 'images/en1.png',
        'link' => 'engagement_details.php'
    ],
    [
        'title' => 'Holud Decoration',
        'subtitle' => 'Colourful traditions',
        'description' => 'Make your holud ceremony colourful with traditional themes, flowers and vibrant decorative arrangements.',
        'image' => 'images/h1.png',
        'link' => 'holud_details.php'
    ],
    [
        'title' => 'Anniversary Decoration',
        'subtitle' => 'Celebrate togetherness',
        'description' => 'Celebrate your memorable moments with romantic decorations and a beautiful event setup.',
        'image' => 'images/an1.jpg',
        'link' => './anniversary_details.php'
    ],
    [
        'title' => 'Birthday Decoration',
        'subtitle' => 'Make it memorable',
        'description' => 'Enjoy a wonderful birthday celebration with creative themes, balloons and attractive decorations.',
        'image' => 'images/b.jpg',
        'link' => './birthday_details.php'
    ],
    [
        'title' => 'Akika Decoration',
        'subtitle' => 'Welcome little blessings',
        'description' => 'Arrange a beautiful and memorable Akika celebration with elegant decorations for your family.',
        'image' => 'images/ak.png',
        'link' => './akika_details.php'
    ],
    [
        'title' => 'Corporate Event',
        'subtitle' => 'Professional events',
        'description' => 'Organize professional corporate events with stylish stage setups and well-planned 
        decorations.',
        'image' => 'images/cr1.png',
        'link' => './corporate_details.php'
    ],
    [
        'title' => 'Customized Events',
        'subtitle' => 'Your idea, our creativity',
        'description' => 'Have a unique event idea? We create customized decoration arrangements according to your 
        requirements.',
        'image' => 'images/cs3.jpg',
        'link' => './custom_details.php'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | DecorEase</title>

    <?php include_once 'includes/nav_style.php'; ?>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f8fc;
            color: #292333;
        }
        .services-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px 70px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 42px;
        }

        .section-heading h2 {
            font-size: 32px;
            margin: 0 0 12px;
            color: #913653;
        }

        .section-heading p {
            color: #77717f;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
        }

        .service-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 22px rgba(42, 25, 55, 0.09);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(42, 25, 55, 0.15);
        }

        .service-image {
            width: 100%;
            height: 215px;
            object-fit: cover;
            display: block;
        }

        .service-content {
            padding: 23px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .service-content h3 {
            font-size: 21px;
            color: #6d3144;
            margin: 0 0 8px;
        }

        .service-subtitle {
            font-size: 13px;
            color: #913653;
            font-weight: 600;
            margin-bottom: 13px;
        }

        .service-content p {
            font-size: 14px;
            line-height: 1.7;
            color: #141012;
            margin: 0 0 22px;
            flex: 1;
        }

        .service-link {
            display: inline-block;
            width: fit-content;
            text-decoration: none;
            background: #913653;
            color: #fff;
            padding: 11px 21px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .service-link:hover {
            background: #913653;
            color: #fff;
        }

        .services-bottom {
            text-align: center;
            margin-top: 55px;
            padding: 35px 20px;
            background: #f0e9f5;
            border-radius: 12px;
        }

        .services-bottom h2 {
            color: #913653;
            font-size: 25px;
            margin: 0 0 12px;
        }

        .services-bottom p {
            color: #716b78;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .contact-btn {
            display: inline-block;
            text-decoration: none;
            background: #913653;
            color: #fff;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 600;
        }

        .contact-btn:hover {
            background: #913653;
            color: #fff;
        }

        @media (max-width: 900px) {
            .services-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .services-hero h1 {
                font-size: 30px;
            }

            .section-heading h2 {
                font-size: 26px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .services-section {
                padding: 45px 16px;
            }
        }
</style>
</head>

<body>
<?php include_once 'includes/navber.php'; ?>
<section class="services-section">

    <div class="section-heading">
        <h2>Explore Our Services</h2>
        <p>Choose the perfect decoration service for your special occasion.</p>
    </div>

    <div class="services-grid">

        <?php foreach ($services as $service): ?>

            <div class="service-card">

                <img
                    class="service-image"
                    src="<?= htmlspecialchars($service['image']) ?>"
                    alt="<?= htmlspecialchars($service['title']) ?>"
                    loading="lazy"
                >

                <div class="service-content">

                    <h3><?= htmlspecialchars($service['title']) ?></h3>

                    <div class="service-subtitle">
                        <?= htmlspecialchars($service['subtitle']) ?>
                    </div>

                    <p>
                        <?= htmlspecialchars($service['description']) ?>
                    </p>

                    <a
                        class="service-link"
                        href="<?= htmlspecialchars($service['link']) ?>"
                    >
                        Learn More
                    </a>

                </div>
            </div>

        <?php endforeach; ?>

    </div>

    <div class="services-bottom">
        <h2>Have a Special Event in Mind?</h2>
        <p>Let us help you plan and decorate your event just the way you want.</p>
        <a href="contact.php" class="contact-btn">Contact Us</a>
    </div>

</section>

<?php include_once 'includes/footer.php'; ?>

</body>
</html>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Service Details | DecorEase";

$services = [
    "wedding" => [
        "name" => "Wedding Decoration",
        "image" => "images/wed1.png",
        "subtitle" => "Make your wedding day truly unforgettable.",
        "description" => "We create elegant and beautiful wedding decoration arrangements with carefully coordinated stage designs, floral decorations and venue styling. Our goal is to make your special day memorable.",
        "features" => [
            "Elegant wedding stage decoration",
            "Beautiful entrance gate design",
            "Floral arrangements and backdrop",
            "Bride and groom seating decoration",
            "Lighting and venue styling",
            "Customized wedding themes"
        ]
    ],

    "engagement" => [
        "name" => "Engagement Decoration",
        "image" => "images/en1.png",
        "subtitle" => "Celebrate the beginning of a beautiful journey.",
        "description" => "Create a charming atmosphere for your engagement ceremony with beautiful backdrops, floral arrangements and coordinated decoration that makes the occasion special.",
        "features" => [
            "Engagement stage and backdrop",
            "Floral decoration",
            "Couple seating arrangement",
            "Entrance decoration",
            "Theme-based color combinations",
            "Customized decoration ideas"
        ]
    ],

    "holud" => [
        "name" => "Holud Decoration",
        "image" => "images/h1.png",
        "subtitle" => "Bring color and tradition to your Holud celebration.",
        "description" => "Celebrate your Holud with vibrant yellow themes, traditional decorative elements, colorful backdrops and beautiful floral arrangements.",
        "features" => [
            "Traditional Holud stage",
            "Yellow and marigold-inspired themes",
            "Floral and traditional props",
            "Entrance decoration",
            "Seating arrangement",
            "Colorful photo backdrop"
        ]
    ],

    "anniversary" => [
        "name" => "Anniversary Decoration",
        "image" => "images/wed2.png",
        "subtitle" => "Make every anniversary a memorable celebration.",
        "description" => "Celebrate your special milestone with warm lighting, romantic backdrops, beautiful floral accents and personalized decoration arrangements.",
        "features" => [
            "Anniversary stage decoration",
            "Romantic backdrop design",
            "Floral arrangements",
            "Decorative lighting",
            "Photo corner setup",
            "Customized anniversary themes"
        ]
    ],

    "birthday" => [
        "name" => "Birthday Decoration",
        "image" => "images/b.png",
        "subtitle" => "Add more joy and color to your birthday celebration.",
        "description" => "We arrange creative birthday decorations with beautiful balloon styling, themed backdrops and colorful setups for children and adults.",
        "features" => [
            "Birthday backdrop and stage",
            "Balloon decoration",
            "Themed party setup",
            "Cake table decoration",
            "Welcome board",
            "Customized birthday themes"
        ]
    ],

    "akika" => [
        "name" => "Akika Decoration",
        "image" => "images/ak.png",
        "subtitle" => "A beautiful setting for your family's special occasion.",
        "description" => "Create a warm and welcoming atmosphere for your Akika ceremony with tasteful decorations, soft color themes and beautiful arrangements.",
        "features" => [
            "Akika stage decoration",
            "Soft and elegant color themes",
            "Floral arrangements",
            "Welcome decoration",
            "Guest seating area styling",
            "Customized family event setup"
        ]
    ],

    "corporate" => [
        "name" => "Corporate Event Decoration",
        "image" => "images/c.jpg",
        "subtitle" => "Professional decoration for professional occasions.",
        "description" => "We provide neat and professional decoration concepts for corporate meetings, product launches, business events and company celebrations.",
        "features" => [
            "Corporate stage decoration",
            "Conference and meeting backdrop",
            "Welcome and reception area",
            "Product launch decoration",
            "Professional lighting arrangements",
            "Company-themed decoration"
        ]
    ],

    "custom" => [
        "name" => "Customized Events",
        "image" => "images/com1.png",
        "subtitle" => "Your idea, our creativity.",
        "description" => "Have a unique celebration in mind? We can help create a decoration concept based on your preferred theme, event type, colors and requirements.",
        "features" => [
            "Personalized decoration concepts",
            "Custom stage and backdrop",
            "Theme-based arrangements",
            "Floral and lighting decoration",
            "Special photo corner",
            "Decoration according to your requirements"
        ]
    ]
];

$serviceKey = $_GET['service'] ?? '';

if (!isset($services[$serviceKey])) {
    header("Location: services.php");
    exit;
}

$service = $services[$serviceKey];
$page_title = $service['name'] . " | DecorEase";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo htmlspecialchars($page_title); ?></title>

<?php include 'includes/nav_style.php'; ?>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #fffaf7;
    color: #38232d;
    font-family: Arial, sans-serif;
    line-height: 1.7;
}

.details-hero {
    background: linear-gradient(115deg, #38202f, #85415f);
    color: white;
    padding: 38px 7%;
}

.breadcrumb {
    font-size: 13px;
    color: #f2dce5;
    margin-bottom: 15px;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.details-hero h1 {
    margin: 0;
    font-size: clamp(28px, 4vw, 42px);
}

.details-hero p {
    margin: 8px 0 0;
    color: #f5e5ec;
    font-size: 16px;
}

.details-container {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 7%;
}

.details-card {
    display: grid;
    grid-template-columns: 1.05fr 1fr;
    gap: 35px;
    align-items: center;
    background: white;
    border: 1px solid #f0dce4;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(65, 28, 46, .08);
}

.details-image {
    height: 390px;
    overflow: hidden;
    border-radius: 11px;
    background: #f4e4ea;
}

.details-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.details-content h2 {
    color: #57283c;
    font-size: 27px;
    margin: 0 0 12px;
}

.details-content p {
    color: #756870;
    font-size: 15px;
    margin-bottom: 20px;
}

.features-section {
    margin-top: 38px;
    background: white;
    border: 1px solid #f0dce4;
    border-radius: 13px;
    padding: 25px 28px;
}

.features-section h2 {
    color: #57283c;
    font-size: 23px;
    margin: 0 0 18px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 13px 25px;
}

.feature-item {
    color: #65535c;
    font-size: 14px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.feature-check {
    color: #b84a72;
    font-weight: bold;
    font-size: 17px;
}

.booking-section {
    margin-top: 30px;
    padding: 30px 25px;
    border-radius: 13px;
    text-align: center;
    color: white;
    background: linear-gradient(110deg, #57283c, #a34d70);
}

.booking-section h2 {
    color: white;
    margin: 0 0 7px;
    font-size: 25px;
}

.booking-section p {
    color: #f7e9ef;
    margin: 0 0 20px;
    font-size: 14px;
}

.booking-btn {
    display: inline-block;
    background: white;
    color: #57283c;
    text-decoration: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 14px;
    transition: .25s;
}

.booking-btn:hover {
    background: #ffe5ee;
    color: #57283c;
    transform: translateY(-2px);
}

.back-link {
    display: inline-block;
    margin-top: 22px;
    color: #a34d70;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.back-link:hover {
    color: #57283c;
}

@media(max-width: 800px) {
    .details-card {
        grid-template-columns: 1fr;
    }

    .details-image {
        height: 280px;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 600px) {
    .details-container {
        padding: 0 5%;
        margin: 28px auto;
    }

    .details-card {
        padding: 14px;
        gap: 20px;
    }

    .details-image {
        height: 220px;
    }

    .features-section {
        padding: 20px;
    }

    .details-hero {
        padding: 30px 5%;
    }
}
</style>
</head>

<body>

<?php include 'includes/navber.php'; ?>

<main>

<section class="details-hero">
    <div class="breadcrumb">
        <a href="index.php">Home</a> /
        <a href="services.php">Services</a> /
        <?php echo htmlspecialchars($service['name']); ?>
    </div>

    <h1><?php echo htmlspecialchars($service['name']); ?></h1>

    <p><?php echo htmlspecialchars($service['subtitle']); ?></p>
</section>

<section class="details-container">

    <div class="details-card">

        <div class="details-image">
            <img
                src="<?php echo htmlspecialchars($service['image']); ?>"
                alt="<?php echo htmlspecialchars($service['name']); ?>"
            >
        </div>

        <div class="details-content">
            <h2>About This Service</h2>

            <p>
                <?php echo htmlspecialchars($service['description']); ?>
            </p>

            <p>
                Explore our available decoration packages to find an option
                that matches your event and budget.
            </p>
        </div>

    </div>

    <div class="features-section">

        <h2>What We Can Arrange</h2>

        <div class="features-grid">

            <?php foreach ($service['features'] as $feature): ?>

                <div class="feature-item">
                    <span class="feature-check">✓</span>
                    <span><?php echo htmlspecialchars($feature); ?></span>
                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <div class="booking-section">

        <h2>Ready to Plan Your Event?</h2>

        <p>
            Explore our packages and choose the decoration package
            that suits your celebration.
        </p>

        <a href="packages.php" class="booking-btn">
            Booking <span>→</span>
        </a>

    </div>

    <a href="services.php" class="back-link">
        ← Back to All Services
    </a>

</section>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Wedding Decoration Details | DecorEase";
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
    font-family: Arial, sans-serif;
    background: #fffaf7;
    color: #38232d;
    line-height: 1.7;
}

/* Hero Section */
.wedding-hero {
    padding: 65px 7%;
    text-align: center;
    color: white;
    background: linear-gradient(115deg, #38202f, #a34d70);
}

.breadcrumb {
    font-size: 13px;
    color: #f5dce6;
    margin-bottom: 12px;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.wedding-hero h1 {
    font-size: clamp(30px, 4vw, 45px);
    margin: 5px 0 10px;
    color: white;
}

.wedding-hero p {
    max-width: 700px;
    margin: auto;
    color: #f8eaf0;
    font-size: 16px;
}

/* Main Section */
.wedding-container {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 7%;
}

.wedding-intro {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 35px;
    align-items: center;
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid #f0dce4;
    box-shadow: 0 7px 22px rgba(65, 28, 46, .08);
}

.wedding-image {
    height: 370px;
    overflow: hidden;
    border-radius: 11px;
}

.wedding-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.wedding-content h2 {
    color: #57283c;
    font-size: 27px;
    margin: 0 0 12px;
}

.wedding-content p {
    color: #756870;
    font-size: 15px;
    margin-bottom: 15px;
}

/* Features */
.features-section {
    margin-top: 35px;
    padding: 28px;
    background: white;
    border: 1px solid #f0dce4;
    border-radius: 13px;
}

.features-section h2 {
    color: #57283c;
    font-size: 25px;
    margin: 0 0 20px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px 25px;
}

.feature {
    display: flex;
    gap: 10px;
    color: #65535c;
    font-size: 14px;
}

.feature span:first-child {
    color: #b84a72;
    font-weight: bold;
    font-size: 17px;
}

/* Booking Section */
.booking-section {
    margin-top: 30px;
    padding: 35px 25px;
    text-align: center;
    border-radius: 13px;
    color: white;
    background: linear-gradient(110deg, #57283c, #a34d70);
}

.booking-section h2 {
    color: white;
    font-size: 27px;
    margin: 0 0 8px;
}

.booking-section p {
    color: #f8eaf0;
    font-size: 14px;
    margin: 0 0 22px;
}

.booking-btn {
    display: inline-block;
    padding: 12px 32px;
    background: white;
    color: #57283c;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    transition: .3s;
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
    font-weight: bold;
    font-size: 14px;
}

.back-link:hover {
    color: #57283c;
}

/* Responsive */
@media(max-width: 800px) {
    .wedding-intro {
        grid-template-columns: 1fr;
    }

    .wedding-image {
        height: 280px;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 600px) {
    .wedding-container {
        padding: 0 5%;
        margin: 28px auto;
    }

    .wedding-hero {
        padding: 42px 5%;
    }

    .wedding-image {
        height: 220px;
    }

    .features-section {
        padding: 20px;
    }
}
</style>

</head>

<body>

<?php include 'includes/navber.php'; ?>

<main>

    <!-- Hero Section -->
    <section class="wedding-hero">

        <div class="breadcrumb">
            <a href="../index.php">Home</a> /
            <a href="../services.php">Services</a> /
            Wedding Decoration
        </div>

        <h1>Wedding Decoration</h1>

        <p>
            Celebrate your beautiful beginning with elegant decoration,
            stunning floral arrangements and unforgettable wedding designs.
        </p>

    </section>


    <!-- Wedding Details -->
    <section class="wedding-container">

        <div class="wedding-intro">

            <div class="wedding-image">
                <img
                    src="images/wed1.png"
                    alt="Beautiful Wedding Decoration"
                >
            </div>

            <div class="wedding-content">

                <h2>Make Your Wedding Day Special</h2>

                <p>
                    Your wedding is one of the most memorable moments
                    of your life. At DecorEase, we create beautiful
                    wedding environments with elegant stage designs,
                    floral decorations and carefully coordinated
                    venue styling.
                </p>

                <p>
                    From traditional wedding arrangements to modern
                    and luxurious themes, we aim to make every detail
                    reflect your celebration and personal preferences.
                </p>

                <p>
                    Explore our available decoration packages and
                    choose the option that best matches your event
                    and budget.
                </p>

            </div>

        </div>


        <!-- Features -->
        <div class="features-section">

            <h2>Our Wedding Decoration Services</h2>

            <div class="features-grid">

                <div class="feature">
                    <span>✓</span>
                    <span>Elegant wedding stage decoration</span>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <span>Beautiful entrance gate decoration</span>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <span>Floral arrangements and backdrops</span>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <span>Bride and groom seating decoration</span>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <span>Decorative lighting arrangements</span>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <span>Customized wedding themes</span>
                </div>

            </div>

        </div>


        <!-- Booking -->
        <div class="booking-section">

            <h2>Ready to Plan Your Wedding?</h2>

            <p>
                Explore our decoration packages, compare prices
                and choose the perfect package for your special day.
            </p>

            <a href="../packages.php" class="booking-btn">
                Booking →
            </a>

        </div>


        <a href="../services.php" class="back-link">
            ← Back to All Services
        </a>

    </section>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>

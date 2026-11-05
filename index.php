<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Fetch active decoration packages
$packageSql = "
    SELECT
        p.package_id AS id,
        p.title AS package_name,
        p.description,
        p.base_price AS price,
        c.name AS type_name
    FROM packages p
    INNER JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.is_active = 1
    ORDER BY p.package_id ASC
";

$packages = $conn->query($packageSql);

if (!$packages) {
    error_log("Package query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DecorEase | Event Decoration</title>

    <?php include 'includes/nav_style.php'; ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #fffaf7;
            color: #333333;
            line-height: 1.6;
        }

        /* Hero Section */
        .hero {
            min-height: 480px;
            padding: 80px 7%;
            display: flex;
            align-items: center;
            background: linear-gradient(90deg, #fff0f4, #fffaf7);
        }

        .hero-content {
            max-width: 650px;
        }

        .hero h1 {
            margin-bottom: 20px;
            color: #57283c;
            font-size: 46px;
            line-height: 1.2;
        }

        .hero p {
            margin-bottom: 28px;
            color: #666666;
            font-size: 17px;
        }

        .btn {
            display: inline-block;
            margin: 0 10px 8px 0;
            padding: 12px 25px;
            border-radius: 5px;
            background-color: #b84a72;
            color: #ffffff;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #913653;
        }

        .btn-secondary {
            border: 1px solid #b84a72;
            background-color: transparent;
            color: #b84a72;
        }

        .btn-secondary:hover {
            background-color: #b84a72;
            color: #ffffff;
        }

        /* General Sections */
        .content-section {
            padding: 65px 7%;
        }

        .section-title {
            margin-bottom: 35px;
            text-align: center;
        }

        .section-title h2 {
            margin-bottom: 8px;
            color: #57283c;
            font-size: 32px;
        }

        .section-title p {
            color: #777777;
        }

        /* Services */
        .service-container {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .service-card {
            padding: 30px 22px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            text-align: center;
            transition: 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            margin-bottom: 12px;
            font-size: 42px;
        }

        .service-card h3 {
            margin-bottom: 10px;
            color: #b84a72;
        }

        .service-card p {
            color: #777777;
            font-size: 14px;
        }

        /* Packages */
        .packages-section {
            background-color: #fff0f4;
        }

        .package-container {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .package-card {
            overflow: hidden;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .package-image {
            height: 170px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f9dce6;
            font-size: 55px;
        }

        .package-details {
            padding: 22px;
        }

        .package-details h3 {
            margin-bottom: 10px;
            color: #57283c;
        }

        .package-details p {
            margin-bottom: 15px;
            color: #777777;
            font-size: 14px;
        }

        .price {
            color: #b84a72;
            font-size: 20px;
            font-weight: bold;
        }

        .book-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #b84a72;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .book-btn:hover {
            background-color: #913653;
            color: #ffffff;
        }

        /* Booking Call to Action */
        .booking-section {
            padding: 60px 7%;
            background-color: #57283c;
            color: #ffffff;
            text-align: center;
        }

        .booking-section h2 {
            margin-bottom: 12px;
            font-size: 30px;
        }

        .booking-section p {
            margin-bottom: 22px;
        }

        .booking-section .btn {
            background-color: #ffffff;
            color: #57283c;
        }

        /* Contact */
        .contact-container {
            text-align: center;
        }

        .contact-container p {
            margin: 8px 0;
            color: #666666;
        }

        /* Footer */
        .site-footer {
            padding: 20px;
            background-color: #2d1b24;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
        }

        /* Responsive Content */
        @media (max-width: 768px) {
            .hero {
                min-height: 380px;
                padding: 60px 7%;
            }

            .hero h1 {
                font-size: 34px;
            }

            .service-container,
            .package-container {
                grid-template-columns: 1fr;
            }

            .section-title h2 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navber.php'; ?>

    <main>

        <!-- Hero Section -->
        <section class="hero" id="home">

            <div class="hero-content">

                <h1>Make Every Event Beautiful &amp; Memorable</h1>

                <p>
                    From weddings and birthdays to family celebrations,
                    we help you create beautiful moments with customized
                    event decoration services.
                </p>

                <a href="#packages" class="btn">
                    Explore Packages
                </a>

                <a href="#contact" class="btn btn-secondary">
                    Contact Us
                </a>

            </div>

        </section>


        <!-- Services Section -->
        <section class="content-section" id="services">

            <div class="section-title">
                <h2>Our Decoration Services</h2>
                <p>Beautiful decoration for every special occasion.</p>
            </div>

            <div class="service-container">

                <div class="service-card">
                    <div class="service-icon">💍</div>
                    <h3>Wedding Decoration</h3>
                    <p>
                        Elegant wedding stages, floral arrangements
                        and beautiful venue decoration.
                    </p>
                </div>

                <div class="service-card">
                    <div class="service-icon">🎂</div>
                    <h3>Birthday Decoration</h3>
                    <p>
                        Creative birthday themes, balloon decoration
                        and attractive party setups.
                    </p>
                </div>

                <div class="service-card">
                    <div class="service-icon">🌼</div>
                    <h3>Holud &amp; Family Events</h3>
                    <p>
                        Colorful traditional decoration for holud,
                        akika and other family celebrations.
                    </p>
                </div>

            </div>

        </section>


        <!-- Packages Section -->
        <section class="content-section packages-section" id="packages">

            <div class="section-title">
                <h2>Featured Decoration Packages</h2>
                <p>Choose a package that suits your event and budget.</p>
            </div>

            <div class="package-container">

                <?php if ($packages && $packages->num_rows > 0): ?>

                    <?php while ($package = $packages->fetch_assoc()): ?>

                        <?php
                        $eventName = strtolower($package['type_name']);
                        $icon = '🎉';

                        if (strpos($eventName, 'wedding') !== false) {
                            $icon = '💐';
                        } elseif (strpos($eventName, 'birthday') !== false) {
                            $icon = '🎈';
                        } elseif (strpos($eventName, 'holud') !== false) {
                            $icon = '🌼';
                        } elseif (strpos($eventName, 'akika') !== false) {
                            $icon = '🌺';
                        } elseif (strpos($eventName, 'corporate') !== false) {
                            $icon = '🏢';
                        }
                        ?>

                        <div class="package-card">

                            <div class="package-image">
                                <?php echo $icon; ?>
                            </div>

                            <div class="package-details">

                                <h3>
                                    <?php
                                    echo htmlspecialchars($package['package_name']);
                                    ?>
                                </h3>

                                <p>
                                    <?php
                                    echo htmlspecialchars(
                                        $package['description'] ?? ''
                                    );
                                    ?>
                                </p>

                                <div class="price">
                                    ৳ <?php
                                    echo number_format(
                                        (float) $package['price'],
                                        2
                                    );
                                    ?>
                                </div>

                                <a
                                    href="booking.php?package_id=<?php echo (int) $package['id']; ?>"
                                    class="book-btn"
                                >
                                    Book Now
                                </a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p>
                        No decoration packages are currently available.
                    </p>

                <?php endif; ?>

            </div>

        </section>


        <!-- Booking Section -->
        <section class="booking-section">

            <h2>Planning a Special Event?</h2>

            <p>
                Tell us about your event, preferred theme,
                date and budget.
            </p>

            <a href="login.php" class="btn">
                Start Your Planning
            </a>

        </section>


        <!-- Contact Section -->
        <section class="content-section" id="contact">

            <div class="section-title">
                <h2>Contact Us</h2>
                <p>We would love to hear about your upcoming event.</p>
            </div>

            <div class="contact-container">

                <p>
                    <strong>Project:</strong>
                    Event Decoration Management System
                </p>

                <p>
                    <strong>Email:</strong>
                    info@decorease.com
                </p>

                <p>
                    <strong>Phone:</strong>
                    +880 1XXXXXXXXX
                </p>

                <p>
                    <strong>Location:</strong>
                    Dhaka, Bangladesh
                </p>

            </div>

        </section>

    </main>


    <!-- Footer -->
    <footer class="site-footer">

        <p>
            &copy; <?php echo date("Y"); ?>
            DecorEase | Event Decoration Management System
        </p>

    </footer>

</body>
</html>
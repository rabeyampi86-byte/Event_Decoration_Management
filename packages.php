<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Fetch packages from decoration_packages and event_types
$packageSql = "
    SELECT
        dp.id,
        dp.package_name,
        dp.description,
        dp.price,
        et.type_name
    FROM decoration_packages dp
    INNER JOIN event_types et
        ON dp.event_type_id = et.id
    WHERE dp.status = 'active'
      AND et.status = 'active'
    ORDER BY dp.id ASC
    LIMIT 6
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

    <title>Our Packages | DecorEase</title>

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

        /* Page Header */

        .page-header {
            padding: 65px 7%;
            text-align: center;
            background: linear-gradient(90deg, #fff0f4, #fffaf7);
        }

        .page-header h1 {
            margin-bottom: 12px;
            color: #57283c;
            font-size: 38px;
        }

        .page-header p {
            color: #777777;
            font-size: 16px;
        }

        /* Packages Section */

        .packages-section {
            padding: 65px 7%;
            background-color: #fff0f4;
            min-height: 400px;
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
            transition: 0.3s;
        }

        .package-card:hover {
            transform: translateY(-5px);
        }

       .package-image {
    width: 100%;
    height: 210px;
    overflow: hidden;
    background-color: #f9dce6;
}

.package-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}

.package-card:hover .package-image img {
    transform: scale(1.06);
}

        .package-details {
            padding: 22px;
        }

        .package-details h3 {
            margin-bottom: 10px;
            color: #57283c;
            font-size: 20px;
        }

        .package-type {
            display: inline-block;
            margin-bottom: 12px;
            padding: 4px 12px;
            background-color: #fff0f4;
            color: #b84a72;
            border-radius: 20px;
            font-size: 12px;
        }

        .package-details p {
            margin-bottom: 15px;
            color: #777777;
            font-size: 14px;
            min-height: 45px;
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
            transition: 0.3s;
        }

        .book-btn:hover {
            background-color: #913653;
            color: #ffffff;
        }

        .empty-message {
            grid-column: 1 / -1;
            padding: 35px;
            background-color: #ffffff;
            border-radius: 10px;
            text-align: center;
            color: #777777;
        }

        /* Booking CTA */

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
            display: inline-block;
            padding: 12px 25px;
            background-color: #ffffff;
            color: #57283c;
            border-radius: 5px;
            text-decoration: none;
            transition: 0.3s;
        }

        .booking-section .btn:hover {
            background-color: #f9dce6;
        }

        /* Footer */

        .site-footer {
            padding: 20px;
            background-color: #2d1b24;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
        }

        /* Responsive */

        @media (max-width: 768px) {

            .page-header h1 {
                font-size: 30px;
            }

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

    <!-- Navbar -->
    <?php include 'includes/navber.php'; ?>


    <main>

        <!-- Page Header -->

        <section class="page-header">

            <h1>Our Decoration Packages</h1>

            <p>
                Explore our decoration packages and choose
                the perfect one for your special event.
            </p>

        </section>


        <!-- Packages Section -->

        <section class="packages-section" id="packages">

            <div class="section-title">

                <h2>Featured Decoration Packages</h2>

                <p>
                    Choose a package that suits your event and budget.
                </p>

            </div>


            <div class="package-container">

                <?php if ($packages && $packages->num_rows > 0): ?>

                    <?php while ($package = $packages->fetch_assoc()): ?>

                        <?php

                        $eventName = strtolower(trim($package['type_name']));

$image = 'images/custom.jpg';

if (strpos($eventName, 'wedding') !== false) {

    $image = 'images/wed.png';

} elseif (strpos($eventName, 'engagement') !== false) {

    $image = 'images/gate2.png';

} elseif (strpos($eventName, 'holud') !== false) {

    $image = 'images/h2.jpg';

} elseif (
    strpos($eventName, 'akika') !== false ||
    strpos($eventName, 'aqiqah') !== false
) {

    $image = 'images/ak1.jpg';

} elseif (strpos($eventName, 'birthday') !== false) {

    $image = 'images/h3.jpg';

} elseif (strpos($eventName, 'corporate') !== false) {

    $image = 'images/com2.jpg';

} elseif (strpos($eventName, 'anniversary') !== false) {

    $image = 'images/anniversary.jpg';

}

                        ?>


                        <div class="package-card">

                           <div class="package-image">
    <img
        src="<?php echo htmlspecialchars($image); ?>"
        alt="<?php echo htmlspecialchars($package['type_name']); ?> Decoration"
    >
</div>


                            <div class="package-details">

                                <h3>
                                    <?php
                                    echo htmlspecialchars($package['package_name']);
                                    ?>
                                </h3>


                                <span class="package-type">

                                    <?php
                                    echo htmlspecialchars($package['type_name']);
                                    ?>

                                </span>


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

                    <div class="empty-message">

                        <h3>No Packages Available</h3>

                        <p>
                            Decoration packages are currently unavailable.
                            Please check again later.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Booking CTA -->

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

    </main>


    <?php include 'includes/footer.php'; ?>

</body>

</html>
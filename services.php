
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* =========================================
   HTML ESCAPE FUNCTION
========================================= */

function decorEaseEscape($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================
   CART INITIALIZATION
========================================= */

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (empty($_SESSION['cart_csrf'])) {
    $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
}

/* =========================================
   ADD PACKAGE TO CART
========================================= */

$cartSuccess = '';
$cartError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package_id'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['cart_csrf'], $token)) {

        $cartError = "Invalid request. Please try again.";

    } else {

        $packageId = (int) $_POST['add_package_id'];

        if ($packageId > 0) {

            $stmt = $conn->prepare("
                SELECT package_id
                FROM packages
                WHERE package_id = ?
                AND status = 'active'
                LIMIT 1
            ");

            $stmt->bind_param("i", $packageId);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                if (!in_array($packageId, $_SESSION['cart'], true)) {

                    $_SESSION['cart'][] = $packageId;

                    $cartSuccess = "Package added to your cart successfully.";

                } else {

                    $cartSuccess = "This package is already in your cart.";

                }

            } else {

                $cartError = "This package is currently unavailable.";

            }

            $stmt->close();

        } else {

            $cartError = "Invalid package.";

        }
    }
}

/* =========================================
   FETCH ACTIVE EVENT CATEGORIES
========================================= */

$eventCategories = [];

$categorySql = "
    SELECT
        category_id,
        name
    FROM event_categories
    WHERE status = 'active'
    ORDER BY sort_order ASC, name ASC
";

$categoryResult = $conn->query($categorySql);

if ($categoryResult) {
    while ($category = $categoryResult->fetch_assoc()) {
        $eventCategories[] = $category;
    }
}

/* =========================================
   FETCH ALL ACTIVE PACKAGES
========================================= */

$packages = [];

$packageSql = "
    SELECT
        p.package_id,
        p.category_id,
        p.package_name,
        p.short_description,
        p.description AS package_description,
        p.base_price,
        p.guest_capacity,
        p.is_featured,

        ec.name AS category_name,

        m.file_path AS image_path,
        m.alt_text AS image_alt

    FROM packages p

    INNER JOIN event_categories ec
        ON p.category_id = ec.category_id

    LEFT JOIN media m
        ON p.image_id = m.media_id

    WHERE
        p.status = 'active'
        AND ec.status = 'active'

    ORDER BY
        p.is_featured DESC,
        ec.sort_order ASC,
        p.package_name ASC
";

$packageResult = $conn->query($packageSql);

if ($packageResult) {
    while ($package = $packageResult->fetch_assoc()) {
        $packages[] = $package;
    }
}

/* HEADER */
include_once 'includes/nav_style.php';
include_once 'includes/navber.php';
?>
<link rel="stylesheet" href="assets/css/style.css">


<main class="services-page">

    <!-- HERO SECTION -->

    <section class="services-hero">

        <div class="hero-content">

            <div class="hero-subtitle">
                DecorEase Event Decoration
            </div>

            <h1>
                Make Every Moment
                <span>Unforgettable</span>
            </h1>

            <p>
                From elegant weddings to beautiful birthday celebrations,
                we bring your special moments to life with creative decoration
                and thoughtful planning.
            </p>

        </div>

    </section>


    <!-- ALL SERVICES -->

    <section class="services-container" id="all-services">

        <div class="section-heading">

            <div class="small-title">
                What We Offer
            </div>

            <h2>
                Our Decoration Packages
            </h2>

            <p>
                Discover our carefully designed event decoration packages.
                Choose the perfect arrangement for your special occasion.
            </p>

            <div class="heading-line"></div>

        </div>


        <!-- CATEGORY FILTER -->

        <div class="category-filter">

            <button
                type="button"
                class="category-filter-btn active"
                data-filter="all"
            >
                All Events
            </button>

            <?php foreach ($eventCategories as $category): ?>

                <button
                    type="button"
                    class="category-filter-btn"
                    data-filter="<?php echo (int)$category['category_id']; ?>"
                >
                    <?php echo decorEaseEscape($category['name']); ?>
                </button>

            <?php endforeach; ?>

        </div>


        <!-- CART MESSAGE -->

        <?php if ($cartSuccess): ?>

            <div style="
                background:#eaf7ee;
                color:#237342;
                padding:14px 18px;
                border-radius:7px;
                margin:20px 0;
            ">

                <i class="fas fa-check-circle"></i>

                <?php echo decorEaseEscape($cartSuccess); ?>

                <a href="cart.php" style="
                    color:#237342;
                    font-weight:700;
                    margin-left:10px;
                ">
                    View Cart
                    <i class="fas fa-arrow-right"></i>
                </a>

            </div>

        <?php endif; ?>


        <?php if ($cartError): ?>

            <div style="
                background:#fff0f0;
                color:#c62828;
                padding:14px 18px;
                border-radius:7px;
                margin:20px 0;
            ">

                <i class="fas fa-exclamation-circle"></i>

                <?php echo decorEaseEscape($cartError); ?>

            </div>

        <?php endif; ?>


        <!-- PACKAGE GRID -->

        <?php if (!empty($packages)): ?>

            <div class="package-grid" id="packageGrid">

                <?php foreach ($packages as $package): ?>

                    <article
                        class="package-card"
                        data-category="<?php echo (int)$package['category_id']; ?>"
                    >

                        <!-- PACKAGE IMAGE -->

                        <div class="package-image">

                            <?php if (!empty($package['image_path'])): ?>

                                <img
                                    src="<?php echo decorEaseEscape($package['image_path']); ?>"
                                    alt="<?php echo decorEaseEscape(
                                        $package['image_alt']
                                        ?: $package['package_name']
                                    ); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="image-placeholder">
                                    <i class="fas fa-camera"></i>
                                </div>

                            <?php endif; ?>


                            <?php if ((int)$package['is_featured'] === 1): ?>

                                <span class="featured-label">
                                    Featured
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- PACKAGE CONTENT -->

                        <div class="package-content">

                            <div class="package-category">
                                <?php echo decorEaseEscape($package['category_name']); ?>
                            </div>

                            <h4>
                                <?php echo decorEaseEscape($package['package_name']); ?>
                            </h4>

                            <p class="package-description">

                                <?php
                                echo decorEaseEscape(
                                    $package['short_description']
                                    ?: $package['package_description']
                                );
                                ?>

                            </p>


                            <!-- PRICE AND CAPACITY -->

                            <div class="package-info">

                                <div class="package-price">

                                    <small>
                                        Starting From
                                    </small>

                                    <strong>
                                        ৳<?php echo number_format(
                                            (float)$package['base_price'],
                                            2
                                        ); ?>
                                    </strong>

                                </div>


                                <?php if (!empty($package['guest_capacity'])): ?>

                                    <div class="package-guests">

                                        <i class="fas fa-users"></i>

                                        Up to
                                        <?php echo decorEaseEscape(
                                            $package['guest_capacity']
                                        ); ?>
                                        Guests

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- ACTION BUTTONS -->

                            <div class="package-buttons">

                                <a
                                    href="package_details.php?id=<?php echo (int)$package['package_id']; ?>"
                                    class="details-button"
                                >
                                    View Details
                                </a>

                                <a
                                    href="package_details.php?id=<?php echo (int)$package['package_id']; ?>#booking"
                                    class="booking-button"
                                >
                                    Book Now
                                </a>

                            </div>


                            <!-- ADD TO CART -->

                            <form method="POST" style="margin-top:12px;">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo decorEaseEscape($_SESSION['cart_csrf']); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="add_package_id"
                                    value="<?php echo (int)$package['package_id']; ?>"
                                >

                                <button
                                    type="submit"
                                    style="
                                        width:100%;
                                        padding:12px;
                                        background:#392b2b;
                                        color:#fff;
                                        border:none;
                                        border-radius:6px;
                                        font-size:14px;
                                        font-weight:600;
                                        cursor:pointer;
                                    "
                                >
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <!-- NO FILTER RESULT MESSAGE -->

            <div
                class="empty-category"
                id="noPackageMessage"
                style="display:none;"
            >
                No packages are available in this category yet.
            </div>


        <?php else: ?>

            <div class="empty-category">
                No packages are available at the moment.
                Please check back later.
            </div>

        <?php endif; ?>


        <!-- CONTACT SECTION -->

        <div class="services-cta">

            <h2>
                Have a Unique Event in Mind?
            </h2>

            <p>
                Let us help you create a beautiful and memorable experience.
                Contact our team to discuss your ideas and customize
                your decoration arrangements.
            </p>

            <a href="contact.php" class="cta-button">
                Contact Us
            </a>

        </div>

    </section>

</main>


<!-- CATEGORY FILTER JAVASCRIPT -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const filterButtons = document.querySelectorAll(
        '.services-page .category-filter-btn'
    );

    const packageCards = document.querySelectorAll(
        '.services-page .package-card'
    );

    const noPackageMessage = document.getElementById(
        'noPackageMessage'
    );

    filterButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const selectedCategory = this.dataset.filter;

            let visibleCount = 0;

            filterButtons.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            packageCards.forEach(function (card) {

                const cardCategory = card.dataset.category;

                const shouldShow =
                    selectedCategory === 'all' ||
                    selectedCategory === cardCategory;

                if (shouldShow) {

                    card.classList.remove('hidden');
                    visibleCount++;

                } else {

                    card.classList.add('hidden');

                }

            });

            if (noPackageMessage) {

                noPackageMessage.style.display =
                    visibleCount === 0 ? 'block' : 'none';

            }

        });

    });

});
</script>


<?php include_once __DIR__ . '/includes/footer.php'; ?>

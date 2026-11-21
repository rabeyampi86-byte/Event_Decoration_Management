
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* ===============================
   Cart Initialization
================================ */

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (empty($_SESSION['cart_csrf'])) {
    $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';

/* ===============================
   Add / Remove Cart Items
================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['cart_csrf'], $token)) {
        $error = "Invalid request. Please try again.";
    } else {

        // Add package
        if (isset($_POST['add_package_id'])) {

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
                        $success = "Package added to your cart.";
                    } else {
                        $success = "This package is already in your cart.";
                    }

                } else {
                    $error = "This package is currently unavailable.";
                }

                $stmt->close();

            } else {
                $error = "Invalid package.";
            }
        }

        // Remove package
        if (isset($_POST['remove_package_id'])) {

            $removeId = (int) $_POST['remove_package_id'];

            $_SESSION['cart'] = array_values(
                array_filter(
                    $_SESSION['cart'],
                    function ($id) use ($removeId) {
                        return (int) $id !== $removeId;
                    }
                )
            );

            $success = "Package removed from your cart.";
        }
    }
}

/* ===============================
   Fetch Cart Packages
================================ */

$cartPackages = [];
$totalPrice = 0;

if (!empty($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $packageId) {

        $packageId = (int) $packageId;

        $stmt = $conn->prepare("
            SELECT
                p.package_id,
                p.package_name,
                p.short_description,
                p.description,
                p.base_price,
                p.guest_capacity,
                ec.name AS category_name,
                m.file_path,
                m.alt_text

            FROM packages p

            LEFT JOIN event_categories ec
                ON p.category_id = ec.category_id

            LEFT JOIN media m
                ON p.image_id = m.media_id

            WHERE p.package_id = ?
            AND p.status = 'active'

            LIMIT 1
        ");

        $stmt->bind_param("i", $packageId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $package = $result->fetch_assoc();

            $cartPackages[] = $package;
            $totalPrice += (float) $package['base_price'];

        } else {

            // Remove unavailable packages from cart
            $_SESSION['cart'] = array_values(
                array_filter(
                    $_SESSION['cart'],
                    function ($id) use ($packageId) {
                        return (int) $id !== $packageId;
                    }
                )
            );
        }

        $stmt->close();
    }
}

function cartEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart | DecorEase</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .cart-page {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px 60px;
        }

        .cart-heading {
            margin-bottom: 30px;
        }

        .cart-heading h1 {
            color: #392b2b;
            font-size: 32px;
            margin-bottom: 8px;
        }

        .cart-heading p {
            color: #777;
        }

        .cart-message {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .cart-success {
            background: #eaf7ee;
            color: #237342;
        }

        .cart-error {
            background: #fff0f0;
            color: #c62828;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 25px;
            align-items: start;
        }

        .cart-items {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .cart-item {
            background: #fff;
            border: 1px solid #eee5dc;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        }

        .cart-item-image {
            height: 210px;
            background: #f5f1ec;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c58b45;
            font-size: 45px;
        }

        .cart-item-content {
            padding: 20px;
        }

        .cart-category {
            color: #b77b35;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cart-item-content h3 {
            color: #392b2b;
            font-size: 20px;
            margin: 9px 0;
        }

        .cart-description {
            color: #777;
            font-size: 13px;
            line-height: 1.6;
            min-height: 42px;
        }

        .cart-capacity {
            color: #666;
            font-size: 13px;
            margin: 13px 0;
        }

        .cart-price {
            color: #a97132;
            font-size: 21px;
            font-weight: 700;
            margin-bottom: 17px;
        }

        .cart-item-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .continue-booking,
        .remove-cart {
            border: none;
            padding: 11px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .continue-booking {
            background: #c58b45;
            color: #fff;
            flex: 1;
        }

        .continue-booking:hover {
            background: #a97132;
            color: #fff;
        }

        .remove-cart {
            background: #fff0f0;
            color: #c62828;
        }

        .remove-cart:hover {
            background: #ffe0e0;
        }

        .cart-summary {
            background: #fff;
            border: 1px solid #eee5dc;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
        }

        .cart-summary h3 {
            color: #392b2b;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .summary-total {
            border-top: 1px solid #eee;
            padding-top: 18px;
            margin-top: 18px;
            color: #392b2b;
            font-size: 17px;
            font-weight: 700;
        }

        .browse-services {
            display: inline-block;
            margin-top: 15px;
            color: #a97132;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .empty-cart {
            background: #fff;
            border-radius: 12px;
            padding: 65px 20px;
            text-align: center;
            border: 1px solid #eee5dc;
        }

        .empty-cart i {
            font-size: 55px;
            color: #c58b45;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            color: #392b2b;
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #777;
            margin-bottom: 20px;
        }

        .empty-cart a {
            display: inline-block;
            background: #c58b45;
            color: #fff;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .cart-items {
                grid-template-columns: 1fr;
            }

            .cart-heading h1 {
                font-size: 26px;
            }
        }
    </style>
    <?php include 'includes/nav_style.php'; ?>
</head>

<body>

<?php include_once 'includes/navber.php'; ?>

<main class="cart-page">

    <div class="cart-heading">
        <h1><i class="fas fa-shopping-cart"></i> My Cart</h1>
        <p>Review your selected decoration packages before booking.</p>
    </div>

    <?php if ($success): ?>
        <div class="cart-message cart-success">
            <i class="fas fa-check-circle"></i>
            <?php echo cartEscape($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="cart-message cart-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo cartEscape($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartPackages)): ?>

        <div class="empty-cart">
            <i class="fas fa-shopping-basket"></i>

            <h2>Your Cart is Empty</h2>

            <p>You haven't added any decoration packages yet.</p>

            <a href="services.php">
                <i class="fas fa-arrow-left"></i>
                Explore Packages
            </a>
        </div>

    <?php else: ?>

        <div class="cart-layout">

            <div class="cart-items">

                <?php foreach ($cartPackages as $package): ?>

                    <div class="cart-item">

                        <div class="cart-item-image">

                            <?php if (!empty($package['file_path'])): ?>

                                <img
                                    src="<?php echo cartEscape($package['file_path']); ?>"
                                    alt="<?php echo cartEscape($package['alt_text'] ?: $package['package_name']); ?>"
                                >

                            <?php else: ?>

                                <div class="cart-image-placeholder">
                                    <i class="fas fa-gem"></i>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="cart-item-content">

                            <span class="cart-category">
                                <?php echo cartEscape($package['category_name']); ?>
                            </span>

                            <h3>
                                <?php echo cartEscape($package['package_name']); ?>
                            </h3>

                            <p class="cart-description">
                                <?php
                                echo cartEscape(
                                    $package['short_description']
                                    ?: $package['description']
                                );
                                ?>
                            </p>

                            <p class="cart-capacity">
                                <i class="fas fa-users"></i>
                                Guest Capacity:
                                <?php echo (int) $package['guest_capacity']; ?>
                            </p>

                            <div class="cart-price">
                                ৳<?php echo number_format((float) $package['base_price'], 2); ?>
                            </div>

                            <div class="cart-item-actions">

                                <a
                                    href="package_details.php?id=<?php echo (int) $package['package_id']; ?>"
                                    class="continue-booking"
                                >
                                    Continue Booking
                                    <i class="fas fa-arrow-right"></i>
                                </a>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo cartEscape($_SESSION['cart_csrf']); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="remove_package_id"
                                        value="<?php echo (int) $package['package_id']; ?>"
                                    >

                                    <button type="submit" class="remove-cart">
                                        <i class="fas fa-trash-alt"></i>
                                        Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <aside class="cart-summary">

                <h3>Cart Summary</h3>

                <div class="summary-row">
                    <span>Total Packages</span>
                    <strong><?php echo count($cartPackages); ?></strong>
                </div>

                <div class="summary-row summary-total">
                    <span>Total Price</span>
                    <span>
                        ৳<?php echo number_format($totalPrice, 2); ?>
                    </span>
                </div>

                <p style="font-size:12px; color:#888; line-height:1.6;">
                    Each package will be booked separately.
                    The final booking amount may depend on your event requirements.
                </p>

                <a href="services.php" class="browse-services">
                    <i class="fas fa-plus"></i>
                    Add More Packages
                </a>

            </aside>

        </div>

    <?php endif; ?>

</main>

<?php include_once 'includes/footer.php'; ?>

</body>
</html>

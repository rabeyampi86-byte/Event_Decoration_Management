<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';

// Current page identify
$currentPage = basename($_SERVER['PHP_SELF']);

$cartCount = 0;

if (
    $userRole === 'customer' &&
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {
    $cartCount = count($_SESSION['cart']);
}

function isActivePage($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<!-- Font Awesome Icons -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<header class="site-header">

    <a href="index.php" class="logo">
        DecorEase
    </a>

    <nav class="main-nav">

        <a href="index.php"
           class="<?php echo isActivePage('index.php', $currentPage); ?>">
            Home
        </a>

        <a href="about.php"
           class="<?php echo isActivePage('about.php', $currentPage); ?>">
            About
        </a>

        <a href="services.php"
           class="<?php echo isActivePage('services.php', $currentPage); ?>">
            Services
        </a>

        <a href="gallery.php"
           class="<?php echo isActivePage('gallery.php', $currentPage); ?>">
            Gallery
        </a>

        <a href="contact.php"
           class="<?php echo isActivePage('contact.php', $currentPage); ?>">
            Contact
        </a>

        <?php if ($isLoggedIn): ?>

            <!-- PROFILE DROPDOWN -->
            <div class="nav-profile">

                <button
                    type="button"
                    class="profile-toggle"
                    id="profileToggle"
                    aria-expanded="false"
                >
                    <i class="fas fa-user-circle"></i>

                    <span>
                        <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="profile-dropdown" id="profileDropdown">

                    <div class="dropdown-user-info">

                        <i class="fas fa-user-circle"></i>

                        <div>
                            <strong>
                                <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                            </strong>

                            <small>
                                <?php echo htmlspecialchars(ucfirst($userRole), ENT_QUOTES, 'UTF-8'); ?>
                            </small>
                        </div>

                    </div>

                    <?php if ($userRole === 'customer'): ?>

                        <a href="my_profile.php">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>

                        <a href="my_bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
                        </a>

                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            My Cart

                            <?php if ($cartCount > 0): ?>
                                <span class="cart-count">
                                    <?php echo $cartCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>

                    <?php elseif ($userRole === 'admin'): ?>

                        <a href="admin/admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            Admin Dashboard
                        </a>

                        <a href="my_profile.php">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>

                    <?php elseif ($userRole === 'employee'): ?>

                        <a href="employee/employee_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            Employee Dashboard
                        </a>

                        <a href="my_profile.php">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>

                    <?php endif; ?>

                    <div class="dropdown-divider"></div>

                    <a href="logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>

                </div>
            </div>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </a>

        <?php endif; ?>

    </nav>

</header>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    if (!profileToggle || !profileDropdown) {
        return;
    }

    profileToggle.addEventListener('click', function (event) {

        event.stopPropagation();

        const isOpen = profileDropdown.classList.toggle('show');

        profileToggle.setAttribute('aria-expanded', isOpen);

    });

    document.addEventListener('click', function (event) {

        if (
            !profileDropdown.contains(event.target) &&
            !profileToggle.contains(event.target)
        ) {

            profileDropdown.classList.remove('show');

            profileToggle.setAttribute('aria-expanded', 'false');

        }

    });

});
</script>

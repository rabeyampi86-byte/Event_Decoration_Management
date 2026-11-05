
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php include_once __DIR__ . '/nav_style.php'; ?>

<header class="site-header">

    <!-- Website Logo -->
    <a href="index.php" class="site-logo">
        DecorEase
    </a>

    <!-- Navigation Links -->
    <nav class="main-nav">

        <a href="index.php">Home</a>

        <a href="index.php#services">Services</a>

        <a href="packages.php">Packages</a>

        <a href="index.php#contact">Contact</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <a href="logout.php" class="login-btn">Logout</a>

        <?php else: ?>

            <a href="login.php" class="login-btn">Login</a>

        <?php endif; ?>

    </nav>

</header>

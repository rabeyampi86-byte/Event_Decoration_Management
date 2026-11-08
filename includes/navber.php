<header class="site-header">
    <a href="index.php" class="logo">DecorEase</a>

    <nav class="main-nav">
        <a href="index.php">Home</a>
        <a href="services.php">Services</a>
        <a href="packages.php">Packages</a>
        <a href="contact.php">Contact</a>

        <?php if (isset($_SESSION["user_id"])): ?>
        <span class="welcome-user">
         Hi, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </span>
        <a href="logout.php" class="login-btn">Logout</a>
        <?php else: ?>
        <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>

    </nav>
</header>

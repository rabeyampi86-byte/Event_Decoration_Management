
<style>
/* Navbar Main Style */
.site-header {
    width: 100%;
    min-height: 76px;
    background: #ffffff;
    padding: 0 7%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Logo */
.site-logo {
    color: #b84a72;
    font-size: 27px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.site-logo:hover {
    color: #57283c;
}

/* Navigation Menu */
.main-nav {
    display: flex;
    align-items: center;
    gap: 22px;
}

.main-nav a {
    color: #333333;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: 0.3s ease;
}

.main-nav a:hover {
    color: #b84a72;
}

/* Login / Logout Button */
.main-nav .login-btn {
    min-width: 90px;
    padding: 10px 18px;
    background: #b84a72;
    color: #ffffff;
    border-radius: 25px;
    text-align: center;
    display: inline-block;
    transition: 0.3s ease;
}

.main-nav .login-btn:hover {
    background: #57283c;
    color: #ffffff;
}

/* 900px Responsive */
@media (max-width: 900px) {
    .site-header {
        padding: 0 4%;
    }

    .main-nav {
        gap: 15px;
    }

    .main-nav a {
        font-size: 14px;
    }
}

/* 650px Responsive */
@media (max-width: 650px) {
    .site-header {
        padding: 15px 5%;
        flex-direction: column;
        gap: 15px;
    }

    .main-nav {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }

    .site-logo {
        font-size: 24px;
    }
}
</style>


<style>
/* =====================================
   DECOR EASE - COMMON NAVBAR
===================================== */

header.site-header {
    width: 100%;
    min-height: 76px;
    padding: 0 7%;
    box-sizing: border-box;
    background: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    box-shadow: 0 3px 15px rgba(57, 43, 43, 0.08);
    font-family: Arial, sans-serif;
    position: relative;
    z-index: 1000;
    line-height: normal;
}

header.site-header * {
    box-sizing: border-box;
}

/* LOGO */

header.site-header .logo {
    display: inline-block;
    flex-shrink: 0;
    color: #392b2b;
    font-size: 30px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: 0.5px;
}

header.site-header .logo:hover {
    color: #a97132;
}

/* MAIN NAVIGATION */

header.site-header .main-nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: nowrap;
    gap: 27px;
    margin: 0;
    padding: 0;
}

header.site-header .main-nav > a:not(.login-btn) {
    display: inline-block;
    position: relative;
    padding: 10px 0;
    color: #392b2b;
    font-size: 15px;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.3s ease;
}

/* HOVER */

header.site-header .main-nav > a:not(.login-btn):hover {
    color: #c58b45;
}

/* ACTIVE PAGE */

header.site-header .main-nav > a.active:not(.login-btn) {
    color: #c58b45;
    font-weight: 700;
}

header.site-header .main-nav > a.active:not(.login-btn)::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 2px;
    width: 100%;
    height: 2px;
    background: #c58b45;
    border-radius: 5px;
}

/* LOGIN BUTTON */

header.site-header .main-nav > a.login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 23px;
    background: #c58b45;
    color: #ffffff;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.3s ease;
}

header.site-header .main-nav > a.login-btn:hover {
    background: #a97132;
    color: #ffffff;
}

/* PROFILE */

header.site-header .nav-profile {
    position: relative;
    flex-shrink: 0;
}

header.site-header .profile-toggle {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 13px;
    border: 1px solid #eee5dc;
    border-radius: 7px;
    background: #f8f5f0;
    color: #392b2b;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    max-width: 210px;
}

header.site-header .profile-toggle:hover {
    border-color: #c58b45;
    background: #f5eee4;
}

header.site-header .profile-toggle > .fa-user-circle {
    color: #c58b45;
    font-size: 21px;
}

header.site-header .profile-toggle span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

header.site-header .profile-toggle .dropdown-arrow {
    color: #a97132;
    font-size: 11px;
    transition: transform 0.3s ease;
}

header.site-header .profile-toggle[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

/* DROPDOWN */

header.site-header .profile-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 230px;
    background: #ffffff;
    border: 1px solid #eee5dc;
    border-radius: 9px;
    box-shadow: 0 10px 30px rgba(57, 43, 43, 0.15);
    overflow: hidden;
    z-index: 1100;
}

header.site-header .profile-dropdown.show {
    display: block;
    animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* USER INFO */

header.site-header .dropdown-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 17px 16px;
    background: #f8f5f0;
    border-bottom: 1px solid #eee5dc;
}

header.site-header .dropdown-user-info > i {
    color: #c58b45;
    font-size: 32px;
}

header.site-header .dropdown-user-info div {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

header.site-header .dropdown-user-info strong {
    color: #392b2b;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

header.site-header .dropdown-user-info small {
    color: #a97132;
    font-size: 12px;
    text-transform: capitalize;
}

/* DROPDOWN LINKS */

header.site-header .profile-dropdown > a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 17px;
    margin: 0;
    width: 100%;
    box-sizing: border-box;
    color: #514343;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

header.site-header .profile-dropdown > a:hover {
    background: #f8f5f0;
    color: #a97132;
}

header.site-header .profile-dropdown > a > i {
    width: 17px;
    color: #c58b45;
    font-size: 14px;
    text-align: center;
}

/* CART COUNT */

header.site-header .cart-count {
    margin-left: auto;
    min-width: 21px;
    height: 21px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #c58b45;
    color: #ffffff;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
}

/* DIVIDER */

header.site-header .dropdown-divider {
    height: 1px;
    background: #eee5dc;
    margin: 5px 0;
}

/* LOGOUT */

header.site-header .profile-dropdown > a.logout-link {
    color: #bd4242;
}

header.site-header .profile-dropdown > a.logout-link i {
    color: #bd4242;
}

header.site-header .profile-dropdown > a.logout-link:hover {
    background: #fff1f0;
    color: #bd4242;
}

/* RESPONSIVE */

@media (max-width: 1050px) {
    header.site-header {
        padding: 0 4%;
    }

    header.site-header .main-nav {
        gap: 17px;
    }

    header.site-header .main-nav > a {
        font-size: 14px;
    }
}

@media (max-width: 800px) {
    header.site-header {
        min-height: 76px;
        height: auto;
        padding: 15px 4%;
        flex-direction: column;
        justify-content: center;
        gap: 15px;
    }

    header.site-header .main-nav {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px 20px;
    }
}

@media (max-width: 480px) {
    header.site-header .logo {
        font-size: 27px;
    }

    header.site-header .main-nav {
        gap: 13px 16px;
    }

    header.site-header .main-nav > a {
        font-size: 13px;
    }

    header.site-header .profile-toggle {
        padding: 8px 10px;
        font-size: 13px;
    }

    header.site-header .profile-dropdown {
        right: 0;
        width: 220px;
    }
}
/* Keep navbar menu typography and spacing consistent */
header.site-header .main-nav {
    gap: 34px !important;
}

header.site-header .main-nav a {
    font-weight: 600 !important;
    font-size: 16px !important;
}
</style>

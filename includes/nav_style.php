
<style>
    /* Shared Navbar Styles */

    .site-header {
        width: 100%;
        height: 76px;
        padding: 0 7%;
        box-sizing: border-box;
        background-color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        font-family: Arial, sans-serif;
    }

    .site-header .logo {
        display: inline-block;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
        color: #b84a72;
        font-size: 25px;
        font-weight: bold;
        line-height: 1.5;
        text-decoration: none;
        white-space: nowrap;
    }

    .site-header .main-nav {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-end;
        gap: 22px;
        margin: 0;
        padding: 0;
    }

    .site-header .main-nav a {
        display: inline-block;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
        color: #333333;
        font-size: 15px;
        font-weight: normal;
        line-height: 1.5;
        text-decoration: none;
        white-space: nowrap;
    }

    .site-header .main-nav a:hover {
        color: #b84a72;
    }

    .site-header .main-nav a.login-btn {
    padding: 9px 20px;
    background-color: #b84a72;
    color: #ffffff;
    border-radius: 5px;
    flex: 0 0 70px;
    width: 70px;
    min-width: 70px;
    max-width: 70px;
    padding: 9px 0;
    text-align: center;
    white-space: nowrap;
}
.site-header .main-nav .welcome-user {
    white-space: nowrap;
    font-size: 14px;
    color: #333333;
}

.site-header .main-nav a.login-btn:hover {
    background-color: #913653;
    color: #ffffff;
}

    @media (max-width: 900px) {
        .site-header {
            padding: 0 4%;
            gap: 15px;
        }

        .site-header .main-nav {
            gap: 14px;
        }
    }

    @media (max-width: 650px) {
        .site-header {
            height: auto;
            min-height: 76px;
            padding: 14px 5%;
            flex-direction: column;
            justify-content: center;
            gap: 12px;
        }

        .site-header .main-nav {
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px 18px;
        }
    }
</style>

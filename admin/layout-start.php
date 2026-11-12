
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$current = basename($_SERVER['PHP_SELF']);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money($v) {
    return '৳' . number_format((float)$v, 2);
}

$nav = [
    'dashboard.php' => '▦|Dashboard',
    'analytics.php' => '▥|Analytics',
    'bookings.php' => '▣|Bookings',
    'customers.php' => '♙|Customers',
    'event-categories.php' => '✿|Event Categories',
    'packages.php' => '▤|Decoration Packages',
    'messages.php' => '✉|Messages',
    'settings.php' => '⚙|Settings'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>DecorEase | Admin</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --page: #100c16;
            --sidebar: #17111f;
            --panel: #211827;
            --panel-light: #2b2032;
            --border: #392941;
            --pink: #d51de8;
            --purple: #a83be0;
            --text: #f7f0fa;
            --muted: #a99bad;
            --green: #42d9a0;
            --red: #ff6683;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--text);
            font: 14px Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        /* Main Layout */

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */

        .sidebar {
            width: 230px;
            flex-shrink: 0;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            padding: 25px 15px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-size: 27px;
            font-weight: 800;
            color: #e52be8;
            padding: 4px 13px 30px;
            letter-spacing: .4px;
        }

        .brand span {
            color: white;
        }

        .sidebar-label {
            padding: 8px 13px;
            color: #786b80;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.6px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 8px;
            color: #b7aabe;
            font-size: 13px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(110deg, #d21be6, #a719d0);
            color: white;
            box-shadow: 0 5px 18px #cd18e32b;
        }

        .menu-icon {
            width: 19px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 20px 13px 0;
            color: #817287;
            font-size: 11px;
            line-height: 1.8;
        }

        /* Main Content */

        .main-area {
            flex: 1;
            min-width: 0;
            padding: 0 25px 30px;
        }

        /* Topbar */

        .topbar {
            min-height: 82px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 25px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: bold;
        }

        .topbar-title small {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
            font-weight: normal;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .search-box input {
            width: 200px;
            padding: 11px 13px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: white;
            border-radius: 7px;
            outline: none;
        }

        /* Profile */

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-avatar {
            width: 37px;
            height: 37px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ec4bdc, #7535b9);
            display: grid;
            place-items: center;
            font-weight: bold;
        }

        .profile-name {
            font-size: 12px;
            font-weight: bold;
        }

        .profile-role {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 4px;
        }

        /* Page Heading */

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .page-heading h1 {
            font-size: 23px;
            margin: 0;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 12px;
            margin: 7px 0 0;
        }

        .date-label,
        .panel-tag {
            color: #d9c8e0;
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 9px 12px;
            border-radius: 8px;
            white-space: nowrap;
            font-size: 10px;
        }

        /* Statistics */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 20px;
        }

        .stat-card,
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 20px;
            min-width: 0;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-label {
            color: #c1b2c8;
            font-size: 12px;
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #d01fe626;
            color: #e84bf2;
            font-size: 18px;
        }

        .stat-value {
            margin-top: 14px;
            font-size: clamp(22px, 2.3vw, 30px);
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .stat-foot {
            margin-top: 9px;
            font-size: 11px;
            color: var(--muted);
        }

        /* Charts */

        .charts-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(250px, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 18px;
        }

        .panel-heading h3 {
            font-size: 14px;
            margin: 0;
        }

        .panel-heading p {
            color: var(--muted);
            font-size: 11px;
            margin: 6px 0 0;
        }

        .chart-box {
            height: 260px;
            position: relative;
            width: 100%;
        }

        /* Tables */

        .table-panel {
            margin-bottom: 20px;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        thead {
            background: #39253f;
        }

        th {
            padding: 13px 12px;
            text-align: left;
            color: #e6d9eb;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #34263a;
            color: #ded2e2;
            font-size: 11px;
        }

        tbody tr:hover {
            background: #2b2032;
        }

        .subtext {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        /* Status */

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            white-space: nowrap;
            background: #ffffff14;
            color: #ddd;
        }

        .status.pending,
        .status.quoted {
            background: #f5b14321;
            color: #f5bb56;
        }

        .status.confirmed,
        .status.completed,
        .status.active {
            background: #42d9a021;
            color: #42d9a0;
        }

        .status.cancelled,
        .status.failed,
        .status.suspended {
            background: #ff668321;
            color: #ff8298;
        }

        .empty-row {
            text-align: center;
            color: var(--muted);
            padding: 35px 10px;
        }

        /* Buttons */

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            padding: 8px 11px;
            color: white;
            background: #a83be0;
            cursor: pointer;
            font-size: 11px;
        }

        .btn.danger {
            background: #b83250;
        }

        .btn.secondary {
            background: #51445a;
        }

        .btnrow {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* Forms */

        .form-control {
            background: #17111f;
            border: 1px solid var(--border);
            color: white;
            border-radius: 6px;
            padding: 9px;
            width: 100%;
            margin: 5px 0 12px;
        }

        .notice {
            padding: 11px 14px;
            border-radius: 7px;
            background: #42d9a018;
            border: 1px solid #42d9a04a;
            margin-bottom: 15px;
            color: #8be7c3;
        }

        .notice.error {
            background: #ff668318;
            border-color: #ff66834a;
            color: #ff9cad;
        }

        .grid-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        /* Footer */

        .footer-note {
            text-align: center;
            padding: 18px 0 5px;
            color: #76687c;
            font-size: 10px;
        }

        /* Responsive */

        @media(max-width:1100px) {
            .sidebar {
                width: 205px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:720px) {
            .dashboard-layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 12px;
                border: 0;
                border-bottom: 1px solid var(--border);
            }

            .brand {
                padding: 8px 10px 15px;
            }

            .sidebar-label,
            .sidebar-bottom {
                display: none;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
            }

            .sidebar-menu a {
                padding: 10px;
                font-size: 12px;
            }

            .main-area {
                padding: 0 13px 20px;
            }

            .topbar {
                flex-wrap: wrap;
                padding: 17px 0;
            }

            .topbar-right {
                flex-wrap: wrap;
            }

            .search-box input {
                width: 150px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .stats-grid {
                gap: 10px;
            }

            .stat-card,
            .panel {
                padding: 15px;
            }
        }

        @media(max-width:390px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-box input {
                width: 125px;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-layout">

        <?php include_once __DIR__ . '/leftside.php'; ?>

        <main class="main-area">

            <header class="topbar">

                <div class="topbar-title">
                    Event Management Dashboard
                    <small>Welcome back! Here is your overview.</small>
                </div>

                <div class="topbar-right">

                    <div class="search-box">
                        <input
                            type="search"
                            id="dashboardSearch"
                            placeholder="Search..."
                        >
                    </div>

                    <?php include_once 'header.php'; ?>

                </div>

            </header>

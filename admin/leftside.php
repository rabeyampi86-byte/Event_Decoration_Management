
<?php
$current = $current ?? basename($_SERVER['PHP_SELF']);

$nav = [
    [
        'title' => 'Dashboard',
        'icon' => '⌂',
        'link' => 'dashboard.php'
    ],
    [
        'title' => 'Analytics',
        'icon' => '▥',
        'link' => 'analytics.php'
    ],
    [
        'title' => 'Bookings',
        'icon' => '▣',
        'link' => 'bookings.php'
    ],
    [
        'title' => 'Customers',
        'icon' => '♙',
        'link' => 'customers.php'
    ],
    [
        'title' => 'Event Categories',
        'icon' => '▦',
        'link' => 'event-categories.php'
    ],
    [
        'title' => 'Packages',
        'icon' => '◇',
        'link' => 'packages.php'
    ],
    [
        'title' => 'Progress Tracking',
        'icon' => '◷',
        'link' => 'progress.php'
    ],
    [
        'title' => 'Messages',
        'icon' => '✉',
        'link' => 'messages.php'
    ],
    [
        'title' => 'Payments',
        'icon' => '৳',
        'link' => 'payments.php'
    ],
    [
        'title' => 'Settings',
        'icon' => '⚙',
        'link' => 'settings.php'
    ]
];
?>

<style>
.sidebar {
    width: 230px;
    min-width: 230px;
    background: #251b3e;
    color: #fff;
    min-height: 100vh;
    padding: 22px 15px;
    box-sizing: border-box;
}

.sidebar-brand {
    padding: 8px 10px 28px;
    border-bottom: 1px solid rgba(255,255,255,.12);
    margin-bottom: 25px;
}

.sidebar-brand h2 {
    font-size: 22px;
    margin: 0;
    color: #fff;
    font-weight: 700;
}

.sidebar-brand p {
    color: #bdb2d4;
    font-size: 12px;
    margin: 6px 0 0;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 7px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 12px 13px;
    border-radius: 8px;
    color: #d4cce3;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: .2s;
}

.sidebar-menu a:hover {
    background: rgba(255,255,255,.09);
    color: #fff;
}

.sidebar-menu a.active {
    background: linear-gradient(135deg, #a45de0, #e65c91);
    color: #fff;
    box-shadow: 0 4px 12px rgba(164,93,224,.25);
}

.sidebar-icon {
    width: 22px;
    text-align: center;
    font-size: 18px;
}

@media(max-width: 768px) {
    .sidebar {
        width: 190px;
        min-width: 190px;
    }
}
</style>

<aside class="sidebar">

    <div class="sidebar-brand">
        <h2>DecorEase</h2>
        <p>Admin Panel</p>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($nav as $item): ?>

            <li>
                <a href="<?= h($item['link']) ?>"
                   class="<?= $current === $item['link'] ? 'active' : '' ?>">

                    <span class="sidebar-icon">
                        <?= h($item['icon']) ?>
                    </span>

                    <span>
                        <?= h($item['title']) ?>
                    </span>

                </a>
            </li>

        <?php endforeach; ?>

    </ul>

</aside>

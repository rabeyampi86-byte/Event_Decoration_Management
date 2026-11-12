
<?php
require_once __DIR__ . '/layout-start.php';

/* =====================================
   1. Dashboard Statistics
===================================== */

$stats = [
    'customers' => 0,
    'bookings' => 0,
    'confirmed' => 0,
    'packages' => 0,
    'confirmed_value' => 0
];

// Total Customers
$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($q) {
    $stats['customers'] = (int)$q->fetch_assoc()['total'];
}

// Total Bookings and Confirmed Bookings
$q = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(
            SUM(status IN ('confirmed', 'in_progress', 'completed')),
            0
        ) AS confirmed
    FROM bookings
");

if ($q) {
    $r = $q->fetch_assoc();

    $stats['bookings'] = (int)$r['total'];
    $stats['confirmed'] = (int)$r['confirmed'];
}

// Total Decoration Packages
$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM decoration_packages
");

if ($q) {
    $stats['packages'] = (int)$q->fetch_assoc()['total'];
}

// Total Completed Payment Amount
$q = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE status = 'completed'
");

if ($q) {
    $stats['confirmed_value'] = (float)$q->fetch_assoc()['total'];
}


/* =====================================
   2. Monthly Booking Statistics
===================================== */

$monthly = array_fill(0, 12, 0);

$q = $conn->query("
    SELECT
        MONTH(event_date) AS month_number,
        COUNT(*) AS total
    FROM bookings
    WHERE YEAR(event_date) = YEAR(CURDATE())
    GROUP BY MONTH(event_date)
");

if ($q) {
    while ($r = $q->fetch_assoc()) {
        $monthIndex = (int)$r['month_number'] - 1;

        $monthly[$monthIndex] = (int)$r['total'];
    }
}


/* =====================================
   3. Booking Status Statistics
===================================== */

$statuses = [
    'pending' => 0,
    'quoted' => 0,
    'confirmed' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$q = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM bookings
    GROUP BY status
");

if ($q) {
    while ($r = $q->fetch_assoc()) {
        if (isset($statuses[$r['status']])) {
            $statuses[$r['status']] = (int)$r['total'];
        }
    }
}


/* =====================================
   4. Popular Decoration Packages
===================================== */

$pl = [];
$pc = [];

$q = $conn->query("
    SELECT
        dp.package_name,
        COUNT(b.booking_id) AS total
    FROM decoration_packages dp
    LEFT JOIN bookings b
        ON dp.id = b.package_id
    GROUP BY dp.id, dp.package_name
    ORDER BY total DESC
    LIMIT 7
");

if ($q) {
    while ($r = $q->fetch_assoc()) {
        $pl[] = $r['package_name'];
        $pc[] = (int)$r['total'];
    }
}


/* =====================================
   5. Recent Bookings
===================================== */

$recent = $conn->query("
    SELECT
        b.booking_id,
        b.event_date,
        b.status,
        b.quoted_total,
        u.full_name,
        dp.package_name,
        v.name AS venue,

        (
            SELECT py.method
            FROM payments py
            WHERE py.booking_id = b.booking_id
            ORDER BY py.payment_id DESC
            LIMIT 1
        ) AS payment_method

    FROM bookings b

    JOIN customers c
        ON b.customer_id = c.customer_id

    JOIN users u
        ON c.user_id = u.user_id

    LEFT JOIN decoration_packages dp
        ON b.package_id = dp.id

    LEFT JOIN venues v
        ON b.venue_id = v.venue_id

    ORDER BY b.booking_id DESC
    LIMIT 8
");

?>

<!-- =====================================
     6. Dashboard Heading
===================================== -->

<section class="page-heading">

    <div>
        <h1>Dashboard Overview</h1>
        <p>Monitor your bookings, customers and packages.</p>
    </div>

    <div class="date-label">
        ◷ <?= date('d M Y') ?>
    </div>

</section>


<!-- =====================================
     7. Statistics Cards
===================================== -->

<section class="stats-grid">

    <?php
    $cards = [
        [
            'Total Customers',
            $stats['customers'],
            '♙',
            'Registered customer accounts'
        ],
        [
            'Total Bookings',
            $stats['bookings'],
            '▣',
            'All booking requests'
        ],
        [
            'Paid Revenue',
            money($stats['confirmed_value']),
            '৳',
            $stats['confirmed'] . ' confirmed / active bookings'
        ],
        [
            'Total Packages',
            $stats['packages'],
            '✿',
            'Decoration packages'
        ]
    ];

    foreach ($cards as $s):
    ?>

        <div class="stat-card">

            <div class="stat-top">
                <span class="stat-label">
                    <?= h($s[0]) ?>
                </span>

                <span class="stat-icon">
                    <?= h($s[2]) ?>
                </span>
            </div>

            <div class="stat-value">
                <?= h($s[1]) ?>
            </div>

            <div class="stat-foot">
                <?= h($s[3]) ?>
            </div>

        </div>

    <?php endforeach; ?>

</section>


<!-- =====================================
     8. Monthly and Status Charts
===================================== -->

<section class="charts-grid">

    <div class="panel">

        <div class="panel-heading">

            <div>
                <h3>Monthly Booking Activity</h3>
                <p>Bookings by event date this year</p>
            </div>

            <span class="panel-tag">
                <?= date('Y') ?>
            </span>

        </div>

        <div class="chart-box">
            <canvas id="monthlyChart"></canvas>
        </div>

    </div>


    <div class="panel">

        <div class="panel-heading">

            <div>
                <h3>Booking Status</h3>
                <p>Current booking distribution</p>
            </div>

        </div>

        <div class="chart-box">
            <canvas id="statusChart"></canvas>
        </div>

    </div>

</section>


<!-- =====================================
     9. Package Chart and Quick Information
===================================== -->

<section class="charts-grid">

    <div class="panel">

        <div class="panel-heading">

            <div>
                <h3>Popular Decoration Packages</h3>
                <p>Bookings for each package</p>
            </div>

            <span class="panel-tag">
                Top 7
            </span>

        </div>

        <div class="chart-box">
            <canvas id="packageChart"></canvas>
        </div>

    </div>


    <div class="panel">

        <div class="panel-heading">

            <div>
                <h3>Quick Information</h3>
                <p>Overview of your management system</p>
            </div>

        </div>

        <div class="quick-info">

            <p>
                <strong>Total Customers:</strong>
                <?= number_format($stats['customers']) ?>
            </p>

            <p>
                <strong>Total Bookings:</strong>
                <?= number_format($stats['bookings']) ?>
            </p>

            <p>
                <strong>Pending Bookings:</strong>
                <?= number_format($statuses['pending']) ?>
            </p>

            <p>
                <strong>Completed Bookings:</strong>
                <?= number_format($statuses['completed']) ?>
            </p>

            <p>
                <strong>Total Revenue:</strong>
                <?= money($stats['confirmed_value']) ?>
            </p>

        </div>

        <a class="btn" href="bookings.php">
            Manage Bookings
        </a>

    </div>

</section>


<!-- =====================================
     10. Recent Bookings Table
===================================== -->

<section class="panel table-panel">

    <div class="panel-heading">

        <div>
            <h3>Recent Bookings</h3>
            <p>Latest booking requests from customers</p>
        </div>

        <a class="btn" href="bookings.php">
            View All
        </a>

    </div>


    <div class="table-scroll">

        <table id="bookingsTable">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Package</th>
                    <th>Customer</th>
                    <th>Venue</th>
                    <th>Quoted Total</th>
                    <th>Event Date</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>


            <tbody>

                <?php if ($recent && $recent->num_rows > 0): ?>

                    <?php while ($b = $recent->fetch_assoc()): ?>

                        <tr>

                            <td>
                                #<?= (int)$b['booking_id'] ?>
                            </td>

                            <td>
                                <?= h($b['package_name'] ?? '—') ?>
                            </td>

                            <td>
                                <?= h($b['full_name'] ?? '—') ?>
                            </td>

                            <td>
                                <?= h($b['venue'] ?? '—') ?>
                            </td>

                            <td>
                                <?= money((float)$b['quoted_total']) ?>
                            </td>

                            <td>
                                <?= h($b['event_date']) ?>
                            </td>

                            <td>
                                <?= h($b['payment_method'] ?? 'Not paid') ?>
                            </td>

                            <td>

                                <span class="status <?= h($b['status']) ?>">
                                    <?= h(ucwords(str_replace('_', ' ', $b['status']))) ?>
                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty-row">
                            No bookings found yet.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- =====================================
     11. Dashboard Charts
===================================== -->

<script>

// Default Chart Settings
Chart.defaults.color = '#b9a9c2';
Chart.defaults.font.family = 'Arial, sans-serif';


// Monthly Booking Line Chart
new Chart(document.getElementById('monthlyChart'), {

    type: 'line',

    data: {

        labels: [
            'Jan', 'Feb', 'Mar', 'Apr',
            'May', 'Jun', 'Jul', 'Aug',
            'Sep', 'Oct', 'Nov', 'Dec'
        ],

        datasets: [{

            label: 'Bookings',

            data: <?= json_encode($monthly) ?>,

            borderColor: '#dc39eb',

            backgroundColor: 'rgba(220, 57, 235, 0.13)',

            fill: true,

            tension: 0.38,

            borderWidth: 3

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {
            legend: {
                display: false
            }
        },

        scales: {

            y: {
                beginAtZero: true,

                ticks: {
                    precision: 0
                }
            }

        }

    }

});


// Booking Status Doughnut Chart
new Chart(document.getElementById('statusChart'), {

    type: 'doughnut',

    data: {

        labels: <?= json_encode(array_keys($statuses)) ?>,

        datasets: [{

            data: <?= json_encode(array_values($statuses)) ?>,

            backgroundColor: [
                '#d51de8',
                '#f5bb56',
                '#42d9a0',
                '#8a55c9',
                '#4c9de0',
                '#ff6683'
            ],

            borderColor: '#211827',

            borderWidth: 4

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                position: 'bottom'
            }

        }

    }

});


// Popular Packages Bar Chart
new Chart(document.getElementById('packageChart'), {

    type: 'bar',

    data: {

        labels: <?= json_encode(
            $pl,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) ?>,

        datasets: [{

            label: 'Bookings',

            data: <?= json_encode($pc) ?>,

            backgroundColor: '#c34cdd',

            borderRadius: 5

        }]

    },

    options: {

        indexAxis: 'y',

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            x: {

                beginAtZero: true,

                ticks: {
                    precision: 0
                }

            }

        }

    }

});


// Dashboard Search
const dashboardSearch = document.getElementById('dashboardSearch');

if (dashboardSearch) {

    dashboardSearch.addEventListener('input', function () {

        const searchValue = this.value.toLowerCase();

        document.querySelectorAll('#bookingsTable tbody tr').forEach(function (row) {

            row.style.display =
                row.innerText.toLowerCase().includes(searchValue)
                    ? ''
                    : 'none';

        });

    });

}

</script>


<?php require_once __DIR__ . '/layout-end.php'; ?>

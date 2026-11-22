
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =====================================
   GET PACKAGE ID
===================================== */

$packageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$packageId || $packageId < 1) {
    header("Location: packages.php");
    exit;
}

/* =====================================
   PACKAGE INFORMATION
===================================== */

$stmt = $conn->prepare("
    SELECT
        p.package_id,
        p.package_name,
        p.short_description,
        p.description,
        p.base_price,
        p.guest_capacity,
        m.file_path AS package_image,
        m.alt_text AS image_alt,
        ec.name AS category_name
    FROM packages p
    INNER JOIN event_categories ec
        ON p.category_id = ec.category_id
    LEFT JOIN media m
        ON p.image_id = m.media_id
    WHERE p.package_id = ?
      AND p.status = 'active'
      AND ec.status = 'active'
    LIMIT 1
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$package = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$package) {
    http_response_code(404);
    exit("Package not found.");
}

$page_title = $package['package_name'] . " | DecorEase";

/* =====================================
   PACKAGE DETAILS
===================================== */

$packageDetails = [];

$stmt = $conn->prepare("
    SELECT
        detail_title,
        detail_description
    FROM package_details
    WHERE package_id = ?
    ORDER BY sort_order ASC, detail_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $packageDetails[] = $row;
}

$stmt->close();

/* =====================================
   TEAM ROLES
   All roles remain visible.
   Unassigned quantities show 0.
===================================== */

$teamRoles = [];

$stmt = $conn->prepare("
    SELECT
        tr.role_id,
        tr.role_name,
        tr.description AS role_description,
        COALESCE(ptr.quantity, 0) AS quantity,
        ptr.description AS package_description
    FROM team_roles tr
    LEFT JOIN package_team_roles ptr
        ON tr.role_id = ptr.role_id
       AND ptr.package_id = ?
    ORDER BY tr.role_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $teamRoles[] = $row;
}

$stmt->close();

$photoTeam = [];
$decorationTeam = [];
$cateringTeam = [];

$totalPhoto = 0;
$totalDecoration = 0;
$totalCateringStaff = 0;

$headChefCount = 0;
$assistantChefCount = 0;
$servingTeamCount = 0;

foreach ($teamRoles as $role) {

    $roleName = strtolower($role['role_name']);
    $quantity = (int)$role['quantity'];

    if (
        strpos($roleName, 'photograph') !== false ||
        strpos($roleName, 'videograph') !== false ||
        strpos($roleName, 'video editor') !== false
    ) {
        $photoTeam[] = $role;
        $totalPhoto += $quantity;
    }

    elseif (
        strpos($roleName, 'decorat') !== false ||
        strpos($roleName, 'flower') !== false ||
        strpos($roleName, 'setup') !== false
    ) {
        $decorationTeam[] = $role;
        $totalDecoration += $quantity;
    }

    elseif (
        strpos($roleName, 'chef') !== false ||
        strpos($roleName, 'serving') !== false
    ) {
        $cateringTeam[] = $role;
        $totalCateringStaff += $quantity;

        if (strpos($roleName, 'head chef') !== false) {
            $headChefCount = $quantity;
        }

        elseif (strpos($roleName, 'assistant chef') !== false) {
            $assistantChefCount = $quantity;
        }

        elseif (strpos($roleName, 'serving') !== false) {
            $servingTeamCount = $quantity;
        }
    }
}

/* =====================================
   FLOWERS
===================================== */

$flowers = [];

$stmt = $conn->prepare("
    SELECT
        f.flower_name,
        f.description AS flower_description,
        fm.file_path AS flower_image,
        fd.design_name,
        fd.description AS design_description,
        pfo.quantity_description
    FROM package_flower_options pfo
    INNER JOIN flowers f
        ON pfo.flower_id = f.flower_id
    LEFT JOIN flower_designs fd
        ON pfo.design_id = fd.design_id
    LEFT JOIN media fm
        ON f.image_id = fm.media_id
    WHERE pfo.package_id = ?
      AND f.is_active = 1
    ORDER BY pfo.option_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $flowers[] = $row;
}

$stmt->close();

/* =====================================
   DECORATION AREAS
===================================== */

$areas = [];

$stmt = $conn->prepare("
    SELECT
        da.area_name,
        da.description AS area_description,
        pda.design_description
    FROM package_decoration_areas pda
    INNER JOIN decoration_areas da
        ON pda.area_id = da.area_id
    WHERE pda.package_id = ?
      AND da.is_active = 1
    ORDER BY pda.package_area_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $areas[] = $row;
}

$stmt->close();

/* =====================================
   LIGHTING & SOUND
===================================== */

$lighting = [];
$totalLights = 0;

$stmt = $conn->prepare("
    SELECT
        le.equipment_name,
        le.description,
        pl.quantity,
        pl.purpose
    FROM package_lighting pl
    INNER JOIN lighting_equipment le
        ON pl.lighting_id = le.lighting_id
    WHERE pl.package_id = ?
      AND le.is_active = 1
    ORDER BY pl.package_lighting_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $lighting[] = $row;
    $totalLights += (int)$row['quantity'];
}

$stmt->close();

/* =====================================
   FOOD MENU
===================================== */

$foods = [];

$stmt = $conn->prepare("
    SELECT
        f.food_name,
        f.description,
        pfm.quantity_description
    FROM package_food_menus pfm
    INNER JOIN food_menu_items f
        ON pfm.food_id = f.food_id
    WHERE pfm.package_id = ?
      AND f.is_active = 1
    ORDER BY pfm.package_food_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $foods[] = $row;
}

$stmt->close();

/* =====================================
   PROFESSIONAL PARTNERS
===================================== */

$vendors = [];

$stmt = $conn->prepare("
    SELECT
        v.vendor_name,
        v.service_type,
        v.description,
        pv.service_description,
        pv.partnership_status
    FROM package_vendors pv
    INNER JOIN vendors v
        ON pv.vendor_id = v.vendor_id
    WHERE pv.package_id = ?
    ORDER BY pv.package_vendor_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $vendors[] = $row;
}

$stmt->close();

/* =====================================
   ADDITIONAL FACILITIES
===================================== */

$facilities = [];

$stmt = $conn->prepare("
    SELECT
        f.facility_name,
        f.description
    FROM package_facilities pf
    INNER JOIN facilities f
        ON pf.facility_id = f.facility_id
    WHERE pf.package_id = ?
      AND f.is_active = 1
    ORDER BY pf.package_facility_id ASC
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

$stmt->close();

/* =====================================
   FINAL VALUES
===================================== */

$packageImage = !empty($package['package_image'])
    ? $package['package_image']
    : 'images/wed1.webp';

$guestCapacity = (int)($package['guest_capacity'] ?? 0);

$packageDescription = $package['description'] ?? '';

if (trim($packageDescription) === '' && !empty($packageDetails)) {
    $packageDescription = $packageDetails[0]['detail_description'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo e($page_title); ?></title>

<?php include 'includes/nav_style.php'; ?>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #fffaf7;
    color: #38232d;
    line-height: 1.7;
}

:root {
    --primary: #57283c;
    --secondary: #a34d70;
    --accent: #c77a98;
    --light: #fff4f7;
    --border: #f0dce4;
    --text: #756870;
}

/* MAIN CONTAINER */
.wedding-container {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 25px;
}

/* PACKAGE OVERVIEW */
.package-overview {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 35px;
    align-items: center;
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: 0 7px 22px rgba(65, 28, 46, .08);
}

.package-image {
    height: 380px;
    overflow: hidden;
    border-radius: 11px;
}

.package-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.package-content h2 {
    color: var(--primary);
    font-size: 28px;
    margin: 0 0 12px;
}

.package-content p {
    color: var(--text);
    font-size: 15px;
    margin-bottom: 15px;
}

.package-label {
    display: inline-block;
    background: var(--light);
    color: var(--secondary);
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 12px;
}

.package-price {
    margin-top: 20px;
    padding: 16px;
    background: var(--light);
    border-left: 4px solid var(--secondary);
    border-radius: 7px;
}

.package-price strong {
    display: block;
    color: var(--primary);
    font-size: 22px;
}

.package-price small {
    color: var(--text);
}

/* QUICK INFORMATION */
.quick-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-top: 30px;
}

.info-card {
    background: white;
    padding: 22px 15px;
    border: 1px solid var(--border);
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(65, 28, 46, .05);
}

.info-card .info-icon {
    font-size: 27px;
    margin-bottom: 7px;
}

.info-card h3 {
    font-size: 23px;
    color: var(--primary);
    margin: 0;
}

.info-card p {
    font-size: 13px;
    color: var(--text);
    margin: 3px 0 0;
}

/* COMMON SECTION */
.detail-section {
    margin-top: 32px;
    padding: 30px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 13px;
    box-shadow: 0 5px 18px rgba(65, 28, 46, .05);
}

.section-heading {
    margin-bottom: 23px;
}

.section-heading h2 {
    color: var(--primary);
    font-size: 25px;
    margin: 0 0 5px;
}

.section-heading p {
    color: var(--text);
    font-size: 14px;
    margin: 0;
}

/* TEAM GRID */
.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.team-card {
    background: #fff9fb;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 20px;
}

.team-card .icon {
    font-size: 28px;
}

.team-card h3 {
    font-size: 17px;
    color: var(--primary);
    margin: 8px 0 5px;
}

.team-card .number {
    color: var(--secondary);
    font-size: 23px;
    font-weight: bold;
}

.team-card p {
    color: var(--text);
    font-size: 13px;
    margin: 5px 0 0;
}

/* DETAIL TABLE */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.detail-table th,
.detail-table td {
    padding: 13px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

.detail-table th {
    color: var(--primary);
    background: #fff3f7;
}

.detail-table td {
    color: var(--text);
}

.detail-table tr:last-child td {
    border-bottom: none;
}

/* FLOWER GRID */
.flower-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.flower-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fff9fb;
}

.flower-card img {
    width: 100%;
    height: 175px;
    object-fit: cover;
    display: block;
}

.flower-card div {
    padding: 15px;
}

.flower-card h3 {
    color: var(--primary);
    font-size: 16px;
    margin: 0 0 5px;
}

.flower-card p {
    color: var(--text);
    font-size: 13px;
    margin: 0;
}

/* FOOD */
.food-layout {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 25px;
}

.food-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.food-item {
    background: #fff8fa;
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 11px 13px;
    color: #65535c;
    font-size: 14px;
}

.food-item span {
    color: var(--secondary);
    font-weight: bold;
    margin-right: 7px;
}

.catering-box {
    padding: 22px;
    border-radius: 10px;
    background: #fff3f7;
}

.catering-box h3 {
    color: var(--primary);
    margin: 0 0 10px;
    font-size: 18px;
}

.catering-box p {
    font-size: 14px;
    color: var(--text);
    margin: 8px 0;
}

/* PARTNERS */
.partner-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.partner-card {
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fff9fb;
}

.partner-card h3 {
    color: var(--primary);
    font-size: 17px;
    margin: 0 0 8px;
}

.partner-card p {
    color: var(--text);
    font-size: 13px;
    margin: 0 0 10px;
}

.partner-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background: #fff0d9;
    color: #95631d;
    font-size: 11px;
    font-weight: bold;
}

/* FACILITIES */
.facility-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 25px;
}

.facility {
    display: flex;
    gap: 10px;
    font-size: 14px;
    color: #65535c;
}

.facility span {
    color: var(--secondary);
    font-weight: bold;
}

/* NOTICE */
.demo-note {
    margin-top: 25px;
    padding: 15px 18px;
    border-radius: 8px;
    background: #fff5e7;
    border: 1px solid #f0dfc4;
    color: #785d35;
    font-size: 13px;
}

/* BOOKING */
.booking-section {
    margin-top: 32px;
    padding: 40px 25px;
    text-align: center;
    border-radius: 13px;
    color: white;
    background: linear-gradient(110deg, #57283c, #a34d70);
}

.booking-section h2 {
    color: white;
    font-size: 27px;
    margin: 0 0 8px;
}

.booking-section p {
    color: #f8eaf0;
    font-size: 14px;
    margin: 0 0 22px;
}

.booking-btn {
    display: inline-block;
    padding: 12px 32px;
    background: white;
    color: #57283c;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    transition: .3s;
}

.booking-btn:hover {
    background: #ffe5ee;
    color: #57283c;
    transform: translateY(-2px);
}

.back-link {
    display: inline-block;
    margin-top: 22px;
    color: var(--secondary);
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}

.back-link:hover {
    color: var(--primary);
}

/* RESPONSIVE */
@media(max-width: 900px) {
    .package-overview,
    .food-layout {
        grid-template-columns: 1fr;
    }

    .quick-info {
        grid-template-columns: repeat(2, 1fr);
    }

    .team-grid,
    .flower-grid,
    .partner-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 600px) {
    .wedding-container {
        padding: 0 15px;
        margin: 28px auto;
    }

    .wedding-hero {
        padding: 45px 5%;
    }

    .package-image {
        height: 230px;
    }

    .quick-info,
    .team-grid,
    .flower-grid,
    .partner-grid,
    .facility-grid,
    .food-list {
        grid-template-columns: 1fr;
    }

    .detail-section {
        padding: 20px 16px;
    }

    .section-heading h2 {
        font-size: 22px;
    }

    .detail-table th,
    .detail-table td {
        padding: 10px 8px;
        font-size: 12px;
    }
}
</style>

</head>

<body>

<?php include 'includes/navber.php'; ?>

<main>
<div class="wedding-container">

    <!-- PACKAGE OVERVIEW -->
    <section class="package-overview">

        <div class="package-image">
            <img src="<?php echo e($packageImage); ?>"
                 alt="<?php echo e($package['image_alt'] ?: $package['package_name']); ?>">
        </div>

        <div class="package-content">

            <span class="package-label">
                <?php echo e(strtoupper($package['category_name'])); ?> PACKAGE
            </span>

            <h2><?php echo e($package['package_name']); ?></h2>

            <p><?php echo nl2br(e($package['short_description'])); ?></p>

            <p><?php echo nl2br(e($packageDescription)); ?></p>

            <div class="package-price">
                <strong>
                    Price: ৳<?php echo number_format((float)$package['base_price'], 2); ?>
                </strong>

                <small>
                    Final price may depend on guest count,
                    selected services and event requirements.
                </small>
            </div>

        </div>

    </section>


    <!-- QUICK INFORMATION -->
    <section class="quick-info">

        <div class="info-card">
            <div class="info-icon">📸</div>
            <h3><?php echo $totalPhoto; ?></h3>
            <p>Photography Team Members</p>
        </div>

        <div class="info-card">
            <div class="info-icon">👥</div>
            <h3><?php echo $totalDecoration; ?></h3>
            <p>Decoration Team Members</p>
        </div>

        <div class="info-card">
            <div class="info-icon">💡</div>
            <h3><?php echo $totalLights; ?></h3>
            <p>LED &amp; Decorative Lights</p>
        </div>

        <div class="info-card">
            <div class="info-icon">🍽️</div>
            <h3><?php echo $guestCapacity; ?></h3>
            <p>Guest Meal Arrangement</p>
        </div>

    </section>


    <!-- PHOTOGRAPHY TEAM -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>01. Photography &amp; Videography Team</h2>
            <p>
                <?php
                foreach ($photoTeam as $role) {
                    if (!empty($role['package_description'])) {
                        echo nl2br(e($role['package_description']));
                        break;
                    }
                }
                ?>
            </p>
        </div>

        <div class="team-grid">

            <?php foreach ($photoTeam as $role): ?>

                <div class="team-card">

                    <div class="icon">📷</div>

                    <h3><?php echo e($role['role_name']); ?></h3>

                    <div class="number">
                        <?php echo (int)$role['quantity']; ?>
                        <?php echo ((int)$role['quantity'] === 1) ? 'Person' : 'Persons'; ?>
                    </div>

                    <p><?php echo nl2br(e($role['package_description'])); ?></p>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- DECORATION TEAM -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>02. Decoration Team</h2>
            <p></p>
        </div>

        <div class="team-grid">

            <?php foreach ($decorationTeam as $role): ?>

                <div class="team-card">

                    <div class="icon">🌸</div>

                    <h3><?php echo e($role['role_name']); ?></h3>

                    <div class="number">
                        <?php echo (int)$role['quantity']; ?>
                        <?php echo ((int)$role['quantity'] === 1) ? 'Person' : 'Persons'; ?>
                    </div>

                    <p><?php echo nl2br(e($role['package_description'])); ?></p>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- FLOWERS & DESIGN -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>03. Flowers &amp; Decoration Design</h2>
            <p></p>
        </div>

        <div class="flower-grid">

            <?php foreach ($flowers as $flower): ?>

                <div class="flower-card">

                    <?php if (!empty($flower['flower_image'])): ?>

                        <img src="<?php echo e($flower['flower_image']); ?>"
                             alt="<?php echo e($flower['flower_name']); ?>">

                    <?php else: ?>

                        <div style="height:175px;background:#fff4f7;"></div>

                    <?php endif; ?>

                    <div>

                        <h3><?php echo e($flower['flower_name']); ?></h3>

                        <p><?php echo e($flower['flower_description']); ?></p>

                        <p><?php echo e($flower['design_name']); ?></p>

                        <p><?php echo e($flower['design_description']); ?></p>

                        <p><?php echo e($flower['quantity_description']); ?></p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <table class="detail-table">

            <thead>
                <tr>
                    <th>Decoration Area</th>
                    <th>Design Plan</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($areas as $area): ?>

                    <tr>
                        <td><?php echo e($area['area_name']); ?></td>
                        <td><?php echo nl2br(e($area['design_description'])); ?></td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </section>


    <!-- LIGHTING & SOUND -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>04. Lighting &amp; Sound Arrangement</h2>
            <p></p>
        </div>

        <table class="detail-table">

            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Purpose</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($lighting as $item): ?>

                    <tr>
                        <td><?php echo e($item['equipment_name']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo e($item['purpose']); ?></td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </section>


    <!-- FOOD & CATERING -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>05. Guest Food &amp; Catering Menu</h2>
            <p></p>
        </div>

        <div class="food-layout">

            <div class="food-list">

                <?php foreach ($foods as $food): ?>

                    <div class="food-item">

                        <span>✓</span>

                        <?php echo e($food['food_name']); ?>

                        <?php if (!empty($food['quantity_description'])): ?>
                            (<?php echo e($food['quantity_description']); ?>)
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="catering-box">

                <h3>Catering Information</h3>

                <p>
                    <strong>Guest Capacity:</strong>
                    <?php echo $guestCapacity; ?> Guests
                </p>

                <p>
                    <strong>Food Preparation:</strong>
                </p>

                <p>
                    <strong>Head Chef:</strong>
                    <?php echo $headChefCount; ?> Persons
                </p>

                <p>
                    <strong>Assistant Chefs:</strong>
                    <?php echo $assistantChefCount; ?> Persons
                </p>

                <p>
                    <strong>Serving Team:</strong>
                    <?php echo $servingTeamCount; ?> Persons
                </p>

            </div>

        </div>

    </section>


    <!-- PROFESSIONAL PARTNERS -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>06. Professional Partners</h2>
            <p></p>
        </div>

        <div class="partner-grid">

            <?php foreach ($vendors as $vendor): ?>

                <div class="partner-card">

                    <h3>
                        <?php echo e($vendor['service_type'] ?: $vendor['vendor_name']); ?>
                    </h3>

                    <p><?php echo e($vendor['vendor_name']); ?></p>

                    <p><?php echo nl2br(e($vendor['description'])); ?></p>

                    <p><?php echo nl2br(e($vendor['service_description'])); ?></p>

                    <span class="partner-status">

                        <?php
                        echo $vendor['partnership_status'] === 'confirmed'
                            ? 'Confirmed Partner'
                            : 'Partner to be confirmed';
                        ?>

                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- ADDITIONAL FACILITIES -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>07. Additional Facilities</h2>
            <p></p>
        </div>

        <div class="facility-grid">

            <?php foreach ($facilities as $facility): ?>

                <div class="facility">

                    <span>✓</span>

                    <div>
                        <?php echo e($facility['facility_name']); ?>

                        <p><?php echo e($facility['description']); ?></p>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- DEMO INFORMATION -->
    <div class="demo-note">

        <strong>Important:</strong>

        Package information, quantities, team sizes, food menu,
        partners and facilities are displayed according to the
        information saved for this package.

    </div>


    <!-- BOOKING -->
    <section class="booking-section">

        <h2>Ready to Plan Your Special Event?</h2>

        <p>
            Choose this package and let us help you plan
            a memorable celebration.
        </p>

        <a href="booking.php?package_id=<?php echo (int)$package['package_id']; ?>"
           class="booking-btn">

            Book This Package

        </a>

    </section>


    <a href="packages.php" class="back-link">
        ← Back to All Packages
    </a>

</div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>

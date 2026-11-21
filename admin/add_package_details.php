
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../db.php';

/* =====================================
   HELPER FUNCTIONS
===================================== */

function pd_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pd_get_options($conn, $sql) {
    $result = $conn->query($sql);
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function pd_insert($conn, $sql, $types, $values) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $params = [];
    $params[] = $types;

    foreach ($values as $key => &$value) {
        $params[] = &$value;
    }

    call_user_func_array([$stmt, 'bind_param'], $params);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception($error);
    }

    $stmt->close();
}

/* =====================================
   DROPDOWN DATA
===================================== */

$packages = pd_get_options(
    $conn,
    "SELECT package_id, package_name
     FROM packages
     ORDER BY package_name"
);

$roles = pd_get_options(
    $conn,
    "SELECT role_id, role_name
     FROM team_roles
     ORDER BY role_name"
);

$flowers = pd_get_options(
    $conn,
    "SELECT flower_id, flower_name
     FROM flowers
     WHERE is_active = 1
     ORDER BY flower_name"
);

$designs = pd_get_options(
    $conn,
    "SELECT design_id, design_name
     FROM flower_designs
     WHERE is_active = 1
     ORDER BY design_name"
);

$areas = pd_get_options(
    $conn,
    "SELECT area_id, area_name
     FROM decoration_areas
     WHERE is_active = 1
     ORDER BY area_name"
);

$lightingItems = pd_get_options(
    $conn,
    "SELECT lighting_id, equipment_name
     FROM lighting_equipment
     WHERE is_active = 1
     ORDER BY equipment_name"
);

$foods = pd_get_options(
    $conn,
    "SELECT food_id, food_name
     FROM food_menu_items
     WHERE is_active = 1
     ORDER BY food_name"
);

$vendors = pd_get_options(
    $conn,
    "SELECT vendor_id, vendor_name
     FROM vendors
     WHERE status = 'active'
     ORDER BY vendor_name"
);

$facilities = pd_get_options(
    $conn,
    "SELECT facility_id, facility_name
     FROM facilities
     WHERE is_active = 1
     ORDER BY facility_name"
);

/* =====================================
   PACKAGE SELECTION
===================================== */

$packageId = isset($_GET['package_id'])
    ? (int)$_GET['package_id']
    : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageId = (int)($_POST['package_id'] ?? 0);
}

$selectedPackage = null;

if ($packageId > 0) {
    $stmt = $conn->prepare(
        "SELECT package_id, package_name
         FROM packages
         WHERE package_id = ?"
    );

    $stmt->bind_param("i", $packageId);
    $stmt->execute();

    $selectedPackage = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$error = '';
$success = '';

/* =====================================
   SAVE PACKAGE DETAILS
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$selectedPackage) {
        $error = "Please select a valid package.";
    } else {

        try {

            $conn->begin_transaction();

            $inserted = 0;

            /* 1. PACKAGE OVERVIEW */

            $title = trim($_POST['detail_title'] ?? '');
            $description = trim($_POST['detail_description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($title !== '') {

                pd_insert(
                    $conn,
                    "INSERT INTO package_details
                    (package_id, detail_title, detail_description, sort_order)
                    VALUES (?, ?, ?, ?)",
                    "issi",
                    [$packageId, $title, $description, $sortOrder]
                );

                $inserted++;
            }

            /* 2. TEAM ROLES */

            if (!empty($_POST['team']['role_id'])) {

                foreach ($_POST['team']['role_id'] as $key => $roleId) {

                    $roleId = (int)$roleId;

                    if ($roleId <= 0) continue;

                    $quantity = (int)($_POST['team']['quantity'][$key] ?? 1);
                    $teamDescription = trim(
                        $_POST['team']['description'][$key] ?? ''
                    );

                    pd_insert(
                        $conn,
                        "INSERT INTO package_team_roles
                        (package_id, role_id, quantity, description)
                        VALUES (?, ?, ?, ?)",
                        "iiis",
                        [$packageId, $roleId, $quantity, $teamDescription]
                    );

                    $inserted++;
                }
            }

            /* 3. FLOWER OPTIONS */

            if (!empty($_POST['flower']['flower_id'])) {

                foreach ($_POST['flower']['flower_id'] as $key => $flowerId) {

                    $flowerId = (int)$flowerId;

                    if ($flowerId <= 0) continue;

                    $designId = (int)($_POST['flower']['design_id'][$key] ?? 0);
                    $designId = $designId > 0 ? $designId : null;

                    $quantity = trim(
                        $_POST['flower']['quantity_description'][$key] ?? ''
                    );

                    $price = (float)($_POST['flower']['additional_price'][$key] ?? 0);

                    $selectable = isset($_POST['flower']['is_selectable'][$key])
                        ? 1 : 0;

                    pd_insert(
                        $conn,
                        "INSERT INTO package_flower_options
                        (package_id, flower_id, design_id,
                         quantity_description, additional_price, is_selectable)
                        VALUES (?, ?, ?, ?, ?, ?)",
                        "iiisdi",
                        [$packageId, $flowerId, $designId,
                         $quantity, $price, $selectable]
                    );

                    $inserted++;
                }
            }

            /* 4. DECORATION AREAS */

            if (!empty($_POST['area']['area_id'])) {

                foreach ($_POST['area']['area_id'] as $key => $areaId) {

                    $areaId = (int)$areaId;

                    if ($areaId <= 0) continue;

                    $areaDescription = trim(
                        $_POST['area']['design_description'][$key] ?? ''
                    );

                    $imageId = (int)($_POST['area']['image_id'][$key] ?? 0);
                    $imageId = $imageId > 0 ? $imageId : null;

                    $price = (float)($_POST['area']['additional_price'][$key] ?? 0);

                    pd_insert(
                        $conn,
                        "INSERT INTO package_decoration_areas
                        (package_id, area_id, design_description,
                         image_id, additional_price)
                        VALUES (?, ?, ?, ?, ?)",
                        "iisid",
                        [$packageId, $areaId, $areaDescription, $imageId, $price]
                    );

                    $inserted++;
                }
            }

            /* 5. LIGHTING EQUIPMENT */

            if (!empty($_POST['lighting']['lighting_id'])) {

                foreach ($_POST['lighting']['lighting_id'] as $key => $lightingId) {

                    $lightingId = (int)$lightingId;

                    if ($lightingId <= 0) continue;

                    $quantity = (int)($_POST['lighting']['quantity'][$key] ?? 1);

                    $purpose = trim(
                        $_POST['lighting']['purpose'][$key] ?? ''
                    );

                    $price = (float)($_POST['lighting']['additional_price'][$key] ?? 0);

                    pd_insert(
                        $conn,
                        "INSERT INTO package_lighting
                        (package_id, lighting_id, quantity, purpose, additional_price)
                        VALUES (?, ?, ?, ?, ?)",
                        "iiisd",
                        [$packageId, $lightingId, $quantity, $purpose, $price]
                    );

                    $inserted++;
                }
            }

            /* 6. FOOD MENU */

            if (!empty($_POST['food']['food_id'])) {

                foreach ($_POST['food']['food_id'] as $key => $foodId) {

                    $foodId = (int)$foodId;

                    if ($foodId <= 0) continue;

                    $quantity = trim(
                        $_POST['food']['quantity_description'][$key] ?? ''
                    );

                    $price = (float)($_POST['food']['additional_price'][$key] ?? 0);

                    pd_insert(
                        $conn,
                        "INSERT INTO package_food_menus
                        (package_id, food_id, quantity_description, additional_price)
                        VALUES (?, ?, ?, ?)",
                        "iisd",
                        [$packageId, $foodId, $quantity, $price]
                    );

                    $inserted++;
                }
            }

            /* 7. PROFESSIONAL VENDORS */

            if (!empty($_POST['vendor']['vendor_id'])) {

                foreach ($_POST['vendor']['vendor_id'] as $key => $vendorId) {

                    $vendorId = (int)$vendorId;

                    if ($vendorId <= 0) continue;

                    $serviceDescription = trim(
                        $_POST['vendor']['service_description'][$key] ?? ''
                    );

                    pd_insert(
                        $conn,
                        "INSERT INTO package_vendors
                        (package_id, vendor_id, service_description)
                        VALUES (?, ?, ?)",
                        "iis",
                        [$packageId, $vendorId, $serviceDescription]
                    );

                    $inserted++;
                }
            }

            /* 8. ADDITIONAL FACILITIES */

            if (!empty($_POST['facility']['facility_id'])) {

                foreach ($_POST['facility']['facility_id'] as $key => $facilityId) {

                    $facilityId = (int)$facilityId;

                    if ($facilityId <= 0) continue;

                    $price = (float)($_POST['facility']['additional_price'][$key] ?? 0);

                    pd_insert(
                        $conn,
                        "INSERT INTO package_facilities
                        (package_id, facility_id, additional_price)
                        VALUES (?, ?, ?)",
                        "iid",
                        [$packageId, $facilityId, $price]
                    );

                    $inserted++;
                }
            }

            if ($inserted === 0) {
                throw new Exception(
                    "Please enter at least one package detail."
                );
            }

            $conn->commit();

            header(
                "Location: package_details.php?success=added"
            );
            exit;

        } catch (Throwable $e) {

            $conn->rollback();
            $error = "Unable to save package details: " . $e->getMessage();
        }
    }
}

/* =====================================
   DROPDOWN RENDER FUNCTION
===================================== */

function pd_render_options($items, $valueKey, $labelKey) {

    echo '<option value="">Select an option</option>';

    foreach ($items as $item) {

        echo '<option value="' . pd_escape($item[$valueKey]) . '">'
            . pd_escape($item[$labelKey])
            . '</option>';
    }
}

/* =====================================
   ADMIN LAYOUT
===================================== */

include 'includes/sidebar.php';
include 'includes/header.php';

?>
<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<div class="main-content add-package-page">

    <div class="add-package-heading">
        <div>
            <h2>Add Package Details</h2>
            <p>Add all information related to a decoration package.</p>
        </div>

        <a href="package_details.php" class="package-back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="packages-alert error">
            <?= pd_escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="add-package-form">

        <!-- PACKAGE SELECTION -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-box"></i>
                Select Package
            </h3>

            <div class="package-form-grid">

                <div class="package-form-group full-width">

                    <label>
                        Package <span>*</span>
                    </label>

                    <select name="package_id" required>

                        <option value="">Select Package</option>

                        <?php foreach ($packages as $package): ?>

                            <option
                                value="<?= (int)$package['package_id'] ?>"
                                <?= $packageId == $package['package_id'] ? 'selected' : '' ?>
                            >
                                <?= pd_escape($package['package_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

        </div>

        <!-- 1. PACKAGE OVERVIEW -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-file-alt"></i>
                1. Package Overview
            </h3>

            <div class="package-form-grid">

                <div class="package-form-group">

                    <label>Overview Title</label>

                    <input
                        type="text"
                        name="detail_title"
                        placeholder="Example: Package Overview"
                    >

                </div>

                <div class="package-form-group">

                    <label>Sort Order</label>

                    <input
                        type="number"
                        name="sort_order"
                        value="0"
                        min="0"
                    >

                </div>

                <div class="package-form-group full-width">

                    <label>Overview Description</label>

                    <textarea
                        name="detail_description"
                        rows="4"
                        placeholder="Write package overview..."
                    ></textarea>

                </div>

            </div>

        </div>

        <!-- 2. TEAM ROLES -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-users"></i>
                2. Photography & Decoration Team
            </h3>

            <div class="repeat-container" data-section="team">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Team Role</label>

                            <select name="team[role_id][]">
                                <?php pd_render_options($roles, 'role_id', 'role_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Quantity</label>

                            <input
                                type="number"
                                name="team[quantity][]"
                                min="1"
                                value="1"
                            >

                        </div>

                        <div class="package-form-group full-width">

                            <label>Description</label>

                            <input
                                type="text"
                                name="team[description][]"
                                placeholder="Describe team responsibility"
                            >

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Team Role
            </button>

        </div>

        <!-- 3. FLOWER OPTIONS -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-spa"></i>
                3. Flower Setup
            </h3>

            <div class="repeat-container" data-section="flower">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Flower</label>

                            <select name="flower[flower_id][]">
                                <?php pd_render_options($flowers, 'flower_id', 'flower_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Flower Design</label>

                            <select name="flower[design_id][]">
                                <?php pd_render_options($designs, 'design_id', 'design_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Quantity Description</label>

                            <input
                                type="text"
                                name="flower[quantity_description][]"
                                placeholder="Example: 100 red roses"
                            >

                        </div>

                        <div class="package-form-group">

                            <label>Additional Price</label>

                            <input
                                type="number"
                                name="flower[additional_price][]"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>

                        <div class="package-form-group full-width">

                            <label class="package-checkbox-label">

                                <input
                                    type="checkbox"
                                    name="flower[is_selectable][0]"
                                    value="1"
                                    checked
                                >

                                Customer can select this flower

                            </label>

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Flower
            </button>

        </div>

        <!-- 4. DECORATION AREAS -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-map-marked-alt"></i>
                4. Decoration Areas & Design Plan
            </h3>

            <div class="repeat-container" data-section="area">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Decoration Area</label>

                            <select name="area[area_id][]">
                                <?php pd_render_options($areas, 'area_id', 'area_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Image ID (Optional)</label>

                            <input
                                type="number"
                                name="area[image_id][]"
                                min="1"
                                placeholder="Media ID"
                            >

                        </div>

                        <div class="package-form-group">

                            <label>Additional Price</label>

                            <input
                                type="number"
                                name="area[additional_price][]"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>

                        <div class="package-form-group full-width">

                            <label>Design Description</label>

                            <textarea
                                name="area[design_description][]"
                                rows="3"
                                placeholder="Describe decoration design..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Decoration Area
            </button>

        </div>

        <!-- 5. LIGHTING -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-lightbulb"></i>
                5. Lighting Equipment
            </h3>

            <div class="repeat-container" data-section="lighting">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Equipment</label>

                            <select name="lighting[lighting_id][]">
                                <?php pd_render_options($lightingItems, 'lighting_id', 'equipment_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Quantity</label>

                            <input
                                type="number"
                                name="lighting[quantity][]"
                                min="1"
                                value="1"
                            >

                        </div>

                        <div class="package-form-group">

                            <label>Additional Price</label>

                            <input
                                type="number"
                                name="lighting[additional_price][]"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>

                        <div class="package-form-group full-width">

                            <label>Purpose</label>

                            <input
                                type="text"
                                name="lighting[purpose][]"
                                placeholder="Describe lighting purpose"
                            >

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Lighting Equipment
            </button>

        </div>

        <!-- 6. FOOD MENU -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-utensils"></i>
                6. Guest Food & Catering
            </h3>

            <div class="repeat-container" data-section="food">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Food Item</label>

                            <select name="food[food_id][]">
                                <?php pd_render_options($foods, 'food_id', 'food_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Quantity Description</label>

                            <input
                                type="text"
                                name="food[quantity_description][]"
                                placeholder="Example: Per guest"
                            >

                        </div>

                        <div class="package-form-group">

                            <label>Additional Price</label>

                            <input
                                type="number"
                                name="food[additional_price][]"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Food Item
            </button>

        </div>

        <!-- 7. VENDORS -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-handshake"></i>
                7. Professional Partners
            </h3>

            <div class="repeat-container" data-section="vendor">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Vendor</label>

                            <select name="vendor[vendor_id][]">
                                <?php pd_render_options($vendors, 'vendor_id', 'vendor_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group full-width">

                            <label>Service Description</label>

                            <textarea
                                name="vendor[service_description][]"
                                rows="3"
                                placeholder="Describe vendor service..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Vendor
            </button>

        </div>

        <!-- 8. FACILITIES -->

        <div class="package-form-section">

            <h3>
                <i class="fas fa-concierge-bell"></i>
                8. Additional Facilities
            </h3>

            <div class="repeat-container" data-section="facility">

                <div class="repeat-row">

                    <div class="package-form-grid">

                        <div class="package-form-group">

                            <label>Facility</label>

                            <select name="facility[facility_id][]">
                                <?php pd_render_options($facilities, 'facility_id', 'facility_name'); ?>
                            </select>

                        </div>

                        <div class="package-form-group">

                            <label>Additional Price</label>

                            <input
                                type="number"
                                name="facility[additional_price][]"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>

                    </div>

                </div>

            </div>

            <button type="button" class="package-cancel-btn add-row-btn">
                <i class="fas fa-plus"></i> Add Facility
            </button>

        </div>

        <!-- FORM ACTIONS -->

        <div class="package-form-actions">

            <a href="package_details.php" class="package-cancel-btn">
                Cancel
            </a>

            <button type="submit" class="package-save-btn">
                <i class="fas fa-save"></i>
                Save Package Details
            </button>

        </div>

    </form>

</div>

<script>
document.querySelectorAll('.add-row-btn').forEach(button => {

    button.addEventListener('click', function () {

        const section = this.previousElementSibling;
        const firstRow = section.querySelector('.repeat-row');

        if (!firstRow) return;

        const newRow = firstRow.cloneNode(true);

        // Clear input values
        newRow.querySelectorAll('input, textarea').forEach(input => {

            if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.type === 'number') {
                input.value = input.defaultValue || '0';
            } else {
                input.value = '';
            }

        });

        // Reset dropdowns
        newRow.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        // Remove cloned checkbox index and use array notation
        newRow.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.name = 'flower[is_selectable][]';
        });

        // Add remove button
        const removeButton = document.createElement('button');

        removeButton.type = 'button';
        removeButton.className = 'package-cancel-btn remove-row-btn';
        removeButton.innerHTML = '<i class="fas fa-trash"></i> Remove';

        removeButton.addEventListener('click', function () {
            newRow.remove();
        });

        newRow.appendChild(removeButton);

        section.appendChild(newRow);

    });

});
</script>

<?php include 'includes/footer.php'; ?>


<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../db.php';

/* =====================================
   HELPER FUNCTIONS
===================================== */

function pd_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pd_options($conn, $sql) {
    $result = $conn->query($sql);
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function pd_bind_execute($conn, $sql, $types, $values) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $params = [$types];

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

function pd_render_group($group, $title, $icon, $rows, $pk, $fields) {

    if (empty($rows)) {
        $rows = [array_fill_keys(array_merge([$pk], array_keys($fields)), '')];
        $rows[0][$pk] = 0;
    }

    echo '<div class="package-form-section">';
    echo '<h3><i class="fas ' . pd_escape($icon) . '"></i> ' . pd_escape($title) . '</h3>';
    echo '<div class="repeat-container">';

    foreach ($rows as $index => $row) {

        $recordId = (int)($row[$pk] ?? 0);

        echo '<div class="repeat-row">';
        echo '<input type="hidden" name="' . $group . '[id][' . $index . ']" value="' . $recordId . '">';

        echo '<div class="package-form-grid">';

        foreach ($fields as $name => $field) {

            $value = $row[$name] ?? '';
            $full = !empty($field['full']) ? ' full-width' : '';

            echo '<div class="package-form-group' . $full . '">';
            echo '<label>' . pd_escape($field['label']) . '</label>';

            $inputName = $group . '[' . $name . '][' . $index . ']';

            if ($field['type'] === 'select') {

                echo '<select name="' . $inputName . '">';
                echo '<option value="">Select an option</option>';

                foreach ($field['options'] as $option) {

                    $selected = ((string)$value === (string)$option['id'])
                        ? 'selected' : '';

                    echo '<option value="' . (int)$option['id'] . '" ' . $selected . '>'
                        . pd_escape($option['name'])
                        . '</option>';
                }

                echo '</select>';

            } elseif ($field['type'] === 'checkbox') {

                $checked = ((int)$value === 1) ? 'checked' : '';

                echo '<label class="package-checkbox-label">';
                echo '<input type="checkbox" name="' . $inputName . '" value="1" ' . $checked . '>';
                echo ' ' . pd_escape($field['label']);
                echo '</label>';

            } elseif ($field['type'] === 'textarea') {

                echo '<textarea name="' . $inputName . '" rows="3">'
                    . pd_escape($value)
                    . '</textarea>';

            } else {

                $type = $field['type'];
                $step = $type === 'number' && !empty($field['decimal'])
                    ? 'step="0.01"' : '';

                echo '<input type="' . $type . '" name="' . $inputName . '" '
                    . $step . ' value="' . pd_escape($value) . '">';
            }

            echo '</div>';
        }

        echo '</div>';

        echo '<label class="package-checkbox-label" style="margin-top:12px;color:#dc2626;">';
        echo '<input type="checkbox" name="' . $group . '[remove][' . $index . ']" value="1">';
        echo ' Remove this record';
        echo '</label>';

        echo '</div>';
    }

    echo '</div>';

    echo '<button type="button" class="package-cancel-btn add-row-btn">';
    echo '<i class="fas fa-plus"></i> Add Another';
    echo '</button>';

    echo '</div>';
}

/* =====================================
   PACKAGE ID
===================================== */

$packageId = (int)($_GET['package_id'] ?? $_POST['package_id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT package_id, package_name
     FROM packages
     WHERE package_id = ?"
);

$stmt->bind_param("i", $packageId);
$stmt->execute();

$selectedPackage = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$selectedPackage) {
    header("Location: package_details.php");
    exit;
}

$error = '';

/* =====================================
   DROPDOWN DATA
===================================== */

$roles = pd_options($conn,
    "SELECT role_id AS id, role_name AS name FROM team_roles ORDER BY role_name");

$flowers = pd_options($conn,
    "SELECT flower_id AS id, flower_name AS name FROM flowers WHERE is_active=1 ORDER BY flower_name");

$designs = pd_options($conn,
    "SELECT design_id AS id, design_name AS name FROM flower_designs WHERE is_active=1 ORDER BY design_name");

$areas = pd_options($conn,
    "SELECT area_id AS id, area_name AS name FROM decoration_areas WHERE is_active=1 ORDER BY area_name");

$lightingItems = pd_options($conn,
    "SELECT lighting_id AS id, equipment_name AS name FROM lighting_equipment WHERE is_active=1 ORDER BY equipment_name");

$foods = pd_options($conn,
    "SELECT food_id AS id, food_name AS name FROM food_menu_items WHERE is_active=1 ORDER BY food_name");

$vendors = pd_options($conn,
    "SELECT vendor_id AS id, vendor_name AS name FROM vendors WHERE status='active' ORDER BY vendor_name");

$facilities = pd_options($conn,
    "SELECT facility_id AS id, facility_name AS name FROM facilities WHERE is_active=1 ORDER BY facility_name");

/* =====================================
   TABLE CONFIGURATION
===================================== */

$config = [

    'overview' => [
        'table' => 'package_details',
        'pk' => 'detail_id',
        'fields' => [
            'detail_title' => ['type'=>'text', 'label'=>'Overview Title'],
            'detail_description' => ['type'=>'textarea', 'label'=>'Overview Description', 'full'=>true],
            'sort_order' => ['type'=>'number', 'label'=>'Sort Order']
        ],
        'types' => 'issi'
    ],

    'team' => [
        'table' => 'package_team_roles',
        'pk' => 'package_team_id',
        'fields' => [
            'role_id' => ['type'=>'select', 'label'=>'Team Role', 'options'=>$roles],
            'quantity' => ['type'=>'number', 'label'=>'Quantity'],
            'description' => ['type'=>'text', 'label'=>'Description', 'full'=>true]
        ],
        'types' => 'iiis'
    ],

    'flower' => [
        'table' => 'package_flower_options',
        'pk' => 'option_id',
        'fields' => [
            'flower_id' => ['type'=>'select', 'label'=>'Flower', 'options'=>$flowers],
            'design_id' => ['type'=>'select', 'label'=>'Flower Design', 'options'=>$designs],
            'quantity_description' => ['type'=>'text', 'label'=>'Quantity Description'],
            'additional_price' => ['type'=>'number', 'label'=>'Additional Price', 'decimal'=>true],
            'is_selectable' => ['type'=>'checkbox', 'label'=>'Customer can select this flower', 'full'=>true]
        ],
        'types' => 'iiisdi'
    ],

    'area' => [
        'table' => 'package_decoration_areas',
        'pk' => 'package_area_id',
        'fields' => [
            'area_id' => ['type'=>'select', 'label'=>'Decoration Area', 'options'=>$areas],
            'design_description' => ['type'=>'textarea', 'label'=>'Design Description', 'full'=>true],
            'image_id' => ['type'=>'number', 'label'=>'Image ID'],
            'additional_price' => ['type'=>'number', 'label'=>'Additional Price', 'decimal'=>true]
        ],
        'types' => 'iisid'
    ],

    'lighting' => [
        'table' => 'package_lighting',
        'pk' => 'package_lighting_id',
        'fields' => [
            'lighting_id' => ['type'=>'select', 'label'=>'Equipment', 'options'=>$lightingItems],
            'quantity' => ['type'=>'number', 'label'=>'Quantity'],
            'purpose' => ['type'=>'text', 'label'=>'Purpose', 'full'=>true],
            'additional_price' => ['type'=>'number', 'label'=>'Additional Price', 'decimal'=>true]
        ],
        'types' => 'iiisd'
    ],

    'food' => [
        'table' => 'package_food_menus',
        'pk' => 'package_food_id',
        'fields' => [
            'food_id' => ['type'=>'select', 'label'=>'Food Item', 'options'=>$foods],
            'quantity_description' => ['type'=>'text', 'label'=>'Quantity Description'],
            'additional_price' => ['type'=>'number', 'label'=>'Additional Price', 'decimal'=>true]
        ],
        'types' => 'iisd'
    ],

    'vendor' => [
        'table' => 'package_vendors',
        'pk' => 'package_vendor_id',
        'fields' => [
            'vendor_id' => ['type'=>'select', 'label'=>'Vendor', 'options'=>$vendors],
            'service_description' => ['type'=>'textarea', 'label'=>'Service Description', 'full'=>true]
        ],
        'types' => 'iis'
    ],

    'facility' => [
        'table' => 'package_facilities',
        'pk' => 'package_facility_id',
        'fields' => [
            'facility_id' => ['type'=>'select', 'label'=>'Facility', 'options'=>$facilities],
            'additional_price' => ['type'=>'number', 'label'=>'Additional Price', 'decimal'=>true]
        ],
        'types' => 'iid'
    ]
];

/* =====================================
   UPDATE / REMOVE / INSERT
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $conn->begin_transaction();

        foreach ($config as $group => $settings) {

            $table = $settings['table'];
            $pk = $settings['pk'];
            $fields = $settings['fields'];
            $types = $settings['types'];

            $posted = $_POST[$group] ?? [];

            $ids = $posted['id'] ?? [];

            foreach ($ids as $index => $recordId) {

                $recordId = (int)$recordId;

                /* INDIVIDUAL REMOVE */

                if (!empty($posted['remove'][$index]) && $recordId > 0) {

                    pd_bind_execute(
                        $conn,
                        "DELETE FROM $table WHERE $pk = ? AND package_id = ?",
                        "ii",
                        [$recordId, $packageId]
                    );

                    continue;
                }

                $values = [];
                $valid = true;

                foreach ($fields as $name => $field) {

                    if ($field['type'] === 'checkbox') {
                        $value = isset($posted[$name][$index]) ? 1 : 0;
                    } else {
                        $value = $posted[$name][$index] ?? '';
                    }

                    if ($field['type'] === 'select') {
                        $value = (int)$value;

                        // A row without its main selection is ignored.
                        if ($name === array_key_first($fields) && $value <= 0) {
                            $valid = false;
                        }

                        if ($value <= 0) {
                            $value = null;
                        }

                    } elseif ($name === 'design_id' || $name === 'image_id') {

                        $value = (int)$value;
                        $value = $value > 0 ? $value : null;

                    } elseif ($field['type'] === 'number') {

                        $value = $field['decimal']
                            ?? false
                            ? (float)$value
                            : (int)$value;

                    } else {
                        $value = trim((string)$value);
                    }

                    $values[] = $value;
                }

                if (!$valid) {
                    continue;
                }

                /* OVERVIEW NEEDS A TITLE */

                if ($group === 'overview' && trim((string)($posted['detail_title'][$index] ?? '')) === '') {
                    continue;
                }

                $columns = array_keys($fields);

                if ($recordId > 0) {

                    $setParts = [];

                    foreach ($columns as $column) {
                        $setParts[] = "$column = ?";
                    }

                    $updateTypes = substr($types, 1) . "ii";

                    $updateValues = array_slice($values, 1);
                    array_unshift($updateValues, $values[0]);

                    // Bind values in their original order.
                    $updateValues = $values;
                    $updateValues[] = $recordId;
                    $updateValues[] = $packageId;

                    pd_bind_execute(
                        $conn,
                        "UPDATE $table SET " . implode(', ', $setParts) .
                        " WHERE $pk = ? AND package_id = ?",
                        $types . "ii",
                        $updateValues
                    );

                } else {

                    $insertColumns = array_merge(['package_id'], $columns);
                    $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));

                    pd_bind_execute(
                        $conn,
                        "INSERT INTO $table (" . implode(',', $insertColumns) .
                        ") VALUES ($placeholders)",
                        'i' . $types,
                        array_merge([$packageId], $values)
                    );
                }
            }
        }

        $conn->commit();

        header("Location: package_details.php?success=updated");
        exit;

    } catch (Throwable $e) {

        $conn->rollback();
        $error = "Unable to update package details: " . $e->getMessage();
    }
}

/* =====================================
   FETCH EXISTING RECORDS
===================================== */

$records = [];

foreach ($config as $group => $settings) {

    $table = $settings['table'];

    $stmt = $conn->prepare(
        "SELECT * FROM $table WHERE package_id = ? ORDER BY " . $settings['pk'] . " ASC"
    );

    $stmt->bind_param("i", $packageId);
    $stmt->execute();

    $records[$group] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
}

/* =====================================
   ADMIN LAYOUT
===================================== */

include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/includes/header.php';
?>

<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="includes/css/admin.css">
</head>

<div class="main-content add-package-page">

    <div class="add-package-heading">
        <div>
            <h2>Edit Package Details</h2>
            <p>Update or remove the information of this package.</p>
            <strong><?= pd_escape($selectedPackage['package_name']) ?></strong>
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

        <input type="hidden" name="package_id" value="<?= $packageId ?>">

        <?php
        $titles = [
            'overview' => ['1. Package Overview', 'fa-file-alt'],
            'team' => ['2. Photography & Decoration Team', 'fa-users'],
            'flower' => ['3. Flower Setup', 'fa-spa'],
            'area' => ['4. Decoration Areas & Design Plan', 'fa-map-marked-alt'],
            'lighting' => ['5. Lighting Equipment', 'fa-lightbulb'],
            'food' => ['6. Guest Food & Catering', 'fa-utensils'],
            'vendor' => ['7. Professional Partners', 'fa-handshake'],
            'facility' => ['8. Additional Facilities', 'fa-concierge-bell']
        ];

        foreach ($config as $group => $settings) {

            pd_render_group(
                $group,
                $titles[$group][0],
                $titles[$group][1],
                $records[$group],
                $settings['pk'],
                $settings['fields']
            );
        }
        ?>

        <div class="package-form-actions">

            <a href="package_details.php" class="package-cancel-btn">
                Cancel
            </a>

            <button type="submit" class="package-save-btn">
                <i class="fas fa-save"></i>
                Update Package Details
            </button>

        </div>

    </form>

</div>

<script>
document.querySelectorAll('.add-row-btn').forEach(button => {

    button.addEventListener('click', function () {

        const container = this.previousElementSibling;
        const firstRow = container.querySelector('.repeat-row');

        if (!firstRow) return;

        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, textarea').forEach(input => {

            if (input.type === 'hidden') {
                input.value = 0;
            } else if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.type === 'number') {
                input.value = input.name.includes('quantity') ? 1 : 0;
            } else {
                input.value = '';
            }
        });

        newRow.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        newRow.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = false;
        });

        const removeLabel = newRow.querySelector('label[style]');
        if (removeLabel) {
            removeLabel.remove();
        }

        let removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'package-cancel-btn';
        removeButton.innerHTML = '<i class="fas fa-trash"></i> Remove New Row';
        removeButton.style.marginTop = '12px';

        removeButton.addEventListener('click', () => newRow.remove());

        newRow.appendChild(removeButton);

        const newIndex = 'new_' + Date.now();

        newRow.querySelectorAll('[name]').forEach(element => {
            element.name = element.name.replace(/\[[^\]]+\]$/, '[' + newIndex + ']');
        });

        container.appendChild(newRow);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

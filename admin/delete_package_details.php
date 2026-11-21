
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../db.php';

/* =====================================
   VALIDATE REQUEST
===================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: package_details.php");
    exit;
}

$packageId = (int)($_POST['package_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($packageId <= 0) {
    header("Location: package_details.php?error=invalid_package");
    exit;
}

/* =====================================
   PACKAGE EXISTS CHECK
===================================== */

$stmt = $conn->prepare(
    "SELECT package_id FROM packages WHERE package_id = ?"
);

$stmt->bind_param("i", $packageId);
$stmt->execute();

$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    header("Location: package_details.php?error=package_not_found");
    exit;
}

/* =====================================
   TABLE CONFIGURATION
===================================== */

$tables = [
    'package_details' => 'detail_id',
    'package_team_roles' => 'package_team_id',
    'package_flower_options' => 'option_id',
    'package_decoration_areas' => 'package_area_id',
    'package_lighting' => 'package_lighting_id',
    'package_food_menus' => 'package_food_id',
    'package_vendors' => 'package_vendor_id',
    'package_facilities' => 'package_facility_id'
];

/* =====================================
   DELETE PROCESS
===================================== */

try {

    $conn->begin_transaction();

    if ($action === 'delete_all') {

        /* DELETE ALL PACKAGE DETAILS */

        foreach ($tables as $table => $primaryKey) {

            $stmt = $conn->prepare(
                "DELETE FROM `$table` WHERE package_id = ?"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("i", $packageId);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
        }

        $conn->commit();

        header(
            "Location: package_details.php?success=deleted_all"
        );
        exit;

    } elseif ($action === 'delete_single') {

        /* DELETE ONE RECORD */

        $table = $_POST['table'] ?? '';
        $recordId = (int)($_POST['record_id'] ?? 0);

        if (
            !isset($tables[$table]) ||
            $recordId <= 0
        ) {
            throw new Exception("Invalid delete request.");
        }

        $primaryKey = $tables[$table];

        $stmt = $conn->prepare(
            "DELETE FROM `$table`
             WHERE `$primaryKey` = ?
             AND package_id = ?"
        );

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("ii", $recordId, $packageId);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Record not found.");
        }

        $stmt->close();

        $conn->commit();

        header(
            "Location: edit_package_details.php?package_id=" .
            $packageId . "&success=deleted"
        );
        exit;

    } else {

        throw new Exception("Invalid delete action.");
    }

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Package Details Delete Error: " . $e->getMessage());

    header(
        "Location: package_details.php?error=delete_failed"
    );
    exit;
}

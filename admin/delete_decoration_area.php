<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: decoration_areas.php");
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM decoration_areas
    WHERE area_id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: decoration_areas.php?success=deleted");
    exit;
}

$stmt->close();

header("Location: decoration_areas.php?error=delete_failed");
exit;
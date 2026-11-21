<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: navigation.php");
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM navigation_items
    WHERE nav_id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: navigation.php?success=deleted");
    exit;
}

$stmt->close();

header("Location: navigation.php?error=delete_failed");
exit;
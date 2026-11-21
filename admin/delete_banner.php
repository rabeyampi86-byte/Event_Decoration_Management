<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: banners.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM banners WHERE banner_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: banners.php?success=deleted");
    exit;
}

$stmt->close();
header("Location: banners.php?error=delete_failed");
exit;
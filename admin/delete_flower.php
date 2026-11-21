<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: flowers.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("DELETE FROM flowers WHERE flower_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: flowers.php?success=deleted");
    } else {
        header("Location: flowers.php?error=notfound");
    }
} else {
    header("Location: flowers.php?error=delete_failed");
}

$stmt->close();
exit;
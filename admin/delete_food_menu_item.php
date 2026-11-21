<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: food_menu_items.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM food_menu_items
    WHERE food_id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        header("Location: food_menu_items.php?success=deleted");
    } else {
        header("Location: food_menu_items.php?error=notfound");
    }

} else {

    header("Location: food_menu_items.php?error=delete_failed");

}

$stmt->close();
exit;
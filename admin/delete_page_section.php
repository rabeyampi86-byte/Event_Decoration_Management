
<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: page_sections.php?error=invalid_id");
    exit;
}

/* Check whether section exists */
$check = $conn->prepare("
    SELECT section_id
    FROM page_sections
    WHERE section_id = ?
");

$check->bind_param("i", $id);
$check->execute();

if (!$check->get_result()->fetch_assoc()) {
    header("Location: page_sections.php?error=not_found");
    exit;
}

/* Delete section */
$delete = $conn->prepare("
    DELETE FROM page_sections
    WHERE section_id = ?
");

$delete->bind_param("i", $id);

if ($delete->execute()) {
    header("Location: page_sections.php?success=deleted");
    exit;
}

header("Location: page_sections.php?error=delete_failed");
exit;

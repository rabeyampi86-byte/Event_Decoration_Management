
<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: pages.php");
    exit;
}

/* Check whether this page has sections */
$check = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM page_sections
    WHERE page_id = ?
");

$check->bind_param("i", $id);
$check->execute();

$sectionResult = $check->get_result();
$sectionData = $sectionResult->fetch_assoc();

if ((int)$sectionData['total'] > 0) {
    header("Location: pages.php?error=has_sections");
    exit;
}

/* Delete page */
$stmt = $conn->prepare("
    DELETE FROM pages
    WHERE page_id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: pages.php?success=deleted");
    exit;
}

header("Location: pages.php?error=delete_failed");
exit;


<?php
requir-*e_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact_messages.php");
    exit;
}

$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

if (!$message_id || $message_id < 1) {
    header("Location: contact_messages.php");
    exit;
}

$stmt = $conn->prepare("
    UPDATE contact_messages
    SET status = 'replied'
    WHERE message_id = ?
");

$stmt->bind_param("i", $message_id);

if ($stmt->execute()) {
    header("Location: view_contact_message.php?message_id=" . $message_id . "&replied=1");
    exit;
}

die("Failed to update message status.");

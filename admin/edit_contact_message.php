
<?php
require_once __DIR__ . '/../db.php';

$message_id = filter_input(INPUT_GET, 'message_id', FILTER_VALIDATE_INT);

if (!$message_id || $message_id < 1) {
    header("Location: contact_messages.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM contact_messages
    WHERE message_id = ?
");
$stmt->bind_param("i", $message_id);
$stmt->execute();

$result = $stmt->get_result();
$contact = $result->fetch_assoc();

if (!$contact) {
    header("Location: contact_messages.php");
    exit;
}

$errors = [];

$name = $contact['name'];
$email = $contact['email'];
$phone = $contact['phone'] ?? '';
$subject = $contact['subject'] ?? '';
$message = $contact['message'];
$status = $contact['status'] ?? 'unread';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($message === '') {
        $errors[] = "Message is required.";
    }

    if (!in_array($status, ['unread', 'read', 'replied'], true)) {
        $errors[] = "Invalid status selected.";
    }

    if (empty($errors)) {

        $stmt = $conn->prepare("
            UPDATE contact_messages
            SET name = ?,
                email = ?,
                phone = ?,
                subject = ?,
                message = ?,
                status = ?
            WHERE message_id = ?
        ");

        $stmt->bind_param(
            "ssssssi",
            $name,
            $email,
            $phone,
            $subject,
            $message,
            $status,
            $message_id
        );

        if ($stmt->execute()) {
            header("Location: view_contact_message.php?message_id=" . $message_id . "&updated=1");
            exit;
        } else {
            $errors[] = "Failed to update contact message.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Contact Message</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .edit-contact-page {
            padding: 25px 30px;
        }

        .edit-contact-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .edit-contact-card h2 {
            color: #1e293b;
            margin-top: 0;
        }

        .edit-contact-card > p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .edit-contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .edit-contact-group {
            margin-bottom: 18px;
        }

        .edit-contact-group.full {
            grid-column: 1 / -1;
        }

        .edit-contact-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .edit-contact-group input,
        .edit-contact-group select,
        .edit-contact-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .edit-contact-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .edit-contact-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-contact-actions button,
        .edit-contact-actions a {
            padding: 11px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .edit-contact-save {
            background: #2563eb;
            color: #fff;
        }

        .edit-contact-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .edit-contact-errors {
            padding: 12px 18px;
            margin-bottom: 20px;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 6px;
        }

        @media(max-width: 650px) {
            .edit-contact-page {
                padding: 20px 15px;
            }

            .edit-contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="edit-contact-page">

            <div class="edit-contact-card">

                <h2>Edit Contact Message #<?php echo (int)$message_id; ?></h2>
                <p>Update sender information, message and status.</p>

                <?php if (!empty($errors)): ?>
                    <div class="edit-contact-errors">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="edit-contact-grid">

                        <div class="edit-contact-group">
                            <label>Sender Name *</label>
                            <input type="text" name="name" required
                                value="<?php echo htmlspecialchars($name); ?>">
                        </div>

                        <div class="edit-contact-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required
                                value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="edit-contact-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone"
                                value="<?php echo htmlspecialchars($phone); ?>">
                        </div>

                        <div class="edit-contact-group">
                            <label>Subject</label>
                            <input type="text" name="subject"
                                value="<?php echo htmlspecialchars($subject); ?>">
                        </div>

                        <div class="edit-contact-group full">
                            <label>Message *</label>
                            <textarea name="message" required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>

                        <div class="edit-contact-group full">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo $status === 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </div>

                    </div>

                    <div class="edit-contact-actions">
                        <button type="submit" class="edit-contact-save">
                            <i class="fas fa-save"></i> Update Message
                        </button>

                        <a href="view_contact_message.php?message_id=<?php echo (int)$message_id; ?>"
                           class="edit-contact-cancel">
                            Cancel
                        </a>
                    </div>

                </form>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

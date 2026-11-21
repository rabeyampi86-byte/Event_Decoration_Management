
<?php
require_once '../db.php';

$errors = [];

$name = '';
$email = '';
$phone = '';
$subject = '';
$message = '';
$status = 'unread';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'unread';

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
        $errors[] = "Invalid message status.";
    }

    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO contact_messages
            (name, email, phone, subject, message, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssss",
            $name,
            $email,
            $phone,
            $subject,
            $message,
            $status
        );

        if ($stmt->execute()) {
            header("Location: contact_messages.php?added=1");
            exit;
        } else {
            $errors[] = "Failed to add contact message.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Contact Message</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .contact-form-page {
            padding: 25px 30px;
        }

        .contact-form-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .contact-form-card h2 {
            margin: 0 0 8px;
            color: #1e293b;
        }

        .contact-form-card p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .contact-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .contact-form-group {
            margin-bottom: 18px;
        }

        .contact-form-group.full {
            grid-column: 1 / -1;
        }

        .contact-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .contact-form-group input,
        .contact-form-group select,
        .contact-form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .contact-form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .contact-form-group input:focus,
        .contact-form-group select:focus,
        .contact-form-group textarea:focus {
            border-color: #2563eb;
        }

        .contact-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .contact-save-btn,
        .contact-cancel-btn {
            padding: 11px 18px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .contact-save-btn {
            background: #2563eb;
            color: #fff;
        }

        .contact-cancel-btn {
            background: #f1f5f9;
            color: #475569;
        }

        .contact-errors {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media(max-width: 650px) {
            .contact-form-page {
                padding: 20px 15px;
            }

            .contact-form-grid {
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

        <section class="contact-form-page">

            <div class="contact-form-card">

                <h2>Add Contact Message</h2>
                <p>Enter the sender information and message details.</p>

                <?php if (!empty($errors)): ?>
                    <div class="contact-errors">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="contact-form-grid">

                        <div class="contact-form-group">
                            <label>Sender Name *</label>
                            <input type="text" name="name" required
                                value="<?php echo htmlspecialchars($name); ?>">
                        </div>

                        <div class="contact-form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required
                                value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="contact-form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone"
                                value="<?php echo htmlspecialchars($phone); ?>">
                        </div>

                        <div class="contact-form-group">
                            <label>Subject</label>
                            <input type="text" name="subject"
                                value="<?php echo htmlspecialchars($subject); ?>">
                        </div>

                        <div class="contact-form-group full">
                            <label>Message *</label>
                            <textarea name="message" required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>

                        <div class="contact-form-group full">
                            <label>Status</label>
                            <select name="status">
                                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo $status === 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </div>

                    </div>

                    <div class="contact-form-actions">
                        <button type="submit" class="contact-save-btn">
                            <i class="fas fa-save"></i> Save Message
                        </button>

                        <a href="contact_messages.php" class="contact-cancel-btn">
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

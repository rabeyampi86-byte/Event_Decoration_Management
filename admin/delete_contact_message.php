
<?php
require_once __DIR__ . '/../db.php';

$message_id = filter_input(INPUT_GET, 'message_id', FILTER_VALIDATE_INT);

if (!$message_id || $message_id < 1) {
    header("Location: contact_messages.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT message_id, name, email, subject
    FROM contact_messages
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $conn->prepare("
        DELETE FROM contact_messages
        WHERE message_id = ?
    ");

    $stmt->bind_param("i", $message_id);

    if ($stmt->execute()) {
        header("Location: contact_messages.php?deleted=1");
        exit;
    } else {
        $error = "Failed to delete contact message.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Contact Message</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .delete-contact-page {
            padding: 30px;
        }

        .delete-contact-card {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }

        .delete-contact-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            font-size: 25px;
        }

        .delete-contact-card h2 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        .delete-contact-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .delete-contact-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            color: #475569;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .delete-contact-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .delete-contact-actions button,
        .delete-contact-actions a {
            padding: 11px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .delete-confirm-btn {
            background: #dc2626;
            color: #fff;
        }

        .delete-cancel-btn {
            background: #f1f5f9;
            color: #475569;
        }

        .delete-contact-error {
            color: #b91c1c;
            background: #fee2e2;
            padding: 10px;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="delete-contact-page">

            <div class="delete-contact-card">

                <div class="delete-contact-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h2>Delete Contact Message?</h2>

                <p>
                    Are you sure you want to permanently delete this contact message?
                    This action cannot be undone.
                </p>

                <div class="delete-contact-details">

                    <strong>Message ID:</strong>
                    #<?php echo (int)$contact['message_id']; ?>
                    <br><br>

                    <strong>Name:</strong>
                    <?php echo htmlspecialchars($contact['name']); ?>
                    <br><br>

                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($contact['email']); ?>
                    <br><br>

                    <strong>Subject:</strong>
                    <?php echo htmlspecialchars($contact['subject'] ?: 'No Subject'); ?>

                </div>

                <?php if ($error !== ''): ?>
                    <div class="delete-contact-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="delete-contact-actions">

                        <button type="submit" class="delete-confirm-btn">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>

                        <a href="contact_messages.php" class="delete-cancel-btn">
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

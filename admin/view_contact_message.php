
<?php
require_once __DIR__ . '/../db.php';

$message_id = filter_input(INPUT_GET, 'message_id', FILTER_VALIDATE_INT);

if (!$message_id || $message_id < 1) {
    header("Location: contact_messages.php");
    exit;
}

// Mark as read if currently unread
$stmt = $conn->prepare("
    UPDATE contact_messages
    SET status = 'read'
    WHERE message_id = ? AND status = 'unread'
");
$stmt->bind_param("i", $message_id);
$stmt->execute();

// Fetch message
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Contact Message</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .view-contact-page {
            padding: 25px 30px;
        }

        .view-contact-card {
            max-width: 850px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .view-contact-card h2 {
            color: #1e293b;
            margin-top: 0;
        }

        .view-contact-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }

        .view-contact-item label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .view-contact-item strong {
            color: #1e293b;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .view-contact-message {
            padding: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            margin-top: 10px;
            color: #334155;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .view-contact-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .view-contact-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .view-contact-actions a {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .view-edit-btn {
            background: #2563eb;
            color: #fff;
        }

        .view-back-btn {
            background: #f1f5f9;
            color: #475569;
        }

        @media(max-width: 600px) {
            .view-contact-page {
                padding: 20px 15px;
            }

            .view-contact-info {
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

        <section class="view-contact-page">

            <div class="view-contact-card">

                <h2>
                    <i class="fas fa-envelope-open-text"></i>
                    Contact Message #<?php echo (int)$contact['message_id']; ?>
                </h2>

                <div class="view-contact-info">

                    <div class="view-contact-item">
                        <label>Sender Name</label>
                        <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                    </div>

                    <div class="view-contact-item">
                        <label>Email Address</label>
                        <strong><?php echo htmlspecialchars($contact['email']); ?></strong>
                    </div>

                    <div class="view-contact-item">
                        <label>Phone Number</label>
                        <strong><?php echo htmlspecialchars($contact['phone'] ?: 'N/A'); ?></strong>
                    </div>

                    <div class="view-contact-item">
                        <label>Subject</label>
                        <strong><?php echo htmlspecialchars($contact['subject'] ?: 'No Subject'); ?></strong>
                    </div>

                    <div class="view-contact-item">
                        <label>Status</label>
                        <span class="view-contact-status">
                            <?php echo htmlspecialchars($contact['status'] ?? 'unread'); ?>
                        </span>
                    </div>

                    <div class="view-contact-item">
                        <label>Received At</label>
                        <strong>
                            <?php echo date('d M Y, h:i A', strtotime($contact['created_at'])); ?>
                        </strong>
                    </div>

                </div>

                <div class="view-contact-item">
                    <label>Message</label>
                    <div class="view-contact-message"><?php echo htmlspecialchars($contact['message']); ?></div>
                </div>

<!-- Reply Message Section -->

<div class="contact-reply-section">

    <h3>
        <i class="fas fa-reply"></i>
        Reply to Message
    </h3>

    <p>
        Write your reply below. Clicking Send Reply will open your default email application.
    </p>

    <form id="replyForm">

        <div class="reply-field">
            <label>To</label>

            <input
                type="email"
                value="<?php echo htmlspecialchars($contact['email']); ?>"
                readonly
            >
        </div>

        <div class="reply-field">
            <label>Subject</label>

            <input
                type="text"
                id="replySubject"
                value="<?php
                    echo htmlspecialchars(
                        'Re: ' . ($contact['subject'] ?: 'Your Contact Message')
                    );
                ?>"
            >
        </div>

        <div class="reply-field">
            <label>Your Reply</label>

            <textarea
                id="replyBody"
                rows="7"
                placeholder="Write your reply here..."
                required
            ></textarea>
        </div>

        <div class="reply-actions">

            <button type="button" class="send-reply-btn" onclick="sendReply()">
                <i class="fas fa-paper-plane"></i>
                Send Reply
            </button>

        </div>

    </form>

</div>

<style>
    .contact-reply-section {
        margin-top: 30px;
        padding: 22px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
    }

    .contact-reply-section h3 {
        color: #1e293b;
        font-size: 18px;
        margin: 0 0 8px;
    }

    .contact-reply-section > p {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 20px;
    }

    .reply-field {
        margin-bottom: 17px;
    }

    .reply-field label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
    }

    .reply-field input,
    .reply-field textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .reply-field textarea {
        resize: vertical;
        min-height: 150px;
    }

    .send-reply-btn {
        padding: 11px 19px;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .send-reply-btn:hover {
        background: #1d4ed8;
    }
</style>

<script>
function sendReply() {

    const email = <?php echo json_encode($contact['email']); ?>;
    const subject = document.getElementById('replySubject').value.trim();
    const body = document.getElementById('replyBody').value.trim();

    if (body === '') {
        alert('Please write your reply first.');
        return;
    }

    const mailtoLink =
        'mailto:' + encodeURIComponent(email) +
        '?subject=' + encodeURIComponent(subject) +
        '&body=' + encodeURIComponent(body);

    window.location.href = mailtoLink;
}
</script>

<form method="POST" action="mark_contact_replied.php">

    <input
        type="hidden"
        name="message_id"
        value="<?php echo (int)$contact['message_id']; ?>"
    >

    <button type="submit" class="mark-replied-btn">
        <i class="fas fa-check-circle"></i>
        Mark as Replied
    </button>

</form>

<style>
    .mark-replied-btn {
        margin-top: 12px;
        padding: 10px 17px;
        background: #dcfce7;
        color: #15803d;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
</style>


                <div class="view-contact-actions">

                    <a href="edit_contact_message.php?message_id=<?php echo (int)$contact['message_id']; ?>"
                       class="view-edit-btn">
                        <i class="fas fa-edit"></i> Edit Status
                    </a>

                    <a href="contact_messages.php" class="view-back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Messages
                    </a>

                </div>

            </div>

        </section>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>

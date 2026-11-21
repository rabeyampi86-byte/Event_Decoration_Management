
<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Accept venue_id from GET or POST
$venue_id = filter_input(INPUT_GET, 'venue_id', FILTER_VALIDATE_INT);

if (!$venue_id) {
    $venue_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if (!$venue_id) {
    $venue_id = filter_input(INPUT_POST, 'venue_id', FILTER_VALIDATE_INT);
}

if (!$venue_id) {
    header("Location: venues.php");
    exit;
}

$error = '';
$venue = null;

// Fetch venue
$stmt = $conn->prepare("
    SELECT venue_id, name
    FROM venues
    WHERE venue_id = ?
");

$stmt->bind_param("i", $venue_id);
$stmt->execute();

$result = $stmt->get_result();
$venue = $result->fetch_assoc();
$stmt->close();

if (!$venue) {
    header("Location: venues.php?error=not_found");
    exit;
}

// Delete only after confirmation through POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check whether this venue is used in bookings
    $check = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM bookings
        WHERE venue_id = ?
    ");

    $check->bind_param("i", $venue_id);
    $check->execute();

    $bookingResult = $check->get_result();
    $bookingData = $bookingResult->fetch_assoc();
    $check->close();

    if ((int)$bookingData['total'] > 0) {

        $error = "This venue cannot be deleted because it is already linked to existing bookings.";

    } else {

        $delete = $conn->prepare("
            DELETE FROM venues
            WHERE venue_id = ?
        ");

        if ($delete) {
            $delete->bind_param("i", $venue_id);

            if ($delete->execute()) {
                $delete->close();
                header("Location: venues.php?success=deleted");
                exit;
            } else {
                $error = "Unable to delete venue. Please try again.";
            }

            $delete->close();

        } else {
            $error = "Database query preparation failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Venue | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .venue-delete-page {
            padding: 25px 30px 35px;
        }

        .venue-delete-card {
            max-width: 600px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .venue-delete-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .venue-delete-card h2 {
            color: #1e293b;
            font-size: 21px;
            margin-bottom: 12px;
        }

        .venue-delete-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .venue-delete-name {
            color: #1e293b;
            font-weight: 700;
        }

        .venue-delete-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .venue-confirm-delete,
        .venue-cancel-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .venue-confirm-delete {
            background: #dc2626;
            color: #fff;
        }

        .venue-confirm-delete:hover {
            background: #b91c1c;
        }

        .venue-cancel-delete {
            background: #f1f5f9;
            color: #475569;
        }

        .venue-delete-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            .venue-delete-page {
                padding: 20px 15px;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="venue-delete-page">

            <div class="venue-delete-card">

                <div class="venue-delete-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h2>Delete Venue</h2>

                <p>
                    Are you sure you want to delete
                    <span class="venue-delete-name">
                        <?php echo htmlspecialchars($venue['name']); ?>
                    </span>?
                </p>

                <p>
                    This action cannot be undone.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="venue-delete-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="venue-delete-actions">

                    <form method="POST">
                        <input
                            type="hidden"
                            name="venue_id"
                            value="<?php echo (int)$venue_id; ?>"
                        >

                        <button type="submit" class="venue-confirm-delete">
                            <i class="fas fa-trash"></i>
                            Confirm Delete
                        </button>
                    </form>

                    <a href="venues.php" class="venue-cancel-delete">
                        Cancel
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Only logged-in users can submit a booking
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Only accept form submissions
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php#packages");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$packageId = filter_input(
    INPUT_POST,
    "package_id",
    FILTER_VALIDATE_INT
);

$eventDate = trim($_POST["event_date"] ?? "");
$eventLocation = trim($_POST["event_location"] ?? "");
$guestCount = filter_input(
    INPUT_POST,
    "guest_count",
    FILTER_VALIDATE_INT
);
$specialRequest = trim($_POST["special_request"] ?? "");
$paymentMethod = $_POST["payment_method"] ?? "";
$transactionId = trim($_POST["transaction_id"] ?? "");

// Validate required fields
if (
    !$packageId ||
    $packageId < 1 ||
    $eventDate === "" ||
    $eventLocation === "" ||
    !$guestCount ||
    $guestCount < 1 ||
    $guestCount > 100000 ||
    !in_array($paymentMethod, ["bkash", "nagad"], true) ||
    $transactionId === ""
) {
    exit("Invalid information. Please go back and check the form.");
}

// Validate date format and prevent past dates
$dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $eventDate ||
    $eventDate < date("Y-m-d")
) {
    exit("Please select a valid event date.");
}

// Verify that the selected package is active
$packageStmt = $conn->prepare("
    SELECT decoration_packages.id
    FROM decoration_packages
    INNER JOIN event_types
        ON decoration_packages.event_type_id = event_types.id
    WHERE decoration_packages.id = ?
      AND decoration_packages.status = 'active'
      AND event_types.status = 'active'
");

$packageStmt->bind_param("i", $packageId);
$packageStmt->execute();

$packageResult = $packageStmt->get_result();

if ($packageResult->num_rows !== 1) {
    exit("This package is unavailable. Please select another package.");
}

// Save booking with pending status
$insertStmt = $conn->prepare("
    INSERT INTO bookings (
        user_id,
        package_id,
        event_date,
        event_location,
        guest_count,
        special_request,
        payment_method,
        transaction_id,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

$insertStmt->bind_param(
    "iississs",
    $userId,
    $packageId,
    $eventDate,
    $eventLocation,
    $guestCount,
    $specialRequest,
    $paymentMethod,
    $transactionId
);

if ($insertStmt->execute()) {
    $bookingId = $conn->insert_id;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Submitted | DecorEase</title>

        <?php include 'includes/nav_style.php'; ?>

        <style>
            body {
                margin: 0;
                background-color: #fff0f4;
                font-family: Arial, sans-serif;
            }

            .success-container {
                max-width: 600px;
                margin: 60px auto;
                padding: 35px;
                background-color: #ffffff;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .success-container h1 {
                color: #57283c;
            }

            .success-container p {
                color: #555555;
                line-height: 1.7;
            }

            .home-btn {
                display: inline-block;
                margin-top: 15px;
                padding: 12px 22px;
                background-color: #b84a72;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
            }

            .home-btn:hover {
                background-color: #913653;
            }
        </style>
    </head>

    <body>

    <?php include 'includes/navber.php'; ?>

    <div class="success-container">
        <h1>Booking Request Submitted!</h1>

        <p>
            Thank you,
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.
        </p>

        <p>
            Your Booking ID is:
            <strong>#<?php echo (int) $bookingId; ?></strong>
        </p>

        <p>
            Your booking status is
            <strong>Pending</strong>.
        </p>

        <p>
            Payment method:
            <strong>
                <?php
                echo $paymentMethod === "bkash" ? "bKash" : "Nagad";
                ?>
            </strong>
        </p>

        <p>
            Your transaction details still need to be verified.
            This page does not confirm that a payment was received.
        </p>

        <a href="index.php" class="home-btn">
            Back to Home
        </a>
    </div>

    </body>
    </html>
    <?php
} else {
    exit("Could not save your booking. Please try again.");
}

$insertStmt->close();
$packageStmt->close();
$conn->close();
?>

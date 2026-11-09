
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

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php#packages");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$packageId = filter_input(INPUT_POST, "package_id", FILTER_VALIDATE_INT);

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

// Validate form information
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

// Validate event date
$dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $eventDate ||
    $eventDate < date("Y-m-d")
) {
    exit("Please select a valid event date.");
}

// 1. Verify selected package
$packageStmt = $conn->prepare("
    SELECT id, package_name, price
    FROM decoration_packages
    WHERE id = ?
      AND status = 'active'
");

$packageStmt->bind_param("i", $packageId);
$packageStmt->execute();

$packageResult = $packageStmt->get_result();

if ($packageResult->num_rows !== 1) {
    exit("This package is unavailable. Please select another package.");
}

$package = $packageResult->fetch_assoc();

$quotedTotal = (float) $package["price"];
$packageStmt->close();

// 2. Get or create customer profile
$customerStmt = $conn->prepare("
    SELECT customer_id
    FROM customers
    WHERE user_id = ?
    LIMIT 1
");

$customerStmt->bind_param("i", $userId);
$customerStmt->execute();

$customerResult = $customerStmt->get_result();

if ($customerResult->num_rows === 1) {

    $customerData = $customerResult->fetch_assoc();
    $customerId = (int) $customerData["customer_id"];

} else {

    $createCustomer = $conn->prepare("
        INSERT INTO customers (user_id)
        VALUES (?)
    ");

    $createCustomer->bind_param("i", $userId);
    $createCustomer->execute();

    $customerId = (int) $conn->insert_id;

    $createCustomer->close();
}

$customerStmt->close();

// 3. Set event time
$startTime = "10:00:00";
$endTime = "18:00:00";

// 4. Save booking
// venue_id is NULL because customer provides their own event location

$insertStmt = $conn->prepare("
    INSERT INTO bookings (
        customer_id,
        package_id,
        venue_id,
        event_date,
        start_time,
        end_time,
        guest_count,
        event_location,
        special_request,
        payment_method,
        transaction_id,
        status,
        quoted_total
    )
    VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
");

$insertStmt->bind_param(
    "iisssissssd",
    $customerId,
    $packageId,
    $eventDate,
    $startTime,
    $endTime,
    $guestCount,
    $eventLocation,
    $specialRequest,
    $paymentMethod,
    $transactionId,
    $quotedTotal
);

// Execute booking
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
            Selected Package:
            <strong>
                <?php echo htmlspecialchars($package["package_name"]); ?>
            </strong>
        </p>

        <p>
            Package Price:
            <strong>
                ৳ <?php echo number_format($quotedTotal, 2); ?>
            </strong>
        </p>

        <p>
            Your booking status is
            <strong>Pending</strong>.
        </p>

        <p>
            Payment Method:
            <strong>
                <?php echo $paymentMethod === "bkash" ? "bKash" : "Nagad"; ?>
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
$conn->close();
?>

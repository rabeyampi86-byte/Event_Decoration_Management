
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Only logged-in customers can submit a booking
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php#packages");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Get form information
$packageId = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);

$eventDate = trim($_POST['event_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');
$endTime = trim($_POST['end_time'] ?? '');
$eventLocation = trim($_POST['event_location'] ?? '');

$guestCount = filter_input(
    INPUT_POST,
    'guest_count',
    FILTER_VALIDATE_INT
);

$specialRequest = trim($_POST['special_request'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? '';
$transactionReference = trim($_POST['transaction_reference'] ?? '');

// Validate required information
if (
    !$packageId ||
    $packageId < 1 ||
    $eventDate === '' ||
    $startTime === '' ||
    $endTime === '' ||
    $eventLocation === '' ||
    !$guestCount ||
    $guestCount < 1 ||
    $guestCount > 100000 ||
    !in_array($paymentMethod, ['bkash', 'nagad', 'cash'], true) ||
    strlen($transactionReference) > 191
) {
    exit("Invalid information. Please go back and check the form.");
}

// Validate event date
$dateObject = DateTime::createFromFormat('!Y-m-d', $eventDate);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $eventDate ||
    $eventDate < date('Y-m-d')
) {
    exit("Please select a valid event date.");
}

// Validate event times
$startObject = DateTime::createFromFormat('!H:i', $startTime);
$endObject = DateTime::createFromFormat('!H:i', $endTime);

if (
    !$startObject ||
    !$endObject ||
    $startObject->format('H:i') !== $startTime ||
    $endObject->format('H:i') !== $endTime ||
    $endTime <= $startTime
) {
    exit("Please select valid event start and end times.");
}

// Convert time to MySQL format
$startTime .= ':00';
$endTime .= ':00';

// Get package information
$packageStmt = $conn->prepare("
    SELECT
        p.package_id,
        p.package_name,
        p.base_price,
        p.description,
        p.short_description
    FROM packages p
    INNER JOIN event_categories ec
        ON p.category_id = ec.category_id
    WHERE p.package_id = ?
      AND p.status = 'active'
      AND ec.status = 'active'
    LIMIT 1
");

if (!$packageStmt) {
    exit("Unable to verify the selected package.");
}

$packageStmt->bind_param("i", $packageId);
$packageStmt->execute();

$packageResult = $packageStmt->get_result();
$package = $packageResult->fetch_assoc();

$packageStmt->close();

if (!$package) {
    exit("This package is unavailable. Please select another package.");
}

$quotedTotal = (float) $package['base_price'];

$packageDescription = !empty($package['description'])
    ? $package['description']
    : ($package['short_description'] ?? null);

// Empty transaction reference will be stored as NULL
$transactionReference = $transactionReference !== ''
    ? $transactionReference
    : null;

try {

    // Start database transaction
    $conn->begin_transaction();

    // Find existing customer profile
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
        $customerId = (int) $customerData['customer_id'];

    } else {

        // Create customer profile if it does not exist
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

    // Insert booking
    $insertStmt = $conn->prepare("
        INSERT INTO bookings (
            customer_id,
            package_id,
            venue_id,
            package_name_snapshot,
            package_price_snapshot,
            package_description_snapshot,
            event_date,
            start_time,
            end_time,
            guest_count,
            event_location,
            special_request,
            quoted_total,
            status
        )
        VALUES (
            ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
        )
    ");

    $packageName = $package['package_name'];

    $insertStmt->bind_param(
        "iisdssssissd",
        $customerId,
        $packageId,
        $packageName,
        $quotedTotal,
        $packageDescription,
        $eventDate,
        $startTime,
        $endTime,
        $guestCount,
        $eventLocation,
        $specialRequest,
        $quotedTotal
    );

    $insertStmt->execute();

    $bookingId = (int) $conn->insert_id;

    $insertStmt->close();

    // Insert payment record
    $paymentStmt = $conn->prepare("
        INSERT INTO payments (
            booking_id,
            amount,
            method,
            transaction_reference,
            status
        )
        VALUES (?, ?, ?, ?, 'pending')
    ");

    $paymentStmt->bind_param(
        "idss",
        $bookingId,
        $quotedTotal,
        $paymentMethod,
        $transactionReference
    );

    $paymentStmt->execute();

    $paymentStmt->close();

    // Save booking and payment together
    $conn->commit();

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Booking/Payment Error: " . $e->getMessage());

    exit("Could not save your booking and payment record. Please try again.");

}

// Booking successfully saved
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
            background: #fffaf7;
            font-family: 'DM Sans', sans-serif;
            color: #45343b;
        }

        .success-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 35px;
            background: #ffffff;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(83, 38, 58, 0.10);
        }

        .success-container h1 {
            color: #53263a;
            font-family: 'Playfair Display', serif;
        }

        .success-container p {
            color: #66565d;
            line-height: 1.8;
        }

        .success-container strong {
            color: #6d314b;
        }

        .home-btn {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 25px;
            background: #6d314b;
            color: #ffffff;
            text-decoration: none;
            border-radius: 7px;
        }

        .home-btn:hover {
            background: #53263a;
        }
    </style>
</head>

<body>

<?php include 'includes/navber.php'; ?>

<div class="success-container">

    <h1>Booking Request Submitted!</h1>

    <p>
        Thank you,
        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>.
    </p>

    <p>
        Your Booking ID:
        <strong>#<?= $bookingId ?></strong>
    </p>

    <p>
        Selected Package:
        <strong>
            <?= htmlspecialchars($packageName) ?>
        </strong>
    </p>

    <p>
        Event Date:
        <strong><?= htmlspecialchars($eventDate) ?></strong>
    </p>

    <p>
        Package Price:
        <strong>
            ৳ <?= number_format($quotedTotal, 2) ?>
        </strong>
    </p>

    <p>
        Payment Method:
        <strong>
            <?= htmlspecialchars(ucfirst($paymentMethod)) ?>
        </strong>
    </p>

    <p>
        Booking Status:
        <strong>Pending</strong>
    </p>

    <p>
        Your booking request has been received.
        Payment verification will be handled separately.
    </p>

    <a href="index.php" class="home-btn">
        Back to Home
    </a>

</div>

</body>
</html>

<?php
$conn->close();
?>

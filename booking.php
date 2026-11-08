<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Only logged-in customers can book
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Get selected package ID
$packageId = filter_input(
    INPUT_GET,
    'package_id',
    FILTER_VALIDATE_INT
);

if (!$packageId || $packageId < 1) {
    exit("Invalid package selected.");
}

// Get package details
$stmt = $conn->prepare("
    SELECT
        decoration_packages.id,
        decoration_packages.package_name,
        decoration_packages.description,
        decoration_packages.price,
        event_types.type_name
    FROM decoration_packages
    INNER JOIN event_types
        ON decoration_packages.event_type_id = event_types.id
    WHERE decoration_packages.id = ?
      AND decoration_packages.status = 'active'
      AND event_types.status = 'active'
");

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    exit("This package is not available.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book a Package | DecorEase</title>

    <?php include 'includes/nav_style.php'; ?>

    <style>
        body {
            margin: 0;
            background-color: #fff0f4;
            font-family: Arial, sans-serif;
        }

        .booking-container {
            max-width: 650px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .booking-container h1 {
            text-align: center;
            color: #57283c;
            margin-bottom: 10px;
        }

        .package-summary {
            background-color: #fff0f4;
            padding: 18px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .package-summary h3 {
            color: #b84a72;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #333333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 5px;
            background-color: #b84a72;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #913653;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #b84a72;
            text-decoration: none;
        }

        @media (max-width: 700px) {
            .booking-container {
                margin: 25px 15px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<?php include 'includes/navber.php'; ?>

<div class="booking-container">

    <h1>Book Your Event</h1>

    <p style="text-align:center; color:#777;">
        Fill in the details to request your decoration booking.
    </p>

    <div class="package-summary">
        <h3>
            <?php echo htmlspecialchars($package['package_name']); ?>
        </h3>

        <p>
            Event Type:
            <?php echo htmlspecialchars($package['type_name']); ?>
        </p>

        <p>
            <?php echo htmlspecialchars($package['description'] ?? ''); ?>
        </p>

        <strong style="color:#b84a72; font-size:20px;">
            ৳ <?php echo number_format((float)$package['price'], 2); ?>
        </strong>
    </div>

    <form action="save_booking.php" method="POST">

        <input
            type="hidden"
            name="package_id"
            value="<?php echo (int)$package['id']; ?>"
        >

        <div class="form-group">
            <label for="event_date">Event Date</label>
            <input
                type="date"
                id="event_date"
                name="event_date"
                min="<?php echo date('Y-m-d'); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="event_location">Event Location</label>
            <input
                type="text"
                id="event_location"
                name="event_location"
                maxlength="255"
                placeholder="Enter event venue or address"
                required
            >
        </div>

        <div class="form-group">
            <label for="guest_count">Number of Guests</label>
            <input
                type="number"
                id="guest_count"
                name="guest_count"
                min="1"
                max="100000"
                placeholder="Enter expected guests"
                required
            >
        </div>

        <div class="form-group">
            <label for="special_request">
                Special Request (Optional)
            </label>
            <textarea
                id="special_request"
                name="special_request"
                placeholder="Describe any special decoration requirements"
            ></textarea>
        </div>
        
<div class="form-group">
    <label for="payment_method">Payment Method</label>

    <select
        id="payment_method"
        name="payment_method"
        required
        style="
            width: 100%;
            padding: 11px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            background-color: #ffffff;
            font-size: 15px;
        "
    >
        <option value="">-- Select Payment Method --</option>
        <option value="bkash">bKash</option>
        <option value="nagad">Nagad</option>
    </select>
</div>

<div class="form-group">
    <label for="transaction_id">
        Transaction ID
    </label>

    <input
        type="text"
        id="transaction_id"
        name="transaction_id"
        maxlength="100"
        placeholder="Enter transaction ID after payment"
        required
    >
</div>


        <button type="submit" class="submit-btn">
            Submit Booking Request
        </button>

    </form>

    <a href="index.php#packages" class="back-link">
        ← Back to Packages
    </a>

</div>

</body>
</html>

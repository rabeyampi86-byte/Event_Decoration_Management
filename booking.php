
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Only logged-in customers can book
if (!isset($_SESSION['user_id'])) {
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
        p.package_id,
        p.package_name,
        p.description,
        p.short_description,
        p.base_price,
        p.guest_capacity,
        ec.name AS category_name
    FROM packages p
    INNER JOIN event_categories ec
        ON p.category_id = ec.category_id
    WHERE p.package_id = ?
      AND p.status = 'active'
      AND ec.status = 'active'
    LIMIT 1
");

if (!$stmt) {
    exit("Unable to load package details.");
}

$stmt->bind_param("i", $packageId);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();

$stmt->close();

if (!$package) {
    exit("This package is not available.");
}

$packageDescription = !empty($package['description'])
    ? $package['description']
    : $package['short_description'];
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
            background-color: #fffaf7;
            font-family: 'DM Sans', sans-serif;
            color: #45343b;
        }

        .booking-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 35px;
            background-color: #ffffff;
            border-radius: 14px;
            box-shadow: 0 5px 25px rgba(83, 38, 58, 0.10);
        }

        .booking-container h1 {
            text-align: center;
            color: #53263a;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .booking-intro {
            text-align: center;
            color: #83747a;
            margin-bottom: 25px;
        }

        .package-summary {
            background-color: #fff0f4;
            padding: 22px;
            border-radius: 10px;
            margin: 25px 0;
        }

        .package-summary h3 {
            color: #6d314b;
            margin-top: 0;
            font-family: 'Playfair Display', serif;
            font-size: 23px;
        }

        .package-category {
            color: #c0547b;
            font-weight: 600;
        }

        .package-price {
            color: #b84a72;
            font-size: 22px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #45343b;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ded3d8;
            border-radius: 7px;
            box-sizing: border-box;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c0547b;
            box-shadow: 0 0 0 3px rgba(192, 84, 123, 0.10);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .time-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background-color: #6d314b;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background .3s;
        }

        .submit-btn:hover {
            background-color: #53263a;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #b84a72;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 700px) {
            .booking-container {
                margin: 25px 15px;
                padding: 22px;
            }

            .time-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>

<body>

<?php include 'includes/navber.php'; ?>

<div class="booking-container">

    <h1>Book Your Event</h1>

    <p class="booking-intro">
        Fill in the details to request your decoration booking.
    </p>

    <!-- Selected Package Details -->
    <div class="package-summary">

        <h3>
            <?= htmlspecialchars($package['package_name']) ?>
        </h3>

        <p class="package-category">
            Event Category:
            <?= htmlspecialchars($package['category_name']) ?>
        </p>

        <!-- <p>
            <?= nl2br(htmlspecialchars($packageDescription ?? '')) ?>
        </p> -->

        <?php if (!empty($package['guest_capacity'])): ?>
            <p>
                Guest Capacity:
                <?= (int)$package['guest_capacity'] ?> Guests
            </p>
        <?php endif; ?>

        <div class="package-price">
            ৳ <?= number_format((float)$package['base_price'], 2) ?>
        </div>

    </div>

    <!-- Booking Form -->
    <form action="save_booking.php" method="POST">

        <input
            type="hidden"
            name="package_id"
            value="<?= (int)$package['package_id'] ?>"
        >

        <div class="form-group">
            <label for="event_date">Event Date</label>

            <input
                type="date"
                id="event_date"
                name="event_date"
                min="<?= date('Y-m-d') ?>"
                required
            >
        </div>

        <div class="time-row">

            <div class="form-group">
                <label for="start_time">Event Start Time</label>

                <input
                    type="time"
                    id="start_time"
                    name="start_time"
                    required
                >
            </div>

            <div class="form-group">
                <label for="end_time">Event End Time</label>

                <input
                    type="time"
                    id="end_time"
                    name="end_time"
                    required
                >
            </div>

        </div>

        <div class="form-group">
            <label for="event_location">Event Location</label>

            <textarea
                id="event_location"
                name="event_location"
                placeholder="Enter event venue or complete address"
                required
            ></textarea>
        </div>

        <div class="form-group">
            <label for="guest_count">Number of Guests</label>

            <input
                type="number"
                id="guest_count"
                name="guest_count"
                min="1"
                max="100000"
                placeholder="Enter expected number of guests"
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
            >
                <option value="">-- Select Payment Method --</option>
                <option value="bkash">bKash</option>
                <option value="nagad">Nagad</option>
                <option value="cash">Cash</option>
            </select>
        </div>

        <div class="form-group">
            <label for="transaction_reference">
                Transaction Reference (Optional)
            </label>

            <input
                type="text"
                id="transaction_reference"
                name="transaction_reference"
                maxlength="191"
                placeholder="Enter transaction reference if already paid"
            >
        </div>

        <button type="submit" class="submit-btn">
            Submit Booking Request
        </button>

    </form>

    <a href="index.php#packages" class="back-link">
        &larr; Back to Packages
    </a>

</div>

</body>
</html>

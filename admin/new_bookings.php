
<?php
include_once 'layout-start.php';

$message = '';
$messageType = '';

/* CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Load Customers */
$customers = $conn->query("
    SELECT
        c.customer_id,
        u.full_name,
        u.email
    FROM customers c
    INNER JOIN users u ON u.user_id = c.user_id
    WHERE u.role = 'customer'
    ORDER BY u.full_name ASC
");

/* Load Packages */
$packages = $conn->query("
    SELECT id, package_name, price
    FROM decoration_packages
    ORDER BY package_name ASC
");

/* Load Venues */
$venues = $conn->query("
    SELECT venue_id, name, address, capacity
    FROM venues
    ORDER BY name ASC
");


/* =====================================
   Save New Booking
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $message = "Invalid request. Please try again.";
        $messageType = "error";

    } else {

        $customerId = filter_input(
            INPUT_POST,
            'customer_id',
            FILTER_VALIDATE_INT
        );

        $packageId = filter_input(
            INPUT_POST,
            'package_id',
            FILTER_VALIDATE_INT
        );

        $venueId = !empty($_POST['venue_id'])
            ? filter_var($_POST['venue_id'], FILTER_VALIDATE_INT)
            : null;

        $eventDate = trim($_POST['event_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');

        $guestCount = filter_input(
            INPUT_POST,
            'guest_count',
            FILTER_VALIDATE_INT
        );

        $quotedTotal = filter_input(
            INPUT_POST,
            'quoted_total',
            FILTER_VALIDATE_FLOAT
        );

        $paymentMethod = $_POST['payment_method'] ?? '';
        $transactionId = trim($_POST['transaction_id'] ?? '');

        $eventLocation = trim($_POST['event_location'] ?? '');
        $specialRequest = trim($_POST['special_request'] ?? '');

        try {

            /* Validate Required Fields */

            if (
                !$customerId ||
                !$packageId ||
                !$eventDate ||
                !$startTime ||
                !$endTime ||
                !$guestCount ||
                $quotedTotal === false ||
                $quotedTotal === null ||
                $quotedTotal < 0 ||
                !in_array($paymentMethod, ['cash', 'bkash', 'nagad'], true)
            ) {
                throw new Exception("Please fill in all required fields correctly.");
            }

            if ($guestCount < 1) {
                throw new Exception("Guest count must be at least 1.");
            }

            if (strtotime($endTime) <= strtotime($startTime)) {
                throw new Exception("End time must be later than start time.");
            }

            if (strlen($transactionId) > 191) {
                throw new Exception("Transaction ID is too long.");
            }

            /* Verify Customer */

            $customerCheck = $conn->prepare("
                SELECT customer_id
                FROM customers
                WHERE customer_id = ?
            ");

            $customerCheck->bind_param("i", $customerId);
            $customerCheck->execute();

            if ($customerCheck->get_result()->num_rows === 0) {
                throw new Exception("Selected customer was not found.");
            }

            $customerCheck->close();

            /* Verify Package */

            $packageCheck = $conn->prepare("
                SELECT id
                FROM decoration_packages
                WHERE id = ?
            ");

            $packageCheck->bind_param("i", $packageId);
            $packageCheck->execute();

            if ($packageCheck->get_result()->num_rows === 0) {
                throw new Exception("Selected package was not found.");
            }

            $packageCheck->close();

            /* Verify Venue if selected */

            if ($venueId !== null) {

                if (!$venueId) {
                    throw new Exception("Invalid venue selected.");
                }

                $venueCheck = $conn->prepare("
                    SELECT venue_id
                    FROM venues
                    WHERE venue_id = ?
                ");

                $venueCheck->bind_param("i", $venueId);
                $venueCheck->execute();

                if ($venueCheck->get_result()->num_rows === 0) {
                    throw new Exception("Selected venue was not found.");
                }

                $venueCheck->close();
            }

            /* Prepare Optional Values */

            $transactionValue = $transactionId !== ''
                ? $transactionId
                : null;

            $locationValue = $eventLocation !== ''
                ? $eventLocation
                : null;

            $requestValue = $specialRequest !== ''
                ? $specialRequest
                : null;

            $bookingStatus = 'pending';

            /* Start Database Transaction */

            $conn->begin_transaction();

            /* Insert Booking */

            $bookingStmt = $conn->prepare("
                INSERT INTO bookings (
                    customer_id,
                    package_id,
                    venue_id,
                    event_date,
                    start_time,
                    end_time,
                    guest_count,
                    status,
                    quoted_total,
                    payment_method,
                    transaction_id,
                    event_location,
                    special_request
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $bookingStmt->bind_param(
                "iiisssisdssss",
                $customerId,
                $packageId,
                $venueId,
                $eventDate,
                $startTime,
                $endTime,
                $guestCount,
                $bookingStatus,
                $quotedTotal,
                $paymentMethod,
                $transactionValue,
                $locationValue,
                $requestValue
            );

            $bookingStmt->execute();

            $bookingId = $conn->insert_id;

            $bookingStmt->close();

            /* Insert Pending Payment Record */

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
                $transactionValue
            );

            $paymentStmt->execute();
            $paymentStmt->close();

            /* Commit Both Records */

            $conn->commit();

            echo "<script>
                window.location.href = 'bookings.php?added=1';
            </script>";

            exit;

        } catch (Throwable $e) {

            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                // No additional action required.
            }

            $message = $e instanceof Exception
                ? $e->getMessage()
                : "Unable to save booking. Please try again.";

            $messageType = "error";
        }
    }
}
?>

<style>
.new-booking-page {
    padding-bottom: 30px;
}

.booking-form-panel {
    background: #131313;
    border: 1px solid #292929;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
}

.booking-form-panel h3 {
    color: #f6f8fa;
    font-size: 18px;
    margin: 0 0 22px;
}

.booking-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.booking-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.booking-form-group.full-width {
    grid-column: 1 / -1;
}

.booking-form-group label {
    color: #d1d5db;
    font-size: 14px;
    font-weight: 500;
}

.booking-form-group input,
.booking-form-group select,
.booking-form-group textarea {
    width: 100%;
    padding: 12px 14px;
    background: #202020;
    border: 1px solid #383838;
    border-radius: 7px;
    color: #f5f5f5;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}

.booking-form-group select option {
    background: #202020;
    color: #fff;
}

.booking-form-group input:focus,
.booking-form-group select:focus,
.booking-form-group textarea:focus {
    border-color: #16a34a;
}

.booking-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.booking-form-group input::placeholder,
.booking-form-group textarea::placeholder {
    color: #777;
}

.booking-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
}

.booking-submit-btn,
.booking-cancel-btn {
    padding: 11px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.booking-submit-btn {
    background: #16a34a;
    color: white;
}

.booking-cancel-btn {
    background: #be4e4e;
    color: #e5e7eb;
}

.booking-submit-btn:hover {
    background: #127035;
}

.booking-alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin-top: 18px;
    font-size: 14px;
}

.booking-alert.error {
    background: #351616;
    color: #fca5a5;
}

.booking-note {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 5px;
}

@media(max-width: 650px) {
    .booking-form-grid {
        grid-template-columns: 1fr;
    }

    .booking-form-group.full-width {
        grid-column: auto;
    }

    .booking-form-panel {
        padding: 16px;
    }
}
</style>


<div class="new-booking-page">

    <section class="page-heading">
        <div>
            <h2>New Booking</h2>
            <p>Create a booking manually for an existing customer.</p>
        </div>
    </section>

    <?php if ($message): ?>

        <div class="booking-alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <div class="booking-form-panel">

        <h3>Booking Information</h3>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <div class="booking-form-grid">

                <!-- Customer -->

                <div class="booking-form-group">
                    <label>Customer *</label>

                    <select name="customer_id" required>

                        <option value="">Select Customer</option>

                        <?php if ($customers): ?>
                            <?php while ($customer = $customers->fetch_assoc()): ?>

                                <option
                                    value="<?= (int)$customer['customer_id'] ?>"
                                    <?= (($_POST['customer_id'] ?? '') == $customer['customer_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($customer['full_name']) ?>
                                    (<?= htmlspecialchars($customer['email']) ?>)
                                </option>

                            <?php endwhile; ?>
                        <?php endif; ?>

                    </select>
                </div>


                <!-- Package -->

                <div class="booking-form-group">
                    <label>Event Package *</label>

                    <select name="package_id" id="packageSelect" required>

                        <option value="">Select Package</option>

                        <?php if ($packages): ?>
                            <?php while ($package = $packages->fetch_assoc()): ?>

                                <option
                                    value="<?= (int)$package['id'] ?>"
                                    data-price="<?= htmlspecialchars($package['price']) ?>"
                                    <?= (($_POST['package_id'] ?? '') == $package['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($package['package_name']) ?>
                                    - <?= money($package['price']) ?>
                                </option>

                            <?php endwhile; ?>
                        <?php endif; ?>

                    </select>
                </div>


                <!-- Venue -->

                <div class="booking-form-group">
                    <label>Venue (Optional)</label>

                    <select name="venue_id">

                        <option value="">Select Venue</option>

                        <?php if ($venues): ?>
                            <?php while ($venue = $venues->fetch_assoc()): ?>

                                <option
                                    value="<?= (int)$venue['venue_id'] ?>"
                                    <?= (($_POST['venue_id'] ?? '') == $venue['venue_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($venue['name']) ?>
                                    (Capacity: <?= (int)$venue['capacity'] ?>)
                                </option>

                            <?php endwhile; ?>
                        <?php endif; ?>

                    </select>
                </div>


                <!-- Event Date -->

                <div class="booking-form-group">
                    <label>Event Date *</label>

                    <input
                        type="date"
                        name="event_date"
                        value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>"
                        required
                    >
                </div>


                <!-- Start Time -->

                <div class="booking-form-group">
                    <label>Start Time *</label>

                    <input
                        type="time"
                        name="start_time"
                        value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>"
                        required
                    >
                </div>


                <!-- End Time -->

                <div class="booking-form-group">
                    <label>End Time *</label>

                    <input
                        type="time"
                        name="end_time"
                        value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>"
                        required
                    >
                </div>


                <!-- Guest Count -->

                <div class="booking-form-group">
                    <label>Guest Count *</label>

                    <input
                        type="number"
                        name="guest_count"
                        min="1"
                        placeholder="Enter number of guests"
                        value="<?= htmlspecialchars($_POST['guest_count'] ?? '') ?>"
                        required
                    >
                </div>


                <!-- Quoted Total -->

                <div class="booking-form-group">
                    <label>Quoted Total (৳) *</label>

                    <input
                        type="number"
                        name="quoted_total"
                        id="quotedTotal"
                        min="0"
                        step="0.01"
                        placeholder="Enter total amount"
                        value="<?= htmlspecialchars($_POST['quoted_total'] ?? '') ?>"
                        required
                    >

                    <small class="booking-note">
                        Automatically filled from the selected package. You can edit it.
                    </small>
                </div>


                <!-- Payment Method -->

                <div class="booking-form-group">
                    <label>Payment Method *</label>

                    <select name="payment_method" required>

                        <option value="">Select Payment Method</option>

                        <option value="cash"
                            <?= (($_POST['payment_method'] ?? '') === 'cash') ? 'selected' : '' ?>>
                            Cash
                        </option>

                        <option value="bkash"
                            <?= (($_POST['payment_method'] ?? '') === 'bkash') ? 'selected' : '' ?>>
                            bKash
                        </option>

                        <option value="nagad"
                            <?= (($_POST['payment_method'] ?? '') === 'nagad') ? 'selected' : '' ?>>
                            Nagad
                        </option>

                    </select>
                </div>


                <!-- Transaction ID -->

                <div class="booking-form-group">
                    <label>Transaction ID (Optional)</label>

                    <input
                        type="text"
                        name="transaction_id"
                        placeholder="Enter transaction ID"
                        value="<?= htmlspecialchars($_POST['transaction_id'] ?? '') ?>"
                    >
                </div>


                <!-- Event Location -->

                <div class="booking-form-group full-width">
                    <label>Event Location</label>

                    <textarea
                        name="event_location"
                        placeholder="Enter event location"
                    ><?= htmlspecialchars($_POST['event_location'] ?? '') ?></textarea>
                </div>


                <!-- Special Request -->

                <div class="booking-form-group full-width">
                    <label>Special Request</label>

                    <textarea
                        name="special_request"
                        placeholder="Enter any special requirements"
                    ><?= htmlspecialchars($_POST['special_request'] ?? '') ?></textarea>
                </div>

            </div>


            <!-- Form Actions -->

            <div class="booking-form-actions">

                <a href="bookings.php" class="booking-cancel-btn">
                    Cancel
                </a>

                <button type="submit" class="booking-submit-btn">
                    Add Booking
                </button>

            </div>

        </form>

    </div>

</div>


<script>
/* Auto-fill Package Price */

document.getElementById('packageSelect').addEventListener('change', function () {

    const selectedOption = this.options[this.selectedIndex];

    const price = selectedOption.getAttribute('data-price');

    if (price !== null) {
        document.getElementById('quotedTotal').value = price;
    }

});
</script>

<?php include 'layout-end.php'; ?>

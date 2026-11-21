
<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';

$name = '';
$address = '';
$capacity = '';
$contact_phone = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        $error = "Venue name is required.";
    } elseif ($capacity !== '' && (!ctype_digit($capacity) || (int)$capacity < 0)) {
        $error = "Capacity must be a valid positive number.";
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = "Invalid venue status.";
    } else {

        $capacityValue = ($capacity === '') ? null : (int)$capacity;

        $stmt = $conn->prepare("
            INSERT INTO venues
            (name, address, capacity, contact_phone, status)
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param(
                "ssiss",
                $name,
                $address,
                $capacityValue,
                $contact_phone,
                $status
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: venues.php?success=added");
                exit;
            } else {
                $error = "Unable to add venue. Please try again.";
            }

            $stmt->close();
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
    <title>Add Venue | Admin Panel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="includes/css/admin.css">

    <style>
        .venue-form-page {
            padding: 25px 30px 35px;
        }

        .venue-form-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px;
            max-width: 850px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .venue-form-title {
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .venue-form-description {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .venue-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .venue-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .venue-form-group.full-width {
            grid-column: 1 / -1;
        }

        .venue-form-group label {
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .venue-form-group input,
        .venue-form-group textarea,
        .venue-form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            color: #334155;
            outline: none;
            background: #fff;
            box-sizing: border-box;
        }

        .venue-form-group input:focus,
        .venue-form-group textarea:focus,
        .venue-form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .venue-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .venue-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .venue-submit-btn,
        .venue-cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .venue-submit-btn {
            background: #2563eb;
            color: #fff;
        }

        .venue-submit-btn:hover {
            background: #1d4ed8;
        }

        .venue-cancel-btn {
            background: #f1f5f9;
            color: #475569;
        }

        .venue-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            .venue-form-page {
                padding: 20px 15px;
            }

            .venue-form-grid {
                grid-template-columns: 1fr;
            }

            .venue-form-group.full-width {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="venue-form-page">

            <div class="venue-form-card">

                <h2 class="venue-form-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Add New Venue
                </h2>

                <p class="venue-form-description">
                    Enter the information below to add a new event venue.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="venue-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="venue-form-grid">

                        <div class="venue-form-group full-width">
                            <label for="name">Venue Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?php echo htmlspecialchars($name); ?>"
                                placeholder="Enter venue name"
                                required
                            >
                        </div>

                        <div class="venue-form-group full-width">
                            <label for="address">Address</label>
                            <textarea
                                id="address"
                                name="address"
                                placeholder="Enter venue address"
                            ><?php echo htmlspecialchars($address); ?></textarea>
                        </div>

                        <div class="venue-form-group">
                            <label for="capacity">Guest Capacity</label>
                            <input
                                type="number"
                                id="capacity"
                                name="capacity"
                                min="0"
                                value="<?php echo htmlspecialchars($capacity); ?>"
                                placeholder="e.g. 500"
                            >
                        </div>

                        <div class="venue-form-group">
                            <label for="contact_phone">Contact Phone</label>
                            <input
                                type="text"
                                id="contact_phone"
                                name="contact_phone"
                                value="<?php echo htmlspecialchars($contact_phone); ?>"
                                placeholder="Enter contact number"
                            >
                        </div>

                        <div class="venue-form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                    Active
                                </option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="venue-form-actions">

                        <button type="submit" class="venue-submit-btn">
                            <i class="fas fa-save"></i>
                            Save Venue
                        </button>

                        <a href="venues.php" class="venue-cancel-btn">
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

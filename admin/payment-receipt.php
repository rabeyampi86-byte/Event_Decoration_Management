
<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$categories = [
    'Decoration',
    'Lighting',
    'Flower Decoration',
    'Food Service',
    'Other Services'
];

$errors = [];
$success = '';

$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

/* =====================================
   GET BOOKING LIST
===================================== */

$bookingList = $conn->query("
    SELECT
        b.booking_id,
        b.package_name_snapshot,
        u.full_name
    FROM bookings b
    INNER JOIN customers c ON b.customer_id = c.customer_id
    INNER JOIN users u ON c.user_id = u.user_id
    ORDER BY b.booking_id DESC
");

/* =====================================
   GET SELECTED BOOKING
===================================== */

$booking = null;

if ($bookingId > 0) {

    $stmt = $conn->prepare("
        SELECT
            b.booking_id,
            b.event_date,
            b.package_name_snapshot,
            b.package_price_snapshot,
            b.status,
            u.full_name,
            u.phone,
            u.email,
            ec.name AS event_type
        FROM bookings b
        INNER JOIN customers c ON b.customer_id = c.customer_id
        INNER JOIN users u ON c.user_id = u.user_id
        LEFT JOIN packages p ON b.package_id = p.package_id
        LEFT JOIN event_categories ec ON p.category_id = ec.category_id
        WHERE b.booking_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $booking = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

/* =====================================
   GET EXISTING INVOICE
===================================== */

$invoice = null;
$items = [];

if ($booking) {

    $stmt = $conn->prepare("
        SELECT *
        FROM invoices
        WHERE booking_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $invoice = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($invoice) {

        $stmt = $conn->prepare("
            SELECT *
            FROM invoice_items
            WHERE invoice_id = ?
            ORDER BY sort_order, invoice_item_id
        ");

        $stmt->bind_param("i", $invoice['invoice_id']);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[$row['category']][] = $row;
        }

        $stmt->close();
    }
}

/* =====================================
   SAVE / UPDATE BILL
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bill'])) {

    if (!$booking) {

        $errors[] = "Please select a valid booking.";

    } else {

        $packageAmount = (float)$booking['package_price_snapshot'];

        $discount = max(0, (float)($_POST['discount'] ?? 0));

        $notes = trim($_POST['notes'] ?? '');

        $submittedItems = $_POST['items'] ?? [];

        $additionalTotal = 0;
        $validItems = [];
        $sortOrder = 1;

        foreach ($categories as $category) {

            $categoryItems = $submittedItems[$category] ?? [];

            foreach ($categoryItems as $item) {

                $name = trim($item['name'] ?? '');
                $description = trim($item['description'] ?? '');

                if ($name === '') {
                    continue;
                }

                $quantity = (int)($item['quantity'] ?? 1);
                $price = (float)($item['price'] ?? 0);

                if ($quantity < 1 || $price < 0) {
                    $errors[] = "Invalid quantity or price in $category.";
                    continue;
                }

                $lineTotal = $quantity * $price;

                $additionalTotal += $lineTotal;

                $validItems[] = [
                    'category' => $category,
                    'name' => $name,
                    'description' => $description,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                    'sort_order' => $sortOrder++
                ];
            }
        }

        $subtotal = $packageAmount + $additionalTotal;

        if ($discount > $subtotal) {
            $errors[] = "Discount cannot exceed subtotal.";
        }

        if (empty($errors)) {

            $taxableSubtotal = $subtotal - $discount;

            $tax = round($taxableSubtotal * 0.05, 2);

            $grandTotal = $taxableSubtotal + $tax;

            try {

                $conn->begin_transaction();

                /* INSERT OR UPDATE INVOICE */

                $stmt = $conn->prepare("
                    INSERT INTO invoices
                    (
                        booking_id,
                        subtotal,
                        discount,
                        tax,
                        total,
                        notes,
                        issued_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, NOW())

                    ON DUPLICATE KEY UPDATE
                        subtotal = VALUES(subtotal),
                        discount = VALUES(discount),
                        tax = VALUES(tax),
                        total = VALUES(total),
                        notes = VALUES(notes),
                        issued_at = NOW()
                ");

                $stmt->bind_param(
                    "idddds",
                    $bookingId,
                    $subtotal,
                    $discount,
                    $tax,
                    $grandTotal,
                    $notes
                );

                $stmt->execute();
                $stmt->close();

                /* GET INVOICE ID */

                $stmt = $conn->prepare("
                    SELECT invoice_id
                    FROM invoices
                    WHERE booking_id = ?
                    LIMIT 1
                ");

                $stmt->bind_param("i", $bookingId);
                $stmt->execute();

                $invoiceRow = $stmt->get_result()->fetch_assoc();

                $invoiceId = (int)$invoiceRow['invoice_id'];

                $stmt->close();

                /* DELETE OLD ITEMS */

                $stmt = $conn->prepare("
                    DELETE FROM invoice_items
                    WHERE invoice_id = ?
                ");

                $stmt->bind_param("i", $invoiceId);
                $stmt->execute();
                $stmt->close();

                /* INSERT UPDATED ITEMS */

                $stmt = $conn->prepare("
                    INSERT INTO invoice_items
                    (
                        invoice_id,
                        category,
                        item_name,
                        description,
                        quantity,
                        unit_price,
                        total_price,
                        sort_order
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($validItems as $item) {

                    $category = $item['category'];
                    $name = $item['name'];
                    $description = $item['description'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];
                    $total = $item['total'];
                    $order = $item['sort_order'];

                    $stmt->bind_param(
                        "isssiddi",
                        $invoiceId,
                        $category,
                        $name,
                        $description,
                        $quantity,
                        $price,
                        $total,
                        $order
                    );

                    $stmt->execute();
                }

                $stmt->close();

                $conn->commit();

                header(
                    "Location: payment-receipt.php?booking_id=" .
                    $bookingId . "&saved=1"
                );

                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                $errors[] = "Bill could not be saved. Please check the database.";
            }
        }
    }
}

/* =====================================
   RELOAD AFTER SAVE
===================================== */

if (isset($_GET['saved']) && $booking) {

    $success = "Bill saved successfully!";

    $stmt = $conn->prepare("
        SELECT *
        FROM invoices
        WHERE booking_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $invoice = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $items = [];

    if ($invoice) {

        $stmt = $conn->prepare("
            SELECT *
            FROM invoice_items
            WHERE invoice_id = ?
            ORDER BY sort_order, invoice_item_id
        ");

        $stmt->bind_param("i", $invoice['invoice_id']);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[$row['category']][] = $row;
        }

        $stmt->close();
    }
}

/* =====================================
   VIEW MODE
===================================== */

$isEditMode = !$invoice
    || isset($_GET['edit'])
    || !empty($errors);

$discountValue = $invoice['discount'] ?? 0;
$notesValue = $invoice['notes'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $discountValue = $_POST['discount'] ?? 0;
    $notesValue = $_POST['notes'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment Receipt | DecorEase</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="includes/css/admin.css">

<style>

/* =====================================
   ADMIN PAGE
===================================== */

.page-content {
    padding: 28px;
    background: #f5f7fb;
    min-height: 100vh;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-heading h2 {
    font-size: 25px;
    color: #1e293b;
    margin-bottom: 5px;
}

.page-heading p {
    color: #64748b;
    font-size: 14px;
}

.bill-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 22px;
}

.section-title {
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 19px;
}

.booking-select {
    max-width: 500px;
}

.form-control {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
    color: #334155;
    background: #fff;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
}

/* =====================================
   EDIT FORM
===================================== */

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.info-item label {
    display: block;
    color: #64748b;
    font-size: 13px;
    margin-bottom: 6px;
}

.info-item strong {
    color: #1e293b;
    font-size: 14px;
}

.category-box {
    margin-top: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    overflow: hidden;
}

.category-heading {
    background: #f8fafc;
    padding: 13px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-heading h4 {
    color: #334155;
    font-size: 15px;
}

.category-content {
    padding: 15px;
}

.item-row {
    display: grid;
    grid-template-columns: 2fr 1.5fr .7fr 1fr auto;
    gap: 10px;
    margin-bottom: 12px;
    align-items: center;
}

.item-row input {
    min-width: 0;
}

/* EDIT SUMMARY */

.edit-summary {
    max-width: 520px;
    margin-left: auto;
    margin-top: 25px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.edit-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
    font-size: 14px;
}

.edit-summary-row:last-child {
    border-bottom: none;
}

.edit-summary-row.total {
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 18px;
    font-weight: 800;
}

.form-actions,
.preview-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 25px;
}

/* =====================================
   BUTTONS
===================================== */

.btn {
    border: none;
    padding: 10px 16px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
}

.btn-primary {
    background: #2563eb;
    color: #fff;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-light {
    background: #eff6ff;
    color: #2563eb;
}

.btn-secondary {
    background: #475569;
    color: #fff;
}

.btn-success {
    background: #15803d;
    color: #fff;
}

.btn-danger {
    background: #fee2e2;
    color: #dc2626;
    padding: 9px 12px;
}

.alert {
    padding: 13px 16px;
    border-radius: 7px;
    margin-bottom: 18px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* =====================================
   PROFESSIONAL INVOICE TEMPLATE
===================================== */

.invoice-paper {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 45px 50px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    color: #334155;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 25px;
    border-bottom: 3px solid #2563eb;
    gap: 20px;
}

.invoice-logo h1 {
    color: #2563eb;
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 5px;
}

.invoice-logo p {
    color: #64748b;
    font-size: 11px;
    letter-spacing: 1.5px;
    margin: 0;
}

.invoice-title {
    background: #eff6ff;
    padding: 14px 22px;
    border-radius: 8px;
    text-align: center;
}

.invoice-title h2 {
    color: #1d4ed8;
    font-size: 23px;
    margin: 0 0 4px;
}

.invoice-title p {
    color: #64748b;
    font-size: 11px;
    letter-spacing: 1px;
    margin: 0;
}

.invoice-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 35px;
    padding: 28px 0;
}

.invoice-info h4 {
    color: #2563eb;
    font-size: 12px;
    letter-spacing: 1px;
    margin: 0 0 12px;
}

.invoice-info p {
    color: #475569;
    font-size: 13px;
    line-height: 1.8;
    margin: 0;
}

.invoice-info strong {
    color: #1e293b;
}

.invoice-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin: 10px 0 15px;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 9px;
}

.invoice-table th {
    background: #eff6ff;
    color: #1e40af;
    font-size: 12px;
    font-weight: 700;
    padding: 14px 15px;
    text-align: left;
    border-bottom: 1px solid #bfdbfe;
}

.invoice-table th:last-child,
.invoice-table td:last-child {
    text-align: right;
}

.invoice-table td {
    padding: 13px 16px;
    font-size: 13px;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
}

.invoice-table td strong {
    color: #1e293b;
}

.invoice-item-description {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-top: 5px;
    line-height: 1.6;
}

.invoice-table .additional-row td {
    background: #f8fafc;
    color: #64748b;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 1.5px;
    text-align: left !important;
    padding: 11px 16px;
}

.invoice-table .summary-row td {
    background: #fff;
    font-size: 13px;
}

.invoice-table .summary-row.discount td {
    color: #dc2626;
}

.invoice-table .summary-row.grand-total td {
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 17px;
    font-weight: 800;
    border-top: 2px solid #2563eb;
    border-bottom: 2px solid #2563eb;
    padding: 17px 16px;
}

.invoice-notes {
    background: #f8fafc;
    border-left: 3px solid #2563eb;
    padding: 15px 18px;
    margin-top: 25px;
    border-radius: 4px;
}

.invoice-notes h4 {
    color: #1e293b;
    font-size: 13px;
    margin: 0 0 7px;
}

.invoice-notes p {
    color: #64748b;
    font-size: 12px;
    line-height: 1.7;
    margin: 0;
}

.invoice-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 55px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    gap: 20px;
}

.invoice-footer p {
    color: #64748b;
    font-size: 11px;
    margin: 0;
}

.signature {
    width: 180px;
    text-align: center;
    border-top: 1px solid #94a3b8;
    padding-top: 8px;
}

/* =====================================
   RESPONSIVE
===================================== */

@media(max-width: 900px) {
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .item-row {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 650px) {
    .page-content {
        padding: 15px;
    }

    .invoice-paper {
        padding: 22px 15px;
    }

    .invoice-header {
        align-items: flex-start;
    }

    .invoice-logo h1 {
        font-size: 24px;
    }

    .invoice-title {
        padding: 10px;
    }

    .invoice-title h2 {
        font-size: 17px;
    }

    .invoice-info,
    .info-grid {
        grid-template-columns: 1fr;
    }

    .item-row {
        grid-template-columns: 1fr;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 10px 8px;
        font-size: 12px;
    }

    .invoice-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* =====================================
   PRINT
===================================== */

@media print {

    @page {
        size: A4;
        margin: 12mm;
    }

    body {
        background: #fff !important;
    }

    .sidebar,
    .main-content > header,
    .page-heading,
    .booking-selection-card,
    .preview-actions,
    .alert,
    .no-print {
        display: none !important;
    }

    .admin-container,
    .main-content,
    .page-content {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        min-height: auto !important;
    }

    .invoice-paper {
        max-width: none !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-radius: 0 !important;
    }

    .invoice-table th,
    .invoice-table .additional-row td,
    .invoice-table .summary-row.grand-total td,
    .invoice-title {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

</style>

</head>

<body>

<div class="admin-container">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="page-content">

            <div class="page-heading">
                <div>
                    <h2>Payment Receipt</h2>
                    <p>Generate and manage customer bills.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>

            <!-- BOOKING SELECTION -->

            <div class="bill-card booking-selection-card">

                <h3 class="section-title">Select Booking</h3>

                <form method="GET">

                    <div class="booking-select">

                        <label>Booking ID / Customer</label>

                        <select name="booking_id"
                                class="form-control"
                                onchange="this.form.submit()"
                                required>

                            <option value="">Select a Booking</option>

                            <?php if ($bookingList): ?>

                                <?php while ($row = $bookingList->fetch_assoc()): ?>

                                    <option
                                        value="<?= $row['booking_id'] ?>"
                                        <?= $bookingId == $row['booking_id'] ? 'selected' : '' ?>>

                                        #<?= $row['booking_id'] ?> -
                                        <?= htmlspecialchars($row['full_name']) ?> -
                                        <?= htmlspecialchars($row['package_name_snapshot']) ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>

                </form>

            </div>

            <?php if ($booking): ?>

                <?php if ($isEditMode): ?>

                <!-- =================================
                     EDIT BILL FORM
                ================================== -->

                <form method="POST" id="billForm">

                    <input type="hidden"
                           name="booking_id"
                           value="<?= $bookingId ?>">

                    <div class="bill-card">

                        <h3 class="section-title">
                            <i class="fas fa-user-circle"></i>
                            Customer & Event Information
                        </h3>

                        <div class="info-grid">

                            <div class="info-item">
                                <label>Customer Name</label>
                                <strong><?= htmlspecialchars($booking['full_name']) ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Phone</label>
                                <strong><?= htmlspecialchars($booking['phone'] ?? 'N/A') ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Email</label>
                                <strong><?= htmlspecialchars($booking['email']) ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Booking ID</label>
                                <strong>#<?= $booking['booking_id'] ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Event Date</label>
                                <strong><?= date('d M Y', strtotime($booking['event_date'])) ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Event Type</label>
                                <strong><?= htmlspecialchars($booking['event_type'] ?? 'N/A') ?></strong>
                            </div>

                            <div class="info-item">
                                <label>Package Name</label>
                                <strong><?= htmlspecialchars($booking['package_name_snapshot']) ?></strong>
                            </div>

                        </div>

                    </div>

                    <div class="bill-card">

                        <h3 class="section-title">Edit Bill Details</h3>

                        <div class="info-item" style="margin-bottom:20px;">

                            <label>Main Package</label>

                            <strong>
                                <?= htmlspecialchars($booking['package_name_snapshot']) ?>
                                — ৳<?= number_format((float)$booking['package_price_snapshot'], 2) ?>
                            </strong>

                        </div>

                        <p style="color:#64748b;font-size:13px;margin-bottom:18px;">
                            Add individual services, quantities and prices under each category.
                        </p>

                        <?php foreach ($categories as $category): ?>

                            <div class="category-box">

                                <div class="category-heading">

                                    <h4><?= htmlspecialchars($category) ?></h4>

                                    <button type="button"
                                            class="btn btn-light"
                                            onclick="addItem(this, <?= htmlspecialchars(json_encode($category), ENT_QUOTES) ?>)">

                                        <i class="fas fa-plus"></i>
                                        Add Item

                                    </button>

                                </div>

                                <div class="category-content">

                                    <div class="items-container">

                                        <?php

                                        $savedRows = $items[$category] ?? [];

                                        if (empty($savedRows)) {
                                            $savedRows = [[
                                                'item_name' => '',
                                                'description' => '',
                                                'quantity' => 1,
                                                'unit_price' => 0
                                            ]];
                                        }

                                        ?>

                                        <?php foreach ($savedRows as $index => $item): ?>

                                            <div class="item-row">

                                                <input type="text"
                                                       class="form-control"
                                                       name="items[<?= htmlspecialchars($category) ?>][<?= $index ?>][name]"
                                                       placeholder="Item Name"
                                                       value="<?= htmlspecialchars($item['item_name']) ?>">

                                                <input type="text"
                                                       class="form-control"
                                                       name="items[<?= htmlspecialchars($category) ?>][<?= $index ?>][description]"
                                                       placeholder="Description"
                                                       value="<?= htmlspecialchars($item['description'] ?? '') ?>">

                                                <input type="number"
                                                       class="form-control"
                                                       name="items[<?= htmlspecialchars($category) ?>][<?= $index ?>][quantity]"
                                                       placeholder="Qty"
                                                       min="1"
                                                       value="<?= (int)$item['quantity'] ?>">

                                                <input type="number"
                                                       class="form-control"
                                                       name="items[<?= htmlspecialchars($category) ?>][<?= $index ?>][price]"
                                                       placeholder="Unit Price"
                                                       min="0"
                                                       step="0.01"
                                                       value="<?= htmlspecialchars($item['unit_price']) ?>">

                                                <button type="button"
                                                        class="btn btn-danger"
                                                        onclick="removeItem(this)">

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <!-- EDIT SUMMARY -->

                        <div class="edit-summary">

                            <div class="edit-summary-row">
                                <span>Subtotal</span>
                                <strong id="subtotal">৳0.00</strong>
                            </div>

                            <div class="edit-summary-row">

                                <label for="discount">Discount</label>

                                <input type="number"
                                       id="discount"
                                       name="discount"
                                       class="form-control"
                                       style="max-width:150px;text-align:right;"
                                       min="0"
                                       step="0.01"
                                       value="<?= htmlspecialchars((string)$discountValue) ?>">

                            </div>

                            <div class="edit-summary-row">
                                <span>Taxable Subtotal</span>
                                <strong id="taxable">৳0.00</strong>
                            </div>

                            <div class="edit-summary-row">
                                <span>VAT (5%)</span>
                                <strong id="vat">৳0.00</strong>
                            </div>

                            <div class="edit-summary-row total">
                                <span>Grand Total</span>
                                <strong id="grandTotal">৳0.00</strong>
                            </div>

                        </div>

                        <!-- NOTES -->

                        <div style="margin-top:25px;">

                            <label class="section-title" for="notes">
                                Additional Notes
                            </label>

                            <textarea name="notes"
                                      id="notes"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Write any additional information..."><?= htmlspecialchars($notesValue) ?></textarea>

                        </div>

                        <div class="form-actions">

                            <?php if ($invoice): ?>

                                <a href="payment-receipt.php?booking_id=<?= $bookingId ?>"
                                   class="btn btn-secondary">

                                    <i class="fas fa-arrow-left"></i>
                                    Cancel

                                </a>

                            <?php endif; ?>

                            <button type="submit"
                                    name="save_bill"
                                    value="1"
                                    class="btn btn-primary">

                                <i class="fas fa-save"></i>
                                Save Bill

                            </button>

                        </div>

                    </div>

                </form>

                <?php else: ?>

                <!-- =================================
                     PROFESSIONAL BILL PREVIEW
                ================================== -->

                <div class="invoice-paper" id="printableBill">

                    <!-- HEADER -->

                    <div class="invoice-header">

                        <div class="invoice-logo">
                            <h1>DecorEase</h1>
                            <p>EVENT DECORATION MANAGEMENT</p>
                        </div>

                    </div>

                    <!-- CUSTOMER INFORMATION -->

                    <div class="invoice-info">

                        <div>

                            <h4>BILL TO</h4>

                            <p>
                                <strong><?= htmlspecialchars($booking['full_name']) ?></strong><br>

                                <?= htmlspecialchars($booking['phone'] ?? 'N/A') ?><br>

                                <?= htmlspecialchars($booking['email']) ?>
                            </p>

                        </div>

                        <div>

                            <h4>INVOICE DETAILS</h4>

                            <p>
                                <strong>Invoice No:</strong>
                                INV-<?= str_pad($invoice['invoice_id'], 5, '0', STR_PAD_LEFT) ?><br>

                                <strong>Booking ID:</strong>
                                #<?= $booking['booking_id'] ?><br>

                                <strong>Issue Date:</strong>
                                <?= date('d M Y', strtotime($invoice['issued_at'])) ?><br>

                                <strong>Event Date:</strong>
                                <?= date('d M Y', strtotime($booking['event_date'])) ?><br>

                                <strong>Event Type:</strong>
                                <?= htmlspecialchars($booking['event_type'] ?? 'N/A') ?>
                            </p>

                        </div>

                    </div>

                    <!-- BILL DETAILS -->

                    <h3 class="invoice-section-title">Bill Details</h3>

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                            <!-- MAIN PACKAGE -->

                            <tr>

                                <td>
                                    <strong>Main Package</strong>

                                    <span class="invoice-item-description">
                                        <?= htmlspecialchars($booking['package_name_snapshot']) ?>
                                    </span>
                                </td>

                                <td>
                                    ৳<?= number_format((float)$booking['package_price_snapshot'], 2) ?>
                                </td>

                            </tr>

                            <!-- ADDITIONAL -->

                            <tr class="additional-row">
                                <td colspan="2">ADDITIONAL SERVICES</td>
                            </tr>

                            <?php foreach ($categories as $category): ?>

                                <?php

                                $categoryTotal = 0;

                                $categoryItems = $items[$category] ?? [];

                                foreach ($categoryItems as $savedItem) {
                                    $categoryTotal += (float)$savedItem['total_price'];
                                }

                                ?>

                                <tr>

                                    <td>

                                        <strong><?= htmlspecialchars($category) ?></strong>

                                        <?php foreach ($categoryItems as $savedItem): ?>

                                            <span class="invoice-item-description">

                                                <?= htmlspecialchars($savedItem['item_name']) ?>

                                                <?php if (!empty($savedItem['description'])): ?>
                                                    — <?= htmlspecialchars($savedItem['description']) ?>
                                                <?php endif; ?>

                                                (<?= (int)$savedItem['quantity'] ?> ×
                                                ৳<?= number_format((float)$savedItem['unit_price'], 2) ?>)

                                            </span>

                                        <?php endforeach; ?>

                                    </td>

                                    <td>
                                        ৳<?= number_format($categoryTotal, 2) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <!-- SUMMARY -->

                            <tr class="summary-row">

                                <td><strong>Subtotal</strong></td>

                                <td>
                                    <strong>৳<?= number_format((float)$invoice['subtotal'], 2) ?></strong>
                                </td>

                            </tr>

                            <tr class="summary-row discount">

                                <td>Discount</td>

                                <td>
                                    - ৳<?= number_format((float)$invoice['discount'], 2) ?>
                                </td>

                            </tr>

                            <tr class="summary-row">

                                <td>Taxable Subtotal</td>

                                <td>
                                    ৳<?= number_format(
                                        (float)$invoice['subtotal'] - (float)$invoice['discount'],
                                        2
                                    ) ?>
                                </td>

                            </tr>

                            <tr class="summary-row">

                                <td>VAT (5%)</td>

                                <td>
                                    ৳<?= number_format((float)$invoice['tax'], 2) ?>
                                </td>

                            </tr>

                            <tr class="summary-row grand-total">

                                <td>GRAND TOTAL</td>

                                <td>
                                    ৳<?= number_format((float)$invoice['total'], 2) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                    <!-- NOTES -->

                    <?php if (!empty($invoice['notes'])): ?>

                        <div class="invoice-notes">

                            <h4>Additional Notes</h4>

                            <p>
                                <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                            </p>

                        </div>

                    <?php endif; ?>

                    <!-- FOOTER -->

                    <div class="invoice-footer">

                        <p>
                            Thank you for choosing DecorEase.<br>
                            We appreciate your trust in our services.
                        </p>

                        <div class="signature">
                            <p>Authorized Signature</p>
                        </div>

                    </div>

                </div>

                <!-- ACTION BUTTONS -->

                <div class="preview-actions">

                    <a href="payment-receipt.php?booking_id=<?= $bookingId ?>&edit=1"
                       class="btn btn-primary">

                        <i class="fas fa-edit"></i>
                        Edit Bill

                    </a>

                    <button type="button"
                            class="btn btn-success"
                            onclick="window.print()">

                        <i class="fas fa-print"></i>
                        Print Bill

                    </button>

                </div>

                <?php endif; ?>

            <?php elseif ($bookingId > 0): ?>

                <div class="alert alert-danger">
                    Booking information was not found.
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<script>

/* =====================================
   ADD ITEM
===================================== */

let itemCounters = {};

function addItem(button, category) {

    const container = button.closest('.category-box')
                            .querySelector('.items-container');

    itemCounters[category] = (itemCounters[category] || 0) + 100;

    const index = itemCounters[category];

    const row = document.createElement('div');

    row.className = 'item-row';

    const safeCategory = category.replace(/&/g, '&amp;')
                                 .replace(/"/g, '&quot;')
                                 .replace(/</g, '&lt;')
                                 .replace(/>/g, '&gt;');

    row.innerHTML = `
        <input type="text"
               class="form-control"
               name="items[${safeCategory}][${index}][name]"
               placeholder="Item Name">

        <input type="text"
               class="form-control"
               name="items[${safeCategory}][${index}][description]"
               placeholder="Description">

        <input type="number"
               class="form-control"
               name="items[${safeCategory}][${index}][quantity]"
               placeholder="Qty"
               min="1"
               value="1">

        <input type="number"
               class="form-control"
               name="items[${safeCategory}][${index}][price]"
               placeholder="Unit Price"
               min="0"
               step="0.01"
               value="0">

        <button type="button"
                class="btn btn-danger"
                onclick="removeItem(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;

    container.appendChild(row);

    calculateBill();
}

/* =====================================
   REMOVE ITEM
===================================== */

function removeItem(button) {

    button.closest('.item-row').remove();

    calculateBill();
}

/* =====================================
   CALCULATE BILL
===================================== */

function calculateBill() {

    const discountInput = document.getElementById('discount');

    if (!discountInput) {
        return;
    }

    const packageAmount =
        <?= $booking ? (float)$booking['package_price_snapshot'] : 0 ?>;

    let additionalTotal = 0;

    document.querySelectorAll('.item-row').forEach(row => {

        const quantity = parseFloat(
            row.querySelector('[name$="[quantity]"]').value
        ) || 0;

        const price = parseFloat(
            row.querySelector('[name$="[price]"]').value
        ) || 0;

        additionalTotal += quantity * price;

    });

    const subtotal = packageAmount + additionalTotal;

    const discount = Math.max(
        0,
        parseFloat(discountInput.value) || 0
    );

    const taxable = Math.max(0, subtotal - discount);

    const vat = taxable * 0.05;

    const grandTotal = taxable + vat;

    const money = value => '৳' + value.toFixed(2);

    document.getElementById('subtotal').textContent = money(subtotal);

    document.getElementById('taxable').textContent = money(taxable);

    document.getElementById('vat').textContent = money(vat);

    document.getElementById('grandTotal').textContent = money(grandTotal);
}

/* =====================================
   LIVE CALCULATION
===================================== */

document.addEventListener('input', function(e) {

    if (
        e.target.matches('.item-row input') ||
        e.target.id === 'discount'
    ) {
        calculateBill();
    }

});

if (document.getElementById('billForm')) {
    calculateBill();
}

</script>

<?php include 'includes/footer.php'; ?>

</body>
</html>

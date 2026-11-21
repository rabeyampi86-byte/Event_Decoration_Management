
<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$invoice_id = filter_input(INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT);

if (!$invoice_id) {
    header("Location: invoices.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM invoices
    WHERE invoice_id = ?
");

$stmt->bind_param("i", $invoice_id);
$stmt->execute();

$result = $stmt->get_result();
$invoice = $result->fetch_assoc();
$stmt->close();

if (!$invoice) {
    header("Location: invoices.php");
    exit;
}

$error = '';

$subtotal = $invoice['subtotal'];
$discount = $invoice['discount'];
$tax = $invoice['tax'];

$issued_at = $invoice['issued_at']
    ? date('Y-m-d\TH:i', strtotime($invoice['issued_at']))
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subtotal = trim($_POST['subtotal'] ?? '');
    $discount = trim($_POST['discount'] ?? '');
    $tax = trim($_POST['tax'] ?? '');
    $issued_at = trim($_POST['issued_at'] ?? '');

    if (
        !is_numeric($subtotal) ||
        !is_numeric($discount) ||
        !is_numeric($tax)
    ) {
        $error = "Please enter valid amount values.";
    } elseif (
        (float)$subtotal < 0 ||
        (float)$discount < 0 ||
        (float)$tax < 0
    ) {
        $error = "Amounts cannot be negative.";
    } elseif ((float)$discount > (float)$subtotal) {
        $error = "Discount cannot be greater than subtotal.";
    } else {

        $subtotal_value = (float)$subtotal;
        $discount_value = (float)$discount;
        $tax_value = (float)$tax;

        $total = $subtotal_value - $discount_value + $tax_value;

        if ($issued_at === '') {

            $stmt = $conn->prepare("
                UPDATE invoices
                SET subtotal = ?,
                    discount = ?,
                    tax = ?,
                    total = ?,
                    issued_at = NULL
                WHERE invoice_id = ?
            ");

            $stmt->bind_param(
                "ddddi",
                $subtotal_value,
                $discount_value,
                $tax_value,
                $total,
                $invoice_id
            );

        } else {

            $date = DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $issued_at
            );

            if (!$date) {
                $error = "Please enter a valid issue date and time.";
            } else {

                $issued_at_value = $date->format('Y-m-d H:i:s');

                $stmt = $conn->prepare("
                    UPDATE invoices
                    SET subtotal = ?,
                        discount = ?,
                        tax = ?,
                        total = ?,
                        issued_at = ?
                    WHERE invoice_id = ?
                ");

                $stmt->bind_param(
                    "ddddsi",
                    $subtotal_value,
                    $discount_value,
                    $tax_value,
                    $total,
                    $issued_at_value,
                    $invoice_id
                );
            }
        }

        if ($error === '') {

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: invoices.php?success=updated");
                exit;
            } else {
                $error = "Failed to update invoice.";
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Invoice</title>

    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .invoice-form-page {
            padding: 25px 30px 35px;
            margin-top: 20px;
            color: #1e293b;
        }

        .invoice-form-header {
            margin-bottom: 25px;
        }

        .invoice-form-header h2 {
            margin: 0 0 7px;
            font-size: 24px;
        }

        .invoice-form-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .invoice-form-card {
            max-width: 850px;
            padding: 28px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .invoice-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .invoice-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .invoice-form-group.full-width {
            grid-column: 1 / -1;
        }

        .invoice-form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .invoice-form-group input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            color: #1e293b;
            outline: none;
            box-sizing: border-box;
        }

        .invoice-form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .invoice-total-preview {
            margin-top: 22px;
            padding: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .invoice-total-preview span {
            font-size: 15px;
            font-weight: 600;
            color: #334155;
        }

        .invoice-total-preview strong {
            font-size: 22px;
            color: #2563eb;
        }

        .invoice-error {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 7px;
            font-size: 14px;
        }

        .invoice-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .invoice-submit-btn,
        .invoice-cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 19px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .invoice-submit-btn {
            background: #2563eb;
            color: #fff;
        }

        .invoice-submit-btn:hover {
            background: #1d4ed8;
        }

        .invoice-cancel-btn {
            background: #f1f5f9;
            color: #334155;
        }

        .invoice-cancel-btn:hover {
            background: #e2e8f0;
        }

        @media (max-width: 650px) {
            .invoice-form-page {
                padding: 20px 15px;
            }

            .invoice-form-card {
                padding: 18px;
            }

            .invoice-form-grid {
                grid-template-columns: 1fr;
            }

            .invoice-form-group.full-width {
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

        <section class="invoice-form-page">

            <div class="invoice-form-header">
                <h2>Edit Invoice</h2>
                <p>Update invoice #<?php echo (int)$invoice_id; ?> information.</p>
            </div>

            <div class="invoice-form-card">

                <?php if ($error !== ''): ?>
                    <div class="invoice-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="invoice-form-grid">

                        <div class="invoice-form-group full-width">
                            <label>Booking ID</label>

                            <input
                                type="text"
                                value="<?php echo (int)$invoice['booking_id']; ?>"
                                disabled
                            >
                        </div>

                        <div class="invoice-form-group">
                            <label for="subtotal">Subtotal (৳) *</label>

                            <input
                                type="number"
                                name="subtotal"
                                id="subtotal"
                                min="0"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string)$subtotal); ?>"
                                required
                            >
                        </div>

                        <div class="invoice-form-group">
                            <label for="discount">Discount (৳)</label>

                            <input
                                type="number"
                                name="discount"
                                id="discount"
                                min="0"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string)$discount); ?>"
                            >
                        </div>

                        <div class="invoice-form-group">
                            <label for="tax">Tax (৳)</label>

                            <input
                                type="number"
                                name="tax"
                                id="tax"
                                min="0"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string)$tax); ?>"
                            >
                        </div>

                        <div class="invoice-form-group">
                            <label for="issued_at">Issue Date & Time</label>

                            <input
                                type="datetime-local"
                                name="issued_at"
                                id="issued_at"
                                value="<?php echo htmlspecialchars($issued_at); ?>"
                            >
                        </div>

                    </div>

                    <div class="invoice-total-preview">
                        <span>Calculated Total</span>
                        <strong id="totalPreview">৳0.00</strong>
                    </div>

                    <div class="invoice-form-actions">

                        <button type="submit" class="invoice-submit-btn">
                            <i class="fas fa-save"></i>
                            Update Invoice
                        </button>

                        <a href="invoices.php" class="invoice-cancel-btn">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<?php include 'includes/footer.php'; ?>

<script>
const subtotalInput = document.getElementById('subtotal');
const discountInput = document.getElementById('discount');
const taxInput = document.getElementById('tax');
const totalPreview = document.getElementById('totalPreview');

function calculateInvoiceTotal() {
    const subtotal = parseFloat(subtotalInput.value) || 0;
    const discount = parseFloat(discountInput.value) || 0;
    const tax = parseFloat(taxInput.value) || 0;

    const total = subtotal - discount + tax;

    totalPreview.textContent = '৳' + total.toFixed(2);
}

subtotalInput.addEventListener('input', calculateInvoiceTotal);
discountInput.addEventListener('input', calculateInvoiceTotal);
taxInput.addEventListener('input', calculateInvoiceTotal);

calculateInvoiceTotal();
</script>

</body>
</html>

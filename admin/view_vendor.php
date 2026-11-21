<?php
require_once __DIR__ . '/../db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: vendors.php?error=invalid");
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM vendors
    WHERE vendor_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vendor) {
    header("Location: vendors.php?error=notfound");
    exit;
}

function viewVendorEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Vendor</title>
    <link rel="stylesheet" href="includes/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .page-content{padding:25px}
        .details-card{max-width:850px;background:#fff;padding:25px;border-radius:10px}
        .detail-row{display:flex;padding:14px 0;border-bottom:1px solid #e2e8f0}
        .detail-label{width:200px;flex-shrink:0;color:#64748b;font-weight:600}
        .detail-value{flex:1;color:#1e293b;overflow-wrap:anywhere}
        .status{display:inline-block;padding:5px 11px;border-radius:20px;font-size:12px;text-transform:capitalize}
        .active{background:#dcfce7;color:#166534}
        .inactive{background:#fee2e2;color:#991b1b}
        .pending{background:#fef3c7;color:#92400e}
        .back-btn{display:inline-block;margin-top:20px;background:#2563eb;color:#fff;padding:10px 16px;text-decoration:none;border-radius:6px}
        @media(max-width:600px){.detail-row{display:block}.detail-label{width:auto;margin-bottom:7px}}
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <section class="page-content">
            <h2 style="margin-bottom:20px;">Vendor Details</h2>

            <div class="details-card">

                <div class="detail-row">
                    <div class="detail-label">Vendor ID</div>
                    <div class="detail-value"><?= (int)$vendor['vendor_id']; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Vendor Name</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['vendor_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Service Type</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['service_type'] ?: '—'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Contact Person</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['contact_person'] ?: '—'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['phone'] ?: '—'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['email'] ?: '—'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?= nl2br(viewVendorEscape($vendor['address'] ?: '—')); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value"><?= nl2br(viewVendorEscape($vendor['description'] ?: '—')); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status <?= viewVendorEscape($vendor['status']); ?>">
                            <?= viewVendorEscape($vendor['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created At</div>
                    <div class="detail-value"><?= viewVendorEscape($vendor['created_at']); ?></div>
                </div>

                <a href="vendors.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Vendors
                </a>

            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
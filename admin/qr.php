<?php
require '../includes/auth.php';

require '../includes/db.php';

$business = db_query("SELECT * FROM business_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$menuUrl = $business['menu_url'] ?? 'http://localhost/naji_bakery/public/index.php';

$error = '';
$success = '';

// Handle URL update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUrl = trim($_POST['menu_url'] ?? '');

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($newUrl === '' || !is_web_url($newUrl)) {
        $error = 'Please enter a valid URL.';
    } else {
        db_query("UPDATE business_settings SET menu_url = ? WHERE id = ?", [$newUrl, $business['id']]);
        $menuUrl = $newUrl;
        $success = 'Menu link updated.';
    }
}

$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($menuUrl);

$activePage = 'qr';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Code - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
<style>
    .qr-card {
        text-align: center;
    }
    .qr-image-wrap {
        background: #fff;
        border: 1.5px solid #f0e6cc;
        border-radius: 16px;
        padding: 1.5rem;
        display: inline-block;
        margin-bottom: 1.25rem;
    }
    .qr-image-wrap img {
        width: 220px;
        height: 220px;
        display: block;
    }
    .qr-url-display {
        background: #FDFBF6;
        border: 1px solid #f0e6cc;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
        color: #555;
        word-break: break-all;
        margin-bottom: 1.25rem;
    }
    @media print {
        .sidebar, .page-header, .url-settings-card, .no-print {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .qr-col { width: 100% !important; }
        .qr-card {
            margin: 0 auto;
            max-width: 480px;
        }
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">QR Code</h3>
            <p class="page-sub">Scan to open your public menu. Download or print for table cards.</p>
        </div>
    </div>

    <div class="row g-4">
    <div class="col-12 col-lg-6 qr-col">
    <div class="card-soft p-4 qr-card h-100">
        <div class="qr-image-wrap">
            <img src="<?= e($qrImageUrl) ?>" alt="Menu QR Code" id="qrImage">
        </div>
        <div class="qr-url-display"><?= e($menuUrl) ?></div>

        <div class="d-flex gap-2 justify-content-center no-print">
            <a href="qr_download.php" class="btn-gold">
                <i class="bi bi-download"></i> Download
            </a>
            <button onclick="window.print()" class="btn-outline-soft">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    </div>
    <div class="col-12 col-lg-6 url-settings-card">
    <div class="card-soft p-4 h-100">
        <h6 class="brand-font mb-3">Menu Link</h6>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <label class="form-label-soft">Update this once you go live with a real domain.</label>
            <input type="url" name="menu_url" class="form-control mb-3" value="<?= e($menuUrl) ?>" required>
            <button type="submit" class="btn-outline-soft w-100 justify-content-center">Update Link &amp; Regenerate QR</button>
        </form>
    </div>
    </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
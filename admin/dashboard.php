<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require '../includes/db.php';

$totalItems = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$hiddenItems = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 0")->fetchColumn();
$missingPrice = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE price IS NULL OR price = 0")->fetchColumn();
$missingPriceItems = $pdo->query("SELECT name_ar FROM menu_items WHERE price IS NULL OR price = 0 LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css?v=9">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">Good to see you, <?= htmlspecialchars($_SESSION['admin_username']) ?></h3>
            <p class="page-sub">Here's what's happening with your menu.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="item_form.php" class="btn-gold"><i class="bi bi-plus-lg"></i> Add Menu Item</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-soft p-3">
                <p class="stat-label mb-1">Menu Items</p>
                <div class="stat-number"><?= $totalItems ?></div>
                <p class="stat-caption">across <?= $totalCategories ?> categories</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-soft p-3">
                <p class="stat-label mb-1">Categories</p>
                <div class="stat-number"><?= $totalCategories ?></div>
                <p class="stat-caption">manage anytime</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-soft p-3">
                <p class="stat-label mb-1">Hidden Items</p>
                <div class="stat-number"><?= $hiddenItems ?></div>
                <p class="stat-caption">not shown to customers</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-soft p-3">
                <p class="stat-label mb-1">Missing Price</p>
                <div class="stat-number"><?= $missingPrice ?></div>
                <p class="stat-caption <?= $missingPrice > 0 ? 'warn' : '' ?>">
                    <?= $missingPrice > 0 ? 'needs attention' : 'all good' ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($missingPrice > 0): ?>
    <div class="card-soft p-4">
        <h6 class="brand-font mb-3">Needs your attention</h6>
        <div class="attention-item">
            <div class="d-flex align-items-start">
                <span class="attention-dot dot-red mt-2"></span>
                <div>
                    <p class="attention-title mb-0"><?= $missingPrice ?> items have no price</p>
                    <p class="attention-desc"><?= htmlspecialchars(implode('، ', $missingPriceItems)) ?></p>
                </div>
            </div>
            <a href="items.php?filter=missing_price" class="attention-action">Fix now</a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
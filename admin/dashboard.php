<?php
require '../includes/auth.php';

require '../includes/db.php';

$totalItems = db_query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$totalCategories = db_query("SELECT COUNT(*) FROM categories")->fetchColumn();
$hiddenItems = db_query("SELECT COUNT(*) FROM menu_items WHERE is_available = 0")->fetchColumn();
$missingPrice = db_query("SELECT COUNT(*) FROM menu_items WHERE price IS NULL OR price = 0")->fetchColumn();
$missingPriceItems = db_query("SELECT name_ar FROM menu_items WHERE price IS NULL OR price = 0 LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
$withoutPhoto = db_query("SELECT COUNT(*) FROM menu_items WHERE image_path IS NULL OR image_path = ''")->fetchColumn();

// Latest items for the "Recently Added" list
$recentItems = db_query("
    SELECT m.id, m.name_ar, m.price, m.currency, m.image_path, m.is_available, m.created_at, c.name_en AS category_name_en
    FROM menu_items m
    JOIN categories c ON c.id = m.category_id
    ORDER BY m.created_at DESC, m.id DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

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
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">Good to see you, <?= e($_SESSION['admin_username']) ?></h3>
            <p class="page-sub">Here's what's happening with your menu.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="item_form.php" class="btn-gold"><i class="bi bi-plus-lg"></i> Add Menu Item</a>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card-soft p-3 h-100">
                <span class="stat-icon"><i class="bi bi-card-list"></i></span>
                <p class="stat-label mb-1">Menu Items</p>
                <div class="stat-number"><?= e($totalItems) ?></div>
                <p class="stat-caption">across <?= e($totalCategories) ?> categories</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-soft p-3 h-100">
                <span class="stat-icon"><i class="bi bi-tags-fill"></i></span>
                <p class="stat-label mb-1">Categories</p>
                <div class="stat-number"><?= e($totalCategories) ?></div>
                <p class="stat-caption">manage anytime</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-soft p-3 h-100">
                <span class="stat-icon"><i class="bi bi-eye-slash-fill"></i></span>
                <p class="stat-label mb-1">Hidden Items</p>
                <div class="stat-number"><?= e($hiddenItems) ?></div>
                <p class="stat-caption">not shown to customers</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-soft p-3 h-100">
                <span class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></span>
                <p class="stat-label mb-1">Missing Price</p>
                <div class="stat-number"><?= e($missingPrice) ?></div>
                <p class="stat-caption <?= $missingPrice > 0 ? 'warn' : '' ?>">
                    <?= $missingPrice > 0 ? 'needs attention' : 'all good' ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recently Added -->
        <div class="col-12 col-xxl-8">
            <div class="card-soft p-3 p-md-4 h-100">
                <div class="section-head">
                    <h6 class="brand-font section-title">Recently Added</h6>
                    <a href="items.php" class="section-link">View all items <i class="bi bi-arrow-right"></i></a>
                </div>

                <?php if (empty($recentItems)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        No menu items yet. <a href="item_form.php" class="section-link">Add your first item</a>
                    </div>
                <?php else: ?>
                <div class="table-scroll table-scroll-free">
                    <table class="table-soft" id="recentTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Added</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentItems as $item): ?>
                            <tr>
                                <td style="width:56px;">
                                    <div class="item-thumb">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img src="../<?= e($item['image_path']) ?>" alt="">
                                        <?php else: ?>
                                            <i class="bi bi-image"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="item-name-ar"><?= e($item['name_ar']) ?></div>
                                    <?php if (!$item['is_available']): ?>
                                        <span class="badge-unavailable">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="category-pill"><?= e($item['category_name_en']) ?></span></td>
                                <td class="col-nowrap">
                                    <?php if ($item['price'] === null || $item['price'] == 0): ?>
                                        <span class="badge-noprice">No price</span>
                                    <?php else: ?>
                                        <?= e(format_price($item['price'])) ?> <?= $item['currency'] === 'USD' ? '$' : 'L.L.' ?>
                                    <?php endif; ?>
                                </td>
                                <td class="recent-date" title="<?= e(date('Y-m-d H:i', strtotime($item['created_at']))) ?>">
                                    <?= e(date('M j, Y', strtotime($item['created_at']))) ?>
                                </td>
                                <td class="col-actions">
                                    <a href="item_form.php?id=<?= e($item['id']) ?>" class="action-btn" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <button type="button" class="action-btn danger delete-item-btn" data-id="<?= e($item['id']) ?>" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions + attention -->
        <div class="col-12 col-xxl-4">
            <div class="card-soft p-3 p-md-4 mb-4">
                <div class="section-head">
                    <h6 class="brand-font section-title">Quick Actions</h6>
                </div>
                <div class="quick-actions">
                    <a href="item_form.php" class="quick-action primary"><i class="bi bi-plus-circle-fill"></i> Add Item</a>
                    <a href="categories.php?add=1" class="quick-action"><i class="bi bi-folder-plus"></i> Add Category</a>
                    <a href="items.php" class="quick-action"><i class="bi bi-card-list"></i> Manage Items</a>
                    <a href="categories.php" class="quick-action"><i class="bi bi-tags-fill"></i> Manage Categories</a>
                    <a href="../public/index.php" target="_blank" rel="noopener" class="quick-action"><i class="bi bi-eye-fill"></i> View Menu</a>
                    <a href="qr.php" class="quick-action"><i class="bi bi-qr-code"></i> QR Code</a>
                    <a href="settings.php" class="quick-action"><i class="bi bi-gear-fill"></i> Business Settings</a>
                    <a href="items.php?filter=missing_price" class="quick-action"><i class="bi bi-currency-dollar"></i> Fix Prices</a>
                </div>
            </div>

            <?php if ($missingPrice > 0 || $withoutPhoto > 0): ?>
            <div class="card-soft p-3 p-md-4">
                <h6 class="brand-font mb-3">Needs your attention</h6>
                <?php if ($missingPrice > 0): ?>
                <div class="attention-item">
                    <div class="d-flex align-items-start">
                        <span class="attention-dot dot-red mt-2"></span>
                        <div>
                            <p class="attention-title mb-0"><?= e($missingPrice) ?> items have no price</p>
                            <p class="attention-desc"><?= e(implode('، ', $missingPriceItems)) ?></p>
                        </div>
                    </div>
                    <a href="items.php?filter=missing_price" class="attention-action">Fix now</a>
                </div>
                <?php endif; ?>
                <?php if ($withoutPhoto > 0): ?>
                <div class="attention-item">
                    <div class="d-flex align-items-start">
                        <span class="attention-dot dot-gold mt-2"></span>
                        <div>
                            <p class="attention-title mb-0"><?= e($withoutPhoto) ?> items have no photo</p>
                            <p class="attention-desc">Photos help customers choose.</p>
                        </div>
                    </div>
                    <a href="items.php" class="attention-action">View items</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
    const CSRF_TOKEN = "<?= e($_SESSION['csrf_token']) ?>";

    // Quick delete from the Recently Added list (same endpoint as the Items page)
    const recentTable = document.getElementById('recentTable');
    if (recentTable) {
        recentTable.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-item-btn');
            if (!deleteBtn) return;
            if (!confirm('Delete this item permanently?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', deleteBtn.dataset.id);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch('item_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('toastMessage', data.message || 'Item deleted.');
                        location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('Something went wrong. Please try again.', 'error'));
        });
    }
</script>
<?php
require '../includes/db.php';

// Get business info
$stmt = $pdo->query("SELECT * FROM business_settings LIMIT 1");
$business = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all categories, ordered
$stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($business['business_name_ar']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">

    <!-- Header: Logo + Business Name -->
    <div class="text-center mb-4">
        <?php if (!empty($business['logo_path'])): ?>
            <img src="../<?= htmlspecialchars($business['logo_path']) ?>" alt="Logo" style="max-width: 120px;" class="mb-2">
        <?php endif; ?>
        <h1><?= htmlspecialchars($business['business_name_ar']) ?></h1>
        <p class="text-muted"><?= htmlspecialchars($business['address_ar']) ?></p>
    </div>

    <?php foreach ($categories as $category): ?>
    <div class="mb-5">
        <h3 class="border-bottom pb-2 mb-3"><?= htmlspecialchars($category['name_ar']) ?></h3>

        <?php
        // Fetch available items for this category
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY display_order ASC");
        $stmt->execute([$category['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (empty($items)): ?>
            <p class="text-muted">لا توجد عناصر متاحة حالياً</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($item['name_ar']) ?></h5>
                                    <?php if (!empty($item['description_ar'])): ?>
                                        <p class="card-text text-muted small mb-0"><?= htmlspecialchars($item['description_ar']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-bold text-nowrap ms-3">
                                    <?= number_format($item['price']) ?> <?= $item['currency'] === 'USD' ? '$' : 'ل.ل' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require '../includes/db.php';

$isEdit = isset($_GET['id']);
$item = [
    'id' => '',
    'category_id' => '',
    'name_ar' => '',
    'description_ar' => '',
    'price' => '',
    'currency' => 'LBP',
    'image_path' => '',
    'is_available' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        header('Location: items.php');
        exit;
    }
    $item = $found;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $nameAr = trim($_POST['name_ar'] ?? '');
    $descriptionAr = trim($_POST['description_ar'] ?? '');
    $price = $_POST['price'] ?? '';
    $currency = $_POST['currency'] ?? 'LBP';
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $imagePath = $item['image_path']; // keep existing unless a new one is uploaded
    $priceValue = ($price === '') ? 0 : (float)$price;

   if ($categoryId <= 0 || $nameAr === '') {
    $error = 'Category and item name are required.';
} elseif (!containsArabic($nameAr)) {
    $error = 'Item name must contain Arabic text.';
} elseif ($priceValue < 0) {
    $error = 'Price cannot be negative.';
} else {
        // Handle image upload if a file was provided
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];

            if (!in_array($fileType, $allowedTypes)) {
                $error = 'Image must be JPG, PNG, or WEBP.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newFileName = 'item_' . uniqid() . '.' . $ext;
                $destination = '../uploads/' . $newFileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $imagePath = 'uploads/' . $newFileName;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }

        if ($error === '') {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE menu_items
                    SET category_id = ?, name_ar = ?, description_ar = ?, price = ?, currency = ?, image_path = ?, is_available = ?
                    WHERE id = ?
                ");
                $stmt->execute([$categoryId, $nameAr, $descriptionAr, $priceValue, $currency, $imagePath, $isAvailable, $item['id']]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_items (category_id, name_ar, description_ar, price, currency, image_path, is_available)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$categoryId, $nameAr, $descriptionAr, $priceValue, $currency, $imagePath, $isAvailable]);
            }

           header('Location: items.php?saved=1');
exit;
        }
    }
}

$activePage = 'items';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isEdit ? 'Edit Item' : 'Add Item' ?> - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css?v=12">
<style>
    .form-card {
        max-width: 640px;
    }
    .image-preview {
        width: 100%;
        max-width: 220px;
        height: 160px;
        border-radius: 12px;
        background-color: #FDFBF6;
        border: 1.5px dashed #e5ddc8;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d8c48a;
        font-size: 2rem;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .form-label-soft {
        font-size: 0.85rem;
        color: #999;
        font-weight: 500;
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font"><?= $isEdit ? 'Edit Item' : 'Add Menu Item' ?></h3>
            <p class="page-sub"><?= $isEdit ? 'Update this item\'s details.' : 'Add a new item to your menu.' ?></p>
        </div>
        <a href="items.php" class="btn-outline-soft"><i class="bi bi-arrow-right"></i> Back to Items</a>
    </div>

    <div class="card-soft p-4 form-card">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label-soft">Item Photo</label>
                <div class="image-preview" id="imagePreview">
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($item['image_path']) ?>" id="previewImg">
                    <?php else: ?>
                        <i class="bi bi-image" id="previewIcon"></i>
                        <img src="" id="previewImg" style="display:none;">
                    <?php endif; ?>
                </div>
                <input type="file" name="image" id="imageInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">JPG, PNG, or WEBP. Max 5MB.</small>
            </div>

            <div class="mb-3">
                <label class="form-label-soft">Category</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name_en']) ?> — <?= htmlspecialchars($cat['name_ar']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label-soft">Item Name (Arabic)</label>
                <input type="text" name="name_ar" class="form-control" dir="rtl" value="<?= htmlspecialchars($item['name_ar']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label-soft">Description (Arabic) — optional</label>
                <textarea name="description_ar" class="form-control" dir="rtl" rows="3"><?= htmlspecialchars($item['description_ar'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-8">
                    <label class="form-label-soft">Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= $item['price'] !== null ? htmlspecialchars($item['price']) : '' ?>">
                </div>
                <div class="col-4">
                    <label class="form-label-soft">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="LBP" <?= $item['currency'] === 'LBP' ? 'selected' : '' ?>>LBP</option>
                        <option value="USD" <?= $item['currency'] === 'USD' ? 'selected' : '' ?>>USD</option>
                    </select>
                </div>
            </div>

            <div class="mb-4 form-check form-switch">
                <input type="checkbox" name="is_available" class="form-check-input" id="availableCheck" <?= $item['is_available'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="availableCheck">Available to customers</label>
            </div>

            <button type="submit" class="btn-gold w-100 justify-content-center">
                <?= $isEdit ? 'Save Changes' : 'Add Item' ?>
            </button>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Live preview of the selected image before upload
    document.getElementById('imageInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            const previewImg = document.getElementById('previewImg');
            const previewIcon = document.getElementById('previewIcon');
            previewImg.src = event.target.result;
            previewImg.style.display = 'block';
            if (previewIcon) previewIcon.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
</script>

</body>
</html>
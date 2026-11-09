<?php
require '../includes/auth.php';

require '../includes/db.php';
require '../includes/upload.php';

// ?id=... means "edit". It must be a valid positive integer AND an existing item.
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
    $id = input_id(INPUT_GET, 'id');
    $found = $id ? db_query("SELECT * FROM menu_items WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC) : false;
    if (!$found) {
        header('Location: items.php?error=notfound');
        exit;
    }
    $item = $found;
}

$categories = db_query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$categoryIds = array_map('intval', array_column($categories, 'id'));

$error = '';

if (post_was_too_large()) {
    $error = 'Image is too large. The maximum size is 1 MB.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = input_id(INPUT_POST, 'category_id');
    $nameAr = trim($_POST['name_ar'] ?? '');
    $descriptionAr = trim($_POST['description_ar'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $currency = $_POST['currency'] ?? 'LBP';
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $removeImage = isset($_POST['remove_image']);
    $imagePath = $item['image_path']; // keep existing unless changed below
    $priceValue = ($price === '') ? 0 : filter_var($price, FILTER_VALIDATE_FLOAT);

    // Keep what was typed if there's an error
    $item = array_merge($item, [
        'category_id' => $categoryId, 'name_ar' => $nameAr, 'description_ar' => $descriptionAr,
        'price' => $price, 'currency' => $currency, 'is_available' => $isAvailable,
    ]);

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$categoryId || !in_array($categoryId, $categoryIds, true) || $nameAr === '') {
        $error = 'Category and item name are required.';
    } elseif (!containsArabic($nameAr)) {
        $error = 'Item name must contain Arabic text.';
    } elseif ($priceValue === false || $priceValue < 0) {
        $error = 'Price must be a number that is not negative.';
    } elseif (!in_array($currency, ['LBP', 'USD'], true)) {
        $error = 'Invalid currency.';
    } else {
        $oldImage = $item['image_path'];
        $newImage = upload_image('image', 'item_', $error);

        if ($newImage !== null) {
            $imagePath = $newImage;
        } elseif ($error === '' && $removeImage) {
            $imagePath = null;
        }

        if ($error === '') {
            if ($isEdit) {
                db_query("
                    UPDATE menu_items
                    SET category_id = ?, name_ar = ?, description_ar = ?, price = ?, currency = ?, image_path = ?, is_available = ?
                    WHERE id = ?
                ", [$categoryId, $nameAr, $descriptionAr, $priceValue, $currency, $imagePath, $isAvailable, $item['id']]);
            } else {
                db_query("
                    INSERT INTO menu_items (category_id, name_ar, description_ar, price, currency, image_path, is_available)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$categoryId, $nameAr, $descriptionAr, $priceValue, $currency, $imagePath, $isAvailable]);
            }

            // Saved — now it's safe to delete the old photo if it was replaced or removed
            if ($isEdit && $oldImage && $oldImage !== $imagePath) {
                delete_upload($oldImage);
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
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
<style>
    .image-preview {
        width: 100%;
        aspect-ratio: 4 / 3;
        border-radius: 14px;
        background-color: #FDFBF6;
        border: 1.5px dashed #e5ddc8;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d8c48a;
        font-size: 2.5rem;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    /* Below the two-column layout, don't let the photo box grow huge */
    @media (max-width: 991.98px) {
        .image-preview { max-width: 360px; }
    }
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .form-check-input:checked { background-color: #C89B2B; border-color: #C89B2B; }
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
        <a href="items.php" class="btn-outline-soft"><i class="bi bi-arrow-left"></i> Back to Items</a>
    </div>

    <div class="card-soft p-4 form-card">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-4">
                <!-- Left: photo -->
                <div class="col-12 col-lg-4">
                    <p class="form-section-title">Item Photo</p>
                    <div class="image-preview" id="imagePreview">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="../<?= e($item['image_path']) ?>" id="previewImg" alt="">
                        <?php else: ?>
                            <i class="bi bi-image" id="previewIcon"></i>
                            <img src="" id="previewImg" alt="" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <input type="file" name="image" id="imageInput" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <small class="text-muted">JPG, JPEG, PNG, or WEBP. <strong>Max 1 MB.</strong></small>
                    <div class="text-danger small mt-1" id="imageError" style="display:none;"></div>

                    <?php if ($isEdit && !empty($item['image_path'])): ?>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="remove_image" class="form-check-input" id="removeImageCheck">
                        <label class="form-check-label" for="removeImageCheck">Remove current photo</label>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: details -->
                <div class="col-12 col-lg-8">
                    <p class="form-section-title">Details</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label-soft" for="nameInput">Item Name (Arabic)</label>
                            <input type="text" name="name_ar" id="nameInput" class="form-control" dir="rtl" value="<?= e($item['name_ar']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft" for="categorySelect">Category</label>
                            <select name="category_id" id="categorySelect" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= (int)$item['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name_en']) ?> — <?= e($cat['name_ar']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label-soft" for="descriptionInput">Description (Arabic) — optional</label>
                            <textarea name="description_ar" id="descriptionInput" class="form-control" dir="rtl" rows="4"><?= e($item['description_ar'] ?? '') ?></textarea>
                        </div>

                        <div class="col-8 col-md-4">
                            <label class="form-label-soft" for="priceInput">Price</label>
                            <input type="number" step="0.01" min="0" name="price" id="priceInput" class="form-control" value="<?= $item['price'] !== null ? e($item['price']) : '' ?>">
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label-soft" for="currencySelect">Currency</label>
                            <select name="currency" id="currencySelect" class="form-select">
                                <option value="LBP" <?= $item['currency'] === 'LBP' ? 'selected' : '' ?>>LBP</option>
                                <option value="USD" <?= $item['currency'] === 'USD' ? 'selected' : '' ?>>USD</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="checkbox" name="is_available" class="form-check-input" id="availableCheck" <?= $item['is_available'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="availableCheck">Available to customers</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions mt-4">
                <a href="items.php" class="btn-outline-soft">Cancel</a>
                <button type="submit" class="btn-gold">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save Changes' : 'Add Item' ?>
                </button>
            </div>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Live preview of the selected image before upload
    const removeImageCheck = document.getElementById('removeImageCheck');

    document.getElementById('imageInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        const imageError = document.getElementById('imageError');
        imageError.style.display = 'none';
        if (!file) return;

        // Quick check in the browser so the user doesn't wait for an upload
        // that will be rejected. The server checks again (that's the real check).
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            imageError.textContent = 'Image must be JPG, JPEG, PNG, or WEBP.';
            imageError.style.display = 'block';
            this.value = '';
            return;
        }
        if (file.size > 1048576) {
            imageError.textContent = 'This image is ' + (file.size / 1048576).toFixed(1) + ' MB. The maximum size is 1 MB.';
            imageError.style.display = 'block';
            this.value = '';
            return;
        }

        if (removeImageCheck) removeImageCheck.checked = false; // uploading a new photo overrides "remove"

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

    // Hide the preview immediately when "Remove current photo" is checked
    if (removeImageCheck) {
        removeImageCheck.addEventListener('change', function () {
            document.getElementById('previewImg').style.display = this.checked ? 'none' : 'block';
        });
    }
</script>

</body>
</html>
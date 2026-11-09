<?php
require '../includes/auth.php';

require '../includes/db.php';
require '../includes/upload.php';

$business = db_query("SELECT * FROM business_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$error = '';

if (post_was_too_large()) {
    $error = 'Logo is too large. The maximum size is 1 MB.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameEn = trim($_POST['business_name_en'] ?? '');
    $nameAr = trim($_POST['business_name_ar'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
    $addressEn = trim($_POST['address_en'] ?? '');
    $addressAr = trim($_POST['address_ar'] ?? '');
    $hoursAr = trim($_POST['hours_ar'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $openTime = trim($_POST['open_time'] ?? '');
    $closeTime = trim($_POST['close_time'] ?? '');
    $instagramUrl = trim($_POST['instagram_url'] ?? '');
    $logoPath = $business['logo_path']; // keep existing unless a new one is uploaded

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($nameEn === '' || $nameAr === '') {
        $error = 'Business name (English and Arabic) is required.';
    } elseif (!containsArabic($nameAr)) {
        $error = 'Arabic business name must contain Arabic text.';
    } elseif ($whatsappNumber !== '' && !preg_match('/^\+?[0-9]{8,15}$/', $whatsappNumber)) {
        $error = 'WhatsApp number should be digits only (with country code), e.g. 9613xxxxxx.';
    } elseif ($instagramUrl !== '' && !is_web_url($instagramUrl)) {
        $error = 'Please enter a valid Instagram URL.';
    } elseif (mb_strlen($tagline) > 150) {
        $error = 'Tagline must be 150 characters or less.';
    } elseif (($openTime !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $openTime))
           || ($closeTime !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $closeTime))) {
        $error = 'Opening and closing times must be valid times.';
    } elseif (($openTime === '') !== ($closeTime === '')) {
        $error = 'Please fill in both the opening and the closing time (or leave both empty).';
    } else {
        $oldLogo = $business['logo_path'];
        $newLogo = upload_image('logo', 'business_logo_', $error);
        if ($newLogo !== null) {
            $logoPath = $newLogo;
        }

        if ($error === '') {
            db_query("
                UPDATE business_settings
                SET business_name_en = ?, business_name_ar = ?, tagline = ?, phone = ?, whatsapp_number = ?, address_en = ?, address_ar = ?, hours_ar = ?, open_time = ?, close_time = ?, instagram_url = ?, logo_path = ?
                WHERE id = ?
            ", [$nameEn, $nameAr, $tagline, $phone, $whatsappNumber, $addressEn, $addressAr, $hoursAr, $openTime ?: null, $closeTime ?: null, $instagramUrl, $logoPath, $business['id']]);

            // Saved — now delete the old logo if it was replaced. delete_upload()
            // only removes logos uploaded here (business_logo_*), never the
            // original logo.png / logo-dark.png used elsewhere in the admin panel.
            if ($oldLogo !== $logoPath) {
                delete_upload($oldLogo);
            }

            header('Location: settings.php?saved=1');
            exit;
        }
    }

    // Keep the form showing what was just submitted (except the logo, which
    // is re-read fresh below) so an error doesn't wipe out what was typed.
    $business['business_name_en'] = $nameEn;
    $business['business_name_ar'] = $nameAr;
    $business['phone'] = $phone;
    $business['address_en'] = $addressEn;
    $business['address_ar'] = $addressAr;
    $business['hours_ar'] = $hoursAr;
    $business['tagline'] = $tagline;
    $business['open_time'] = $openTime;
    $business['close_time'] = $closeTime;
    $business['whatsapp_number'] = $whatsappNumber;
    $business['instagram_url'] = $instagramUrl;
}

$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Naji Bakery Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/admin.css') ?>">
<style>
    .logo-preview {
        width: 100%;
        height: 180px;
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
    .logo-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h3 class="page-title brand-font">Settings</h3>
            <p class="page-sub">Business info shown on your public menu.</p>
        </div>
    </div>

    <div class="card-soft p-4 form-card">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <p class="form-section-title">Logo (shown on the public menu)</p>
                    <div class="logo-preview" id="logoPreview">
                        <?php if (!empty($business['logo_path'])): ?>
                            <img src="../<?= e($business['logo_path']) ?>" id="previewImg">
                        <?php else: ?>
                            <i class="bi bi-image" id="previewIcon"></i>
                            <img src="" id="previewImg" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <input type="file" name="logo" id="logoInput" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="text-danger small mt-1" id="logoError" style="display:none;"></div>
                    <small class="text-muted">JPG, JPEG, PNG, or WEBP. <strong>Max 1 MB.</strong> This does not change the admin panel's own logo — only the customer-facing menu.</small>
                </div>

                <div class="col-12 col-lg-8">
                    <p class="form-section-title">Business Details</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Business Name (English)</label>
                            <input type="text" name="business_name_en" class="form-control" value="<?= e($business['business_name_en'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Business Name (Arabic)</label>
                            <input type="text" name="business_name_ar" class="form-control" dir="rtl" value="<?= e($business['business_name_ar'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Phone — optional</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($business['phone'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">WhatsApp Number — optional</label>
                            <input type="text" name="whatsapp_number" class="form-control" dir="ltr" placeholder="9613xxxxxx" value="<?= e($business['whatsapp_number'] ?? '') ?>">
                            <small class="text-muted">Digits only, with country code, no spaces or +. Powers the WhatsApp button on the public menu — the button won't show until this is filled in.</small>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Address (English) — optional</label>
                            <input type="text" name="address_en" class="form-control" value="<?= e($business['address_en'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Address (Arabic) — optional</label>
                            <input type="text" name="address_ar" class="form-control" dir="rtl" value="<?= e($business['address_ar'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Opening Hours (Arabic) — optional</label>
                            <input type="text" name="hours_ar" class="form-control" dir="rtl" placeholder="مثال: ٩ صباحاً - ١٠ مساءً" value="<?= e($business['hours_ar'] ?? '') ?>">
                            <small class="text-muted">Shown on the public menu with a clock icon.</small>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label-soft" for="openTimeInput">Opens at</label>
                            <input type="time" name="open_time" id="openTimeInput" class="form-control" value="<?= e(substr($business['open_time'] ?? '', 0, 5)) ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label-soft" for="closeTimeInput">Closes at</label>
                            <input type="time" name="close_time" id="closeTimeInput" class="form-control" value="<?= e(substr($business['close_time'] ?? '', 0, 5)) ?>">
                        </div>
                        <div class="col-12" style="margin-top: 0.35rem;">
                            <small class="text-muted">Opens at / Closes at power the live "Open now / Closed" badge on the public menu (Lebanon time) — keep them in sync with the hours text. A closing time earlier than the opening time means it closes after midnight.</small>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft" for="taglineInput">Tagline — optional</label>
                            <input type="text" name="tagline" id="taglineInput" class="form-control" maxlength="150" placeholder="Freshly baked every morning" value="<?= e($business['tagline'] ?? '') ?>">
                            <small class="text-muted">Shown under the logo at the top of the public menu.</small>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label-soft">Instagram URL — optional</label>
                            <input type="url" name="instagram_url" class="form-control" dir="ltr" placeholder="https://instagram.com/yourbakery" value="<?= e($business['instagram_url'] ?? '') ?>">
                            <small class="text-muted">Shown as an icon link in the public menu header.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn-gold"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Live preview of the selected logo before upload
    document.getElementById('logoInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        const logoError = document.getElementById('logoError');
        logoError.style.display = 'none';
        if (!file) return;

        // Quick check in the browser; the server checks again (that's the real check).
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            logoError.textContent = 'Logo must be JPG, JPEG, PNG, or WEBP.';
            logoError.style.display = 'block';
            this.value = '';
            return;
        }
        if (file.size > 1048576) {
            logoError.textContent = 'This image is ' + (file.size / 1048576).toFixed(1) + ' MB. The maximum size is 1 MB.';
            logoError.style.display = 'block';
            this.value = '';
            return;
        }

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

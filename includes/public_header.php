<?php
// --- Shared header for every public page ---
// Centered logo with a "Since 1996" tagline under it, a small location/hours
// line below that, and an Instagram button in the top corner (if set).
//
// Everything shown here comes from the business_settings table and is edited
// on the admin Settings page — nothing is hardcoded in this HTML.
// Expects $business (the business_settings row) to be loaded by the page.
// Also sets $telLink and $whatsappLink for the page's floating buttons.

// tel: link from the first number in the phone field (it may list more than
// one number separated by "/"), and a wa.me link from the WhatsApp field —
// kept separate since wa.me needs a strict digits-only international number.
$firstPhone     = trim(explode('/', $business['phone'] ?? '')[0]);
$telLink        = $firstPhone !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $firstPhone) : '';
$whatsappDigits = preg_replace('/\D/', '', $business['whatsapp_number'] ?? '');
$whatsappLink   = $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits : '';
$instagramLink  = (!empty($business['instagram_url']) && is_web_url($business['instagram_url'])) ? $business['instagram_url'] : '';
?>
<header class="menu-header">
    <?php if ($instagramLink !== ''): ?>
    <div class="header-social">
        <a class="header-social-btn instagram" href="<?= e($instagramLink) ?>" target="_blank" rel="noopener" aria-label="انستغرام" title="انستغرام">
            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
        </a>
    </div>
    <?php endif; ?>

    <h1 class="menu-header-logo">
        <img src="../<?= e($business['logo_path'] ?: 'uploads/logo.png') ?>"
             alt="<?= e($business['business_name_ar'] ?? 'Naji Bakery') ?>"
             class="logo" width="419" height="419" decoding="async">
    </h1>
    <p class="header-since" dir="ltr">Since 1996</p>

    <?php if (!empty($business['address_ar']) || !empty($business['hours_ar'])): ?>
    <p class="header-info">
        <?php if (!empty($business['address_ar'])): ?>
            <span class="header-info-item"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($business['address_ar']) ?></span>
        <?php endif; ?>
        <?php if (!empty($business['address_ar']) && !empty($business['hours_ar'])): ?>
            <span class="header-info-dot" aria-hidden="true">•</span>
        <?php endif; ?>
        <?php if (!empty($business['hours_ar'])): ?>
            <span class="header-info-item"><i class="fa-regular fa-clock" aria-hidden="true"></i><?= e($business['hours_ar']) ?></span>
        <?php endif; ?>
    </p>
    <?php endif; ?>
</header>

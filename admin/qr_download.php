<?php
require '../includes/auth.php';

require '../includes/db.php';

$business = db_query("SELECT * FROM business_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$menuUrl = $business['menu_url'] ?? 'http://localhost/naji_bakery/public/index.php';

$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($menuUrl);

// We fetch the QR image ourselves (server-side) instead of linking the
// browser straight to api.qrserver.com. A <a download> link only forces a
// real download when the file comes from the SAME site — for an external
// (cross-origin) image, browsers just open/navigate to it instead. By
// fetching it here and serving it from our own site, the download works.
$imageData = @file_get_contents($qrImageUrl);

if ($imageData === false) {
    http_response_code(502);
    echo 'Could not generate the QR code image right now. Please try again.';
    exit;
}

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="naji-bakery-menu-qr.png"');
header('Content-Length: ' . strlen($imageData));
echo $imageData;

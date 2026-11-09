<?php
define('AUTH_JSON', true);
require '../includes/auth.php';

require '../includes/db.php';
require '../includes/upload.php';

header('Content-Type: application/json');

if (!csrf_valid()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
    exit;
}

$action = $_POST['action'] ?? '';

// Every action needs a valid item ID that exists in the database.
$id = input_id(INPUT_POST, 'id');
$existing = $id ? db_query("SELECT id, image_path FROM menu_items WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC) : false;
if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Item not found. Please refresh the page.']);
    exit;
}

if ($action === 'toggle_availability') {
    $isAvailable = ($_POST['is_available'] ?? '') === '1' ? 1 : 0;

    db_query("UPDATE menu_items SET is_available = ? WHERE id = ?", [$isAvailable, $id]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    db_query("DELETE FROM menu_items WHERE id = ?", [$id]);

    // Remove the item's photo from disk too
    delete_upload($existing['image_path']);

    echo json_encode(['success' => true, 'message' => 'Item deleted']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
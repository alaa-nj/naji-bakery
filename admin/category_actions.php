<?php
define('AUTH_JSON', true);
require '../includes/auth.php';

require '../includes/db.php';
require '../includes/category_icons.php';

header('Content-Type: application/json');

if (!csrf_valid()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
    exit;
}

$action = $_POST['action'] ?? '';

// Icon must be one of the allowed Font Awesome icons (see includes/category_icons.php)
$icon = $_POST['icon'] ?? CATEGORY_ICON_DEFAULT;
if (($action === 'add' || $action === 'edit') && !isset(CATEGORY_ICONS[$icon])) {
    echo json_encode(['success' => false, 'message' => 'Please choose an icon from the list.']);
    exit;
}

function category_exists($id) {
    return (bool)db_query("SELECT 1 FROM categories WHERE id = ?", [$id])->fetchColumn();
}

if ($action === 'add') {
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

   if ($nameEn === '' || $nameAr === '') {
    echo json_encode(['success' => false, 'message' => 'Both names are required']);
    exit;
}

if (!containsArabic($nameAr)) {
    echo json_encode(['success' => false, 'message' => 'Arabic name must contain Arabic text']);
    exit;
}

    // Block duplicate names (case-insensitive) so you don't end up with
    // two categories that are really the same thing, like "Bread" / "bread".
    $dup = db_query("SELECT COUNT(*) FROM categories WHERE LOWER(name_en) = LOWER(?)", [$nameEn]);
    if ($dup->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'A category with this English name already exists.']);
        exit;
    }

    $dup = db_query("SELECT COUNT(*) FROM categories WHERE LOWER(name_ar) = LOWER(?)", [$nameAr]);
    if ($dup->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'A category with this Arabic name already exists.']);
        exit;
    }

    // Put new category at the end of the list
    $maxOrder = db_query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();

    $stmt = db_query("INSERT INTO categories (name_en, name_ar, icon, display_order) VALUES (?, ?, ?, ?)", [$nameEn, $nameAr, $icon, $maxOrder + 1]);

    echo json_encode(['success' => true, 'message' => 'Category added']);
    exit;
}

if ($action === 'edit') {
    $id = input_id(INPUT_POST, 'id');
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if (!$id || $nameEn === '' || $nameAr === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    if (!category_exists($id)) {
        echo json_encode(['success' => false, 'message' => 'Category not found. Please refresh the page.']);
        exit;
    }

    if (!containsArabic($nameAr)) {
        echo json_encode(['success' => false, 'message' => 'Arabic name must contain Arabic text']);
        exit;
    }

    // Block duplicate names (case-insensitive), excluding this category itself
    $dup = db_query("SELECT COUNT(*) FROM categories WHERE LOWER(name_en) = LOWER(?) AND id != ?", [$nameEn, $id]);
    if ($dup->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'A category with this English name already exists.']);
        exit;
    }

    $dup = db_query("SELECT COUNT(*) FROM categories WHERE LOWER(name_ar) = LOWER(?) AND id != ?", [$nameAr, $id]);
    if ($dup->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'A category with this Arabic name already exists.']);
        exit;
    }

    $stmt = db_query("UPDATE categories SET name_en = ?, name_ar = ?, icon = ? WHERE id = ?", [$nameEn, $nameAr, $icon, $id]);

    echo json_encode(['success' => true, 'message' => 'Category updated']);
    exit;
}

if ($action === 'delete') {
    $id = input_id(INPUT_POST, 'id');

    if (!$id || !category_exists($id)) {
        echo json_encode(['success' => false, 'message' => 'Category not found. Please refresh the page.']);
        exit;
    }

    // Check if this category still has items before allowing deletion
    $countStmt = db_query("SELECT COUNT(*) FROM menu_items WHERE category_id = ?", [$id]);
    $itemCount = $countStmt->fetchColumn();

    if ($itemCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Can't delete this category — it still has $itemCount item(s). Move or delete them first."
        ]);
        exit;
    }

    $stmt = db_query("DELETE FROM categories WHERE id = ?", [$id]);

    echo json_encode(['success' => true, 'message' => 'Category deleted']);
    exit;
}

if ($action === 'move_up' || $action === 'move_down') {
    $id = input_id(INPUT_POST, 'id');

    if (!$id || !category_exists($id)) {
        echo json_encode(['success' => false, 'message' => 'Category not found. Please refresh the page.']);
        exit;
    }

    // Get every category in its current order, find this one, and swap its
    // display_order with the neighbor directly above or below it.
    $ordered = db_query("SELECT id, display_order FROM categories ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $index = null;
    foreach ($ordered as $i => $row) {
        if ((int)$row['id'] === $id) {
            $index = $i;
            break;
        }
    }

    if ($index === null) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit;
    }

    $swapWith = $action === 'move_up' ? $index - 1 : $index + 1;

    if ($swapWith < 0 || $swapWith >= count($ordered)) {
        echo json_encode(['success' => false, 'message' => 'Already at the ' . ($action === 'move_up' ? 'top' : 'bottom')]);
        exit;
    }

    $current = $ordered[$index];
    $neighbor = $ordered[$swapWith];

    $stmt = $pdo->prepare("UPDATE categories SET display_order = ? WHERE id = ?");
    $stmt->execute([$neighbor['display_order'], $current['id']]);
    $stmt->execute([$current['display_order'], $neighbor['id']]);

    echo json_encode(['success' => true, 'message' => 'Order updated']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

require '../includes/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

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

    // Put new category at the end of the list
    $maxOrder = $pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO categories (name_en, name_ar, display_order) VALUES (?, ?, ?)");
    $stmt->execute([$nameEn, $nameAr, $maxOrder + 1]);

    echo json_encode(['success' => true, 'message' => 'Category added']);
    exit;
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if ($id <= 0 || $nameEn === '' || $nameAr === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE categories SET name_en = ?, name_ar = ? WHERE id = ?");
    $stmt->execute([$nameEn, $nameAr, $id]);

    echo json_encode(['success' => true, 'message' => 'Category updated']);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        exit;
    }

    // Check if this category still has items before allowing deletion
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
    $countStmt->execute([$id]);
    $itemCount = $countStmt->fetchColumn();

    if ($itemCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Can't delete this category — it still has $itemCount item(s). Move or delete them first."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Category deleted']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
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

if ($action === 'toggle_availability') {
    $id = (int)($_POST['id'] ?? 0);
    $isAvailable = (int)($_POST['is_available'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
    $stmt->execute([$isAvailable, $id]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Item deleted']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
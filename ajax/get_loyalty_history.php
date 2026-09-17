<?php
// ajax/get_loyalty_history.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

$stmt = $db->prepare("
    SELECT id, type, points, points_before, points_after, description, created_at
    FROM loyalty_point_history
    WHERE customer_id = :cid
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute(['cid' => $customer_id]);
$items = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'items' => array_map(function($i) {
        return [
            'id' => (int)$i['id'],
            'type' => $i['type'],
            'points' => (int)$i['points'],
            'points_before' => (int)$i['points_before'],
            'points_after' => (int)$i['points_after'],
            'description' => $i['description'],
            'created_at' => $i['created_at']
        ];
    }, $items)
]);
exit;
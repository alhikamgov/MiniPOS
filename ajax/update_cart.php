<?php
// ajax/update_cart.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] != 'Kasir') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart = $input['cart'] ?? [];

// Sanitasi data
$clean = [];
foreach ($cart as $item) {
    if (!isset($item['id'], $item['name'], $item['price'], $item['quantity'])) continue;
    $clean[] = [
        'id' => (int)$item['id'],
        'name' => (string)$item['name'],
        'price' => (float)$item['price'],
        'quantity' => max(1, (int)$item['quantity']),
        'max_stock' => (int)($item['max_stock'] ?? 0)
    ];
}

$_SESSION['cart'] = $clean;

$total = 0;
foreach ($clean as $item) $total += $item['price'] * $item['quantity'];

echo json_encode([
    'success' => true,
    'cart' => $clean,
    'total' => $total
]);
exit;
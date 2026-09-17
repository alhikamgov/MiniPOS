<?php
// ajax/add_to_cart.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] != 'Kasir') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$name = $input['name'] ?? '';
$price = (float)($input['price'] ?? 0);
$stock = (int)($input['stock'] ?? 0);

if (!$id || !$name || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data produk tidak valid']);
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['id'] == $id) {
        if ($item['quantity'] < $item['max_stock']) {
            $item['quantity']++;
            $found = true;
        } else {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
            exit;
        }
        break;
    }
}
unset($item);

if (!$found) {
    $_SESSION['cart'][] = [
        'id' => (int)$id,
        'name' => $name,
        'price' => $price,
        'quantity' => 1,
        'max_stock' => $stock
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
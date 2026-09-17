<?php
// ajax/get_cart.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] != 'Kasir') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$cart = $_SESSION['cart'] ?? [];
header('Content-Type: application/json');
echo json_encode($cart);
exit;
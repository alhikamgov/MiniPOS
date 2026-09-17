<?php
// ajax/get_product.php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$response = [];

if (strlen($search) > 0) {
    $stmt = $db->prepare("SELECT id, barcode, name, selling_price, stock, unit 
                          FROM products 
                          WHERE barcode LIKE :search OR name LIKE :search 
                          ORDER BY name LIMIT 20");
    $stmt->execute(['search' => "%$search%"]);
    $products = $stmt->fetchAll();
    
    foreach ($products as $p) {
        $response[] = [
            'id' => (int)$p['id'],
            'barcode' => $p['barcode'],
            'name' => $p['name'],
            'selling_price' => (float)$p['selling_price'],
            'stock' => (int)$p['stock'],
            'unit' => $p['unit']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
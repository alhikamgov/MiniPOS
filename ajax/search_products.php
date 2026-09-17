<?php
// ajax/search_products.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] != 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ''; $params = [];
if ($search !== '') {
    $where = "WHERE p.name LIKE :search OR p.barcode LIKE :search";
    $params['search'] = "%$search%";
}

$count_stmt = $db->prepare("SELECT COUNT(*) FROM products p $where");
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();
$total_pages = (int)ceil($total_products / $limit);

// ===== FIX: Produk stok menipis di atas =====
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, s.name as supplier_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    $where 
    ORDER BY (p.stock <= p.min_stock) DESC, p.stock ASC, p.name ASC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$items = [];
foreach ($products as $p) {
    $photo_url = null;
    if ($p['photo'] && file_exists(__DIR__ . '/../' . $p['photo'])) {
        $photo_url = '../' . $p['photo'];
    }
    $items[] = [
        'id' => (int)$p['id'],
        'barcode' => $p['barcode'] ?? '',
        'name' => $p['name'],
        'category_name' => $p['category_name'] ?? '',
        'category_id' => $p['category_id'],
        'supplier_id' => $p['supplier_id'],
        'purchase_price' => (float)$p['purchase_price'],
        'selling_price' => (float)$p['selling_price'],
        'min_stock' => (int)$p['min_stock'],
        'stock' => (int)$p['stock'],
        'unit' => $p['unit'] ?: 'Pcs',
        'photo_url' => $photo_url,
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'total' => $total_products,
    'page' => $page,
    'total_pages' => $total_pages,
    'search' => $search,
]);
exit;
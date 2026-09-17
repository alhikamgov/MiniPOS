<?php
// ajax/get_transaction_detail.php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($transaction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Kasir hanya bisa lihat miliknya sendiri, admin bisa lihat semua
if ($_SESSION['role_name'] === 'Kasir') {
    $check = $db->prepare("SELECT id FROM transactions WHERE id = :id AND cashier_id = :uid AND status = 'completed'");
    $check->execute(['id' => $transaction_id, 'uid' => $_SESSION['user_id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit;
    }
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE id = :id");
$stmt->execute(['id' => $transaction_id]);
$transaction = $stmt->fetch();

$items_stmt = $db->prepare("
    SELECT ti.*, p.name 
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    WHERE ti.transaction_id = :tid
");
$items_stmt->execute(['tid' => $transaction_id]);
$items = $items_stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => [
        'invoice_number' => $transaction['invoice_number'],
        'transaction_date' => $transaction['transaction_date'],
        'total_amount' => (float)$transaction['total_amount'],
        'discount_amount' => (float)$transaction['discount_amount'],
        'final_amount' => (float)$transaction['final_amount'],
        'total_profit' => (float)$transaction['total_profit'],
        'payment_method' => $transaction['payment_method'],
        'cash_received' => (float)$transaction['cash_received'],
        'change_amount' => (float)$transaction['change_amount'],
        'points_earned' => (int)($transaction['points_earned'] ?? 0),
        'items' => array_map(function($i) {
            return [
                'name' => $i['name'],
                'quantity' => (int)$i['quantity'],
                'selling_price' => (float)$i['selling_price'],
                'purchase_price' => (float)($i['purchase_price_at_time'] ?? 0),
                'subtotal' => (float)$i['subtotal']
            ];
        }, $items)
    ]
]);
exit;
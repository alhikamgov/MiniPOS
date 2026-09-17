<?php
// ajax/process_transaction.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] != 'Kasir') {
    http_response_code(403);
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid input']);
}

$cart = $input['cart'] ?? [];
$payment_method = $input['payment_method'] ?? 'cash';
$customer_id = isset($input['customer_id']) ? (int)$input['customer_id'] : null;
$cash_received = (float)($input['cash_received'] ?? 0);
$discount_amount = (float)($input['discount_amount'] ?? 0);

if (empty($cart)) {
    sendJsonResponse(['success' => false, 'message' => 'Keranjang kosong!']);
}

$user_id = $_SESSION['user_id'];
$invoice = generateInvoiceNumber();

$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Validasi diskon: 0 ≤ diskon ≤ total
if ($discount_amount < 0) $discount_amount = 0;
if ($discount_amount > $total_amount) $discount_amount = $total_amount;

$tax = 0;
$final_amount = $total_amount - $discount_amount + $tax;
$change_amount = $payment_method === 'cash' ? ($cash_received - $final_amount) : 0;

// ============================================================
// HITUNG LABA
// Laba = final_amount - total_harga_beli
// ============================================================
$total_purchase_cost = 0;
foreach ($cart as $item) {
    $p = $db->prepare("SELECT purchase_price FROM products WHERE id = :id");
    $p->execute(['id' => $item['id']]);
    $purchase_price = (float)$p->fetchColumn();
    $total_purchase_cost += $purchase_price * $item['quantity'];
}
$total_profit = $final_amount - $total_purchase_cost;

// ============================================================
// HITUNG POIN: Setiap Rp 1.000 = 1 poin (setelah diskon)
// ============================================================
$points_earned = 0;
$points_before = 0;
$points_after = 0;
$customer_name_snapshot = null;

if ($customer_id) {
    $cust_stmt = $db->prepare("SELECT name, loyalty_points FROM customers WHERE id = :id");
    $cust_stmt->execute(['id' => $customer_id]);
    $customer = $cust_stmt->fetch();

    if ($customer) {
        $customer_name_snapshot = $customer['name'];
        $points_before = (int)$customer['loyalty_points'];
        $points_earned = (int)floor($final_amount / 1000);
        $points_after = $points_before + $points_earned;
    }
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO transactions 
                          (invoice_number, cashier_id, customer_id, customer_name_snapshot, 
                           total_amount, discount_amount, tax_amount, final_amount, total_profit,
                           points_earned, points_before, points_after,
                           payment_method, cash_received, change_amount, 
                           payment_status, status, notes) 
                          VALUES (:inv, :cashier, :cust, :cust_name, 
                                  :total, :disc, :tax, :final, :profit,
                                  :pe, :pb, :pa,
                                  :pay_method, :cash_recv, :change_amt, 
                                  'paid', 'completed', '')");
    $stmt->execute([
        'inv' => $invoice,
        'cashier' => $user_id,
        'cust' => $customer_id,
        'cust_name' => $customer_name_snapshot,
        'total' => $total_amount,
        'disc' => $discount_amount,
        'tax' => $tax,
        'final' => $final_amount,
        'profit' => $total_profit,
        'pe' => $points_earned,
        'pb' => $points_before,
        'pa' => $points_after,
        'pay_method' => $payment_method,
        'cash_recv' => $cash_received,
        'change_amt' => $change_amount
    ]);

    $transaction_id = $db->lastInsertId();

    foreach ($cart as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        
        // Ambil harga beli saat ini untuk historical
        $p = $db->prepare("SELECT purchase_price FROM products WHERE id = :id");
        $p->execute(['id' => $item['id']]);
        $purchase_price = (float)$p->fetchColumn();

        $stmt = $db->prepare("INSERT INTO transaction_items 
                              (transaction_id, product_id, quantity, selling_price, purchase_price_at_time, discount_per_item, subtotal) 
                              VALUES (:tid, :pid, :qty, :price, :buy, 0, :subtotal)");
        $stmt->execute([
            'tid' => $transaction_id,
            'pid' => $item['id'],
            'qty' => $item['quantity'],
            'price' => $item['price'],
            'buy' => $purchase_price,
            'subtotal' => $subtotal
        ]);

        $reduce = reduceStock($item['id'], $item['quantity'], $user_id, $invoice);
        if ($reduce !== true) {
            throw new Exception($reduce);
        }
    }

    if ($customer_id && $points_earned > 0) {
        $db->prepare("UPDATE customers SET loyalty_points = :pts WHERE id = :id")
           ->execute(['pts' => $points_after, 'id' => $customer_id]);

        $db->prepare("INSERT INTO loyalty_point_history 
                      (customer_id, transaction_id, type, points, points_before, points_after, description) 
                      VALUES (:cid, :tid, 'earn', :pts, :pb, :pa, :desc)")
           ->execute([
               'cid' => $customer_id,
               'tid' => $transaction_id,
               'pts' => $points_earned,
               'pb' => $points_before,
               'pa' => $points_after,
               'desc' => "Belanja $invoice (Rp " . number_format($final_amount, 0, ',', '.') . ")"
           ]);
    }

    $db->commit();
    unset($_SESSION['cart']);

    sendJsonResponse([
        'success' => true,
        'invoice' => $invoice,
        'total_amount' => $total_amount,
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount,
        'total_profit' => $total_profit,
        'cash_received' => $cash_received,
        'change' => $change_amount,
        'payment_method' => $payment_method,
        'items' => $cart,
        'customer_name' => $customer_name_snapshot,
        'points_earned' => $points_earned,
        'points_before' => $points_before,
        'points_after' => $points_after
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
}
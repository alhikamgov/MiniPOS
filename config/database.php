<?php
// config/database.php

// ===== SET TIMEZONE KE WIB =====
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'minimarket';

try {
    $db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ============================================================
// PASSWORD HASHING (Bcrypt) - Menggantikan MD5
// ============================================================
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

function verifyPassword($password, $hash) {
    // Deteksi apakah hash MD5 lama (32 karakter hex)
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        // Fallback ke MD5 untuk migrasi
        return md5($password) === $hash;
    }
    return password_verify($password, $hash);
}

// ============================================================
// HELPER SETTINGS
// ============================================================
function getSetting($key) {
    global $db;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['setting_value'] : null;
    return $cache[$key];
}

$app_name = getSetting('app_name') ?: 'MiniPOS';
$theme = getSetting('theme') ?: 'light';
$store_address = getSetting('store_address') ?: 'Alamat belum diatur';
$store_phone = getSetting('store_phone') ?: '-';

// ============================================================
// HELPER TRANSAKSI
// ============================================================
function generateInvoiceNumber() {
    global $db;
    $date = date('Ymd');
    $stmt = $db->query("SELECT COUNT(*) FROM transactions WHERE invoice_number LIKE 'INV-{$date}-%'");
    $count = $stmt->fetchColumn() + 1;
    return 'INV-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

function reduceStock($product_id, $quantity, $user_id, $reference = '') {
    global $db;
    try {
        $stmt = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty");
        $stmt->execute(['qty' => $quantity, 'id' => $product_id]);
        if ($stmt->rowCount() == 0) return "Stok tidak mencukupi untuk produk ID: $product_id";
        $stmt = $db->prepare("INSERT INTO stock_mutations (product_id, user_id, type, quantity, reference, notes) 
                              VALUES (:pid, :uid, 'out', :qty, :ref, 'Terjual via POS')");
        $stmt->execute(['pid' => $product_id, 'uid' => $user_id, 'qty' => -$quantity, 'ref' => $reference]);
        return true;
    } catch (Exception $e) { return $e->getMessage(); }
}

function addStock($product_id, $quantity, $user_id, $reference = '', $notes = '') {
    global $db;
    try {
        $stmt = $db->prepare("UPDATE products SET stock = stock + :qty WHERE id = :id");
        $stmt->execute(['qty' => $quantity, 'id' => $product_id]);
        $stmt = $db->prepare("INSERT INTO stock_mutations (product_id, user_id, type, quantity, reference, notes) 
                              VALUES (:pid, :uid, 'in', :qty, :ref, :notes)");
        $stmt->execute(['pid' => $product_id, 'uid' => $user_id, 'qty' => $quantity, 'ref' => $reference, 'notes' => $notes]);
        return true;
    } catch (Exception $e) { return $e->getMessage(); }
}
?>
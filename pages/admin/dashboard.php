<?php
include '../../includes/header.php';
requireRole('Admin');

// Statistik
$total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$today_sales = $db->query("SELECT COALESCE(SUM(final_amount), 0) FROM transactions WHERE DATE(transaction_date) = CURDATE() AND status = 'completed'")->fetchColumn();
$low_stock = $db->query("SELECT COUNT(*) FROM products WHERE stock <= min_stock")->fetchColumn();
$total_transactions = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

// LABA HARI INI
$today_profit = $db->query("
    SELECT COALESCE(SUM((ti.selling_price - p.purchase_price) * ti.quantity), 0)
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.transaction_date) = CURDATE() AND t.status = 'completed'
")->fetchColumn();
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-chart-pie text-primary mr-2"></i>Dashboard Admin</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5 mb-6">
    <?php
    $cards = [
        ['icon' => 'fa-boxes', 'label' => 'Total Produk', 'value' => number_format($total_products), 'color' => 'bg-blue-500'],
        ['icon' => 'fa-money-bill-wave', 'label' => 'Omzet Hari Ini', 'value' => 'Rp ' . number_format($today_sales, 0, ',', '.'), 'color' => 'bg-emerald-500'],
        ['icon' => 'fa-chart-line', 'label' => 'Laba Hari Ini', 'value' => 'Rp ' . number_format($today_profit, 0, ',', '.'), 'color' => 'bg-teal-500'],
        ['icon' => 'fa-exclamation-triangle', 'label' => 'Stok Menipis', 'value' => number_format($low_stock), 'color' => 'bg-amber-500'],
        ['icon' => 'fa-receipt', 'label' => 'Total Transaksi', 'value' => number_format($total_transactions), 'color' => 'bg-purple-500'],
    ];
    foreach ($cards as $c): ?>
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden hover:shadow-md transition">
            <i class="fas <?= $c['icon'] ?> absolute right-4 bottom-4 text-5xl opacity-10"></i>
            <div class="text-sm text-slate-500 dark:text-slate-400"><?= $c['label'] ?></div>
            <div class="text-2xl font-extrabold mt-1"><?= $c['value'] ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 border-l-4 border-l-primary">
    <h3 class="font-bold text-lg mb-3"><i class="fas fa-info-circle text-primary mr-2"></i>Menu Admin</h3>
    <ul class="list-disc list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
        <li><b>Riwayat Transaksi</b> – data transaksi pembelian.</li>
        <li><b>Manajemen Produk</b> – kelola barang, stok, kategori, supplier.</li>
        <li><b>Manajemen Pengguna</b> – kelola akun kasir.</li>
        <li><b>Manajemen Pelanggan</b> – data pelanggan & poin loyalty.</li>
        <li><b>Laporan Penjualan</b> – analisa penjualan, pie chart, jam sibuk.</li>
        <li><b>Pengaturan</b> – nama toko, alamat, tema.</li>
    </ul>
</div>

<?php include '../../includes/footer.php'; ?>
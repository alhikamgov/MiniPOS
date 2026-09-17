<?php
include '../../includes/header.php';
requireRole('Kasir');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Omzet & jumlah transaksi hari ini
$stmt = $db->prepare("SELECT COALESCE(SUM(final_amount),0), COUNT(*) FROM transactions 
                      WHERE cashier_id=:uid AND DATE(transaction_date)=:t AND status='completed'");
$stmt->execute(['uid' => $user_id, 't' => $today]);
[$omzet, $count] = $stmt->fetch(PDO::FETCH_NUM);

// Laba hari ini
$stmt = $db->prepare("
    SELECT COALESCE(SUM((ti.selling_price - p.purchase_price) * ti.quantity), 0)
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.cashier_id = :uid AND DATE(t.transaction_date) = :t AND t.status = 'completed'
");
$stmt->execute(['uid' => $user_id, 't' => $today]);
$today_profit = $stmt->fetchColumn();

$low_stock = $db->query("SELECT COUNT(*) FROM products WHERE stock <= min_stock")->fetchColumn();
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-chart-pie text-primary mr-2"></i>Dashboard Kasir</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden">
        <i class="fas fa-coins absolute right-4 bottom-4 text-5xl opacity-10"></i>
        <div class="text-sm text-slate-500 dark:text-slate-400">Omzet Hari Ini</div>
        <div class="text-2xl font-extrabold mt-1 text-emerald-600">Rp <?= number_format($omzet, 0, ',', '.') ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden">
        <i class="fas fa-chart-line absolute right-4 bottom-4 text-5xl opacity-10"></i>
        <div class="text-sm text-slate-500 dark:text-slate-400">Laba Hari Ini</div>
        <div class="text-2xl font-extrabold mt-1 text-teal-600">Rp <?= number_format($today_profit, 0, ',', '.') ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden">
        <i class="fas fa-shopping-cart absolute right-4 bottom-4 text-5xl opacity-10"></i>
        <div class="text-sm text-slate-500 dark:text-slate-400">Jumlah Transaksi</div>
        <div class="text-2xl font-extrabold mt-1"><?= $count ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 relative overflow-hidden">
        <i class="fas fa-box-open absolute right-4 bottom-4 text-5xl opacity-10"></i>
        <div class="text-sm text-slate-500 dark:text-slate-400">Notifikasi Stok</div>
        <div class="text-2xl font-extrabold mt-1 <?= $low_stock > 0 ? 'text-red-500' : 'text-emerald-600' ?>"><?= $low_stock ?></div>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 border-l-4 border-l-primary">
    <h3 class="font-bold text-lg mb-3"><i class="fas fa-info-circle text-primary mr-2"></i>Panduan Kasir</h3>
    <ul class="list-disc list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
        <li>Buka menu <b>Kasir / Transaksi</b> untuk melayani pembelian.</li>
        <li>Lihat <b>Laporan Kasir</b> untuk melihat riwayat shift Anda.</li>
    </ul>
</div>

<?php include '../../includes/footer.php'; ?>
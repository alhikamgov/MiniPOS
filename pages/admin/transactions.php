<?php
// pages/admin/transactions.php
session_start();
include '../../includes/header.php';
requireRole('Admin');

// Filter
$filter = $_GET['filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_cashier = (int)($_GET['cashier'] ?? 0);
$filter_method = $_GET['method'] ?? '';

switch ($filter) {
    case 'today':
        $start_date = $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE
$where = "WHERE DATE(t.transaction_date) BETWEEN :s AND :e AND t.status = 'completed'";
$params = ['s' => $start_date, 'e' => $end_date];

if ($filter_cashier > 0) {
    $where .= " AND t.cashier_id = :cid";
    $params['cid'] = $filter_cashier;
}
if ($filter_method !== '') {
    $where .= " AND t.payment_method = :pm";
    $params['pm'] = $filter_method;
}

// Total
$count_stmt = $db->prepare("SELECT COUNT(*), COALESCE(SUM(t.final_amount),0), COALESCE(SUM(t.total_profit),0) FROM transactions t $where");
$count_stmt->execute($params);
[$total, $total_omzet, $total_laba] = $count_stmt->fetch(PDO::FETCH_NUM);
$total_pages = ceil($total / $limit);

// Data
$stmt = $db->prepare("
    SELECT t.*, u.full_name as cashier_name
    FROM transactions t
    JOIN users u ON t.cashier_id = u.id
    $where
    ORDER BY t.transaction_date DESC
    LIMIT :l OFFSET :o
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':l', $limit, PDO::PARAM_INT);
$stmt->bindValue(':o', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Ambil daftar kasir untuk filter
$cashiers = $db->query("SELECT id, full_name FROM users WHERE role_id = 2 ORDER BY full_name")->fetchAll();

$period_label = date('d M Y', strtotime($start_date)) . ' — ' . date('d M Y', strtotime($end_date));
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-receipt text-primary mr-2"></i>Riwayat Transaksi</h1>

<!-- Filter -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-sm font-semibold mb-1.5">Periode</label>
            <select name="filter" id="filterSelect" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5">Kasir</label>
            <select name="cashier" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <option value="0">Semua Kasir</option>
                <?php foreach ($cashiers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filter_cashier == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5">Metode</label>
            <select name="method" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <option value="">Semua Metode</option>
                <option value="cash" <?= $filter_method === 'cash' ? 'selected' : '' ?>>Tunai</option>
                <option value="qris" <?= $filter_method === 'qris' ? 'selected' : '' ?>>QRIS</option>
                <option value="transfer" <?= $filter_method === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                <option value="debit" <?= $filter_method === 'debit' ? 'selected' : '' ?>>Debit</option>
            </select>
        </div>
        <div id="dateRange" style="display:<?= $filter === 'custom' ? 'grid' : 'none' ?>">
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="start_date" value="<?= $start_date ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="date" name="end_date" value="<?= $end_date ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            </div>
        </div>
        <div class="md:col-span-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg">
                <i class="fas fa-filter mr-2"></i>Tampilkan
            </button>
            <a href="transactions.php" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">
                <i class="fas fa-redo mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Total Transaksi</div>
        <div class="text-3xl font-extrabold"><?= number_format($total) ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= $period_label ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Total Omzet</div>
        <div class="text-2xl font-extrabold text-emerald-600">Rp <?= number_format($total_omzet, 0, ',', '.') ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= $period_label ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Total Laba</div>
        <div class="text-2xl font-extrabold text-teal-600">Rp <?= number_format($total_laba, 0, ',', '.') ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= $period_label ?></div>
    </div>
</div>

<!-- Tabel -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <?php if (empty($transactions)): ?>
        <div class="p-12 text-center text-slate-400">
            <i class="fas fa-inbox text-5xl opacity-30 mb-3 block"></i>
            <p>Tidak ada transaksi untuk filter ini</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 text-left text-slate-500">
                    <tr>
                        <th class="p-3">Invoice</th>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Kasir</th>
                        <th class="p-3">Pelanggan</th>
                        <th class="p-3">Metode</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-right">Diskon</th>
                        <th class="p-3 text-right">Laba</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($transactions as $t): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-3 font-mono text-xs"><?= htmlspecialchars($t['invoice_number']) ?></td>
                        <td class="p-3 text-xs">
                            <?= date('d/m H:i', strtotime($t['transaction_date'])) ?>
                        </td>
                        <td class="p-3 text-xs"><?= htmlspecialchars($t['cashier_name']) ?></td>
                        <td class="p-3 text-xs"><?= htmlspecialchars($t['customer_name_snapshot'] ?: '-') ?></td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                <?= $t['payment_method'] === 'cash' ? 'bg-emerald-100 text-emerald-700' :
                                   ($t['payment_method'] === 'qris' ? 'bg-blue-100 text-blue-700' :
                                   ($t['payment_method'] === 'transfer' ? 'bg-purple-100 text-purple-700' :
                                   'bg-amber-100 text-amber-700')) ?>">
                                <?= strtoupper($t['payment_method']) ?>
                            </span>
                        </td>
                        <td class="p-3 text-right font-semibold">Rp <?= number_format($t['final_amount'], 0, ',', '.') ?></td>
                        <td class="p-3 text-right text-xs <?= $t['discount_amount'] > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' ?>">
                            <?= $t['discount_amount'] > 0 ? '-Rp ' . number_format($t['discount_amount'], 0, ',', '.') : '-' ?>
                        </td>
                        <td class="p-3 text-right font-semibold text-teal-600">Rp <?= number_format($t['total_profit'], 0, ',', '.') ?></td>
                        <td class="p-3 text-center">
                            <button onclick="showDetail(<?= $t['id'] ?>, '<?= htmlspecialchars($t['invoice_number']) ?>')" class="px-3 py-1 bg-primary text-white rounded text-xs hover:bg-blue-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 p-5 border-t border-slate-200 dark:border-slate-800 flex-wrap">
            <?php
            $base_q = $_GET;
            for ($i = 1; $i <= $total_pages; $i++):
                $base_q['page'] = $i;
                $link = '?' . http_build_query($base_q);
            ?>
                <a href="<?= $link ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $i == $page ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- MODAL DETAIL -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl p-5">
        <h3 id="detailTitle" class="text-center font-bold text-lg mb-4"><i class="fas fa-receipt text-primary mr-2"></i>Detail Transaksi</h3>
        <div id="detailContent"></div>
        <div class="flex justify-end mt-5">
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Tutup</button>
        </div>
    </div>
</div>

<script>
document.getElementById('filterSelect').addEventListener('change', function() {
    document.getElementById('dateRange').style.display = (this.value === 'custom') ? 'grid' : 'none';
});

function showDetail(id, inv) {
    fetch(`../../ajax/get_transaction_detail.php?id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return alert(res.message);
            const d = res.data;
            const dt = new Date(d.transaction_date).toLocaleString('id-ID');
            
            let items_html = d.items.map(i => {
                const profit = (i.selling_price - i.purchase_price) * i.quantity;
                return `
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <td class="p-2 text-sm">${i.name}</td>
                        <td class="p-2 text-center text-sm">${i.quantity}</td>
                        <td class="p-2 text-right text-sm">Rp ${i.selling_price.toLocaleString()}</td>
                        <td class="p-2 text-right text-sm font-bold text-teal-600">Rp ${profit.toLocaleString()}</td>
                    </tr>`;
            }).join('');

            const html = `
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 mb-4 space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Invoice</span><span class="font-mono font-bold">${d.invoice_number}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Tanggal</span><span>${dt}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Metode</span><span class="font-semibold">${d.payment_method.toUpperCase()}</span></div>
                </div>
                
                <div class="mb-4">
                    <div class="font-bold mb-2 text-sm">Detail Item</div>
                    <table class="w-full">
                        <thead class="bg-slate-100 dark:bg-slate-800 text-xs text-slate-500">
                            <tr>
                                <th class="p-2 text-left">Produk</th>
                                <th class="p-2 text-center">Qty</th>
                                <th class="p-2 text-right">Harga</th>
                                <th class="p-2 text-right">Laba</th>
                            </tr>
                        </thead>
                        <tbody>${items_html}</tbody>
                    </table>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-semibold">Rp ${d.total_amount.toLocaleString()}</span></div>
                    ${d.discount_amount > 0 ? `<div class="flex justify-between text-amber-600"><span>Diskon</span><span class="font-semibold">-Rp ${d.discount_amount.toLocaleString()}</span></div>` : ''}
                    <div class="flex justify-between border-t border-slate-200 dark:border-slate-700 pt-2">
                        <span class="font-bold">Total</span>
                        <span class="font-bold text-primary text-lg">Rp ${d.final_amount.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between text-teal-600 font-bold">
                        <span>💰 Laba</span>
                        <span>Rp ${d.total_profit.toLocaleString()}</span>
                    </div>
                </div>
            `;
            document.getElementById('detailContent').innerHTML = html;
            document.getElementById('detailTitle').textContent = '🧾 ' + inv;
            document.getElementById('detailModal').classList.remove('hidden');
        });
}
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>

<?php include '../../includes/footer.php'; ?>
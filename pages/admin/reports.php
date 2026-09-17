<?php
// pages/admin/reports.php
session_start();
include '../../includes/header.php';
requireRole('Admin');

// Filter
$filter = $_GET['filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

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

// ===== Summary (TANPA JOIN ke transaction_items) =====
$stmt = $db->prepare("
    SELECT COUNT(*) as total_transactions,
           COALESCE(SUM(final_amount), 0) as total_revenue
    FROM transactions
    WHERE status = 'completed' AND DATE(transaction_date) BETWEEN :s AND :e
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$summary = $stmt->fetch();

// ===== Total Produk Terjual (query terpisah) =====
$stmt = $db->prepare("
    SELECT COALESCE(SUM(ti.quantity), 0)
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.status = 'completed' AND DATE(t.transaction_date) BETWEEN :s AND :e
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$total_items_sold = $stmt->fetchColumn();

// ===== Total Laba (query terpisah) =====
$stmt = $db->prepare("
    SELECT COALESCE(SUM((ti.selling_price - p.purchase_price) * ti.quantity), 0)
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.status = 'completed' AND DATE(t.transaction_date) BETWEEN :s AND :e
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$total_profit = $stmt->fetchColumn();

// ===== Penjualan per Kategori =====
$stmt = $db->prepare("
    SELECT COALESCE(c.name, 'Tanpa Kategori') as category_name,
           COALESCE(SUM(ti.quantity * ti.selling_price), 0) as total_sales
    FROM transactions t
    LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
    LEFT JOIN products p ON ti.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE t.status = 'completed' AND DATE(t.transaction_date) BETWEEN :s AND :e
    GROUP BY c.id
    ORDER BY total_sales DESC
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$category_sales = $stmt->fetchAll();

// ===== Metode Pembayaran =====
$stmt = $db->prepare("
    SELECT payment_method, COUNT(*) as total, COALESCE(SUM(final_amount), 0) as amount
    FROM transactions
    WHERE status = 'completed' AND DATE(transaction_date) BETWEEN :s AND :e
    GROUP BY payment_method
    ORDER BY total DESC
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$payment_methods = $stmt->fetchAll();

// ===== Jam Sibuk =====
$stmt = $db->prepare("
    SELECT HOUR(transaction_date) as hour, COUNT(*) as total
    FROM transactions
    WHERE status = 'completed' AND DATE(transaction_date) BETWEEN :s AND :e
    GROUP BY HOUR(transaction_date)
    ORDER BY hour ASC
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$hourly = $stmt->fetchAll();

// ===== Produk Terlaris =====
$stmt = $db->prepare("
    SELECT p.name, SUM(ti.quantity) as total_sold, SUM(ti.subtotal) as revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.status = 'completed' AND DATE(t.transaction_date) BETWEEN :s AND :e
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute(['s' => $start_date, 'e' => $end_date]);
$top_products = $stmt->fetchAll();

// Siapkan data chart
$cat_labels = array_column($category_sales, 'category_name');
$cat_data = array_map('floatval', array_column($category_sales, 'total_sales'));

$pay_labels = array_map(fn($p) => strtoupper($p['payment_method']), $payment_methods);
$pay_data = array_map('intval', array_column($payment_methods, 'total'));

// Jam sibuk: lengkapi 0-23
$hourly_map = [];
foreach ($hourly as $h) $hourly_map[(int)$h['hour']] = (int)$h['total'];
$hour_labels = [];
$hour_data = [];
for ($i = 0; $i < 24; $i++) {
    $hour_labels[] = str_pad($i, 2, '0', STR_PAD_LEFT);
    $hour_data[] = $hourly_map[$i] ?? 0;
}
$peak_hour = '-';
if (!empty($hourly_map)) {
    $max_hour = array_search(max($hourly_map), $hourly_map);
    $peak_hour = str_pad($max_hour, 2, '0', STR_PAD_LEFT) . ':00';
}

$colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316'];

// Label periode
$period_label = '';
if ($filter === 'today') $period_label = 'Hari Ini (' . date('d M Y', strtotime($start_date)) . ')';
elseif ($filter === 'week') $period_label = 'Minggu Ini (' . date('d M', strtotime($start_date)) . ' – ' . date('d M Y', strtotime($end_date)) . ')';
elseif ($filter === 'month') $period_label = 'Bulan Ini (' . date('F Y', strtotime($start_date)) . ')';
else $period_label = date('d M Y', strtotime($start_date)) . ' – ' . date('d M Y', strtotime($end_date));
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-file-alt text-primary mr-2"></i>Laporan Penjualan</h1>

<!-- Filter -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-sm font-semibold mb-1.5">Periode</label>
            <select name="filter" id="filterSelect" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
        </div>
        <div id="dateRange" class="flex gap-3" style="display:<?= $filter === 'custom' ? 'flex' : 'none' ?>">
            <div>
                <label class="block text-sm font-semibold mb-1.5">Dari</label>
                <input type="date" name="start_date" value="<?= $start_date ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">Sampai</label>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
            </div>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg">
            <i class="fas fa-filter mr-2"></i>Tampilkan
        </button>
        <div class="text-xs text-slate-500 ml-auto self-center">
            <i class="fas fa-calendar mr-1"></i><?= date('d M Y', strtotime($start_date)) ?> — <?= date('d M Y', strtotime($end_date)) ?>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-receipt text-blue-600"></i>
            </div>
            <div class="text-sm text-slate-500">Total Transaksi</div>
        </div>
        <div class="text-3xl font-extrabold"><?= number_format($summary['total_transactions']) ?></div>
        <div class="text-xs text-slate-400 mt-1"><i class="fas fa-calendar"></i> <?= $period_label ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-emerald-600"></i>
            </div>
            <div class="text-sm text-slate-500">Total Omzet</div>
        </div>
        <div class="text-3xl font-extrabold text-emerald-600">Rp <?= number_format($summary['total_revenue'], 0, ',', '.') ?></div>
        <div class="text-xs text-slate-400 mt-1"><i class="fas fa-calendar"></i> <?= $period_label ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                <i class="fas fa-chart-line text-teal-600"></i>
            </div>
            <div class="text-sm text-slate-500">Total Laba</div>
        </div>
        <div class="text-3xl font-extrabold text-teal-600">Rp <?= number_format($total_profit, 0, ',', '.') ?></div>
        <div class="text-xs text-slate-400 mt-1"><i class="fas fa-calendar"></i> <?= $period_label ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <i class="fas fa-boxes text-purple-600"></i>
            </div>
            <div class="text-sm text-slate-500">Produk Terjual</div>
        </div>
        <div class="text-3xl font-extrabold"><?= number_format($total_items_sold) ?></div>
        <div class="text-xs text-slate-400 mt-1"><i class="fas fa-calendar"></i> <?= $period_label ?></div>
    </div>
</div>

<!-- Row: Pie Kategori, Pie Metode Bayar, Bar Jam Sibuk -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <h3 class="font-bold mb-4"><i class="fas fa-chart-pie text-primary mr-2"></i>Kategori Produk</h3>
        <?php if (empty($cat_data) || array_sum($cat_data) == 0): ?>
            <div class="h-56 flex items-center justify-center text-slate-400 text-sm text-center">
                <div><i class="fas fa-chart-pie text-4xl opacity-30 mb-2 block"></i>Belum ada data</div>
            </div>
        <?php else: ?>
            <div class="h-56"><canvas id="catChart"></canvas></div>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <h3 class="font-bold mb-4"><i class="fas fa-credit-card text-emerald-500 mr-2"></i>Metode Pembayaran</h3>
        <?php if (empty($pay_data) || array_sum($pay_data) == 0): ?>
            <div class="h-56 flex items-center justify-center text-slate-400 text-sm text-center">
                <div><i class="fas fa-credit-card text-4xl opacity-30 mb-2 block"></i>Belum ada data</div>
            </div>
        <?php else: ?>
            <div class="h-56"><canvas id="payChart"></canvas></div>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <h3 class="font-bold mb-4">
            <i class="fas fa-clock text-amber-500 mr-2"></i>Jam Sibuk
            <?php if ($peak_hour !== '-'): ?>
                <span class="text-xs font-normal text-amber-600 dark:text-amber-400 ml-2">
                    <i class="fas fa-star"></i> Puncak: <?= $peak_hour ?>
                </span>
            <?php endif; ?>
        </h3>
        <?php if (empty($hour_data) || array_sum($hour_data) == 0): ?>
            <div class="h-56 flex items-center justify-center text-slate-400 text-sm text-center">
                <div><i class="fas fa-clock text-4xl opacity-30 mb-2 block"></i>Belum ada data</div>
            </div>
        <?php else: ?>
            <div class="h-56"><canvas id="hourChart"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<!-- Produk Terlaris -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
    <h3 class="font-bold text-lg mb-4"><i class="fas fa-trophy text-amber-500 mr-2"></i>Produk Terlaris (Top 5)</h3>
    <?php if (empty($top_products)): ?>
        <div class="py-8 text-center text-slate-400 text-sm">
            <i class="fas fa-box-open text-4xl opacity-30 mb-2 block"></i>
            Belum ada penjualan
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <?php foreach ($top_products as $i => $p): ?>
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <div class="w-9 h-9 rounded-full bg-primary text-white font-bold flex items-center justify-center text-sm flex-shrink-0">
                        <?= $i + 1 ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate text-sm" title="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= number_format($p['total_sold']) ?> terjual</div>
                        <div class="text-xs font-bold text-emerald-600 mt-0.5">Rp <?= number_format($p['revenue'], 0, ',', '.') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.getElementById('filterSelect').addEventListener('change', function() {
    document.getElementById('dateRange').style.display = (this.value === 'custom') ? 'flex' : 'none';
});

const isDark = document.documentElement.classList.contains('dark') || <?= $theme === 'dark' ? 'true' : 'false' ?>;
const textColor = isDark ? '#cbd5e1' : '#475569';
const gridColor = isDark ? '#334155' : '#e2e8f0';
const borderColor = isDark ? '#1e293b' : '#ffffff';
const colors = <?= json_encode($colors) ?>;

// ===== PIE: Kategori =====
<?php if (!empty($cat_data) && array_sum($cat_data) > 0): ?>
new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($cat_labels) ?>,
        datasets: [{
            data: <?= json_encode($cat_data) ?>,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: borderColor
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { color: textColor, padding: 10, font: { size: 11 }, boxWidth: 12 } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                        return ctx.label + ': Rp ' + ctx.parsed.toLocaleString('id-ID') + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// ===== PIE: Metode Pembayaran =====
<?php if (!empty($pay_data) && array_sum($pay_data) > 0): ?>
new Chart(document.getElementById('payChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($pay_labels) ?>,
        datasets: [{
            data: <?= json_encode($pay_data) ?>,
            backgroundColor: ['#10b981','#3b82f6','#f59e0b','#8b5cf6','#ef4444','#06b6d4'],
            borderWidth: 2,
            borderColor: borderColor
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { color: textColor, padding: 10, font: { size: 11 }, boxWidth: 12 } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                        return ctx.label + ': ' + ctx.parsed + ' transaksi (' + pct + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// ===== BAR: Jam Sibuk =====
<?php if (!empty($hour_data) && array_sum($hour_data) > 0): ?>
const hourData = <?= json_encode($hour_data) ?>;
const maxVal = Math.max(...hourData);
new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($hour_labels) ?>,
        datasets: [{
            label: 'Transaksi',
            data: hourData,
            backgroundColor: hourData.map(v => v === maxVal ? '#f59e0b' : '#3b82f6'),
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    title: (items) => 'Jam ' + items[0].label + ':00',
                    label: (ctx) => ctx.parsed.y + ' transaksi'
                }
            }
        },
        scales: {
            x: {
                ticks: { color: textColor, font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
                grid: { display: false }
            },
            y: {
                beginAtZero: true,
                ticks: { color: textColor, font: { size: 10 }, stepSize: 1, precision: 0 },
                grid: { color: gridColor }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>
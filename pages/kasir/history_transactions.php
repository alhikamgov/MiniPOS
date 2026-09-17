<?php
// pages/kasir/cashier_report.php
session_start();
include '../../includes/header.php';
requireRole('Kasir');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE cashier_id=:u AND DATE(transaction_date)=:t AND status='completed'");
$stmt->execute(['u' => $user_id, 't' => $today]);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $db->prepare("SELECT id, invoice_number, final_amount, total_profit, payment_method, transaction_date 
                      FROM transactions 
                      WHERE cashier_id=:u AND DATE(transaction_date)=:t AND status='completed' 
                      ORDER BY transaction_date DESC 
                      LIMIT :l OFFSET :o");
$stmt->bindValue(':u', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':t', $today);
$stmt->bindValue(':l', $limit, PDO::PARAM_INT);
$stmt->bindValue(':o', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Total hari ini
$stmt = $db->prepare("SELECT COALESCE(SUM(final_amount),0), COALESCE(SUM(total_profit),0) FROM transactions WHERE cashier_id=:u AND DATE(transaction_date)=:t AND status='completed'");
$stmt->execute(['u' => $user_id, 't' => $today]);
[$omzet, $laba] = $stmt->fetch(PDO::FETCH_NUM);
?>

<style>
    @page { size: 58mm auto; margin: 0; }
    @media print {
        html, body { width: 58mm; margin: 0; padding: 0; background: #fff; }
        body * { visibility: hidden !important; }
        #detailPrintArea, #detailPrintArea * { visibility: visible !important; }
        #detailPrintArea { position: absolute !important; left: 0 !important; top: 0 !important; width: 58mm !important; padding: 2mm !important; font-family: 'Courier New', monospace !important; font-size: 10px !important; color: #000 !important; line-height: 1.2 !important; }
        .no-print { display: none !important; }
    }
    .receipt-preview { font-family: 'Courier New', monospace; font-size: 11px; color: #000; background: #fff; padding: 10px 8px; max-width: 260px; margin: 0 auto; line-height: 1.35; }
    .receipt-preview .r-center { text-align: center; }
    .receipt-preview .r-bold { font-weight: bold; }
    .receipt-preview .r-line { border-top: 1px dashed #000; margin: 4px 0; }
    .receipt-preview .r-line-double { border-top: 2px solid #000; margin: 4px 0; }
    .receipt-preview .r-row { display: flex; justify-content: space-between; gap: 4px; margin: 1px 0; }
    .receipt-preview .r-item { margin: 4px 0; }
    .receipt-preview .r-item .r-item-detail { display: flex; justify-content: space-between; padding-left: 8px; }
</style>

<div class="flex justify-between items-center mb-6 flex-wrap gap-3">
    <h1 class="text-2xl font-bold"><i class="fas fa-receipt text-primary mr-2"></i>Laporan Kasir</h1>
    <div class="text-sm text-slate-500"><i class="fas fa-calendar-day mr-1"></i><?= date('d M Y') ?></div>
</div>

<!-- Summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Omzet Hari Ini</div>
        <div class="text-2xl font-extrabold text-emerald-600">Rp <?= number_format($omzet, 0, ',', '.') ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Laba Hari Ini</div>
        <div class="text-2xl font-extrabold text-teal-600">Rp <?= number_format($laba, 0, ',', '.') ?></div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5">
        <div class="text-sm text-slate-500 mb-1">Jumlah Transaksi</div>
        <div class="text-2xl font-extrabold"><?= $total ?></div>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-200 dark:border-slate-800 font-bold">
        <i class="fas fa-list-ul text-primary mr-2"></i>Daftar Transaksi Hari Ini
        <span class="ml-2 text-xs font-normal text-slate-500">(<?= $total ?> transaksi)</span>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="p-12 text-center text-slate-400">
            <i class="fas fa-receipt text-5xl opacity-30 mb-3 block"></i>
            <p>Belum ada transaksi hari ini</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 text-left text-slate-500">
                    <tr>
                        <th class="p-3">Invoice</th>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Metode</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-right">Laba</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php foreach ($transactions as $t): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-3">
                            <button onclick="showDetail(<?= $t['id'] ?>, '<?= htmlspecialchars($t['invoice_number']) ?>')" class="text-primary underline font-mono text-xs">
                                <?= htmlspecialchars($t['invoice_number']) ?>
                            </button>
                        </td>
                        <td class="p-3 text-xs"><i class="fas fa-clock text-slate-400 mr-1"></i><?= date('H:i', strtotime($t['transaction_date'])) ?></td>
                        <td class="p-3">
                            <?php
                            $pm_colors = ['cash' => 'bg-emerald-100 text-emerald-700', 'qris' => 'bg-blue-100 text-blue-700', 'transfer' => 'bg-purple-100 text-purple-700', 'debit' => 'bg-amber-100 text-amber-700'];
                            $pm_class = $pm_colors[$t['payment_method']] ?? 'bg-slate-100 text-slate-700';
                            ?>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold <?= $pm_class ?>"><?= strtoupper($t['payment_method']) ?></span>
                        </td>
                        <td class="p-3 text-right font-semibold">Rp <?= number_format($t['final_amount'], 0, ',', '.') ?></td>
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
        <div class="flex justify-center gap-2 p-5 border-t border-slate-200 dark:border-slate-800">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $i == $page ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- MODAL DETAIL -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl p-5">
        <h3 id="detailTitle" class="text-center font-bold text-lg mb-4 no-print"><i class="fas fa-receipt text-primary mr-2"></i>Detail Invoice</h3>
        <div class="bg-slate-100 dark:bg-slate-800 p-3 rounded-lg overflow-x-auto">
            <div id="detailContent" class="receipt-preview"></div>
        </div>
        <div class="flex gap-3 justify-center mt-5 no-print">
            <button onclick="window.print()" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg"><i class="fas fa-print mr-2"></i>Print</button>
            <button onclick="document.getElementById('detailModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Tutup</button>
        </div>
    </div>
</div>

<div id="detailPrintArea" class="receipt-preview" style="position:absolute; left:-9999px; top:0;"></div>

<script>
const STORE_NAME = <?= json_encode($app_name) ?>;
const STORE_ADDRESS = <?= json_encode($store_address) ?>;
const STORE_PHONE = <?= json_encode($store_phone) ?>;
const CASHIER_NAME = <?= json_encode($full_name) ?>;

function showDetail(id, inv) {
    fetch(`../../ajax/get_transaction_detail.php?id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return alert(res.message);
            const d = res.data;
            const dateObj = new Date(d.transaction_date);
            const dt = dateObj.toLocaleDateString('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' }) + ' ' + dateObj.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });

            let h = `
                <div class="r-center">
                    <div class="r-bold" style="font-size:13px;">${STORE_NAME}</div>
                    <div>${STORE_ADDRESS}</div>
                    <div>Telp: ${STORE_PHONE}</div>
                </div>
                <div class="r-line"></div>
                <div class="r-row"><span>Tanggal</span><span>${dt}</span></div>
                <div class="r-row"><span>Invoice</span><span>${d.invoice_number}</span></div>
                <div class="r-row"><span>Kasir</span><span>${CASHIER_NAME}</span></div>
                <div class="r-line"></div>`;

            d.items.forEach(i => {
                h += `<div class="r-item">
                        <div>${i.name}</div>
                        <div class="r-item-detail">
                            <span>${i.quantity} x ${i.selling_price.toLocaleString()}</span>
                            <span>${i.subtotal.toLocaleString()}</span>
                        </div>
                      </div>`;
            });

            h += `<div class="r-line"></div>`;
            if (d.discount_amount > 0) {
                h += `<div class="r-row"><span>Subtotal</span><span>Rp ${d.total_amount.toLocaleString()}</span></div>
                      <div class="r-row" style="color:#d97706;"><span>Diskon</span><span>-Rp ${d.discount_amount.toLocaleString()}</span></div>
                      <div class="r-line"></div>`;
            }
            h += `<div class="r-row r-bold"><span>TOTAL</span><span>Rp ${d.final_amount.toLocaleString()}</span></div>`;

            if (d.payment_method === 'cash') {
                h += `<div class="r-row"><span>Tunai</span><span>Rp ${(d.cash_received||0).toLocaleString()}</span></div>
                      <div class="r-row"><span>Kembali</span><span>Rp ${(d.change_amount||0).toLocaleString()}</span></div>`;
            } else {
                h += `<div class="r-row"><span>Metode</span><span>${d.payment_method.toUpperCase()}</span></div>`;
            }

            if (d.points_earned > 0) {
                h += `<div class="r-line"></div>
                      <div class="r-row r-bold" style="color:#d97706;"><span>⭐ POIN</span><span>+${d.points_earned}</span></div>`;
            }

            h += `<div class="r-line-double"></div>
                  <div class="r-center">Terima Kasih</div>
                  <div class="r-center" style="font-size:9px;">Barang yang sudah dibeli tidak dapat ditukar</div>`;

            document.getElementById('detailContent').innerHTML = h;
            document.getElementById('detailPrintArea').innerHTML = h;
            document.getElementById('detailTitle').textContent = '🧾 ' + inv;
            document.getElementById('detailModal').classList.remove('hidden');
        })
        .catch(err => { console.error(err); alert('Gagal memuat detail.'); });
}
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>

<?php include '../../includes/footer.php'; ?>
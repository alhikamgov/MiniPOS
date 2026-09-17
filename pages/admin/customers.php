<?php
// pages/admin/customers.php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../../config/database.php';
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $flash_message = '';
    $flash_type = '';

    try {
        $data = [
            'n' => $_POST['name'],
            'p' => $_POST['phone'] ?? '',
            'e' => $_POST['email'] ?? '',
            'a' => $_POST['address'] ?? '',
            'pts' => (int)($_POST['points'] ?? 0),
        ];

        if ($action == 'add') {
            $db->prepare("INSERT INTO customers (name, phone, email, address, loyalty_points) VALUES (:n,:p,:e,:a,:pts)")->execute($data);
            $flash_message = 'Pelanggan berhasil ditambahkan!';
        } elseif ($action == 'edit') {
            $data['id'] = $id;
            // Ambil poin lama
            $old_stmt = $db->prepare("SELECT loyalty_points FROM customers WHERE id = :id");
            $old_stmt->execute(['id' => $id]);
            $old_points = (int)$old_stmt->fetchColumn();
            
            $db->prepare("UPDATE customers SET name=:n, phone=:p, email=:e, address=:a, loyalty_points=:pts WHERE id=:id")->execute($data);
            
            // Kalau poin diubah manual, catat di history
            if ($old_points !== $data['pts']) {
                $diff = $data['pts'] - $old_points;
                $db->prepare("INSERT INTO loyalty_point_history 
                              (customer_id, type, points, points_before, points_after, description) 
                              VALUES (:cid, 'adjust', :pts, :pb, :pa, :desc)")
                   ->execute([
                       'cid' => $id,
                       'pts' => $diff,
                       'pb' => $old_points,
                       'pa' => $data['pts'],
                       'desc' => 'Penyesuaian manual oleh admin'
                   ]);
            }
            $flash_message = 'Pelanggan berhasil diupdate!';
        } elseif ($action == 'delete') {
            $db->prepare("DELETE FROM customers WHERE id=:id")->execute(['id' => $id]);
            $flash_message = 'Pelanggan berhasil dihapus!';
        }
        $flash_type = 'success';
    } catch (Exception $e) {
        $flash_message = 'Error: ' . $e->getMessage();
        $flash_type = 'error';
    }

    $_SESSION['flash'] = ['type' => $flash_type, 'msg' => $flash_message];
    header('Location: customers.php');
    exit;
}

include '../../includes/header.php';
requireRole('Admin');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$customers = $db->query("SELECT * FROM customers ORDER BY name")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-user-friends text-primary mr-2"></i>Manajemen Pelanggan</h1>

<!-- Info Box -->
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-5 flex items-start gap-3">
    <i class="fas fa-star text-amber-500 text-xl mt-0.5"></i>
    <div class="text-sm">
        <div class="font-bold text-amber-800 dark:text-amber-300 mb-1">Aturan Poin Loyalty</div>
        <div class="text-amber-700 dark:text-amber-400">
            Setiap <b>Rp 1.000</b> belanja = <b>1 poin</b>. Contoh: belanja Rp 45.500 = <b>45 poin</b>.
            Poin otomatis didapat saat kasir memilih pelanggan di halaman transaksi.
        </div>
    </div>
</div>

<button onclick="document.getElementById('formModal').classList.remove('hidden')" class="px-4 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition mb-5">
    <i class="fas fa-plus mr-2"></i>Tambah Pelanggan
</button>

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800 text-left text-slate-500">
                <tr>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Telepon</th>
                    <th class="p-3">Email</th>
                    <th class="p-3 text-center">Poin</th>
                    <th class="p-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($customers)): ?>
                    <tr><td colspan="5" class="p-8 text-center text-slate-400">Belum ada pelanggan.</td></tr>
                <?php else: foreach ($customers as $c): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-3 font-semibold"><?= htmlspecialchars($c['name']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                        <td class="p-3 text-center">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold">
                                <i class="fas fa-star"></i><?= number_format($c['loyalty_points']) ?>
                            </span>
                        </td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <button onclick='viewHistory(<?= $c['id'] ?>, "<?= addslashes($c['name']) ?>")' 
                                    class="px-3 py-1.5 bg-amber-500 text-white rounded text-xs hover:bg-amber-600" title="History Poin">
                                <i class="fas fa-history"></i>
                            </button>
                            <button onclick='editCustomer(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' 
                                    class="px-3 py-1.5 bg-primary text-white rounded text-xs hover:bg-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="if(confirm('Hapus pelanggan ini?')) document.getElementById('del<?= $c['id'] ?>').submit()" 
                                    class="px-3 py-1.5 bg-red-500 text-white rounded text-xs hover:bg-red-600" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                            <form id="del<?= $c['id'] ?>" method="POST" class="hidden">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div id="formModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <h3 id="formTitle" class="text-xl font-bold mb-5"><i class="fas fa-user-plus text-primary mr-2"></i>Tambah Pelanggan</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="0">
            <div>
                <label class="block text-sm font-semibold mb-1.5">Nama *</label>
                <input type="text" name="name" id="formName" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">Telepon</label>
                <input type="text" name="phone" id="formPhone" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">Email</label>
                <input type="email" name="email" id="formEmail" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">Alamat</label>
                <textarea name="address" id="formAddress" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">
                    Poin Loyalty 
                    <span class="font-normal text-xs text-slate-500">(ubah manual jika perlu)</span>
                </label>
                <input type="number" name="points" id="formPoints" value="0" min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('formModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal History Poin -->
<div id="historyModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-xl font-bold"><i class="fas fa-history text-amber-500 mr-2"></i>History Poin</h3>
            <button onclick="document.getElementById('historyModal').classList.add('hidden')" class="text-slate-400 hover:text-red-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="historyCustomerName" class="text-sm text-slate-500 mb-4"></div>
        <div id="historyContent" class="space-y-2">
            <div class="text-center text-slate-400 py-8">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="mt-2">Memuat data...</p>
            </div>
        </div>
    </div>
</div>

<script>
function editCustomer(data) {
    document.getElementById('formModal').classList.remove('hidden');
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-primary mr-2"></i>Edit Pelanggan';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formName').value = data.name;
    document.getElementById('formPhone').value = data.phone || '';
    document.getElementById('formEmail').value = data.email || '';
    document.getElementById('formAddress').value = data.address || '';
    document.getElementById('formPoints').value = data.loyalty_points || 0;
}

// ===== HISTORY POIN =====
function viewHistory(customerId, customerName) {
    document.getElementById('historyModal').classList.remove('hidden');
    document.getElementById('historyCustomerName').innerHTML = 'Pelanggan: <b>' + customerName + '</b>';
    document.getElementById('historyContent').innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Memuat data...</p></div>';
    
    fetch(`../../ajax/get_loyalty_history.php?customer_id=${customerId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.items.length) {
                document.getElementById('historyContent').innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-inbox text-4xl opacity-30 mb-3 block"></i><p>Belum ada riwayat poin</p></div>';
                return;
            }
            
            const html = data.items.map(h => {
                const isEarn = h.type === 'earn';
                const isRedeem = h.type === 'redeem';
                const colorClass = isEarn ? 'text-emerald-600' : (isRedeem ? 'text-red-600' : 'text-slate-600');
                const sign = h.points > 0 ? '+' : '';
                const icon = isEarn ? 'fa-plus-circle' : (isRedeem ? 'fa-minus-circle' : 'fa-edit');
                
                return `
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas ${icon} ${colorClass}"></i>
                                    <span class="font-bold text-sm ${colorClass}">${sign}${h.points} poin</span>
                                </div>
                                <div class="text-xs text-slate-500">${h.description || '-'}</div>
                                <div class="text-xs text-slate-400 mt-1">
                                    <i class="fas fa-clock"></i> ${new Date(h.created_at).toLocaleString('id-ID')}
                                </div>
                            </div>
                            <div class="text-xs text-right text-slate-500">
                                <div>${h.points_before} → <b>${h.points_after}</b></div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('historyContent').innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('historyContent').innerHTML = '<div class="text-center text-red-500 py-8">Gagal memuat data</div>';
        });
}

document.getElementById('formModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.getElementById('historyModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});

<?php if ($flash): ?>
document.addEventListener('DOMContentLoaded', () => notify('<?= $flash['type'] ?>', '<?= addslashes($flash['msg']) ?>'));
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>
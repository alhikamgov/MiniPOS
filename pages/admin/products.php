<?php
// pages/admin/products.php
session_start();

// ===== HANDLE UPLOAD FOTO =====
function handlePhotoUpload($key = 'photo') {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($_FILES[$key]['size'] > 2 * 1024 * 1024) return false;

    $upload_dir = __DIR__ . '/../../uploads/products/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename = 'prod_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $filename)) {
        return 'uploads/products/' . $filename;
    }
    return false;
}

function deletePhotoFile($path) {
    if (!$path) return;
    $full = __DIR__ . '/../../' . $path;
    if (is_file($full)) @unlink($full);
}

// ===== PROSES POST =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/../../config/database.php';
    $redirect = false;
    $flash_message = '';
    $flash_type = '';

    // ---- Produk ----
    if (isset($_POST['product_action'])) {
        $action = $_POST['product_action'];
        $id = (int)($_POST['id'] ?? 0);
        $barcode = $_POST['barcode'] ?? '';
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?: null;
        $supplier_id = $_POST['supplier_id'] ?: null;
        $purchase_price = (float)($_POST['purchase_price'] ?? 0);
        $selling_price = (float)($_POST['selling_price'] ?? 0);
        $min_stock = (int)($_POST['min_stock'] ?? 5);
        $unit = $_POST['unit'] ?? 'Pcs';

        try {
            if ($action == 'add') {
                $photo_path = handlePhotoUpload();
                if ($photo_path === false) throw new Exception('Format foto tidak valid (maks 2MB, jpg/png/gif/webp)');

                $stmt = $db->prepare("INSERT INTO products (barcode, name, category_id, supplier_id, purchase_price, selling_price, min_stock, unit, stock, photo) 
                                      VALUES (:barcode, :name, :cat, :sup, :buy, :sell, :min, :unit, 0, :photo)");
                $stmt->execute([
                    'barcode' => $barcode, 'name' => $name,
                    'cat' => $category_id, 'sup' => $supplier_id,
                    'buy' => $purchase_price, 'sell' => $selling_price,
                    'min' => $min_stock, 'unit' => $unit,
                    'photo' => $photo_path
                ]);
                $flash_message = 'Produk berhasil ditambahkan!';
                $flash_type = 'success';

            } elseif ($action == 'edit') {
                $old = $db->prepare("SELECT photo FROM products WHERE id=:id");
                $old->execute(['id' => $id]);
                $old_photo = $old->fetchColumn();

                $photo_path = handlePhotoUpload();
                if ($photo_path === false) throw new Exception('Format foto tidak valid (maks 2MB)');

                $remove_photo = isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1';
                if ($photo_path) {
                    if ($old_photo) deletePhotoFile($old_photo);
                    $final_photo = $photo_path;
                } elseif ($remove_photo) {
                    if ($old_photo) deletePhotoFile($old_photo);
                    $final_photo = null;
                } else {
                    $final_photo = $old_photo;
                }

                $stmt = $db->prepare("UPDATE products SET barcode=:barcode, name=:name, category_id=:cat, supplier_id=:sup, 
                                      purchase_price=:buy, selling_price=:sell, min_stock=:min, unit=:unit, photo=:photo WHERE id=:id");
                $stmt->execute([
                    'barcode' => $barcode, 'name' => $name,
                    'cat' => $category_id, 'sup' => $supplier_id,
                    'buy' => $purchase_price, 'sell' => $selling_price,
                    'min' => $min_stock, 'unit' => $unit,
                    'photo' => $final_photo, 'id' => $id
                ]);

                $flash_message = 'Produk berhasil diupdate!';
                $flash_type = 'success';

            } elseif ($action == 'delete') {
                $old = $db->prepare("SELECT photo FROM products WHERE id=:id");
                $old->execute(['id' => $id]);
                $old_photo = $old->fetchColumn();
                if ($old_photo) deletePhotoFile($old_photo);
                $db->prepare("DELETE FROM products WHERE id = :id")->execute(['id' => $id]);
                $flash_message = 'Produk berhasil dihapus!';
                $flash_type = 'success';

            } elseif ($action == 'adjust_stock') {
                $change = (int)($_POST['change'] ?? 0);
                $notes = $change > 0 ? 'Tambah stok' : 'Kurangi stok';

                if ($change === 0) {
                    $flash_message = 'Jumlah tidak boleh 0.';
                    $flash_type = 'error';
                } else {
                    $st = $db->prepare("SELECT stock, name FROM products WHERE id=:id");
                    $st->execute(['id' => $id]);
                    $row = $st->fetch();
                    if (!$row) {
                        $flash_message = 'Produk tidak ditemukan.';
                        $flash_type = 'error';
                    } else {
                        $new_stock = $row['stock'] + $change;
                        if ($new_stock < 0) {
                            $flash_message = 'Stok tidak boleh negatif! Stok saat ini: ' . $row['stock'];
                            $flash_type = 'error';
                        } else {
                            // ===== FIX: Cukup panggil addStock() SAJA, JANGAN update manual 2x =====
                            $result = addStock($id, $change, $_SESSION['user_id'], 'MANUAL-' . date('YmdHis'), $notes);
                            if ($result === true) {
                                $verb = $change > 0 ? 'ditambahkan' : 'dikurangi';
                                $flash_message = "Stok {$row['name']} berhasil {$verb} " . abs($change) . " unit. Stok baru: {$new_stock}";
                                $flash_type = 'success';
                            } else {
                                $flash_message = 'Error: ' . $result;
                                $flash_type = 'error';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $flash_message = 'Error: ' . $e->getMessage();
            $flash_type = 'error';
        }
        $redirect = true;
    }

    // ---- Kategori ----
    if (isset($_POST['cat_action'])) {
        $action = $_POST['cat_action'];
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action == 'add') {
                $db->prepare("INSERT INTO categories (name, description) VALUES (:n,:d)")
                   ->execute(['n'=>$_POST['name'],'d'=>$_POST['description']]);
                $flash_message = 'Kategori ditambahkan!';
            } elseif ($action == 'edit') {
                $db->prepare("UPDATE categories SET name=:n, description=:d WHERE id=:id")
                   ->execute(['n'=>$_POST['name'],'d'=>$_POST['description'],'id'=>$id]);
                $flash_message = 'Kategori diupdate!';
            } elseif ($action == 'delete') {
                $db->prepare("DELETE FROM categories WHERE id=:id")->execute(['id'=>$id]);
                $flash_message = 'Kategori dihapus!';
            }
            $flash_type = 'success';
        } catch (Exception $e) { $flash_message = 'Error: '.$e->getMessage(); $flash_type='error'; }
        $redirect = true;
    }

    // ---- Supplier ----
    if (isset($_POST['sup_action'])) {
        $action = $_POST['sup_action'];
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action == 'add') {
                $db->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (:n,:c,:p,:e,:a)")
                   ->execute(['n'=>$_POST['name'],'c'=>$_POST['contact_person'],'p'=>$_POST['phone'],'e'=>$_POST['email'],'a'=>$_POST['address']]);
                $flash_message = 'Supplier ditambahkan!';
            } elseif ($action == 'edit') {
                $db->prepare("UPDATE suppliers SET name=:n, contact_person=:c, phone=:p, email=:e, address=:a WHERE id=:id")
                   ->execute(['n'=>$_POST['name'],'c'=>$_POST['contact_person'],'p'=>$_POST['phone'],'e'=>$_POST['email'],'a'=>$_POST['address'],'id'=>$id]);
                $flash_message = 'Supplier diupdate!';
            } elseif ($action == 'delete') {
                $db->prepare("DELETE FROM suppliers WHERE id=:id")->execute(['id'=>$id]);
                $flash_message = 'Supplier dihapus!';
            }
            $flash_type = 'success';
        } catch (Exception $e) { $flash_message = 'Error: '.$e->getMessage(); $flash_type='error'; }
        $redirect = true;
    }

    if ($redirect) {
        $_SESSION['flash'] = ['type' => $flash_type, 'msg' => $flash_message];
        header('Location: products.php');
        exit;
    }
}

include '../../includes/header.php';
requireRole('Admin');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Pencarian & pagination
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
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// ===== FIX: Produk dengan stok menipis/habis di atas =====
// (stock <= min_stock) menghasilkan 1 kalau menipis, 0 kalau aman
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

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

function rp($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-box text-primary mr-2"></i>Manajemen Produk</h1>

<div class="flex flex-wrap gap-3 mb-5">
    <button onclick="openProductModal('add')" class="px-4 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition">
        <i class="fas fa-plus mr-2"></i>Tambah Produk
    </button>
    <button onclick="document.getElementById('catModal').classList.remove('hidden')" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow transition">
        <i class="fas fa-tags mr-2"></i>Kelola Kategori
    </button>
    <button onclick="document.getElementById('supModal').classList.remove('hidden')" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg shadow transition">
        <i class="fas fa-truck mr-2"></i>Kelola Supplier
    </button>
</div>

<!-- Info produk menipis -->
<?php
$low_stock_count = $db->query("SELECT COUNT(*) FROM products WHERE stock <= min_stock")->fetchColumn();
if ($low_stock_count > 0):
?>
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-5 flex items-center gap-3">
    <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
    <div class="text-sm text-amber-800 dark:text-amber-300">
        <b><?= $low_stock_count ?> produk</b> memiliki stok menipis/habis. Produk-produk ini ditampilkan <b>paling atas</b> di tabel.
    </div>
</div>
<?php endif; ?>

<!-- Pencarian -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 mb-5">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[200px]">
            <input type="text" id="searchInput"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Ketik minimal 5 karakter untuk pencarian otomatis..."
                   autocomplete="off"
                   class="w-full px-4 py-2.5 pl-10 pr-10 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <span id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-primary">
                <i class="fas fa-spinner fa-spin"></i>
            </span>
            <button type="button" id="clearSearchBtn" onclick="clearSearch()" 
                    class="<?= $search ? '' : 'hidden' ?> absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 text-xs">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="mt-2 text-xs text-slate-500 flex items-center gap-2">
        <i class="fas fa-info-circle"></i>
        <span id="searchHint">
            <?php if ($search): ?>
                Menampilkan hasil untuk: <b>"<?= htmlspecialchars($search) ?>"</b> (<span id="totalCount"><?= $total_products ?></span> produk ditemukan)
            <?php else: ?>
                Pencarian otomatis berjalan setelah Anda mengetik <b>5 karakter</b> (nama atau barcode).
            <?php endif; ?>
        </span>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800 text-left text-slate-500">
                <tr>
                    <th class="p-3">Foto</th>
                    <th class="p-3">Barcode</th>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3 text-right">H. Beli</th>
                    <th class="p-3 text-right">H. Jual</th>
                    <th class="p-3 text-center">Stok</th>
                    <th class="p-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="productsTableBody" class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="p-8 text-center text-slate-400"><?= $search ? 'Produk tidak ditemukan' : 'Belum ada produk' ?></td></tr>
                <?php else: foreach ($products as $p): ?>
                    <?php $is_low = $p['stock'] <= $p['min_stock']; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 <?= $is_low ? 'bg-red-50/40 dark:bg-red-900/10' : '' ?>">
                        <td class="p-3">
                            <?php if ($p['photo'] && file_exists(__DIR__ . '/../../' . $p['photo'])): ?>
                                <img src="../../<?= htmlspecialchars($p['photo']) ?>" alt="foto" class="w-12 h-12 object-cover rounded-lg border border-slate-200 dark:border-slate-700">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-xs text-slate-500"><?= htmlspecialchars($p['barcode'] ?: '-') ?></td>
                        <td class="p-3 font-semibold">
                            <?php if ($is_low): ?><i class="fas fa-exclamation-triangle text-red-500 mr-1"></i><?php endif; ?>
                            <?= htmlspecialchars($p['name']) ?>
                        </td>
                        <td class="p-3 text-xs"><?= htmlspecialchars($p['category_name'] ?: '-') ?></td>
                        <td class="p-3 text-right"><?= rp($p['purchase_price']) ?></td>
                        <td class="p-3 text-right font-semibold text-emerald-600"><?= rp($p['selling_price']) ?></td>
                        <td class="p-3 text-center">
                            <?php if ($is_low): ?>
                                <span class="inline-block px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 font-bold text-xs">
                                    <?= $p['stock'] ?>
                                </span>
                            <?php else: ?>
                                <span class="font-semibold"><?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center whitespace-nowrap"><?= renderActions($p) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div id="paginationContainer" class="p-4 border-t border-slate-200 dark:border-slate-800">
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button onclick="loadPage(<?= $i ?>)" data-page="<?= $i ?>"
                            class="page-btn px-3 py-1.5 rounded-lg text-sm <?= $i == $page ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300' ?>">
                        <?= $i ?>
                    </button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function renderActions($p) {
    $data = htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES);
    $name = addslashes($p['name']);
    $unit = addslashes($p['unit'] ?: 'Pcs');
    return <<<HTML
<button onclick='openProductModal("edit", {$data})' class="px-2.5 py-1.5 bg-primary text-white rounded text-xs hover:bg-blue-700">
    <i class="fas fa-edit"></i>
</button>
<button onclick="openStockModal({$p['id']}, '{$name}', {$p['stock']}, '{$unit}')" class="px-2.5 py-1.5 bg-emerald-500 text-white rounded text-xs hover:bg-emerald-600" title="Sesuaikan Stok">
    <i class="fas fa-plus-circle"></i>
</button>
<button onclick="deleteProduct({$p['id']}, '{$name}')" class="px-2.5 py-1.5 bg-red-500 text-white rounded text-xs hover:bg-red-600">
    <i class="fas fa-trash"></i>
</button>
HTML;
}
?>

<!-- FORM HIDDEN untuk delete -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="product_action" value="delete">
    <input type="hidden" name="id" id="deleteProductId">
</form>

<!-- ===== MODAL PRODUK ===== -->
<div id="productModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <h3 id="productModalTitle" class="text-xl font-bold mb-5"><i class="fas fa-box text-primary mr-2"></i>Tambah Produk</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="product_action" id="productAction" value="add">
            <input type="hidden" name="id" id="productId" value="0">
            <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">

            <div class="flex flex-col sm:flex-row gap-4 items-start bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="w-32 h-32 flex-shrink-0 rounded-xl overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 flex items-center justify-center relative">
                    <img id="photoPreview" class="hidden absolute inset-0 w-full h-full object-cover" src="" alt="">
                    <div id="photoPlaceholder" class="text-center text-slate-400">
                        <i class="fas fa-image text-3xl mb-1"></i>
                        <div class="text-xs">Belum ada foto</div>
                    </div>
                </div>
                <div class="flex-1 space-y-2">
                    <label class="block font-semibold text-sm">Foto Produk</label>
                    <input type="file" name="photo" id="photoInput" accept="image/*"
                           class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                    <p class="text-xs text-slate-500">Format: JPG, PNG, GIF, WEBP. Maks 2MB.</p>
                    <button type="button" id="removePhotoBtn" onclick="removePhoto()" class="hidden text-xs px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg">
                        <i class="fas fa-trash mr-1"></i>Hapus Foto
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Barcode</label>
                    <input type="text" name="barcode" id="pBarcode" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Nama Produk *</label>
                    <input type="text" name="name" id="pName" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Kategori</label>
                    <select name="category_id" id="pCategory" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                        <option value="">- Pilih Kategori -</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Supplier</label>
                    <select name="supplier_id" id="pSupplier" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                        <option value="">- Pilih Supplier -</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Harga Beli (Rp)</label>
                    <input type="number" name="purchase_price" id="pPurchasePrice" step="100" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Harga Jual (Rp) *</label>
                    <input type="number" name="selling_price" id="pSellingPrice" step="100" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Minimal Stok</label>
                    <input type="number" name="min_stock" id="pMinStock" value="5" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">Satuan</label>
                    <input type="text" name="unit" id="pUnit" value="Pcs" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                </div>
            </div>

            <div id="stockInfoBox" class="hidden bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm">
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                    <i class="fas fa-info-circle"></i>
                    <span>Stok saat ini: <b id="currentStockText">0</b>. Untuk mengubah stok, gunakan tombol <i class="fas fa-plus-circle text-emerald-500"></i> di tabel produk.</span>
                </div>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="document.getElementById('productModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL SESUAIKAN STOK ===== -->
<div id="stockModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <h3 class="text-xl font-bold mb-5"><i class="fas fa-sliders-h text-emerald-500 mr-2"></i>Sesuaikan Stok</h3>
        <form method="POST" class="space-y-4" onsubmit="return validateStockForm(this)">
            <input type="hidden" name="product_action" value="adjust_stock">
            <input type="hidden" name="id" id="stockProductId">

            <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3">
                <div class="font-semibold text-slate-700 dark:text-slate-200" id="stockProductName">-</div>
                <div class="text-sm text-slate-500 mt-1">
                    Stok saat ini: <b id="stockCurrent">0</b> <span id="stockUnit">Pcs</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">Jumlah Perubahan</label>
                <input type="number" name="change" id="stockChange" required value="0"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-2xl font-bold text-center"
                       placeholder="0">

                <div class="mt-3">
                    <div class="text-xs text-slate-500 mb-2">Klik tombol untuk menambah/mengurangi. Setiap klik diakumulasikan.</div>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <button type="button" onclick="quickAdd(1)" class="px-3 py-2.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg font-bold hover:bg-emerald-200">+1</button>
                        <button type="button" onclick="quickAdd(5)" class="px-3 py-2.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg font-bold hover:bg-emerald-200">+5</button>
                        <button type="button" onclick="quickAdd(25)" class="px-3 py-2.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg font-bold hover:bg-emerald-200">+25</button>
                        <button type="button" onclick="quickAdd(-1)" class="px-3 py-2.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg font-bold hover:bg-red-200">−1</button>
                        <button type="button" onclick="quickAdd(-5)" class="px-3 py-2.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg font-bold hover:bg-red-200">−5</button>
                        <button type="button" onclick="quickAdd(-25)" class="px-3 py-2.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg font-bold hover:bg-red-200">−25</button>
                    </div>
                    <button type="button" onclick="resetStockChange()" class="w-full px-3 py-2 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg font-semibold text-sm hover:bg-slate-300">
                        <i class="fas fa-undo mr-1"></i>Reset ke 0
                    </button>
                </div>
            </div>

            <div id="previewBox" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-sm border border-blue-200 dark:border-blue-800">
                <div class="text-blue-700 dark:text-blue-300">
                    Stok setelah perubahan: <b id="stockPreview">-</b>
                </div>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg"><i class="fas fa-check mr-2"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL KATEGORI ===== -->
<div id="catModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <h3 class="text-xl font-bold mb-5"><i class="fas fa-tags text-purple-500 mr-2"></i>Kelola Kategori</h3>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 mb-5">
            <input type="hidden" name="cat_action" value="add">
            <input type="text" name="name" placeholder="Nama Kategori" required class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <input type="text" name="description" placeholder="Deskripsi" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg text-sm"><i class="fas fa-plus"></i></button>
        </form>
        <div class="space-y-2 max-h-72 overflow-y-auto">
            <?php foreach ($categories as $c): ?>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <div>
                        <div class="font-semibold text-sm"><?= htmlspecialchars($c['name']) ?></div>
                        <?php if ($c['description']): ?><div class="text-xs text-slate-500"><?= htmlspecialchars($c['description']) ?></div><?php endif; ?>
                    </div>
                    <div class="flex gap-1">
                        <button onclick='editCategory(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="px-2 py-1 bg-primary text-white rounded text-xs"><i class="fas fa-edit"></i></button>
                        <button onclick="if(confirm('Hapus?')) document.getElementById('cdel<?= $c['id'] ?>').submit()" class="px-2 py-1 bg-red-500 text-white rounded text-xs"><i class="fas fa-trash"></i></button>
                        <form id="cdel<?= $c['id'] ?>" method="POST" class="hidden"><input type="hidden" name="cat_action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="catEditForm" class="hidden mt-5 pt-5 border-t border-slate-200 dark:border-slate-800">
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto_auto] gap-2">
                <input type="hidden" name="cat_action" value="edit">
                <input type="hidden" name="id" id="catEditId">
                <input type="text" name="name" id="catEditName" required class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="text" name="description" id="catEditDesc" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm"><i class="fas fa-save"></i></button>
                <button type="button" onclick="document.getElementById('catEditForm').classList.add('hidden')" class="px-4 py-2 bg-slate-400 text-white rounded-lg text-sm">X</button>
            </form>
        </div>
        <div class="flex justify-end mt-4">
            <button onclick="document.getElementById('catModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Tutup</button>
        </div>
    </div>
</div>

<!-- ===== MODAL SUPPLIER ===== -->
<div id="supModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <h3 class="text-xl font-bold mb-5"><i class="fas fa-truck text-amber-500 mr-2"></i>Kelola Supplier</h3>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-5">
            <input type="hidden" name="sup_action" value="add">
            <input type="text" name="name" placeholder="Nama Supplier" required class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <input type="text" name="contact_person" placeholder="Kontak Person" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <input type="text" name="phone" placeholder="Telepon" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <input type="email" name="email" placeholder="Email" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <input type="text" name="address" placeholder="Alamat" class="sm:col-span-2 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <button type="submit" class="sm:col-span-2 px-4 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg text-sm"><i class="fas fa-plus mr-2"></i>Tambah Supplier</button>
        </form>
        <div class="space-y-2 max-h-72 overflow-y-auto">
            <?php foreach ($suppliers as $s): ?>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <div>
                        <div class="font-semibold text-sm"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($s['contact_person'] ?: '-') ?></div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick='editSupplier(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="px-2 py-1 bg-primary text-white rounded text-xs"><i class="fas fa-edit"></i></button>
                        <button onclick="if(confirm('Hapus?')) document.getElementById('sdel<?= $s['id'] ?>').submit()" class="px-2 py-1 bg-red-500 text-white rounded text-xs"><i class="fas fa-trash"></i></button>
                        <form id="sdel<?= $s['id'] ?>" method="POST" class="hidden"><input type="hidden" name="sup_action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="supEditForm" class="hidden mt-5 pt-5 border-t border-slate-200 dark:border-slate-800">
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input type="hidden" name="sup_action" value="edit">
                <input type="hidden" name="id" id="supEditId">
                <input type="text" name="name" id="supEditName" required class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="text" name="contact_person" id="supEditContact" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="text" name="phone" id="supEditPhone" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="email" name="email" id="supEditEmail" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <input type="text" name="address" id="supEditAddress" class="sm:col-span-2 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <button type="submit" class="sm:col-span-2 px-4 py-2 bg-primary text-white rounded-lg text-sm"><i class="fas fa-save mr-2"></i>Update Supplier</button>
            </form>
        </div>
        <div class="flex justify-end mt-4">
            <button onclick="document.getElementById('supModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Tutup</button>
        </div>
    </div>
</div>

<script>
let currentPhoto = null;

// ===========================================================
// SEARCH AJAX
// ===========================================================
const searchInput = document.getElementById('searchInput');
const searchSpinner = document.getElementById('searchSpinner');
const searchHint = document.getElementById('searchHint');
const clearBtn = document.getElementById('clearSearchBtn');
const tableBody = document.getElementById('productsTableBody');
const paginationContainer = document.getElementById('paginationContainer');
let searchTimer = null;
let currentPage = <?= (int)$page ?>;
let currentSearch = <?= json_encode($search) ?>;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    searchSpinner.classList.add('hidden');
    clearBtn.classList.toggle('hidden', q.length === 0);

    if (q.length === 0) {
        searchHint.innerHTML = 'Pencarian otomatis berjalan setelah Anda mengetik <b>5 karakter</b> (nama atau barcode).';
        searchTimer = setTimeout(() => { currentPage = 1; performSearch(''); }, 400);
        return;
    }
    if (q.length >= 5) {
        searchHint.innerHTML = `<i class="fas fa-clock"></i> Mencari "<b>${escapeHtml(q)}</b>"...`;
        searchTimer = setTimeout(() => { currentPage = 1; performSearch(q); }, 500);
    } else {
        const remaining = 5 - q.length;
        searchHint.innerHTML = `<i class="fas fa-keyboard"></i> Ketik <b>${remaining}</b> karakter lagi untuk pencarian otomatis...`;
    }
});

function clearSearch() {
    searchInput.value = '';
    clearBtn.classList.add('hidden');
    searchHint.innerHTML = 'Pencarian otomatis berjalan setelah Anda mengetik <b>5 karakter</b> (nama atau barcode).';
    currentPage = 1;
    performSearch('');
    searchInput.focus();
}

function performSearch(q, page = 1) {
    searchSpinner.classList.remove('hidden');
    currentSearch = q;

    fetch(`../../ajax/search_products.php?search=${encodeURIComponent(q)}&page=${page}`)
        .then(r => r.json())
        .then(data => {
            searchSpinner.classList.add('hidden');
            if (!data.success) return;
            renderTable(data.items);
            renderPagination(data.page, data.total_pages, data.search);
            if (q === '') {
                searchHint.innerHTML = `Menampilkan semua produk (<b>${data.total}</b> produk).`;
            } else {
                searchHint.innerHTML = `Menampilkan hasil untuk: <b>"${escapeHtml(q)}"</b> (<b>${data.total}</b> produk ditemukan)`;
            }
            const newUrl = q === '' ? window.location.pathname : `${window.location.pathname}?search=${encodeURIComponent(q)}&page=${page}`;
            window.history.replaceState({}, '', newUrl);
        })
        .catch(err => { console.error(err); searchSpinner.classList.add('hidden'); });
}

function loadPage(page) {
    currentPage = page;
    performSearch(currentSearch, page);
}

function renderTable(items) {
    if (!items.length) {
        tableBody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-slate-400">${currentSearch ? 'Produk tidak ditemukan untuk pencarian "<b>' + escapeHtml(currentSearch) + '</b>"' : 'Belum ada produk'}</td></tr>`;
        return;
    }

    const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');

    tableBody.innerHTML = items.map(p => {
        const photoHtml = p.photo_url
            ? `<img src="../${escapeHtml(p.photo_url.replace('../',''))}" alt="foto" class="w-12 h-12 object-cover rounded-lg border border-slate-200 dark:border-slate-700" onerror="this.outerHTML='<div class=\\'w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400\\'><i class=\\'fas fa-image\\'></i></div>'">`
            : `<div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400"><i class="fas fa-image"></i></div>`;

        const isLow = p.stock <= p.min_stock;
        const stockHtml = isLow
            ? `<span class="inline-block px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 font-bold text-xs">${p.stock}</span>`
            : `<span class="font-semibold">${p.stock}</span>`;

        const dataJson = JSON.stringify({
            id: p.id, name: p.name, barcode: p.barcode,
            category_id: p.category_id, supplier_id: p.supplier_id,
            purchase_price: p.purchase_price, selling_price: p.selling_price,
            min_stock: p.min_stock, stock: p.stock, unit: p.unit,
            photo: p.photo_url ? p.photo_url.replace('../', '') : null
        }).replace(/'/g, '&#39;');

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 ${isLow ? 'bg-red-50/40 dark:bg-red-900/10' : ''}">
                <td class="p-3">${photoHtml}</td>
                <td class="p-3 text-xs text-slate-500">${escapeHtml(p.barcode || '-')}</td>
                <td class="p-3 font-semibold">${isLow ? '<i class="fas fa-exclamation-triangle text-red-500 mr-1"></i>' : ''}${escapeHtml(p.name)}</td>
                <td class="p-3 text-xs">${escapeHtml(p.category_name || '-')}</td>
                <td class="p-3 text-right">${rp(p.purchase_price)}</td>
                <td class="p-3 text-right font-semibold text-emerald-600">${rp(p.selling_price)}</td>
                <td class="p-3 text-center">${stockHtml}</td>
                <td class="p-3 text-center whitespace-nowrap">
                    <button onclick='openProductModal("edit", ${dataJson})' class="px-2.5 py-1.5 bg-primary text-white rounded text-xs hover:bg-blue-700">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="openStockModal(${p.id}, '${escapeJs(p.name)}', ${p.stock}, '${escapeJs(p.unit)}')" class="px-2.5 py-1.5 bg-emerald-500 text-white rounded text-xs hover:bg-emerald-600">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <button onclick="deleteProduct(${p.id}, '${escapeJs(p.name)}')" class="px-2.5 py-1.5 bg-red-500 text-white rounded text-xs hover:bg-red-600">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(currentPage, totalPages, search) {
    if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }
    let html = '<div class="flex justify-center gap-2 flex-wrap">';
    for (let i = 1; i <= totalPages; i++) {
        const isActive = i === currentPage;
        html += `<button onclick="loadPage(${i})" class="px-3 py-1.5 rounded-lg text-sm ${isActive ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300'}">${i}</button>`;
    }
    html += '</div>';
    paginationContainer.innerHTML = html;
}

function deleteProduct(id, name) {
    if (!confirm('Hapus produk "' + name + '"?')) return;
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteForm').submit();
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeJs(s) {
    return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// ===========================================================
// MODAL PRODUK
// ===========================================================
function openProductModal(action, data = null) {
    const modal = document.getElementById('productModal');
    const title = document.getElementById('productModalTitle');
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('removePhotoBtn');
    const stockInfo = document.getElementById('stockInfoBox');
    document.getElementById('removePhotoFlag').value = '0';
    document.getElementById('photoInput').value = '';

    if (action === 'add') {
        title.innerHTML = '<i class="fas fa-plus text-primary mr-2"></i>Tambah Produk';
        document.getElementById('productAction').value = 'add';
        document.getElementById('productId').value = 0;
        ['pBarcode','pName'].forEach(id => document.getElementById(id).value = '');
        ['pCategory','pSupplier'].forEach(id => document.getElementById(id).value = '');
        ['pPurchasePrice','pSellingPrice'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('pMinStock').value = 5;
        document.getElementById('pUnit').value = 'Pcs';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        stockInfo.classList.add('hidden');
        currentPhoto = null;
    } else if (data) {
        title.innerHTML = '<i class="fas fa-edit text-primary mr-2"></i>Edit Produk';
        document.getElementById('productAction').value = 'edit';
        document.getElementById('productId').value = data.id;
        document.getElementById('pBarcode').value = data.barcode || '';
        document.getElementById('pName').value = data.name;
        document.getElementById('pCategory').value = data.category_id || '';
        document.getElementById('pSupplier').value = data.supplier_id || '';
        document.getElementById('pPurchasePrice').value = data.purchase_price;
        document.getElementById('pSellingPrice').value = data.selling_price;
        document.getElementById('pMinStock').value = data.min_stock;
        document.getElementById('pUnit').value = data.unit || 'Pcs';
        currentPhoto = data.photo || null;
        if (currentPhoto) {
            preview.src = '../../' + currentPhoto;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            removeBtn.classList.add('hidden');
        }
        stockInfo.classList.remove('hidden');
        document.getElementById('currentStockText').textContent = data.stock + ' ' + (data.unit || 'Pcs');
    }
    modal.classList.remove('hidden');
}

document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('photoPreview').src = ev.target.result;
        document.getElementById('photoPreview').classList.remove('hidden');
        document.getElementById('photoPlaceholder').classList.add('hidden');
        document.getElementById('removePhotoBtn').classList.remove('hidden');
        document.getElementById('removePhotoFlag').value = '0';
    };
    reader.readAsDataURL(file);
});

function removePhoto() {
    document.getElementById('photoInput').value = '';
    document.getElementById('photoPreview').classList.add('hidden');
    document.getElementById('photoPlaceholder').classList.remove('hidden');
    document.getElementById('removePhotoBtn').classList.add('hidden');
    document.getElementById('removePhotoFlag').value = '1';
}

// ===========================================================
// MODAL SESUAIKAN STOK
// ===========================================================
let stockCurrentValue = 0;

function openStockModal(id, name, stock, unit) {
    stockCurrentValue = parseInt(stock) || 0;
    document.getElementById('stockProductId').value = id;
    document.getElementById('stockProductName').textContent = '📦 ' + name;
    document.getElementById('stockCurrent').textContent = stockCurrentValue;
    document.getElementById('stockUnit').textContent = unit || 'Pcs';
    document.getElementById('stockChange').value = 0;
    updateStockPreview();
    document.getElementById('stockModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('stockChange').select(), 100);
}

function quickAdd(value) {
    const input = document.getElementById('stockChange');
    const current = parseInt(input.value) || 0;
    input.value = current + value;
    updateStockPreview();
    input.classList.add('ring-2', 'ring-emerald-400');
    setTimeout(() => input.classList.remove('ring-2', 'ring-emerald-400'), 200);
}

function resetStockChange() {
    document.getElementById('stockChange').value = 0;
    updateStockPreview();
}

function updateStockPreview() {
    const change = parseInt(document.getElementById('stockChange').value) || 0;
    const newStock = stockCurrentValue + change;
    const preview = document.getElementById('stockPreview');

    if (newStock < 0) {
        preview.innerHTML = '<span class="text-red-500 font-bold">' + newStock + ' (Tidak valid — stok negatif!)</span>';
    } else {
        preview.innerHTML = '<b>' + newStock + '</b> ' + (change > 0 ? '<span class="text-emerald-600">(+' + change + ')</span>' : change < 0 ? '<span class="text-red-500">(' + change + ')</span>' : '');
    }
}

document.getElementById('stockChange').addEventListener('input', updateStockPreview);

function validateStockForm(form) {
    const change = parseInt(form.change.value) || 0;
    if (change === 0) { alert('Jumlah perubahan tidak boleh 0.'); return false; }
    if (stockCurrentValue + change < 0) { alert('Stok akan menjadi negatif! Stok saat ini: ' + stockCurrentValue); return false; }
    return true;
}

// ===========================================================
// KATEGORI & SUPPLIER
// ===========================================================
function editCategory(data) {
    document.getElementById('catEditForm').classList.remove('hidden');
    document.getElementById('catEditId').value = data.id;
    document.getElementById('catEditName').value = data.name;
    document.getElementById('catEditDesc').value = data.description || '';
}

function editSupplier(data) {
    document.getElementById('supEditForm').classList.remove('hidden');
    document.getElementById('supEditId').value = data.id;
    document.getElementById('supEditName').value = data.name;
    document.getElementById('supEditContact').value = data.contact_person || '';
    document.getElementById('supEditPhone').value = data.phone || '';
    document.getElementById('supEditEmail').value = data.email || '';
    document.getElementById('supEditAddress').value = data.address || '';
}

['productModal','stockModal','catModal','supModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

<?php if ($flash): ?>
document.addEventListener('DOMContentLoaded', () => notify('<?= $flash['type'] ?>', '<?= addslashes($flash['msg']) ?>'));
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>
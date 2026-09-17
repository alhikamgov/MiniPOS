<?php
// pages/kasir/pos.php
session_start();

if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: pos.php'); exit;
}
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: pos.php'); exit;
}

include '../../includes/header.php';
requireRole('Kasir');

$customers = $db->query("SELECT id, name, loyalty_points FROM customers ORDER BY name")->fetchAll();
$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$all_products = $db->query("SELECT id, name, selling_price, purchase_price, stock, unit, barcode, category_id, photo 
                            FROM products WHERE stock > 0 ORDER BY name")->fetchAll();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$initial_cart = array_values($_SESSION['cart']);
$all_customers = $db->query("SELECT id, name, phone, loyalty_points FROM customers ORDER BY name")->fetchAll();
?>

<style>
    /* FULLSCREEN */
    body.pos-fullscreen aside#sidebar, body.pos-fullscreen .sidebar { transform: translateX(-100%) !important; visibility: hidden !important; pointer-events: none !important; }
    body.pos-fullscreen > div[class*="lg:ml-64"] { margin-left: 0 !important; padding-left: 0 !important; width: 100% !important; }
    body.pos-fullscreen > div[class*="lg:ml-64"] > header { display: none !important; }
    body.pos-fullscreen main { padding-top: 1rem !important; }

    /* PRINT */
    @page { size: 58mm auto; margin: 0; }
    @media print {
        html, body { width: 58mm; margin: 0; padding: 0; background: #fff; }
        body * { visibility: hidden !important; }
        #printArea, #printArea * { visibility: visible !important; }
        #printArea { position: absolute !important; left: 0 !important; top: 0 !important; width: 58mm !important; padding: 2mm !important; font-family: 'Courier New', monospace !important; font-size: 10px !important; color: #000 !important; line-height: 1.2 !important; }
        .no-print { display: none !important; }
    }
    .receipt-preview { font-family: 'Courier New', monospace; font-size: 11px; color: #000; background: #fff; padding: 10px 8px; max-width: 260px; margin: 0 auto; line-height: 1.35; }
    .receipt-preview .r-center { text-align: center; } .receipt-preview .r-bold { font-weight: bold; }
    .receipt-preview .r-line { border-top: 1px dashed #000; margin: 4px 0; } .receipt-preview .r-line-double { border-top: 2px solid #000; margin: 4px 0; }
    .receipt-preview .r-row { display: flex; justify-content: space-between; gap: 4px; margin: 1px 0; }
    .receipt-preview .r-item { margin: 4px 0; } .receipt-preview .r-item .r-item-detail { display: flex; justify-content: space-between; padding-left: 8px; }

    /* CART */
    .cart-flash { animation: flash 0.4s ease; }
    @keyframes flash { 0%{background:transparent;} 50%{background:#fef3c7;} 100%{background:transparent;} }

    /* SEARCH */
    .search-highlight { border-color: #10b981 !important; box-shadow: 0 0 0 4px rgba(16,185,129,0.2) !important; }

    /* CATEGORY CHIP */
    .cat-chip { transition: all 0.15s; white-space: nowrap; padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 12px; cursor: pointer; border: none; background: #e2e8f0; color: #475569; }
    .cat-chip:hover { background: #cbd5e1; }
    .cat-chip.active { background: #2563eb !important; color: #fff !important; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
    .dark .cat-chip { background: #1e293b; color: #cbd5e1; }
    .dark .cat-chip:hover { background: #334155; }
    .cat-scroll::-webkit-scrollbar { height: 4px; }
    .cat-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }

    .product-card { transition: all 0.15s; }
    .product-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); border-color: #2563eb; }

.quick-amount { padding: 7px 4px; border-radius: 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; font-weight: 700; font-size: 11px; cursor: pointer; transition: 0.1s; }
.quick-amount:hover { background: #ffedd5; }
.quick-amount:active { transform: scale(0.95); }
.dark .quick-amount { background: #7c2d12; border-color: #9a3412; color: #fdba74; }

    /* DISCOUNT */
    .btn-discount { padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; }
    .btn-discount:hover { box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
    .btn-discount.active { background: linear-gradient(135deg, #16a34a, #22c55e); }
</style>

<div class="flex justify-between items-center mb-4 flex-wrap gap-2">
    <h1 class="text-xl font-bold"><i class="fas fa-shopping-cart text-primary mr-2"></i>Kasir / Transaksi</h1>
    <button type="button" onclick="togglePosFullscreen()" title="Fullscreen"
            class="flex items-center justify-center w-10 h-10 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-lg transition shadow-sm">
        <i class="fas fa-expand" id="fsIcon"></i>
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <!-- KIRI: Produk -->
    <div class="lg:col-span-7">
        <div class="relative">
            <input type="text" id="searchInput" placeholder="🔍 Scan barcode / cari produk..."
                   autocomplete="off" autofocus
                   class="w-full px-4 py-3 text-base rounded-xl border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-primary focus:ring-4 focus:ring-primary/20 outline-none">
            <div id="searchResult" class="hidden absolute w-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg max-h-72 overflow-y-auto z-20"></div>
        </div>

        <div class="mt-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3">
            <div class="cat-scroll overflow-x-auto pb-1">
                <div class="flex gap-2">
                    <button type="button" onclick="filterCategory(0)" data-cat="0" class="cat-chip active"><i class="fas fa-th mr-1"></i>Semua</button>
                    <?php foreach ($categories as $c): ?>
                        <button type="button" onclick="filterCategory(<?= $c['id'] ?>)" data-cat="<?= $c['id'] ?>" class="cat-chip"><?= htmlspecialchars($c['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="mt-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
            <div class="flex justify-between items-center mb-2">
                <h4 class="font-bold text-sm">📋 Produk Tersedia</h4>
                <span id="productCount" class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">0 produk</span>
            </div>
            <div id="productGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2 max-h-[520px] overflow-y-auto pr-1"></div>
        </div>
    </div>

    <!-- KANAN: Keranjang -->
    <div class="lg:col-span-5">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 flex flex-col min-h-[560px]">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold"><i class="fas fa-shopping-basket text-primary mr-1.5"></i>Keranjang</h3>
                <div class="flex gap-1.5">
                    <button id="discountBtn" onclick="openDiscountModal()" class="btn-discount hidden">
                        <i class="fas fa-tag"></i><span id="discountBtnText">Diskon</span>
                    </button>
                    <button id="clearBtn" onclick="clearCart()" class="hidden text-xs px-2.5 py-1 bg-red-500 hover:bg-red-600 text-white rounded-md">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div id="cartContainer" class="flex-1 overflow-y-auto max-h-[340px] divide-y divide-slate-100 dark:divide-slate-800"></div>

            <div class="border-t border-slate-200 dark:border-slate-800 pt-3 mt-2">
                <!-- Diskon Row -->
                <div id="discountRow" style="display:none" class="justify-between items-center px-3 py-2 mb-2 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-md">
                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">
                        <i class="fas fa-tag mr-1"></i>Diskon
                    </span>
                    <div class="flex items-center gap-2">
                        <span id="discountDisplay" class="font-bold text-amber-700 dark:text-amber-300 text-sm">-Rp 0</span>
                        <button onclick="clearDiscount()" class="w-5 h-5 rounded-full bg-red-500 hover:bg-red-600 text-white text-xs flex items-center justify-center">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Subtotal -->
                <div id="subtotalRow" style="display:none" class="justify-between items-center mb-1 text-xs text-slate-500 px-1">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay">Rp 0</span>
                </div>

                <!-- Total + Laba -->
                <div class="flex justify-between items-center mb-2 px-1">
                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">TOTAL</span>
                    <span id="totalDisplay" class="text-2xl font-extrabold text-primary">Rp 0</span>
                </div>

                <div id="profitPreview" style="display:none" class="justify-between items-center px-3 py-1.5 mb-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-dashed border-emerald-400">
                    <span class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold"><i class="fas fa-chart-line mr-1"></i>Laba</span>
                    <span id="profitDisplay" class="font-bold text-emerald-700 dark:text-emerald-300 text-sm">Rp 0</span>
                </div>

                <div id="paymentArea" class="hidden">
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div class="relative">
    <input type="text" id="customerInput"
           placeholder="Cari pelanggan (nama / telepon)"
           autocomplete="off"
           class="w-full px-2.5 py-1.5 pr-7 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-primary outline-none">
    <input type="hidden" id="customerId" value="">
    <button type="button" id="customerClearBtn" onclick="clearCustomer()"
            class="hidden absolute right-1.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 text-[10px]">
        <i class="fas fa-times"></i>
    </button>
    <div id="customerResult"
         class="hidden absolute w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg max-h-48 overflow-y-auto z-30"></div>
</div>
                        <select id="paymentMethod" onchange="toggleCashInput()" class="px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                            <option value="cash">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                            <option value="debit">Debit</option>
                        </select>
                    </div>

                    <div id="cashInputWrap" class="mb-2">
    <label class="block text-xs font-semibold mb-1.5 text-slate-600 dark:text-slate-300">
        <i class="fas fa-money-bill-wave text-emerald-500 mr-1"></i>Uang Tunai Diterima
    </label>

    <!-- Input Field -->
    <div class="relative mb-2">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-lg font-bold text-primary pointer-events-none">Rp</span>
        <input type="text" id="cashInput"
               inputmode="numeric"
               pattern="[0-9]*"
               autocomplete="off"
               placeholder="0"
               class="w-full pl-12 pr-10 py-3 text-2xl font-extrabold text-right rounded-lg border-2 border-primary bg-primary/5 dark:bg-primary/10 text-primary focus:ring-4 focus:ring-primary/20 outline-none transition">
        <button type="button" onclick="clearCash()"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-red-500 hover:text-white text-slate-500 text-xs transition flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Quick Amounts (akumulasi) -->
    <div class="grid grid-cols-4 gap-1.5 mb-2">
        <button type="button" onclick="quickAmount(10000)" class="quick-amount">+10rb</button>
        <button type="button" onclick="quickAmount(20000)" class="quick-amount">+20rb</button>
        <button type="button" onclick="quickAmount(50000)" class="quick-amount">+50rb</button>
        <button type="button" onclick="quickAmount(100000)" class="quick-amount">+100rb</button>
    </div>

    <!-- Uang Pas -->
    <button type="button" onclick="exactAmount()"
            class="w-full py-2 bg-emerald-100 dark:bg-emerald-900/30 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold rounded-lg text-xs mb-2 transition">
        <i class="fas fa-money-bill-wave mr-1"></i>Uang Pas (Rp <span id="exactAmountDisplay">0</span>)
    </button>

    <!-- Kembalian / Kurang -->
    <div id="changeDisplay" style="display:none" class="justify-between items-center px-3 py-1.5 mb-1.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm">
        <span class="text-emerald-700 dark:text-emerald-400 text-xs">Kembalian</span>
        <span id="changeAmount" class="font-bold text-emerald-700 dark:text-emerald-400">Rp 0</span>
    </div>
    <div id="shortDisplay" style="display:none" class="justify-between items-center px-3 py-1.5 mb-1.5 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm">
        <span class="text-red-700 dark:text-red-400 text-xs">Kurang</span>
        <span id="shortAmount" class="font-bold text-red-700 dark:text-red-400">Rp 0</span>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DISKON -->
<div id="discountModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full shadow-2xl">
        <div class="p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-tag text-amber-500 mr-2"></i>Atur Diskon</h3>
                <button onclick="closeDiscountModal()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-lg"></i></button>
            </div>

            <div class="grid grid-cols-2 gap-1.5 mb-4 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                <button id="tabPercent" onclick="switchDiscountTab('percent')" class="py-2 rounded-md font-semibold text-sm transition bg-white dark:bg-slate-700 shadow">Persen (%)</button>
                <button id="tabNominal" onclick="switchDiscountTab('nominal')" class="py-2 rounded-md font-semibold text-sm transition text-slate-500">Nominal (Rp)</button>
            </div>

            <div id="percentMode">
                <div class="grid grid-cols-5 gap-1.5 mb-3">
                    <button type="button" onclick="quickPercent(5)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-sm">5%</button>
                    <button type="button" onclick="quickPercent(10)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-sm">10%</button>
                    <button type="button" onclick="quickPercent(15)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-sm">15%</button>
                    <button type="button" onclick="quickPercent(20)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-sm">20%</button>
                    <button type="button" onclick="quickPercent(50)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-sm">50%</button>
                </div>
                <div class="relative">
                    <input type="number" id="discountPercent" value="0" min="0" max="100" step="1" oninput="previewDiscount()"
                           class="w-full px-4 py-3 pr-10 text-2xl font-bold text-center rounded-lg border-2 border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 outline-none focus:border-amber-500">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-amber-500">%</span>
                </div>
            </div>

            <div id="nominalMode" class="hidden">
                <div class="grid grid-cols-4 gap-1.5 mb-3">
                    <button type="button" onclick="quickNominal(1000)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-xs">1.000</button>
                    <button type="button" onclick="quickNominal(2000)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-xs">2.000</button>
                    <button type="button" onclick="quickNominal(5000)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-xs">5.000</button>
                    <button type="button" onclick="quickNominal(10000)" class="py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-md font-bold hover:bg-amber-200 text-xs">10.000</button>
                </div>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-amber-500">Rp</span>
                    <input type="number" id="discountNominal" value="0" min="0" step="500" oninput="previewDiscount()"
                           class="w-full px-4 py-3 pl-14 text-2xl font-bold text-center rounded-lg border-2 border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 outline-none focus:border-amber-500">
                </div>
            </div>

            <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-500">Subtotal</span><span id="previewSubtotal" class="font-semibold">Rp 0</span></div>
                <div class="flex justify-between text-sm mb-1"><span class="text-slate-500">Diskon</span><span id="previewDiscount" class="font-semibold text-amber-600">-Rp 0</span></div>
                <div class="flex justify-between text-base font-bold pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span>Total</span><span id="previewTotal" class="text-primary">Rp 0</span>
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button onclick="clearDiscountAndClose()" class="flex-1 py-2.5 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 text-red-700 dark:text-red-400 font-semibold rounded-lg text-sm"><i class="fas fa-times mr-1"></i>Hapus</button>
                <button onclick="applyDiscount()" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-sm"><i class="fas fa-check mr-1"></i>Terapkan</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL STRUK -->
<div id="receiptModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="p-4">
            <h3 class="text-center font-bold text-base mb-3 no-print"><i class="fas fa-receipt mr-2"></i>Struk Pembayaran</h3>
            <div class="bg-slate-100 dark:bg-slate-800 p-2 rounded-lg overflow-x-auto">
                <div id="receiptContent" class="receipt-preview"></div>
            </div>
            <div class="flex gap-2 justify-center mt-4 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg text-sm"><i class="fas fa-print mr-2"></i>Print</button>
                <button onclick="closeReceipt()" class="px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg text-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div id="printArea" class="receipt-preview" style="position:absolute; left:-9999px; top:0;"></div>

<script>
let cart = <?= json_encode($initial_cart, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const ALL_PRODUCTS = <?= json_encode($all_products, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const STORE_NAME = <?= json_encode($app_name) ?>;
const STORE_ADDRESS = <?= json_encode($store_address) ?>;
const STORE_PHONE = <?= json_encode($store_phone) ?>;
const CASHIER_NAME = <?= json_encode($full_name) ?>;
const ALL_CUSTOMERS = <?= json_encode($all_customers, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

let activeCategory = 0;
let cashValue = 0;
let discountAmount = 0;
let discountType = 'percent';

function rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function cartSubtotal() { return cart.reduce((s, i) => s + i.price * i.quantity, 0); }
function cartFinalTotal() { return Math.max(0, cartSubtotal() - discountAmount); }
function cartProfit() {
    const cost = cart.reduce((s, i) => {
        const p = ALL_PRODUCTS.find(x => parseInt(x.id) === parseInt(i.id));
        return s + ((p ? parseFloat(p.purchase_price) : 0) * i.quantity);
    }, 0);
    return cartFinalTotal() - cost;
}

// Toggle show/hide element (pakai style.display agar tidak konflik dengan CSS custom)
function show(el, display = 'flex') { el.style.display = display; }
function hide(el) { el.style.display = 'none'; }

// ===== FULLSCREEN =====
function togglePosFullscreen() {
    document.body.classList.toggle('pos-fullscreen');
    const isFs = document.body.classList.contains('pos-fullscreen');
    document.getElementById('fsIcon').className = isFs ? 'fas fa-compress' : 'fas fa-expand';
    localStorage.setItem('posFullscreen', isFs ? '1' : '0');
}
if (localStorage.getItem('posFullscreen') === '1') {
    document.body.classList.add('pos-fullscreen');
    document.addEventListener('DOMContentLoaded', () => { document.getElementById('fsIcon').className = 'fas fa-compress'; });
}

// ===== CASH INPUT (Ganti Numpad) =====
const cashInput = document.getElementById('cashInput');

// Format angka dengan pemisah ribuan
function formatCash(n) {
    if (!n || n === 0) return '';
    return Number(n).toLocaleString('id-ID');
}

// Event listener: hanya terima angka
cashInput.addEventListener('input', function() {
    let raw = this.value.replace(/\D/g, '');
    if (raw.length > 9) raw = raw.slice(0, 9); // max 9 digit
    cashValue = parseInt(raw) || 0;
    this.value = formatCash(cashValue);
    updateCashDisplay();
});

// Enter di kolom input = langsung bayar
cashInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        processPayment();
    }
});

// Clear input
function clearCash() {
    cashInput.value = '';
    cashValue = 0;
    updateCashDisplay();
    cashInput.focus();
}

// Quick amount (akumulasi)
function quickAmount(a) {
    cashValue += a;
    cashInput.value = formatCash(cashValue);
    updateCashDisplay();
}

// Uang pas
function exactAmount() {
    cashValue = cartFinalTotal();
    cashInput.value = formatCash(cashValue);
    updateCashDisplay();
}

function updateCashDisplay() {
    const total = cartFinalTotal();

    // Update tampilan "Uang Pas (Rp xxx)"
    const exactEl = document.getElementById('exactAmountDisplay');
    if (exactEl) exactEl.textContent = total.toLocaleString('id-ID');

    const changeBox = document.getElementById('changeDisplay');
    const shortBox = document.getElementById('shortDisplay');

    if (cashValue >= total && total > 0) {
        show(changeBox);
        hide(shortBox);
        document.getElementById('changeAmount').textContent = rp(cashValue - total);
    } else if (cashValue > 0 && cashValue < total) {
        show(shortBox);
        hide(changeBox);
        document.getElementById('shortAmount').textContent = rp(total - cashValue);
    } else {
        hide(changeBox);
        hide(shortBox);
    }
}

// ===== KATEGORI =====
function filterCategory(catId) {
    activeCategory = Number(catId);
    document.querySelectorAll('.cat-chip').forEach(btn => btn.classList.toggle('active', Number(btn.dataset.cat) === activeCategory));
    renderProducts();
}
function renderProducts() {
    const grid = document.getElementById('productGrid');
    const count = document.getElementById('productCount');
    const filtered = activeCategory === 0 ? ALL_PRODUCTS : ALL_PRODUCTS.filter(p => Number(p.category_id) === activeCategory);
    count.textContent = filtered.length + ' produk';
    if (filtered.length === 0) { grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-8 text-sm">Tidak ada produk</p>'; return; }
    grid.innerHTML = filtered.map(p => {
        const data = JSON.stringify({id: parseInt(p.id), name: p.name, price: parseFloat(p.selling_price), stock: parseInt(p.stock)}).replace(/'/g, "&#39;");
        const photo = (p.photo && p.photo.trim() !== '')
            ? `<img src="../../${escapeHtml(p.photo)}" class="w-full h-20 object-cover rounded-md mb-1.5 bg-slate-100 dark:bg-slate-700" onerror="this.style.display='none'">`
            : `<div class="w-full h-20 rounded-md mb-1.5 bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600"><i class="fas fa-image text-xl"></i></div>`;
        return `<div onclick='addToCart(${data})' class="product-card bg-slate-50 dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer text-center">
                ${photo}
                <div class="font-semibold text-xs truncate" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</div>
                <div class="text-primary font-bold text-xs mt-0.5">Rp ${Number(p.selling_price).toLocaleString('id-ID')}</div>
                <div class="text-[10px] text-slate-500">Stok: ${p.stock}</div>
            </div>`;
    }).join('');
}

// ===== KERANJANG =====
function renderCart(flashIdx = -1) {
    const container = document.getElementById('cartContainer');
    const totalDisplay = document.getElementById('totalDisplay');
    const paymentArea = document.getElementById('paymentArea');
    const clearBtn = document.getElementById('clearBtn');
    const discountBtn = document.getElementById('discountBtn');
    const discountRow = document.getElementById('discountRow');
    const subtotalRow = document.getElementById('subtotalRow');
    const profitPreview = document.getElementById('profitPreview');

    if (!cart.length) {
        container.innerHTML = '<p class="text-center text-slate-400 py-10 text-sm">Keranjang kosong</p>';
        totalDisplay.textContent = 'Rp 0';
        hide(paymentArea);
        hide(clearBtn);
        hide(discountBtn);
        hide(discountRow);
        hide(subtotalRow);
        hide(profitPreview);
        return;
    }

    let html = '';
    cart.forEach((item, idx) => {
        const sub = item.price * item.quantity;
        html += `<div class="flex justify-between items-center py-2 ${flashIdx === idx ? 'cart-flash' : ''}">
                <div class="flex-1 min-w-0 pr-1.5">
                    <div class="font-semibold truncate text-xs">${escapeHtml(item.name)}</div>
                    <div class="text-[11px] text-slate-500">${rp(item.price)} × ${item.quantity} = <b>${rp(sub)}</b></div>
                </div>
                <div class="flex items-center gap-0.5">
                    <button onclick="changeQty(${idx}, -1)" class="w-6 h-6 rounded bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 font-bold text-xs">−</button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.max_stock}" onchange="updateQty(${idx}, this.value)"
                           class="w-10 px-0.5 py-0.5 text-center text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                    <button onclick="changeQty(${idx}, 1)" class="w-6 h-6 rounded bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 font-bold text-xs">+</button>
                    <button onclick="removeItem(${idx})" class="ml-0.5 w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white font-bold text-xs">×</button>
                </div>
            </div>`;
    });
    container.innerHTML = html;
    show(paymentArea, 'block');
    show(clearBtn, 'inline-flex');
    show(discountBtn, 'inline-flex');
    updateTotals();
}

function updateTotals() {
    const sub = cartSubtotal();
    const final = cartFinalTotal();
    const profit = cartProfit();

    document.getElementById('totalDisplay').textContent = rp(final);
    document.getElementById('profitDisplay').textContent = rp(profit);

    const subtotalRow = document.getElementById('subtotalRow');
    const discountRow = document.getElementById('discountRow');
    const profitPreview = document.getElementById('profitPreview');
    const discountBtn = document.getElementById('discountBtn');

    if (discountAmount > 0) {
        show(subtotalRow);
        show(discountRow);
        document.getElementById('subtotalDisplay').textContent = rp(sub);
        document.getElementById('discountDisplay').textContent = '-' + rp(discountAmount);
        document.getElementById('discountBtnText').textContent = '-' + rp(discountAmount);
        discountBtn.classList.add('active');
    } else {
        hide(subtotalRow);
        hide(discountRow);
        document.getElementById('discountBtnText').textContent = 'Diskon';
        discountBtn.classList.remove('active');
    }

    show(profitPreview);
    updateCashDisplay();
}

function syncCart() {
    fetch('../../ajax/update_cart.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart })
    }).then(r => r.json()).catch(err => console.error('Sync error:', err));
}

function addToCart(product) {
    const idx = cart.findIndex(c => c.id === product.id);
    let flashIdx = -1;
    if (idx >= 0) {
        if (cart[idx].quantity >= cart[idx].max_stock) return showToast('Stok tidak mencukupi', 'error');
        cart[idx].quantity++; flashIdx = idx;
    } else {
        if (product.stock <= 0) return showToast('Stok habis', 'error');
        cart.push({ id: product.id, name: product.name, price: product.price, quantity: 1, max_stock: product.stock });
        flashIdx = cart.length - 1;
    }
    renderCart(flashIdx);
    syncCart();
    showToast(product.name + ' ditambahkan', 'success');
}

function changeQty(idx, delta) {
    const item = cart[idx]; if (!item) return;
    const newQty = item.quantity + delta;
    if (newQty < 1) return removeItem(idx);
    if (newQty > item.max_stock) return showToast('Melebihi stok', 'error');
    cart[idx].quantity = newQty;
    renderCart(idx);
    syncCart();
}
function updateQty(idx, val) {
    const q = parseInt(val) || 1;
    if (q < 1) return removeItem(idx);
    if (q > cart[idx].max_stock) { showToast('Melebihi stok', 'error'); return renderCart(idx); }
    cart[idx].quantity = q;
    renderCart(idx);
    syncCart();
}
function removeItem(idx) {
    const removed = cart[idx];
    cart.splice(idx, 1);
    if (cart.length === 0) discountAmount = 0;
    renderCart();
    syncCart();
    if (removed) showToast(removed.name + ' dihapus', 'info');
}
function clearCart() {
    if (!confirm('Kosongkan keranjang?')) return;
    cart = []; cashValue = 0; discountAmount = 0;
    renderCart();
    syncCart();
    showToast('Keranjang dikosongkan', 'info');
}

function showToast(msg, type = 'success') {
    const colors = { success: 'bg-emerald-500', error: 'bg-red-500', info: 'bg-slate-600' };
    const el = document.createElement('div');
    el.className = `fixed bottom-6 right-6 z-[9999] ${colors[type]} text-white px-4 py-2.5 rounded-lg shadow-lg text-sm font-semibold`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.style.opacity = '0', 1200);
    setTimeout(() => el.remove(), 1600);
}

// ===== DISKON =====
function openDiscountModal() {
    if (!cart.length) return;
    document.getElementById('discountModal').classList.remove('hidden');
    if (discountType === 'percent') {
        switchDiscountTab('percent');
        const sub = cartSubtotal();
        document.getElementById('discountPercent').value = sub > 0 ? Math.round((discountAmount / sub) * 100) : 0;
    } else {
        switchDiscountTab('nominal');
        document.getElementById('discountNominal').value = discountAmount;
    }
    previewDiscount();
}
function closeDiscountModal() { document.getElementById('discountModal').classList.add('hidden'); }
function switchDiscountTab(tab) {
    discountType = tab;
    const tabP = document.getElementById('tabPercent');
    const tabN = document.getElementById('tabNominal');
    const modeP = document.getElementById('percentMode');
    const modeN = document.getElementById('nominalMode');

    if (tab === 'percent') {
        tabP.classList.add('bg-white', 'dark:bg-slate-700', 'shadow'); tabP.classList.remove('text-slate-500');
        tabN.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow'); tabN.classList.add('text-slate-500');
        modeP.classList.remove('hidden'); modeN.classList.add('hidden');
    } else {
        tabN.classList.add('bg-white', 'dark:bg-slate-700', 'shadow'); tabN.classList.remove('text-slate-500');
        tabP.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow'); tabP.classList.add('text-slate-500');
        modeN.classList.remove('hidden'); modeP.classList.add('hidden');
    }
    previewDiscount();
}
function quickPercent(v) { document.getElementById('discountPercent').value = v; previewDiscount(); }
function quickNominal(v) { const c = parseFloat(document.getElementById('discountNominal').value) || 0; document.getElementById('discountNominal').value = c + v; previewDiscount(); }
function previewDiscount() {
    const sub = cartSubtotal();
    let disc = discountType === 'percent'
        ? Math.round(sub * (parseFloat(document.getElementById('discountPercent').value) || 0) / 100)
        : (parseFloat(document.getElementById('discountNominal').value) || 0);
    if (disc > sub) disc = sub; if (disc < 0) disc = 0;
    document.getElementById('previewSubtotal').textContent = rp(sub);
    document.getElementById('previewDiscount').textContent = '-' + rp(disc);
    document.getElementById('previewTotal').textContent = rp(sub - disc);
}
function applyDiscount() {
    const sub = cartSubtotal();
    let disc = discountType === 'percent'
        ? Math.round(sub * (parseFloat(document.getElementById('discountPercent').value) || 0) / 100)
        : (parseFloat(document.getElementById('discountNominal').value) || 0);
    if (disc > sub) disc = sub; if (disc < 0) disc = 0;
    discountAmount = disc;
    closeDiscountModal();
    updateTotals();
    showToast(disc > 0 ? 'Diskon diterapkan: ' + rp(disc) : 'Diskon dihapus', disc > 0 ? 'success' : 'info');
}
function clearDiscount() { discountAmount = 0; updateTotals(); showToast('Diskon dihapus', 'info'); }
function clearDiscountAndClose() { discountAmount = 0; updateTotals(); closeDiscountModal(); showToast('Diskon dihapus', 'info'); }

// ===== SEARCH + BARCODE =====
const si = document.getElementById('searchInput');
const sr = document.getElementById('searchResult');
let t;
si.addEventListener('input', function() {
    clearTimeout(t);
    const q = this.value.trim();
    if (q.length < 1) { sr.classList.add('hidden'); return; }
    t = setTimeout(() => searchProducts(q), 250);
});
si.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); handleEnterScan(); }
    else if (e.key === 'Escape') { si.value = ''; sr.classList.add('hidden'); }
});
async function searchProducts(q) {
    try {
        const res = await fetch(`../../ajax/get_product.php?search=${encodeURIComponent(q)}`);
        const data = await res.json();
        sr.innerHTML = '';
        if (!data.length) sr.innerHTML = '<div class="p-3 text-slate-400 text-sm">Tidak ditemukan</div>';
        else data.forEach(p => {
            const d = document.createElement('div');
            d.className = 'p-2.5 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 flex justify-between';
            d.innerHTML = `<span class="text-sm"><b>${escapeHtml(p.name)}</b> <span class="text-xs text-slate-400">${p.barcode || ''}</span></span>
                           <span class="text-xs">Rp ${p.selling_price.toLocaleString()} | Stok ${p.stock}</span>`;
            d.onclick = () => { addToCart({ id: p.id, name: p.name, price: p.selling_price, stock: p.stock }); si.value = ''; sr.classList.add('hidden'); si.focus(); };
            sr.appendChild(d);
        });
        sr.classList.remove('hidden');
        return data;
    } catch (err) { console.error(err); return []; }
}
async function handleEnterScan() {
    const q = si.value.trim(); if (!q) return;
    si.classList.add('search-highlight');
    setTimeout(() => si.classList.remove('search-highlight'), 400);
    const data = await searchProducts(q);
    if (!data || data.length === 0) return showToast('Produk tidak ditemukan', 'error');
    let match = data.find(p => String(p.barcode) === String(q));
    if (!match && data.length === 1) match = data[0];
    if (!match) match = data.find(p => p.name.toLowerCase() === q.toLowerCase());
    if (!match) return showToast('Banyak hasil, silakan pilih', 'info');
    if (match.stock <= 0) return showToast('Stok habis', 'error');
    addToCart({ id: match.id, name: match.name, price: match.selling_price, stock: match.stock });
    si.value = ''; sr.classList.add('hidden'); si.focus();
}
document.addEventListener('click', e => { if (!si.contains(e.target) && !sr.contains(e.target)) sr.classList.add('hidden'); });

function toggleCashInput() {
    const pm = document.getElementById('paymentMethod').value;
    document.getElementById('cashInputWrap').style.display = (pm === 'cash') ? 'block' : 'none';
}

// ===== PAYMENT =====
function processPayment() {
    if (!cart.length) return alert('Keranjang kosong!');
    const cid = document.getElementById('customerId').value;
    const pm = document.getElementById('paymentMethod').value;
    const cr = pm === 'cash' ? cashValue : 0;
    const tot = cartFinalTotal();
    if (pm === 'cash' && cr < tot) return alert('Uang tunai kurang! Total: ' + rp(tot));

    fetch('../../ajax/process_transaction.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart, payment_method: pm, customer_id: cid || null, cash_received: cr, discount_amount: discountAmount })
    })
    .then(r => r.text())
    .then(text => {
        const data = JSON.parse(text);
        if (data.success) {
            // Reset semua state
            cart = [];
            cashValue = 0;
            discountAmount = 0;
            renderCart();
            renderReceipt(data);
        } else alert('Error: ' + data.message);
    })
    .catch(err => { console.error(err); alert('Kesalahan server.'); });
}

function renderReceipt(data) {
    const now = new Date();
    const dt = now.toLocaleDateString('id-ID', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
    let h = `
        <div class="r-center">
            <div class="r-bold" style="font-size:13px;">${STORE_NAME}</div>
            <div>${STORE_ADDRESS}</div>
            <div>Telp: ${STORE_PHONE}</div>
        </div>
        <div class="r-line"></div>
        <div class="r-row"><span>Tanggal</span><span>${dt}</span></div>
        <div class="r-row"><span>Invoice</span><span>${data.invoice}</span></div>
        <div class="r-row"><span>Kasir</span><span>${CASHIER_NAME}</span></div>
        ${data.customer_name ? `<div class="r-row"><span>Pelanggan</span><span>${data.customer_name}</span></div>` : ''}
        <div class="r-line"></div>`;
    data.items.forEach(i => {
        const sub = i.price * i.quantity;
        h += `<div class="r-item"><div class="r-item-name">${i.name}</div>
                <div class="r-item-detail"><span>${i.quantity} x ${i.price.toLocaleString()}</span><span>${sub.toLocaleString()}</span></div>
              </div>`;
    });
    h += `<div class="r-line"></div>`;
    if (data.discount_amount > 0) {
        h += `<div class="r-row"><span>Subtotal</span><span>Rp ${data.total_amount.toLocaleString()}</span></div>
              <div class="r-row" style="color:#d97706;"><span>Diskon</span><span>-Rp ${data.discount_amount.toLocaleString()}</span></div>
              <div class="r-line"></div>`;
    }
    h += `<div class="r-row r-bold"><span>TOTAL</span><span>Rp ${data.final_amount.toLocaleString()}</span></div>`;
    if (data.payment_method === 'cash') {
        h += `<div class="r-row"><span>Tunai</span><span>Rp ${data.cash_received.toLocaleString()}</span></div>
              <div class="r-row"><span>Kembali</span><span>Rp ${data.change.toLocaleString()}</span></div>`;
    } else {
        h += `<div class="r-row"><span>Metode</span><span>${data.payment_method.toUpperCase()}</span></div>`;
    }
    if (data.points_earned && data.points_earned > 0) {
        h += `<div class="r-line"></div>
              <div class="r-row r-bold" style="color:#d97706;"><span>⭐ POIN</span><span>+${data.points_earned}</span></div>`;
    }
    h += `<div class="r-line-double"></div>
        <div class="r-center">Terima Kasih</div>
        <div class="r-center" style="font-size:9px;">Barang yang sudah dibeli tidak dapat ditukar</div>`;
    document.getElementById('receiptContent').innerHTML = h;
    document.getElementById('printArea').innerHTML = h;
    document.getElementById('receiptModal').classList.remove('hidden');

    // ===== AUTO REFRESH =====
    // Kalau user lupa klik Tutup dalam 15 detik, refresh otomatis
    if (receiptAutoRefreshTimer) clearTimeout(receiptAutoRefreshTimer);
    receiptAutoRefreshTimer = setTimeout(() => {
        window.location.reload();
    }, 15000); // 15 detik
}

let receiptAutoRefreshTimer = null;

function closeReceipt() {
    document.getElementById('receiptModal').classList.add('hidden');
    // Clear timer & refresh halaman untuk reset total
    if (receiptAutoRefreshTimer) clearTimeout(receiptAutoRefreshTimer);
    window.location.reload();
}

document.getElementById('receiptModal').addEventListener('click', function(e) { if (e.target === this) closeReceipt(); });
document.getElementById('discountModal').addEventListener('click', function(e) { if (e.target === this) closeDiscountModal(); });

// ===== PENCARIAN PELANGGAN =====
const customerInput = document.getElementById('customerInput');
const customerResult = document.getElementById('customerResult');
const customerId = document.getElementById('customerId');
const customerClearBtn = document.getElementById('customerClearBtn');
let customerTimer;

customerInput.addEventListener('input', function() {
    clearTimeout(customerTimer);
    const q = this.value.trim();
    customerClearBtn.classList.toggle('hidden', q.length === 0);

    // Jika di bawah 3 karakter, sembunyikan hasil
    if (q.length < 3) {
        customerResult.classList.add('hidden');
        // Reset pilihan kalau input berubah setelah memilih
        if (customerId.value !== '') {
            customerId.value = '';
        }
        return;
    }

    // Debounce 250ms
    customerTimer = setTimeout(() => searchCustomers(q), 250);
});

customerInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        customerInput.value = '';
        customerResult.classList.add('hidden');
        customerId.value = '';
        customerClearBtn.classList.add('hidden');
    }
});

customerInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 3) {
        searchCustomers(this.value.trim());
    }
});

function searchCustomers(q) {
    const query = q.toLowerCase();
    const qDigits = q.replace(/\D/g, '');
    const hasDigits = qDigits.length >= 3;

    const results = ALL_CUSTOMERS.filter(c => {
        const nameMatch = c.name.toLowerCase().includes(query);
        const phoneNorm = (c.phone || '').replace(/\D/g, '');
        const phoneMatch = hasDigits && phoneNorm.includes(qDigits);
        return nameMatch || phoneMatch;
    }).slice(0, 8);

    renderCustomerResults(results, q);
}

function renderCustomerResults(items, q) {
    customerResult.innerHTML = '';

    if (items.length === 0) {
        customerResult.innerHTML = '<div class="p-2.5 text-xs text-slate-400 text-center">Tidak ada pelanggan ditemukan</div>';
        customerResult.classList.remove('hidden');
        return;
    }

    // Baris "Umum (Non-Member)" untuk reset
    const umum = document.createElement('div');
    umum.className = 'p-2.5 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 text-xs text-slate-500';
    umum.innerHTML = '<i class="fas fa-user mr-1"></i>Umum (Non-Member)';
    umum.onclick = () => selectCustomer(null);
    customerResult.appendChild(umum);

    items.forEach(c => {
        const div = document.createElement('div');
        div.className = 'p-2.5 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-primary/5 dark:hover:bg-primary/10 flex justify-between items-center';
        div.innerHTML = `
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold truncate">${escapeHtml(c.name)}</div>
                <div class="text-[10px] text-slate-500">${escapeHtml(c.phone || '-')}</div>
            </div>
            <div class="text-[10px] text-amber-600 font-bold ml-2 flex-shrink-0">
                ⭐ ${c.loyalty_points || 0}
            </div>
        `;
        div.onclick = () => selectCustomer(c);
        customerResult.appendChild(div);
    });

    customerResult.classList.remove('hidden');
}

function selectCustomer(c) {
    if (!c) {
        // Umum (Non-Member)
        customerId.value = '';
        customerInput.value = '';
        customerClearBtn.classList.add('hidden');
    } else {
        customerId.value = c.id;
        customerInput.value = c.name;
        customerClearBtn.classList.remove('hidden');
    }
    customerResult.classList.add('hidden');
}

function clearCustomer() {
    customerId.value = '';
    customerInput.value = '';
    customerClearBtn.classList.add('hidden');
    customerResult.classList.add('hidden');
    customerInput.focus();
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', e => {
    if (!customerInput.contains(e.target) && !customerResult.contains(e.target)) {
        customerResult.classList.add('hidden');
    }
});

// ===== INIT =====
renderCart();
renderProducts();
toggleCashInput();
updateCashDisplay();
si.focus();
</script>

<?php include '../../includes/footer.php'; ?>
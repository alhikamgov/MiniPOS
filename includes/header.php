<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$full_name = $_SESSION['full_name'];
$role_name = $_SESSION['role_name'];

// Menu berdasarkan role
$menus = ($role_name == 'Admin') ? [
    ['label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'link' => 'dashboard.php'],
    ['label' => 'Riwayat Transaksi', 'icon' => 'fa-file-invoice-dollar', 'link' => 'transactions.php'],
    ['label' => 'Manajemen Produk', 'icon' => 'fa-box', 'link' => 'products.php'],
    ['label' => 'Manajemen Pengguna', 'icon' => 'fa-users-cog', 'link' => 'users.php'],
    ['label' => 'Manajemen Pelanggan', 'icon' => 'fa-user-friends', 'link' => 'customers.php'],
    ['label' => 'Laporan Penjualan', 'icon' => 'fa-file-alt', 'link' => 'reports.php'],
    ['label' => 'Pengaturan', 'icon' => 'fa-cog', 'link' => 'settings.php'],
] : [
    ['label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'link' => 'dashboard.php'],
    ['label' => 'Kasir / Transaksi', 'icon' => 'fa-shopping-cart', 'link' => 'pos.php'],
    ['label' => 'Riwayat Transaksi', 'icon' => 'fa-receipt', 'link' => 'history_transactions.php'],
];

$current_page = basename($_SERVER['PHP_SELF']);
$is_dark = ($theme === 'dark');
?>
<!DOCTYPE html>
<html lang="id" class="<?= $is_dark ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        sidebar: '#0f172a',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #60a5fa; border-radius: 10px; }
        .page-content { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        /* Struk print style */
        @media print {
            body * { visibility: hidden; }
            #receiptContent, #receiptContent * { visibility: visible; }
            #receiptContent { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen">

<!-- Overlay untuk mobile -->
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-slate-900 text-slate-300 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col">
    <div class="px-6 py-6 border-b border-slate-800">
        <div class="text-2xl font-extrabold text-white tracking-wide"><?= htmlspecialchars($app_name) ?></div>
        <div class="text-xs text-slate-500 mt-1">Point of Sale</div>
    </div>
    
    <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3">
        <?php foreach ($menus as $menu): ?>
            <a href="<?= $menu['link'] ?>" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition
                      <?= $current_page == $menu['link'] 
                          ? 'bg-primary text-white font-semibold shadow-lg shadow-primary/30' 
                          : 'hover:bg-slate-800 text-slate-400 hover:text-white' ?>">
                <i class="fas <?= $menu['icon'] ?> w-5"></i>
                <span><?= $menu['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="border-t border-slate-800 p-4">
        <div class="text-center mb-3">
            <div class="font-semibold text-white text-sm"><?= htmlspecialchars($full_name) ?></div>
            <span class="inline-block mt-1 px-3 py-0.5 text-xs rounded-full bg-primary text-white"><?= $role_name ?></span>
        </div>
        <a href="../../logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- Main -->
<div class="lg:ml-64 min-h-screen">
    <!-- Topbar -->
    <header class="sticky top-0 z-30 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 md:px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <button id="hamburgerBtn" class="lg:hidden text-2xl text-slate-700 dark:text-slate-200 hover:text-primary">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-lg md:text-xl font-bold">
                <i class="fas fa-store-alt text-primary mr-2"></i>
                <?= htmlspecialchars($app_name) ?>
            </h1>
        </div>
        <div class="text-xs md:text-sm text-slate-500 dark:text-slate-400">
            <i class="fas fa-clock mr-1"></i> <?= date('d M Y, H:i') ?>
        </div>
    </header>

    <!-- Content -->
    <main class="p-4 md:p-6 page-content">
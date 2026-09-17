<?php
// pages/unauthorized.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - <?= htmlspecialchars($app_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: '#2563eb' } } } };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 dark:bg-slate-950 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-10 max-w-md w-full text-center">
        <div class="w-20 h-20 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-5">
            <i class="fas fa-ban text-4xl text-red-500"></i>
        </div>
        <h1 class="text-2xl font-extrabold mb-2 text-slate-800 dark:text-slate-100">Akses Ditolak</h1>
        <p class="text-slate-500 dark:text-slate-400 mb-6 text-sm">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
        </p>
        <a href="../index.php"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg transition">
            <i class="fas fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>
</body>
</html>
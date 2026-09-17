<?php
// index.php
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: index.php');
    exit;
}

session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: pages/' . ($_SESSION['role_name'] === 'Admin' ? 'admin' : 'kasir') . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        try {
            // Ambil user by username saja, verifikasi password di PHP
            $stmt = $db->prepare("SELECT u.*, r.name as role_name 
                                  FROM users u 
                                  JOIN roles r ON u.role_id = r.id 
                                  WHERE u.username = :u AND u.is_active = 1");
            $stmt->execute(['u' => $username]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Auto-upgrade hash MD5 lama ke Bcrypt
                if (strlen($user['password']) === 32 && ctype_xdigit($user['password'])) {
                    $new_hash = hashPassword($password);
                    $db->prepare("UPDATE users SET password = :p WHERE id = :id")
                       ->execute(['p' => $new_hash, 'id' => $user['id']]);
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];

                header('Location: pages/' . ($user['role_name'] === 'Admin' ? 'admin' : 'kasir') . '/dashboard.php');
                exit;
            }
            $error = 'Username atau password salah!';
        } catch (PDOException $e) {
            $error = 'Error sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($app_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: '#2563eb' } } } };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        .login-card { animation: fadeInUp 0.5s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap .icon-left { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; transition: color 0.2s; }
        .input-icon-wrap input:focus ~ .icon-left { color: #2563eb; }
        .input-icon-wrap .toggle-pass { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; background: none; border: none; padding: 4px; transition: color 0.2s; }
        .input-icon-wrap .toggle-pass:hover { color: #2563eb; }
        .input-icon-wrap input { padding-left: 42px !important; padding-right: 42px !important; }
        input::placeholder { color: #94a3b8; font-size: 14px; }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 min-h-screen flex items-center justify-center p-4">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-primary/10 dark:bg-primary/5 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-emerald-500/10 dark:bg-emerald-500/5 blur-3xl"></div>
    </div>

    <div class="login-card relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-8">

        <div class="text-center mb-7">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary/10 dark:bg-primary/20 mb-4">
                <i class="fas fa-store text-primary text-3xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($app_name) ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><i class="fas fa-lock text-xs mr-1"></i>Sistem Point of Sale</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3.5 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-l-4 border-red-500 text-sm flex items-start gap-2">
                <i class="fas fa-exclamation-circle mt-0.5"></i><span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4" autocomplete="off">
            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-700 dark:text-slate-300">Username</label>
                <div class="input-icon-wrap">
                    <input type="text" name="username" id="usernameInput" required autofocus
                           placeholder="Masukkan username Anda..."
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           class="w-full py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                    <i class="fas fa-user icon-left"></i>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-700 dark:text-slate-300">Password</label>
                <div class="input-icon-wrap">
                    <input type="password" name="password" id="passwordInput" required
                           placeholder="Masukkan password Anda..."
                           class="w-full py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                    <i class="fas fa-lock icon-left"></i>
                    <button type="button" class="toggle-pass" onclick="togglePassword()" tabindex="-1">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-3.5 bg-primary hover:bg-blue-700 active:scale-[0.98] text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i><span>Masuk</span>
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500 mt-8">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($app_name) ?> &middot; v1.0
        </p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
            else { input.type = 'password'; icon.className = 'fas fa-eye'; }
        }
    </script>
</body>
</html>
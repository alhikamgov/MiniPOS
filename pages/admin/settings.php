<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/../../config/database.php';
    try {
        $db->prepare("UPDATE settings SET setting_value = :v WHERE setting_key = 'app_name'")->execute(['v' => $_POST['app_name']]);
        $db->prepare("UPDATE settings SET setting_value = :v WHERE setting_key = 'theme'")->execute(['v' => $_POST['theme']]);
        $db->prepare("UPDATE settings SET setting_value = :v WHERE setting_key = 'store_address'")->execute(['v' => $_POST['store_address']]);
        $db->prepare("UPDATE settings SET setting_value = :v WHERE setting_key = 'store_phone'")->execute(['v' => $_POST['store_phone']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengaturan berhasil disimpan!'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal: ' . $e->getMessage()];
    }
    header('Location: settings.php');
    exit;
}

include '../../includes/header.php';
requireRole('Admin');

$app_name = getSetting('app_name');
$theme = getSetting('theme');
$store_address = getSetting('store_address');
$store_phone = getSetting('store_phone');
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-cog text-primary mr-2"></i>Pengaturan</h1>

    <form method="POST" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 space-y-5">
        <div>
            <label class="block font-semibold mb-2 text-sm">Nama Aplikasi</label>
            <input type="text" name="app_name" value="<?= htmlspecialchars($app_name) ?>" required
                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>

        <div>
            <label class="block font-semibold mb-2 text-sm">Alamat Toko</label>
            <textarea name="store_address" rows="2" required
                      class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= htmlspecialchars($store_address) ?></textarea>
        </div>

        <div>
            <label class="block font-semibold mb-2 text-sm">Telepon Toko</label>
            <input type="text" name="store_phone" value="<?= htmlspecialchars($store_phone) ?>" required
                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>

        <div>
            <label class="block font-semibold mb-2 text-sm">Tema</label>
            <select name="theme"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Terang (Light)</option>
                <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Gelap (Dark)</option>
            </select>
        </div>

        <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition">
            <i class="fas fa-save mr-2"></i>Simpan Pengaturan
        </button>
    </form>
</div>

<?php if ($flash): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => notify('<?= $flash['type'] ?>', '<?= addslashes($flash['msg']) ?>'));
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
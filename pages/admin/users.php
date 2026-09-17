<?php
// pages/admin/users.php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../../config/database.php';
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $flash_message = '';
    $flash_type = '';

    try {
        if ($action == 'add') {
            $role_id = (int)($_POST['role_id'] ?? 2);
            if (!in_array($role_id, [1, 2])) $role_id = 2;

            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role_id, is_active) VALUES (:u, :p, :f, :r, 1)");
            $stmt->execute([
                'u' => trim($_POST['username']),
                'p' => hashPassword($_POST['password']),
                'f' => trim($_POST['full_name']),
                'r' => $role_id
            ]);
            $flash_message = 'Akun berhasil ditambahkan!';

        } elseif ($action == 'edit') {
            $role_id = (int)($_POST['role_id'] ?? 2);
            if (!in_array($role_id, [1, 2])) $role_id = 2;

            // Cegah admin mengubah role dirinya sendiri
            if ($id === $current_user_id) {
                $role_id = $_SESSION['role_id'];
            }

            $new_password = trim($_POST['password'] ?? '');
            if (!empty($new_password)) {
                $stmt = $db->prepare("UPDATE users SET username=:u, full_name=:f, role_id=:r, password=:p WHERE id=:id");
                $stmt->execute([
                    'u' => trim($_POST['username']),
                    'f' => trim($_POST['full_name']),
                    'r' => $role_id,
                    'p' => hashPassword($new_password),
                    'id' => $id
                ]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=:u, full_name=:f, role_id=:r WHERE id=:id");
                $stmt->execute([
                    'u' => trim($_POST['username']),
                    'f' => trim($_POST['full_name']),
                    'r' => $role_id,
                    'id' => $id
                ]);
            }

            // Update session jika yang diedit adalah diri sendiri
            if ($id === $current_user_id) {
                $_SESSION['full_name'] = trim($_POST['full_name']);
            }

            $flash_message = 'Akun berhasil diupdate!';

        } elseif ($action == 'toggle') {
            if ($id === $current_user_id) {
                throw new Exception('Anda tidak dapat menonaktifkan akun Anda sendiri!');
            }

            $status = (int)$_POST['status'];

            // Cek minimal 1 admin aktif
            if ($status === 0) {
                $check = $db->prepare("SELECT role_id FROM users WHERE id=:id");
                $check->execute(['id' => $id]);
                $target_role = $check->fetchColumn();
                if ($target_role == 1) {
                    $admin_count = $db->query("SELECT COUNT(*) FROM users WHERE role_id=1 AND is_active=1")->fetchColumn();
                    if ($admin_count <= 1) {
                        throw new Exception('Minimal harus ada 1 Admin aktif!');
                    }
                }
            }

            $db->prepare("UPDATE users SET is_active = :s WHERE id = :id")
               ->execute(['s' => $status, 'id' => $id]);
            $flash_message = 'Status akun berhasil diubah!';
        }

        $flash_type = 'success';
    } catch (Exception $e) {
        $flash_message = 'Error: ' . $e->getMessage();
        $flash_type = 'error';
    }

    $_SESSION['flash'] = ['type' => $flash_type, 'msg' => $flash_message];
    header('Location: users.php');
    exit;
}

include '../../includes/header.php';
requireRole('Admin');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$current_user_id = $_SESSION['user_id'];

// Ambil semua user (Admin + Kasir) dengan nama role
$users = $db->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.role_id ASC, u.id ASC
")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6"><i class="fas fa-users-cog text-primary mr-2"></i>Manajemen Pengguna</h1>

<button onclick="openUserModal('add')" class="px-4 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition mb-5">
    <i class="fas fa-user-plus mr-2"></i>Tambah Pengguna
</button>

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800 text-left text-slate-500">
                <tr>
                    <th class="p-3">Username</th>
                    <th class="p-3">Nama Lengkap</th>
                    <th class="p-3">Role</th>
                    <th class="p-3 text-center">Status</th>
                    <th class="p-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="p-8 text-center text-slate-400">Belum ada pengguna.</td></tr>
                <?php else: foreach ($users as $u): ?>
                    <?php
                    $is_self = ($u['id'] == $current_user_id);
                    $is_admin = ($u['role_id'] == 1);
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 <?= $is_self ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' ?>">
                        <td class="p-3 font-semibold">
                            <?= htmlspecialchars($u['username']) ?>
                            <?php if ($is_self): ?>
                                <span class="ml-1 text-[10px] bg-blue-500 text-white px-1.5 py-0.5 rounded-full">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td class="p-3">
                            <?php if ($is_admin): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 text-xs font-semibold">
                                    <i class="fas fa-user-shield"></i> Admin
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold">
                                    <i class="fas fa-user-tie"></i> Kasir
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center">
                            <?php if ($u['is_active']): ?>
                                <span class="inline-block px-3 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-semibold">Aktif</span>
                            <?php else: ?>
                                <span class="inline-block px-3 py-0.5 text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-semibold">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <button onclick='openUserModal("edit", <?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="px-3 py-1.5 bg-primary text-white rounded text-xs hover:bg-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!$is_self): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" class="px-3 py-1.5 <?= $u['is_active'] ? 'bg-slate-500 hover:bg-slate-600' : 'bg-emerald-500 hover:bg-emerald-600' ?> text-white rounded text-xs" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="fas fa-<?= $u['is_active'] ? 'user-slash' : 'user-check' ?>"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="px-3 py-1.5 bg-slate-300 dark:bg-slate-700 text-slate-500 rounded text-xs cursor-not-allowed" title="Tidak dapat menonaktifkan akun sendiri">
                                    <i class="fas fa-lock"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit -->
<div id="userModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <h3 id="userModalTitle" class="text-xl font-bold mb-5"><i class="fas fa-user-plus text-primary mr-2"></i>Tambah Pengguna</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="userAction" value="add">
            <input type="hidden" name="id" id="userId" value="0">

            <div>
                <label class="block text-sm font-semibold mb-1.5">Role *</label>
                <select name="role_id" id="userRole" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
                    <option value="2">Kasir</option>
                    <option value="1">Admin</option>
                </select>
                <p id="roleHint" class="text-xs text-slate-500 mt-1"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">Username *</label>
                <input type="text" name="username" id="userUsername" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">Nama Lengkap *</label>
                <input type="text" name="full_name" id="userFullName" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">
                    Password 
                    <span id="passwordLabel" class="font-normal text-xs text-slate-500">(kosongkan jika tidak diubah)</span>
                </label>
                <input type="password" name="password" id="userPassword" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="document.getElementById('userModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-400 hover:bg-slate-500 text-white font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white font-semibold rounded-lg"><i class="fas fa-save mr-2"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?= $current_user_id ?>;

function openUserModal(action, data = null) {
    const title = document.getElementById('userModalTitle');
    const pwdLabel = document.getElementById('passwordLabel');
    const pwdInput = document.getElementById('userPassword');
    const roleSelect = document.getElementById('userRole');
    const roleHint = document.getElementById('roleHint');

    if (action === 'add') {
        title.innerHTML = '<i class="fas fa-user-plus text-primary mr-2"></i>Tambah Pengguna';
        document.getElementById('userAction').value = 'add';
        document.getElementById('userId').value = 0;
        document.getElementById('userUsername').value = '';
        document.getElementById('userFullName').value = '';
        roleSelect.value = '2';
        roleSelect.disabled = false;
        pwdInput.value = '';
        pwdInput.required = true;
        pwdLabel.textContent = '(wajib diisi)';
        roleHint.textContent = '';
    } else if (data) {
        const isSelf = (data.id == CURRENT_USER_ID);
        title.innerHTML = '<i class="fas fa-edit text-primary mr-2"></i>Edit Pengguna';
        document.getElementById('userAction').value = 'edit';
        document.getElementById('userId').value = data.id;
        document.getElementById('userUsername').value = data.username;
        document.getElementById('userFullName').value = data.full_name;
        roleSelect.value = data.role_id;
        roleSelect.disabled = isSelf;

        pwdInput.value = '';
        pwdInput.required = false;
        pwdLabel.textContent = '(kosongkan jika tidak diubah)';

        roleHint.textContent = isSelf 
            ? 'Anda tidak dapat mengubah role akun Anda sendiri.' 
            : '';
    }
    document.getElementById('userModal').classList.remove('hidden');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});

// Aktifkan kembali select saat form disubmit
document.querySelector('#userModal form').addEventListener('submit', function() {
    document.getElementById('userRole').disabled = false;
});

<?php if ($flash): ?>
document.addEventListener('DOMContentLoaded', () => notify('<?= $flash['type'] ?>', '<?= addslashes($flash['msg']) ?>'));
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>
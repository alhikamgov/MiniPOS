<?php
// includes/auth.php
if (session_status() == PHP_SESSION_NONE) session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
    $prefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
    header('Location: ' . $prefix . 'index.php');
    exit;
}

// Cek role jika diperlukan
function requireRole($role) {
    if ($_SESSION['role_name'] !== $role) {
        $prefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
        header('Location: ' . $prefix . 'pages/unauthorized.php');
        exit;
    }
}
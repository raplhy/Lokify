<?php
require_once __DIR__ . '/functions.php';

// Cek role sebelum session dihancurkan
$role = $_SESSION['role'] ?? 'siswa';

session_unset();
session_destroy();

// Arahkan ke halaman login yang sesuai
if ($role === 'admin') {
    header("Location: admin_login.php");
} else {
    header("Location: login.php");
}
exit();
?>
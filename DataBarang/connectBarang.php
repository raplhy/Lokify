<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "db_kel1";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (!function_exists('perbaruiStatusLoker')) {
    function perbaruiStatusLoker($koneksi, $id_loker) {
        $id_loker = (int)$id_loker;
        if ($id_loker <= 0 || !$koneksi) {
            return;
        }

        $q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_loker = $id_loker AND status = 'Tersimpan'");
        if (!$q) {
            return;
        }

        $data = mysqli_fetch_assoc($q);
        $status = ((int)($data['total'] ?? 0) > 0) ? 'Terisi' : 'Kosong';

        mysqli_query($koneksi, "UPDATE loker SET status = '$status' WHERE id_loker = $id_loker");
    }
}

if (!function_exists('dapatkanIdUserAktif')) {
    function dapatkanIdUserAktif($koneksi, $fallback_role = 'admin') {
        $id = (int)($_SESSION['id_user'] ?? 0);
        if ($id > 0 && isset($koneksi) && $koneksi) {
            $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_user = $id");
            if ($cek && mysqli_num_rows($cek) > 0) {
                return $id;
            }
        }
        if (isset($koneksi) && $koneksi) {
            $role_clean = mysqli_real_escape_string($koneksi, $fallback_role);
            $q = mysqli_query($koneksi, "SELECT id_user FROM users WHERE role = '$role_clean' LIMIT 1");
            if ($q && $r = mysqli_fetch_assoc($q)) {
                return (int)$r['id_user'];
            }
            $q2 = mysqli_query($koneksi, "SELECT id_user FROM users LIMIT 1");
            if ($q2 && $r2 = mysqli_fetch_assoc($q2)) {
                return (int)$r2['id_user'];
            }
        }
        return 1;
    }
}
?>
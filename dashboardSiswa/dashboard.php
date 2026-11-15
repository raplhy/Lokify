<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$gedung = $_GET['gedung'] ?? 'Gedung A';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_profil'])) {
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $no_hp = mysqli_real_escape_string($koneksi, trim($_POST['no_hp'] ?? ''));
    $password_baru = $_POST['password_baru'] ?? '';
    
    $update_foto = ""; 

    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
        $folder_upload = __DIR__ . "/uploads/";
        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0777, true);
        }

        $nama_file = time() . '_' . basename($_FILES["foto_profil"]["name"]);
        $target_file = $folder_upload . $nama_file;
        $ekstensi = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $update_foto = ", foto = '$nama_file'"; 
            }
        }
    }

    $update_pass = "";
    if (!empty($password_baru)) {
        $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update_pass = ", password = '$pass_hash'";
    }

    $query = "UPDATE users SET email = '$email', no_hp = '$no_hp' $update_pass $update_foto WHERE id_user = '$id_user'";
    mysqli_query($koneksi, $query);

    header("Location: indexBarang.php?gedung=" . urlencode($gedung));
    exit();
}

header("Location: indexBarang.php?gedung=" . urlencode($gedung));
exit();

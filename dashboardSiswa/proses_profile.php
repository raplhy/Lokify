<?php
session_start();
require_once "../login/koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $id_user = $_SESSION['id_user'];
    
    // Ambil data lama
    $q_lama = mysqli_query($koneksi, "SELECT email, no_hp, foto FROM users WHERE id_user = '$id_user'");
    $data_lama = mysqli_fetch_assoc($q_lama);

    $email         = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $no_hp         = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    
    $update_foto   = ""; 
    $foto_diupload = false;

    // Proses Upload Foto
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
        $folder_upload = "uploads/";
        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0777, true);
        }

        $nama_file = time() . '_' . basename($_FILES["foto_profil"]["name"]);
        $target_file = $folder_upload . $nama_file;
        $ekstensi = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $update_foto = ", foto = '$nama_file'"; 
                $foto_diupload = true;
                $_SESSION['foto'] = $nama_file; 
            }
        }
    }

    // Susun Pesan Notifikasi Dinamis
    $pesan_sukses = [];
    
    if (!empty($email) && $email !== $data_lama['email']) {
        $pesan_sukses[] = "Email berhasil ditambahkan/diperbarui";
    }
    if (!empty($no_hp) && $no_hp !== $data_lama['no_hp']) {
        $pesan_sukses[] = "No. WhatsApp berhasil ditambahkan/diperbarui";
    }
    if (!empty($password_baru)) {
        $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update_pass = ", password = '$pass_hash'";
        $pesan_sukses[] = "Password berhasil diubah";
    } else {
        $update_pass = "";
    }
    if ($foto_diupload) {
        $pesan_sukses[] = "Foto profil berhasil diperbarui";
    }
    if (empty($pesan_sukses)) {
        $pesan_sukses[] = "Profil berhasil diperbarui";
    }

    $pesan_final = implode(", ", $pesan_sukses);
    $query = "UPDATE users SET email = '$email', no_hp = '$no_hp' $update_pass $update_foto WHERE id_user = '$id_user'";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash_success'] = $pesan_final;
    } else {
        $_SESSION['flash_error'] = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
    }

    // KEMBALI KE HALAMAN SEBELUMNYA (Entah itu dashboard atau indexBarang)
    $halaman_sebelumnya = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header("Location: " . $halaman_sebelumnya);
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}
?>
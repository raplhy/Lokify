<?php
require_once "connectBarang.php";

if (($_SESSION['role'] ?? '') === 'siswa') {
    header("Location: indexBarang.php?pesan=akses_ditolak");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $query = mysqli_query($koneksi, "SELECT foto_barang, id_loker FROM barang WHERE id_barang = '$id'");
    $barang = mysqli_fetch_assoc($query);

    if ($barang) {
        $id_loker = (int)$barang['id_loker'];
        $foto = $barang['foto_barang'];

        mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang = '$id'");

        if ($foto) {
            $foto_sql = mysqli_real_escape_string($koneksi, $foto);
            $cekFoto = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE foto_barang = '$foto_sql'");
            if (mysqli_fetch_assoc($cekFoto)['total'] == 0 && file_exists("uploads/" . $foto)) {
                unlink("uploads/" . $foto);
            }
        }

        $id_admin = (int)($_SESSION['id_user'] ?? 30);
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ('$id_admin', 'Hapus', NULL, NOW())");

        if ($id_loker > 0) {
            $cekLoker = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang
                WHERE id_loker = '$id_loker' AND status = 'Tersimpan'");
            $dataLoker = mysqli_fetch_assoc($cekLoker);

            if ($dataLoker['total'] == 0) {
                mysqli_query($koneksi, "UPDATE loker SET status = 'Kosong' WHERE id_loker = '$id_loker'");
            }
        }
    }
}

header("Location: indexBarang.php?pesan=sukses_hapus");
exit();
?>

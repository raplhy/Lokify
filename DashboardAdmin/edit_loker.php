<?php
session_start();
include "koneksi.php";

if (isset($_POST['edit'])) {

    // Ambil ID dari form
    $id_loker = (int) $_POST['id_loker'];

    $nomor_loker = mysqli_real_escape_string(
        $koneksi,
        $_POST['nomor_loker']
    );

    $lokasi = mysqli_real_escape_string(
        $koneksi,
        $_POST['id_gedung']
    );

    // Validasi gedung (id gedung harus ada di tabel gedung)
    $lokasi = (int) $_POST['id_gedung'];

    $cek_gedung = mysqli_query(
        $koneksi,
        "SELECT id_gedung FROM gedung
         WHERE id_gedung = $lokasi"
    );

    if (mysqli_num_rows($cek_gedung) == 0) {

        header("Location: loker.php");
        exit();

    }

    // Cek apakah nomor loker sudah digunakan
    $cek = mysqli_query(
        $koneksi,
        "SELECT id_loker FROM loker
         WHERE nomor_loker='$nomor_loker'
         AND id_loker != $id_loker"
    );

    if (mysqli_num_rows($cek) > 0) {

        header("Location: loker.php?pesan=duplikat");
        exit();

    }

    // Update nomor dan lokasi saja
    // Status tidak diubah oleh admin
    $update = mysqli_query(
        $koneksi,
        "UPDATE loker SET
            nomor_loker='$nomor_loker',
            id_gedung='$lokasi'
         WHERE id_loker=$id_loker"
    );

    if ($update) {

        // Kembali ke dashboard
        header("Location: dataloker.php?pesan=edit");
        exit();

    } else {

        echo "Gagal mengubah data: " . mysqli_error($koneksi);

    }

} else {

    // Jika file dibuka langsung
    header("Location: loker.php");
    exit();

}
?>
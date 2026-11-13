<?php

session_start();
include "koneksi.php";

if (isset($_POST['simpan'])) {

    $nomor_loker = mysqli_real_escape_string(
        $koneksi,
        $_POST['nomor_loker']
    );

    $lokasi = mysqli_real_escape_string(
        $koneksi,
        $_POST['id_gedung']
    );

    // Validasi gedung (id gedung harus ada di tabel gedung)
    $cek_gedung = mysqli_query(
        $koneksi,
        "SELECT id_gedung FROM gedung
         WHERE id_gedung = '$lokasi'"
    );

    if (mysqli_num_rows($cek_gedung) == 0) {

        header("Location: dataloker.php");
        exit();

    }


    // CEK NOMOR LOKER
    $cek = mysqli_query(
        $koneksi,
        "SELECT * FROM loker
         WHERE nomor_loker='$nomor_loker'"
    );


    if (mysqli_num_rows($cek) > 0) {

        header("Location: dataloker.php?pesan=duplikat");
        exit();

    }


    // LOKER BARU SELALU KOSONG
    $status = "Kosong";


    // SIMPAN DATA
    $simpan = mysqli_query(
        $koneksi,
        "INSERT INTO loker
        (nomor_loker, id_gedung, status)
        VALUES
        ('$nomor_loker', '$lokasi', '$status')"
    );


    if ($simpan) {

        header("Location: dataloker.php?pesan=tambah");
        exit();

    } else {

        echo "Gagal menyimpan data: "
            . mysqli_error($koneksi);

    }

} else {

    // Jika tambah_loker.php dibuka langsung
    header("Location: dataloker.php");
    exit();

}

?>
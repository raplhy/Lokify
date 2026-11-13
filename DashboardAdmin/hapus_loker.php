<?php
session_start();
include "koneksi.php";
$id = $_GET['id_loker'];

//cek apakah ada data
// $cek = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id='$id'");
// $data = mysqli_fetch_assoc($cek);
// if(!$data){
//     header("location: siswa.php?p=Data tidak ditemukan");
//     exit();
// }
//proses hapus
$hapus = mysqli_query($koneksi, "DELETE FROM loker WHERE id_loker='$id'");
if ($hapus) {
    header("location: dataloker.php?pesan=hapus");
    exit();
} else {
    header("location: dataloker.php?pesan=Gagal Loker data!");
    exit();
}
?>
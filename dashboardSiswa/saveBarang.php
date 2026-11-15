<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login/login.php");
    exit();
}

$gedung_asal = $_POST['gedung_asal'] ?? 'Gedung A';

function kembaliError($pesan, $gedung = 'Gedung A') {
    header("Location: indexBarang.php?gedung=" . urlencode($gedung) . "&pesan=error&teks=" . urlencode($pesan) . "&open=modal");
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: indexBarang.php?gedung=" . urlencode($gedung_asal));
    exit();
}

$id_siswa = (int)$_SESSION['id_user'];
$id_loker = (int)($_POST['id_loker'] ?? 0);

if ($id_siswa <= 0 || $id_loker <= 0) {
    kembaliError("Loker tujuan wajib dipilih.", $gedung_asal);
}

$cek_loker = mysqli_query($koneksi, "
    SELECT l.id_loker, l.status, g.nama_gedung 
    FROM loker l 
    JOIN gedung g ON l.id_gedung = g.id_gedung 
    WHERE l.id_loker = $id_loker
");
if (!$cek_loker || mysqli_num_rows($cek_loker) === 0) {
    kembaliError("Data loker tidak valid.", $gedung_asal);
}

$data_loker = mysqli_fetch_assoc($cek_loker);
if ($data_loker['status'] === 'Terisi') {
    kembaliError("Loker yang dipilih baru saja terisi. Silakan pilih loker kosong lainnya.", $gedung_asal);
}

$pilihan_barang = $_POST['pilihan_barang'] ?? [];
$custom_barang = $_POST['nama_barang_custom'] ?? [];
$daftar_barang = [];

foreach ($pilihan_barang as $i => $pilihan) {
    $pilihan = trim($pilihan);
    $custom = trim($custom_barang[$i] ?? '');

    if ($pilihan === 'Lainnya') {
        if ($custom === '') {
            kembaliError("Nama barang wajib diisi jika memilih opsi 'Lainnya'.", $gedung_asal);
        }
        $daftar_barang[] = $custom;
    } elseif ($pilihan !== '') {
        $daftar_barang[] = $pilihan;
    }
}

if (count($daftar_barang) < 2) {
    kembaliError("Minimal harus memasukkan 2 barang bawaan.", $gedung_asal);
}

$barang_tercatat = [];
foreach ($daftar_barang as $nama) {
    if (strlen($nama) < 3 || strlen($nama) > 50) {
        kembaliError("Nama barang '$nama' harus antara 3 sampai 50 karakter.", $gedung_asal);
    }

    if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $nama)) {
        kembaliError("Nama barang '$nama' hanya boleh berisi huruf, angka, spasi, dan tanda hubung (-).", $gedung_asal);
    }

    preg_match_all('/[a-zA-Z]/', $nama, $huruf);
    if (count($huruf[0]) < 3 || preg_match('/(.)\1{2,}/i', $nama)) {
        kembaliError("Nama barang '$nama' harus memiliki minimal 3 huruf dan tidak boleh mengulang karakter 3 kali berturut-turut.", $gedung_asal);
    }

    foreach ($barang_tercatat as $nama_lama) {
        $nama_kecil = strtolower($nama);
        $nama_lama_kecil = strtolower($nama_lama);

        if ($nama_kecil === $nama_lama_kecil || strpos($nama_kecil, $nama_lama_kecil) !== false || strpos($nama_lama_kecil, $nama_kecil) !== false) {
            kembaliError("Terdapat barang yang sama atau terlalu mirip: '$nama' dan '$nama_lama'.", $gedung_asal);
        }
    }

    $barang_tercatat[] = $nama;
}

if (!isset($_FILES['foto_barang']) || $_FILES['foto_barang']['error'] !== UPLOAD_ERR_OK) {
    kembaliError("Foto barang wajib diunggah.", $gedung_asal);
}

$file_tmp = $_FILES['foto_barang']['tmp_name'];
$ekstensi = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
$ekstensi_boleh = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ekstensi, $ekstensi_boleh)) {
    kembaliError("Format foto harus JPG, JPEG, PNG, atau WEBP.", $gedung_asal);
}

if ($_FILES['foto_barang']['size'] > 5 * 1024 * 1024) {
    kembaliError("Ukuran foto maksimal adalah 5MB.", $gedung_asal);
}

$folder_upload = __DIR__ . '/uploads/';
if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

$nama_file_foto = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ekstensi;
if (!move_uploaded_file($file_tmp, $folder_upload . $nama_file_foto)) {
    kembaliError("Foto gagal disimpan pada server.", $gedung_asal);
}

$id_aktor = dapatkanIdUserAktif($koneksi, 'admin');

foreach ($daftar_barang as $nama) {
    $nama_clean = mysqli_real_escape_string($koneksi, $nama);

    mysqli_query($koneksi, "
        INSERT INTO barang (id_siswa, id_loker, nama_barang, foto_barang, waktu_penitipan, status, waktu_pengambilan)
        VALUES ($id_siswa, $id_loker, '$nama_clean', '$nama_file_foto', NOW(), 'Tersimpan', NULL)
    ");

    $id_baru = mysqli_insert_id($koneksi);
    mysqli_query($koneksi, "
        INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
        VALUES ($id_aktor, 'Tambah', $id_baru, NOW())
    ");
}

perbaruiStatusLoker($koneksi, $id_loker);

header("Location: indexBarang.php?gedung=" . urlencode($gedung_asal) . "&pesan=sukses_tambah");
exit();

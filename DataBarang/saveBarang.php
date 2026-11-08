<?php
require_once "connectBarang.php";

function kembaliError($pesan) {
    header("Location: createBarang.php?pesan=error&teks=" . urlencode($pesan));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: indexBarang.php");
    exit();
}

$id_siswa = (int)($_POST['id_siswa'] ?? 0);
if (($_SESSION['role'] ?? '') === 'siswa') {
    $id_siswa = (int)($_SESSION['id_user'] ?? 0);
}
$id_loker = (int)($_POST['id_loker'] ?? 0);

if ($id_siswa <= 0 || $id_loker <= 0) {
    kembaliError("Siswa dan loker wajib dipilih.");
}

$pilihan_barang = $_POST['pilihan_barang'] ?? [];
$custom_barang = $_POST['nama_barang_custom'] ?? [];
$daftar_barang = [];

foreach ($pilihan_barang as $i => $pilihan) {
    $pilihan = trim($pilihan);
    $custom = trim($custom_barang[$i] ?? '');

    if ($pilihan === 'Lainnya') {
        if ($custom === '') {
            kembaliError("Nama barang wajib diisi jika memilih Lainnya.");
        }
        $daftar_barang[] = $custom;
    } elseif ($pilihan !== '') {
        $daftar_barang[] = $pilihan;
    }
}

if (count($daftar_barang) < 3) {
    kembaliError("Minimal harus memasukkan 3 barang.");
}

$barang_tercatat = [];
foreach ($daftar_barang as $nama) {
    if (strlen($nama) < 3 || strlen($nama) > 50) {
        kembaliError("Nama barang harus 3 sampai 50 karakter.");
    }

    if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $nama)) {
        kembaliError("Nama barang hanya boleh berisi huruf, angka, spasi, dan tanda hubung.");
    }

    preg_match_all('/[a-zA-Z]/', $nama, $huruf);
    if (count($huruf[0]) < 3 || preg_match('/(.)\1{2,}/i', $nama)) {
        kembaliError("Nama barang harus memiliki minimal 3 huruf dan tidak boleh mengulang huruf 3 kali berturut-turut.");
    }

    foreach ($barang_tercatat as $nama_lama) {
        $nama_kecil = strtolower($nama);
        $nama_lama_kecil = strtolower($nama_lama);

        if ($nama_kecil === $nama_lama_kecil || strpos($nama_kecil, $nama_lama_kecil) !== false || strpos($nama_lama_kecil, $nama_kecil) !== false) {
            kembaliError("Nama barang tidak boleh sama atau terlalu mirip.");
        }
    }

    $barang_tercatat[] = $nama;
}

if (!isset($_FILES['foto_barang']) || $_FILES['foto_barang']['error'] !== UPLOAD_ERR_OK) {
    kembaliError("Foto barang wajib diunggah.");
}

$file_tmp = $_FILES['foto_barang']['tmp_name'];
$ekstensi = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
$ekstensi_boleh = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ekstensi, $ekstensi_boleh)) {
    kembaliError("Format foto harus JPG, JPEG, PNG, atau WEBP.");
}

if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

$nama_file_foto = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ekstensi;
if (!move_uploaded_file($file_tmp, "uploads/" . $nama_file_foto)) {
    kembaliError("Foto gagal diunggah.");
}

$id_aktor = (int)($_SESSION['id_user'] ?? $id_siswa);
if ($id_aktor <= 0) {
    $id_aktor = 30;
}

foreach ($daftar_barang as $nama) {
    $nama = mysqli_real_escape_string($koneksi, $nama);

    mysqli_query($koneksi, "INSERT INTO barang
        (id_siswa, id_loker, nama_barang, foto_barang, waktu_penitipan, status, waktu_pengambilan)
        VALUES ('$id_siswa', '$id_loker', '$nama', '$nama_file_foto', NOW(), 'Tersimpan', NULL)");

    $id_baru = mysqli_insert_id($koneksi);
    @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
        VALUES ('$id_aktor', 'Tambah', '$id_baru', NOW())");
}

mysqli_query($koneksi, "UPDATE loker SET status = 'Terisi' WHERE id_loker = '$id_loker'");

header("Location: indexBarang.php?pesan=sukses_tambah");
exit();
?>

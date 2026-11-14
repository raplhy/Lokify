<?php
require_once "connectBarang.php";

if (($_SESSION['role'] ?? '') === 'siswa') {
    header("Location: indexBarang.php?pesan=akses_ditolak");
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$queryBarang = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = '$id'");
$barang = mysqli_fetch_assoc($queryBarang);

if (!$barang) {
    header("Location: indexBarang.php");
    exit();
}

$daftarBarang = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol", "Lainnya"];
$barangStandar = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol"];

$isCustom = !in_array($barang['nama_barang'], $barangStandar);
$pilihanSaatIni = $isCustom ? 'Lainnya' : $barang['nama_barang'];
$customSaatIni = $isCustom ? $barang['nama_barang'] : '';
$pesanError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_siswa = (int)($_POST['id_siswa'] ?? 0);
    $id_loker_baru = (int)($_POST['id_loker'] ?? 0);
    $id_loker_lama = (int)$barang['id_loker'];
    $status = $_POST['status'] ?? 'Tersimpan';
    $pilihan = trim($_POST['pilihan_barang'] ?? '');
    $custom = trim($_POST['nama_barang_custom'] ?? '');
    $nama_barang = ($pilihan === 'Lainnya') ? $custom : $pilihan;

    if ($id_siswa <= 0 || $id_loker_baru <= 0 || $nama_barang === '') {
        $pesanError = "Siswa, loker, dan nama barang wajib diisi.";
    } elseif (!in_array($status, ['Tersimpan', 'Sudah diambil'])) {
        $pesanError = "Status barang tidak sesuai.";
    } elseif (strlen($nama_barang) < 3 || strlen($nama_barang) > 50) {
        $pesanError = "Nama barang harus 3 sampai 50 karakter.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $nama_barang)) {
        $pesanError = "Nama barang hanya boleh berisi huruf, angka, spasi, dan tanda hubung.";
    } elseif (preg_match('/(.)\1{2,}/i', $nama_barang)) {
        $pesanError = "Nama barang tidak boleh mengulang karakter 3 kali berturut-turut.";
    }

    $cek_siswa = mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_user = $id_siswa AND role = 'siswa'");
    if (!$cek_siswa || mysqli_num_rows($cek_siswa) === 0) {
        $pesanError = "Siswa pemilik tidak valid.";
    }

    $cek_loker = mysqli_query($koneksi, "SELECT id_loker FROM loker WHERE id_loker = $id_loker_baru");
    if (!$cek_loker || mysqli_num_rows($cek_loker) === 0) {
        $pesanError = "Loker yang dipilih tidak valid.";
    }

    $nama_file_foto = $barang['foto_barang'];
    $foto_lama = $barang['foto_barang'];

    if ($pesanError === '' && isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
        $ekstensi = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));

        if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
            $pesanError = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
        } else {
            $foto_baru = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ekstensi;

            if (move_uploaded_file($_FILES['foto_barang']['tmp_name'], "uploads/" . $foto_baru)) {
                $nama_file_foto = $foto_baru;
            } else {
                $pesanError = "Foto baru gagal diunggah.";
            }
        }
    }

    if ($pesanError === '') {
        $nama_barang = mysqli_real_escape_string($koneksi, $nama_barang);

        if ($status === 'Tersimpan') {
            $waktu_ambil = "NULL";
        } elseif (empty($barang['waktu_pengambilan'])) {
            $waktu_ambil = "NOW()";
        } else {
            $waktu_ambil = "'" . mysqli_real_escape_string($koneksi, $barang['waktu_pengambilan']) . "'";
        }

        mysqli_query($koneksi, "UPDATE barang SET
            id_siswa = '$id_siswa',
            id_loker = '$id_loker_baru',
            nama_barang = '$nama_barang',
            foto_barang = '$nama_file_foto',
            status = '$status',
            waktu_pengambilan = $waktu_ambil
            WHERE id_barang = '$id'");

        if ($foto_lama && $foto_lama !== $nama_file_foto) {
            $foto_sql = mysqli_real_escape_string($koneksi, $foto_lama);
            $cekFoto = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE foto_barang = '$foto_sql'");
            if (mysqli_fetch_assoc($cekFoto)['total'] == 0 && file_exists("uploads/" . $foto_lama)) {
                unlink("uploads/" . $foto_lama);
            }
        }

        $id_admin = dapatkanIdUserAktif($koneksi, 'admin');
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ($id_admin, 'Ubah', $id, NOW())");

        perbaruiStatusLoker($koneksi, $id_loker_lama);
        perbaruiStatusLoker($koneksi, $id_loker_baru);

        header("Location: indexBarang.php?pesan=sukses_edit");
        exit();
    }
}

$queryLoker = mysqli_query($koneksi, "SELECT l.id_loker, l.nomor_loker, g.nama_gedung FROM loker l LEFT JOIN gedung g ON l.id_gedung = g.id_gedung ORDER BY l.nomor_loker ASC");
$querySiswa = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'siswa' ORDER BY nama_lengkap ASC");
$path_foto = 'uploads/' . $barang['foto_barang'];
$ada_foto = !empty($barang['foto_barang']) && file_exists($path_foto);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Barang</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Edit Data Barang</h2>
            <p>Perbarui informasi barang titipan <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong></p>
        </div>
        <div>
            <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <?php if ($pesanError !== ""): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($pesanError) ?></div>
    <?php endif; ?>

    <div class="card">
        <form action="updateBarang.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $barang['id_barang'] ?>">

            <div class="form-group">
                <label for="id_siswa">Siswa Pemilik:</label>
                <select name="id_siswa" id="id_siswa" class="form-control" required>
                    <option value="">Pilih Siswa</option>
                    <?php if ($querySiswa): while ($s = mysqli_fetch_assoc($querySiswa)): ?>
                        <option value="<?= $s['id_user'] ?>" <?= ($barang['id_siswa'] == $s['id_user']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nama_lengkap']) ?> (NIS: <?= htmlspecialchars($s['username']) ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_loker">Loker Tujuan:</label>
                <select name="id_loker" id="id_loker" class="form-control" required>
                    <option value="">Pilih Loker</option>
                    <?php if ($queryLoker): while ($l = mysqli_fetch_assoc($queryLoker)): ?>
                        <option value="<?= $l['id_loker'] ?>" <?= ($barang['id_loker'] == $l['id_loker']) ? 'selected' : '' ?>>
                            Loker <?= htmlspecialchars($l['nomor_loker']) ?> - <?= htmlspecialchars($l['nama_gedung'] ?? '') ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nama / Jenis Barang:</label>
                <div class="barang-item-box" style="margin-bottom: 0;">
                    <select name="pilihan_barang" id="pilihan_barang" class="form-control" onchange="cekLainnya()" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($daftarBarang as $item): ?>
                            <option value="<?= $item ?>" <?= ($pilihanSaatIni === $item) ? 'selected' : '' ?>>
                                <?= $item ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="nama_barang_custom" id="input_custom" class="form-control input-custom" 
                           placeholder="Ketik nama barang..." value="<?= htmlspecialchars($customSaatIni) ?>" 
                           style="display: <?= $isCustom ? 'block' : 'none' ?>;">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status Barang:</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="Tersimpan" <?= ($barang['status'] === 'Tersimpan') ? 'selected' : '' ?>>Tersimpan</option>
                    <option value="Sudah diambil" <?= ($barang['status'] === 'Sudah diambil') ? 'selected' : '' ?>>Sudah diambil</option>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_barang">Foto Barang (Kosongkan jika tidak ingin mengganti foto):</label>
                <?php if ($ada_foto): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="<?= htmlspecialchars($path_foto) ?>" alt="Foto Saat Ini" style="width: 100px; border-radius: 6px; border: 1px solid #D8D2C7;">
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_barang" id="foto_barang" class="form-control" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="indexBarang.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function cekLainnya() {
    const select = document.getElementById('pilihan_barang');
    const input = document.getElementById('input_custom');

    if (select.value === 'Lainnya') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

</body>
</html>
<?php
require_once "connectBarang.php";

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

$query = mysqli_query($koneksi, "SELECT b.*, u.nama_lengkap AS nama_siswa, u.username AS nis_siswa,
        l.nomor_loker, g.nama_gedung AS lokasi
    FROM barang b
    LEFT JOIN users u ON b.id_siswa = u.id_user
    LEFT JOIN loker l ON b.id_loker = l.id_loker
    LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
    WHERE b.id_barang = '$id'");
$barang = mysqli_fetch_assoc($query);

if (!$barang) {
    header("Location: indexBarang.php");
    exit();
}

if (($_SESSION['role'] ?? '') === 'siswa' && (int)$barang['id_siswa'] !== (int)($_SESSION['id_user'] ?? 0)) {
    header("Location: indexBarang.php?pesan=akses_ditolak");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_ambil'])) {
    if ($barang['status'] === 'Tersimpan') {
        $id_loker = (int)$barang['id_loker'];

        mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW()
            WHERE id_barang = '$id'");

        $id_aktor = dapatkanIdUserAktif($koneksi, 'admin');

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ($id_aktor, 'Ubah', '$id', NOW())");

        perbaruiStatusLoker($koneksi, $id_loker);
    }

    header("Location: indexBarang.php?pesan=sukses_ambil");
    exit();
}

$path_foto = 'uploads/' . $barang['foto_barang'];
$ada_foto = !empty($barang['foto_barang']) && file_exists($path_foto);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Barang</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>Verifikasi & Pengambilan Barang</h2>
        <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="detail-box">
            <div class="detail-foto">
                <?php if ($ada_foto): ?>
                    <img src="<?= htmlspecialchars($path_foto) ?>" alt="Bukti Foto">
                <?php else: ?>
                    <p style="text-align: center; padding: 40px 10px; color: #9E988E;">Tidak ada foto</p>
                <?php endif; ?>
            </div>

            <div class="detail-info">
                <table>
                    <tr>
                        <th>Nama Barang</th>
                        <td><strong><?= htmlspecialchars($barang['nama_barang']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Siswa Pemilik</th>
                        <td><?= htmlspecialchars($barang['nama_siswa'] ?? '-') ?> (NIS: <?= htmlspecialchars($barang['nis_siswa'] ?? '-') ?>)</td>
                    </tr>
                    <tr>
                        <th>Loker</th>
                        <td>Loker <?= htmlspecialchars($barang['nomor_loker'] ?? '-') ?> <?= !empty($barang['lokasi']) ? '(' . htmlspecialchars($barang['lokasi']) . ')' : '' ?></td>
                    </tr>
                    <tr>
                        <th>Waktu Penitipan</th>
                        <td><?= date('d/m/Y H:i', strtotime($barang['waktu_penitipan'])) ?> WIB</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($barang['status'] === 'Tersimpan'): ?>
                                <span class="badge badge-tersimpan">Tersimpan</span>
                            <?php else: ?>
                                <span class="badge badge-diambil">Sudah diambil</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($barang['waktu_pengambilan'])): ?>
                    <tr>
                        <th>Waktu Diambil</th>
                        <td><?= date('d/m/Y H:i', strtotime($barang['waktu_pengambilan'])) ?> WIB</td>
                    </tr>
                    <?php endif; ?>
                </table>

                <div style="margin-top: 20px; padding: 14px; background-color: #FAF8F5; border: 1px solid #E8E3D9; border-radius: 4px;">
                    <?php if ($barang['status'] === 'Tersimpan'): ?>
                        <form action="verificationBarang.php" method="POST" onsubmit="return confirm('Apakah Anda yakin barang ini sudah diambil oleh siswa?')">
                            <input type="hidden" name="id" value="<?= $barang['id_barang'] ?>">
                            <button type="submit" name="konfirmasi_ambil" value="1" class="btn btn-success">Ambil Barang</button>
                            <a href="indexBarang.php" class="btn btn-secondary">Batal</a>
                        </form>
                    <?php else: ?>
                        <p style="color: #1E1D1B; font-weight: bold; font-size: 13px;">
                            Barang ini sudah diambil pada <?= date('d/m/Y H:i', strtotime($barang['waktu_pengambilan'])) ?> WIB.
                        </p>
                        <div style="margin-top: 10px;">
                            <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
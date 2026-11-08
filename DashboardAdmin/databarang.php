<?php
require_once "koneksi.php";
require_once "fungsi.php";

$halaman_aktif = 'databarang';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';

$tanggal_hari_ini = tanggalIndonesia();

if (isset($_GET['aksi']) && $_GET['aksi'] === 'verifikasi') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && $koneksi) {
        $q = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = $id");
        $data = mysqli_fetch_assoc($q);
        if ($data) {
            $id_loker = (int)$data['id_loker'];
            $id_admin = (int)($_SESSION['id_user'] ?? 30);

            mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW() WHERE id_barang = $id");

            perbaruiStatusLoker($koneksi, $id_loker);

            @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin, 'Ubah', $id, NOW())");

            header("Location: databarang.php?pesan=sukses_verifikasi");
            exit();
        }
    }
    header("Location: databarang.php");
    exit();
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && $koneksi) {
        $q = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = $id");
        $data = mysqli_fetch_assoc($q);
        if ($data) {
            $id_loker = (int)$data['id_loker'];
            $foto = $data['foto_barang'] ?? '';
            $id_admin = (int)($_SESSION['id_user'] ?? 30);

            if (!empty($foto)) {
                $paths = [
                    __DIR__ . "/../DataBarang/uploads/" . $foto,
                    __DIR__ . "/uploads/" . $foto
                ];
                foreach ($paths as $p) {
                    if (file_exists($p)) @unlink($p);
                }
            }

            mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang = $id");

            perbaruiStatusLoker($koneksi, $id_loker);

            @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin, 'Hapus', NULL, NOW())");

            header("Location: databarang.php?pesan=sukses_hapus");
            exit();
        }
    }
    header("Location: databarang.php");
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_massal'])) {
    $aksi = $_POST['aksi_massal'];
    $ids = $_POST['id_barang'] ?? [];
    if (empty($ids)) {
        header("Location: databarang.php?pesan=pilih_kosong");
        exit();
    }

    $sanitized = array_map('intval', $ids);
    $id_list = implode(',', $sanitized);
    $total = count($sanitized);
    $id_admin = (int)($_SESSION['id_user'] ?? 30);

    $qLok = mysqli_query($koneksi, "SELECT DISTINCT id_loker FROM barang WHERE id_barang IN ($id_list)");
    $loker_terpengaruh = [];
    while ($rl = mysqli_fetch_assoc($qLok)) {
        if (!empty($rl['id_loker'])) $loker_terpengaruh[] = (int)$rl['id_loker'];
    }

    if ($aksi === 'verifikasi') {
        mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW() WHERE id_barang IN ($id_list) AND status = 'Tersimpan'");
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin, 'Ubah', NULL, NOW())");
        $pesan = "sukses_massal_verifikasi";
    } elseif ($aksi === 'hapus') {
        $qFoto = mysqli_query($koneksi, "SELECT foto_barang FROM barang WHERE id_barang IN ($id_list)");
        while ($rf = mysqli_fetch_assoc($qFoto)) {
            $f = $rf['foto_barang'] ?? '';
            if (!empty($f)) {
                $p1 = __DIR__ . "/../DataBarang/uploads/" . $f;
                $p2 = __DIR__ . "/uploads/" . $f;
                if (file_exists($p1)) @unlink($p1);
                if (file_exists($p2)) @unlink($p2);
            }
        }
        mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang IN ($id_list)");
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin, 'Hapus', NULL, NOW())");
        $pesan = "sukses_massal_hapus";
    }

    foreach ($loker_terpengaruh as $id_lkr) {
        perbaruiStatusLoker($koneksi, $id_lkr);
    }

    header("Location: databarang.php?pesan=$pesan&total=$total");
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_admin']) && $_POST['aksi_admin'] === 'tambah') {
    $id_siswa = (int)($_POST['id_siswa'] ?? 0);
    $id_loker = (int)($_POST['id_loker'] ?? 0);
    $nama_barang = trim($_POST['nama_barang'] ?? '');

    if (empty($id_siswa) || empty($id_loker) || empty($nama_barang)) {
        header("Location: databarang.php?pesan=gagal_input&teks=" . urlencode("Siswa, Loker, dan Nama Barang wajib diisi."));
        exit();
    }

    $nama_file_foto = '';
    if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === 0) {
        $file_tmp = $_FILES['foto_barang']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
        $ext_boleh = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $ext_boleh)) {
            $target_dir = __DIR__ . "/../DataBarang/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0777, true);
            $nama_file_foto = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ext;
            @move_uploaded_file($file_tmp, $target_dir . $nama_file_foto);
        }
    }

    $nama_clean = mysqli_real_escape_string($koneksi, $nama_barang);
    $id_admin = (int)($_SESSION['id_user'] ?? 30);

    mysqli_query($koneksi, "INSERT INTO barang (id_siswa, id_loker, nama_barang, foto_barang, waktu_penitipan, status, waktu_pengambilan)
        VALUES ($id_siswa, $id_loker, '$nama_clean', '$nama_file_foto', NOW(), 'Tersimpan', NULL)");
    $id_baru = mysqli_insert_id($koneksi);

    perbaruiStatusLoker($koneksi, $id_loker);

    @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
        VALUES ($id_admin, 'Tambah', $id_baru, NOW())");

    header("Location: databarang.php?pesan=sukses_tambah");
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_admin']) && $_POST['aksi_admin'] === 'edit') {
    $id_barang = (int)($_POST['id_barang'] ?? 0);
    $id_siswa = (int)($_POST['id_siswa'] ?? 0);
    $id_loker_baru = (int)($_POST['id_loker'] ?? 0);
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $status_baru = $_POST['status'] ?? 'Tersimpan';

    $qCur = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = $id_barang");
    $bCur = mysqli_fetch_assoc($qCur);

    if ($bCur && !empty($nama_barang) && $id_siswa > 0 && $id_loker_baru > 0) {
        $id_loker_lama = (int)$bCur['id_loker'];
        $status_lama = $bCur['status'];
        $nama_file_foto = $bCur['foto_barang'];

        if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === 0) {
            $file_tmp = $_FILES['foto_barang']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
            $ext_boleh = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $ext_boleh)) {
                $target_dir = __DIR__ . "/../DataBarang/uploads/";
                if (!is_dir($target_dir)) @mkdir($target_dir, 0777, true);
                $nama_file_foto = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (@move_uploaded_file($file_tmp, $target_dir . $nama_file_foto)) {
                    if (!empty($bCur['foto_barang'])) {
                        $p_old = $target_dir . $bCur['foto_barang'];
                        if (file_exists($p_old)) @unlink($p_old);
                    }
                }
            }
        }

        $nama_clean = mysqli_real_escape_string($koneksi, $nama_barang);
        $foto_clean = mysqli_real_escape_string($koneksi, $nama_file_foto);
        $id_admin = (int)($_SESSION['id_user'] ?? 30);

        $waktu_sql = "";
        if ($status_baru === 'Sudah diambil' && $status_lama === 'Tersimpan') {
            $waktu_sql = ", waktu_pengambilan = NOW()";
        } elseif ($status_baru === 'Tersimpan' && $status_lama === 'Sudah diambil') {
            $waktu_sql = ", waktu_pengambilan = NULL";
        }

        mysqli_query($koneksi, "UPDATE barang SET
            nama_barang = '$nama_clean',
            id_siswa = $id_siswa,
            id_loker = $id_loker_baru,
            status = '$status_baru',
            foto_barang = '$foto_clean'
            $waktu_sql
            WHERE id_barang = $id_barang");

        if ($id_loker_lama !== $id_loker_baru) {
            perbaruiStatusLoker($koneksi, $id_loker_lama);
        }
        perbaruiStatusLoker($koneksi, $id_loker_baru);

        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin, 'Ubah', $id_barang, NOW())");

        header("Location: databarang.php?pesan=sukses_edit");
        exit();
    }

    header("Location: databarang.php?pesan=gagal_input&teks=" . urlencode("Gagal memperbarui data barang."));
    exit();
}

$c_total_b = 0;
$c_tersimpan_b = 0;
$c_diambil_b = 0;
$c_siswa_aktif = 0;

if ($koneksi) {
    $qTotal = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM barang");
    $c_total_b = (int)(mysqli_fetch_assoc($qTotal)['c'] ?? 0);

    $qTersimpan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM barang WHERE status = 'Tersimpan'");
    $c_tersimpan_b = (int)(mysqli_fetch_assoc($qTersimpan)['c'] ?? 0);

    $qDiambil = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM barang WHERE status = 'Sudah diambil'");
    $c_diambil_b = (int)(mysqli_fetch_assoc($qDiambil)['c'] ?? 0);

    $qSiswaAktif = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_siswa) AS c FROM barang WHERE status = 'Tersimpan'");
    $c_siswa_aktif = (int)(mysqli_fetch_assoc($qSiswaAktif)['c'] ?? 0);
}

$cari           = trim($_GET['cari'] ?? '');
$status         = trim($_GET['status'] ?? '');
$gedung         = (int)($_GET['gedung'] ?? 0);
$siswa_terpilih = (int)($_GET['siswa'] ?? 0);

$kondisi = "WHERE 1=1";
if ($cari !== '' && $koneksi) {
    $c = mysqli_real_escape_string($koneksi, $cari);
    $kondisi .= " AND (b.nama_barang LIKE '%$c%' OR u.nama_lengkap LIKE '%$c%' OR l.nomor_loker LIKE '%$c%')";
}
if ($status !== '' && $koneksi) {
    $s = mysqli_real_escape_string($koneksi, $status);
    $kondisi .= " AND b.status = '$s'";
}
if ($gedung > 0) {
    $kondisi .= " AND l.id_gedung = $gedung";
}
if ($siswa_terpilih > 0) {
    $kondisi .= " AND b.id_siswa = $siswa_terpilih";
}

$daftar_barang = [];
if ($koneksi) {
    $sql_b = "SELECT b.*, u.nama_lengkap AS nama_siswa, u.username AS nis_siswa, l.nomor_loker, l.id_gedung, g.nama_gedung AS lokasi
              FROM barang b
              LEFT JOIN users u ON b.id_siswa = u.id_user
              LEFT JOIN loker l ON b.id_loker = l.id_loker
              LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
              $kondisi ORDER BY b.id_barang DESC";
    $q_b = mysqli_query($koneksi, $sql_b);
    if ($q_b) {
        while ($r = mysqli_fetch_assoc($q_b)) {
            $r['lokasi'] = trim($r['lokasi'] ?? 'Gedung A');
            $daftar_barang[] = $r;
        }
    }
}

$daftar_siswa = [];
if ($koneksi) {
    $qS = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'siswa' ORDER BY nama_lengkap ASC");
    if ($qS) {
        while ($rs = mysqli_fetch_assoc($qS)) {
            $daftar_siswa[] = $rs;
        }
    }
}

$daftar_loker_pilihan = [];
if ($koneksi) {
    $qL = mysqli_query($koneksi, "SELECT l.id_loker, l.nomor_loker, l.status, g.nama_gedung FROM loker l LEFT JOIN gedung g ON l.id_gedung = g.id_gedung ORDER BY l.nomor_loker ASC");
    if ($qL) {
        while ($rl = mysqli_fetch_assoc($qL)) {
            $daftar_loker_pilihan[] = $rl;
        }
    }
}

$daftar_gedung_filter = [];
if ($koneksi) {
    $qG = mysqli_query($koneksi, "SELECT id_gedung, nama_gedung FROM gedung ORDER BY id_gedung ASC");
    if ($qG) {
        while ($rg = mysqli_fetch_assoc($qG)) {
            $daftar_gedung_filter[] = $rg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Barang - LokerKu Admin</title>
    <link rel="stylesheet" href="styleAdmin.css">
</head>
<body>

<div class="layout">
    <?php include "sidebar.php"; ?>

    <main class="content">
        <div class="topbar-beranda">
            <div class="topbar-left">
                <h1 class="topbar-greeting">Data Barang Titipan</h1>
                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            </div>
            <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>"><img src="admin-avatar.jpg" alt="Admin"></div>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <?php
                $p = $_GET['pesan'];
                $tot = (int)($_GET['total'] ?? 0);
            ?>
            <?php if ($p === 'sukses_tambah'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Data barang titipan baru berhasil ditambahkan ke loker sekolah.</span>
                </div>
            <?php elseif ($p === 'sukses_edit'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Data barang berhasil diperbarui oleh Administrator.</span>
                </div>
            <?php elseif ($p === 'sukses_hapus'): ?>
                <div class="alert-banner alert-banner-info">
                    <span>Data barang berhasil dihapus dari sistem dan status loker telah disesuaikan.</span>
                </div>
            <?php elseif ($p === 'sukses_verifikasi'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Barang berhasil diverifikasi sudah diambil oleh siswa pemilik.</span>
                </div>
            <?php elseif ($p === 'sukses_massal_verifikasi'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Berhasil memverifikasi <strong><?= $tot ?></strong> data barang terpilih sudah diambil.</span>
                </div>
            <?php elseif ($p === 'sukses_massal_hapus'): ?>
                <div class="alert-banner alert-banner-info">
                    <span>Berhasil menghapus <strong><?= $tot ?></strong> data barang terpilih beserta filenya.</span>
                </div>
            <?php elseif ($p === 'pilih_kosong'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Silakan centang minimal 1 barang pada tabel sebelum menjalankan aksi massal.</span>
                </div>
            <?php elseif ($p === 'gagal_input'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span><?= htmlspecialchars($_GET['teks'] ?? 'Terjadi kesalahan saat memproses data.') ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="statistics-4">
            <div class="stat-card">
                <div class="stat-title">TOTAL BARANG</div>
                <div class="stat-value"><?= $c_total_b ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">BARANG TERSIMPAN</div>
                <div class="stat-value" style="color: #C85A17;"><?= $c_tersimpan_b ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">SUDAH DIAMBIL</div>
                <div class="stat-value" style="color: #2E7D32;"><?= $c_diambil_b ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">SISWA AKTIF MENITIP</div>
                <div class="stat-value"><?= $c_siswa_aktif ?></div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                <form method="GET" action="databarang.php" class="filter-bar" style="margin-bottom: 0;">
                    <input type="text" id="inputCariBarang" name="cari" class="form-control" placeholder="Cari barang, siswa, atau nomor loker..." value="<?= htmlspecialchars($cari) ?>" style="min-width: 240px;" autocomplete="off">

                    <select name="siswa" class="form-control" onchange="this.form.submit()">
                        <option value="0">Semua Siswa</option>
                        <?php foreach ($daftar_siswa as $sw): ?>
                            <option value="<?= $sw['id_user'] ?>" <?= ($siswa_terpilih === (int)$sw['id_user']) ? 'selected' : '' ?>>
                                Siswa: <?= htmlspecialchars($sw['nama_lengkap']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Tersimpan" <?= ($status === 'Tersimpan') ? 'selected' : '' ?>>Tersimpan</option>
                        <option value="Sudah diambil" <?= ($status === 'Sudah diambil') ? 'selected' : '' ?>>Sudah diambil</option>
                    </select>

                    <select name="gedung" class="form-control" onchange="this.form.submit()">
                        <option value="0">Semua Gedung</option>
                        <?php foreach ($daftar_gedung_filter as $gf): ?>
                            <option value="<?= $gf['id_gedung'] ?>" <?= ($gedung === (int)$gf['id_gedung']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gf['nama_gedung']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </form>

                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-primary" onclick="bukaModalTambah()">+ Tambah Barang</button>
                </div>
            </div>

            <form method="POST" action="databarang.php" id="formMassalAdmin">
                <div class="bulk-bar-admin">
                    <div class="bulk-info">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600; color: #181715;">
                            <input type="checkbox" id="checkSemua" onchange="togglePilihSemua(this)">
                            Pilih Semua
                        </label>
                        <span>|</span>
                        <span>Terpilih: <strong id="counterTerpilih">0</strong> item</span>
                    </div>
                    <div class="bulk-actions">
                        <button type="submit" name="aksi_massal" value="verifikasi" class="btn btn-sm btn-success" onclick="return konfirmasiMassal('verifikasi')">
                            Verifikasi
                        </button>
                        <button type="submit" name="aksi_massal" value="hapus" class="btn btn-sm btn-danger" onclick="return konfirmasiMassal('hapus')">
                            Hapus
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                    <input type="checkbox" id="checkSemuaTh" onchange="togglePilihSemua(this)" title="Pilih Semua" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                                </th>
                                <th style="width: 38px; text-align: center;">No</th>
                                <th style="width: 50px; text-align: center;">Foto</th>
                                <th style="text-align: center;">Nama Barang</th>
                                <th style="text-align: center;">Siswa Pemilik</th>
                                <th style="text-align: center;">Loker</th>
                                <th style="text-align: center;">Waktu Penitipan</th>
                                <th style="text-align: center;">Waktu Pengambilan</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center; width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="barisKosongCari" style="display: none;">
                                <td colspan="10" style="text-align: center; color: #9E988E; padding: 30px;">
                                    Tidak ada data barang yang sesuai dengan pencarian.
                                </td>
                            </tr>
                            <?php if (!empty($daftar_barang)): ?>
                                <?php $no = 1; foreach ($daftar_barang as $b): ?>
                                    <?php
                            $foto = $b['foto_barang'] ?? '';
                                        $path_foto = '';
                                        if ($foto !== '') {
                                            if (file_exists(__DIR__ . "/../DataBarang/uploads/$foto")) $path_foto = "../DataBarang/uploads/$foto";
                                            elseif (file_exists(__DIR__ . "/uploads/$foto")) $path_foto = "uploads/$foto";
                                        }
                                        $json_b = htmlspecialchars(json_encode([
                                            'id_barang'   => $b['id_barang'],
                                            'nama_barang' => $b['nama_barang'],
                                            'id_siswa'    => $b['id_siswa'],
                                            'id_loker'    => $b['id_loker'],
                                            'status'      => $b['status'],
                                            'foto_barang' => $path_foto
                                        ]), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr class="baris-barang" data-nama="<?= htmlspecialchars(strtolower($b['nama_barang'])) ?>" data-siswa="<?= htmlspecialchars(strtolower($b['nama_siswa'] ?? '')) ?>" data-loker="<?= htmlspecialchars(strtolower($b['nomor_loker'] ?? '')) ?>">
                                        <td style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                            <input type="checkbox" name="id_barang[]" value="<?= $b['id_barang'] ?>" class="item-cek" onchange="hitungTerpilih()" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                                        </td>
                                        <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                                        <td style="text-align: center;">
                                            <div class="thumb-foto">
                                                <?php if ($path_foto !== ''): ?>
                                                    <img src="<?= $path_foto ?>" alt="Foto" onclick="bukaModalFoto('<?= $path_foto ?>', '<?= htmlspecialchars($b['nama_barang']) ?>')">
                                                <?php else: ?>
                                                    <span style="font-size: 11px; color: #9E988E;">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: center;"><strong><?= htmlspecialchars($b['nama_barang']) ?></strong></td>
                                        <td style="text-align: center;">
                                            <?= htmlspecialchars($b['nama_siswa'] ?? 'Siswa #' . $b['id_siswa']) ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-kode"><?= htmlspecialchars($b['nomor_loker'] ?? '-') ?></span>
                                        </td>
                                        <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                            <?= date('d/m/Y H:i', strtotime($b['waktu_penitipan'])) ?>
                                        </td>
                                        <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                            <?= !empty($b['waktu_pengambilan']) ? date('d/m/Y H:i', strtotime($b['waktu_pengambilan'])) : '<span style="color: #9E988E;">-</span>' ?>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <?php if ($b['status'] === 'Tersimpan'): ?>
                                                <span class="badge badge-tersimpan">Tersimpan</span>
                                            <?php else: ?>
                                                <span class="badge badge-diambil">Sudah diambil</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <?php if ($b['status'] === 'Tersimpan'): ?>
                                                <a href="databarang.php?aksi=verifikasi&id=<?= $b['id_barang'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Apakah barang <?= htmlspecialchars($b['nama_barang']) ?> sudah diambil oleh siswa?')">Verifikasi</a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick='bukaModalEdit(<?= $json_b ?>)'>Edit</button>
                                            <a href="databarang.php?aksi=hapus&id=<?= $b['id_barang'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus permanen data barang <?= htmlspecialchars($b['nama_barang']) ?>?')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="barisKosongPhp">
                                    <td colspan="10" style="text-align: center; color: #9E988E; padding: 30px;">
                                        Tidak ada data barang titipan yang sesuai dengan filter.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </main>
</div>

<div class="modal-backdrop" id="modalTambah">
    <div class="modal-box">
        <div class="modal-header">
            <h3>+ Tambah Barang Titipan (Admin)</h3>
            <button type="button" class="modal-close" onclick="tutupModalTambah()">&times;</button>
        </div>
        <form method="POST" action="databarang.php" enctype="multipart/form-data">
            <input type="hidden" name="aksi_admin" value="tambah">
            <div class="modal-body">
                <div class="form-row">
                    <label for="tambah_siswa">Siswa Pemilik:</label>
                    <select name="id_siswa" id="tambah_siswa" class="form-control" required>
                        <option value="">Pilih Siswa</option>
                        <?php foreach ($daftar_siswa as $sw): ?>
                            <option value="<?= $sw['id_user'] ?>">
                                <?= htmlspecialchars($sw['nama_lengkap']) ?> (NIS: <?= htmlspecialchars($sw['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid-2">
                    <div class="form-row">
                        <label for="tambah_loker">Loker Tujuan:</label>
                        <select name="id_loker" id="tambah_loker" class="form-control" required>
                            <option value="">Pilih Loker</option>
                            <?php foreach ($daftar_loker_pilihan as $lk): ?>
                                <option value="<?= $lk['id_loker'] ?>">
                                    Loker <?= htmlspecialchars($lk['nomor_loker']) ?> (<?= $lk['status'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="tambah_nama">Nama Barang:</label>
                        <input type="text" name="nama_barang" id="tambah_nama" class="form-control" placeholder="Contoh: Laptop Asus / Jaket Parka" required>
                    </div>
                </div>
                <div class="form-row">
                    <label for="tambah_foto">Foto Bukti Barang:</label>
                    <input type="file" name="foto_barang" id="tambah_foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambah()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEdit">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Data Barang</h3>
            <button type="button" class="modal-close" onclick="tutupModalEdit()">&times;</button>
        </div>
        <form method="POST" action="databarang.php" enctype="multipart/form-data">
            <input type="hidden" name="aksi_admin" value="edit">
            <input type="hidden" name="id_barang" id="edit_id_barang">
            <div class="modal-body">
                <div class="form-row">
                    <label for="edit_nama">Nama Barang:</label>
                    <input type="text" name="nama_barang" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-row">
                        <label for="edit_siswa">Siswa Pemilik:</label>
                        <select name="id_siswa" id="edit_siswa" class="form-control" required>
                            <option value="">Pilih Siswa</option>
                            <?php foreach ($daftar_siswa as $sw): ?>
                                <option value="<?= $sw['id_user'] ?>">
                                    <?= htmlspecialchars($sw['nama_lengkap']) ?> (NIS: <?= htmlspecialchars($sw['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="edit_loker">Loker:</label>
                        <select name="id_loker" id="edit_loker" class="form-control" required>
                            <option value="">Pilih Loker</option>
                            <?php foreach ($daftar_loker_pilihan as $lk): ?>
                                <option value="<?= $lk['id_loker'] ?>">
                                    Loker <?= htmlspecialchars($lk['nomor_loker']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <label for="edit_status">Status Barang:</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="Tersimpan">Tersimpan</option>
                        <option value="Sudah diambil">Sudah diambil</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="edit_foto">Ganti Foto Bukti (Opsional):</label>
                    <input type="file" name="foto_barang" id="edit_foto" class="form-control" accept="image/*">
                    <div id="preview_foto_container" style="margin-top: 8px; display: none;">
                        <span style="font-size: 11px; color: #6E6A63;">Foto saat ini:</span>
                        <br>
                        <img id="preview_foto_img" src="" alt="Foto Saat Ini" style="max-height: 80px; border-radius: 4px; border: 1px solid #E8E3D9; margin-top: 4px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalEdit()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalFoto" onclick="tutupModalFoto()">
    <div class="modal-box" style="max-width: 480px; text-align: center; background: #FFFFFF; padding: 10px;" onclick="event.stopPropagation()">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px 10px 10px; border-bottom: 1px solid #E8E3D9;">
            <strong id="modalFotoCaption" style="font-size: 13px; color: #181715;"></strong>
            <button type="button" class="modal-close" onclick="tutupModalFoto()">&times;</button>
        </div>
        <div style="padding: 14px 10px;">
            <img id="modalFotoSrc" src="" alt="Pratinjau Foto" style="max-width: 100%; max-height: 460px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
    </div>
</div>

<script>
function togglePilihSemua(el) {
    var ceks = document.querySelectorAll('.item-cek');
    for (var i = 0; i < ceks.length; i++) {
        var tr = ceks[i].closest('tr');
        if (!tr || tr.style.display !== 'none') {
            ceks[i].checked = el.checked;
        }
    }
    var m1 = document.getElementById('checkSemua');
    var m2 = document.getElementById('checkSemuaTh');
    if (m1 && m1 !== el) m1.checked = el.checked;
    if (m2 && m2 !== el) m2.checked = el.checked;
    hitungTerpilih();
}

function hitungTerpilih() {
    var ceks = document.querySelectorAll('.item-cek');
    var totalVisible = 0;
    var checkedVisible = 0;
    for (var i = 0; i < ceks.length; i++) {
        var tr = ceks[i].closest('tr');
        if (!tr || tr.style.display !== 'none') {
            totalVisible++;
            if (ceks[i].checked) checkedVisible++;
        }
    }
    var el = document.getElementById('counterTerpilih');
    if (el) el.textContent = checkedVisible;
    var m1 = document.getElementById('checkSemua');
    var m2 = document.getElementById('checkSemuaTh');
    var all = (totalVisible > 0 && checkedVisible === totalVisible);
    if (m1) m1.checked = all;
    if (m2) m2.checked = all;
}

(function() {
    var inputCari = document.getElementById('inputCariBarang');
    if (!inputCari) return;

    var rows = document.querySelectorAll('.baris-barang');
    var emptyRow = document.getElementById('barisKosongCari');
    var emptyPhp = document.getElementById('barisKosongPhp');

    function filterBarang() {
        var q = inputCari.value.trim().toLowerCase();
        var visibleCount = 0;

        rows.forEach(function(row) {
            var nama = row.getAttribute('data-nama') || '';
            var siswa = row.getAttribute('data-siswa') || '';
            var loker = row.getAttribute('data-loker') || '';

            if (q === '' || nama.indexOf(q) !== -1 || siswa.indexOf(q) !== -1 || loker.indexOf(q) !== -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
                var cb = row.querySelector('.item-cek');
                if (cb && cb.checked) {
                    cb.checked = false;
                }
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
        if (emptyPhp && rows.length === 0) {
            emptyPhp.style.display = '';
        }

        hitungTerpilih();
    }

    inputCari.addEventListener('input', filterBarang);
    inputCari.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    if (inputCari.value.trim() !== '') {
        filterBarang();
    }
})();

function konfirmasiMassal(aksi) {
    var ceks = document.querySelectorAll('.item-cek:checked');
    if (ceks.length === 0) {
        alert('Silakan pilih minimal 1 barang terlebih dahulu dengan mencentang kotak.');
        return false;
    }
    if (aksi === 'verifikasi') {
        return confirm('Verifikasi ' + ceks.length + ' barang terpilih sebagai Sudah Diambil?');
    }
    if (aksi === 'hapus') {
        return confirm('Hapus permanen ' + ceks.length + ' barang terpilih beserta fotonya? Tindakan ini tidak dapat dibatalkan.');
    }
    return true;
}

function bukaModalTambah() {
    document.getElementById('modalTambah').classList.add('aktif');
}

function tutupModalTambah() {
    document.getElementById('modalTambah').classList.remove('aktif');
}

function bukaModalEdit(data) {
    document.getElementById('edit_id_barang').value = data.id_barang || '';
    document.getElementById('edit_nama').value = data.nama_barang || '';
    document.getElementById('edit_siswa').value = data.id_siswa || '';
    document.getElementById('edit_loker').value = data.id_loker || '';
    document.getElementById('edit_status').value = data.status || 'Tersimpan';

    var prevBox = document.getElementById('preview_foto_container');
    var prevImg = document.getElementById('preview_foto_img');
    if (data.foto_barang && data.foto_barang !== '') {
        prevImg.src = data.foto_barang;
        prevBox.style.display = 'block';
    } else {
        prevBox.style.display = 'none';
        prevImg.src = '';
    }

    document.getElementById('modalEdit').classList.add('aktif');
}

function tutupModalEdit() {
    document.getElementById('modalEdit').classList.remove('aktif');
}

function bukaModalFoto(src, caption) {
    document.getElementById('modalFotoSrc').src = src;
    document.getElementById('modalFotoCaption').textContent = caption || 'Foto Barang';
    document.getElementById('modalFoto').classList.add('aktif');
}

function tutupModalFoto() {
    document.getElementById('modalFoto').classList.remove('aktif');
}
</script>

</body>
</html>

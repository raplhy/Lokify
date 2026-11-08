<?php

require_once "koneksi.php";

require_once "fungsi.php";

$halaman_aktif = 'laporan';

$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';

$tanggal_hari_ini = tanggalIndonesia();

$tab = $_GET['tab'] ?? 'rekap';

$tgl_awal  = trim($_GET['tgl_awal'] ?? '');

$tgl_akhir = trim($_GET['tgl_akhir'] ?? '');

$status    = trim($_GET['status'] ?? '');

$kategori  = trim($_GET['kategori'] ?? '');

$filter_aksi    = trim($_GET['aksi'] ?? '');

$filter_tanggal = trim($_GET['tanggal'] ?? '');

$rekap_barang = ambilRekapBarang($koneksi, $mock_barang, $tgl_awal, $tgl_akhir, $status, $kategori);

$daftar_log   = ambilLogAktivitas($koneksi, $mock_log, $filter_aksi, $filter_tanggal);

if (($_GET['export'] ?? '') === 'csv') {

    if ($tab === 'rekap') {

        $nama_file = 'rekap_data_barang_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename="' . $nama_file . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['No', 'Nama Barang', 'Nama Siswa', 'Nomor Loker', 'Waktu Penitipan', 'Waktu Pengambilan', 'Durasi Titip', 'Status']);

        $no = 1;

        foreach ($rekap_barang['data'] as $row) {

            fputcsv($output, [

                $no++,

                $row['nama_barang'],

                $row['nama_siswa'],

                $row['nomor_loker'],

                $row['waktu_penitipan'],

                $row['waktu_pengambilan'] ?? '-',

                $row['durasi'] ?? '-',

                $row['status']

            ]);

        }

        fclose($output);

        exit();

    } else {

        $nama_file = 'laporan_log_aktivitas_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename="' . $nama_file . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['Nomor', 'Pengguna', 'Peran', 'Aksi', 'Barang / Objek', 'Waktu']);

        $no = 1;

        foreach ($daftar_log as $row) {

            fputcsv($output, [

                $no++,

                $row['nama_pengguna'] ?? $row['nama_admin'] ?? 'Administrator',

                ucfirst($row['role'] ?? 'Admin'),

                $row['aksi'],

                $row['barang'],

                $row['waktu']

            ]);

        }

        fclose($output);

        exit();

    }

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rekap Data Barang & Statistik - LokerKu</title>

    <link rel="stylesheet" href="styleAdmin.css">

    <style>

        .nav-tabs {

            display: inline-flex;

            align-items: center;

            background-color: #FAF8F5;

            border: 1px solid #D8D2C7;

            border-radius: 8px;

            padding: 4px;

            gap: 4px;

            margin-bottom: 20px;

        }

        .tab-btn {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 16px;

            border-radius: 6px;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            color: #6E6A63;

            background-color: transparent;

            border: 1px solid transparent;

            transition: all 0.15s ease-in-out;

        }

        .tab-btn:hover {

            color: #181715;

            background-color: #EFECE4;

        }

        .tab-btn.active {

            background-color: #FFFFFF;

            color: #181715;

            border-color: #D8D2C7;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);

            font-weight: bold;

        }

        .category-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));

            gap: 12px;

            margin-top: 12px;

        }

        .category-item {

            background-color: #FAF8F5;

            border: 1px solid #E8E3D9;

            border-radius: 6px;

            padding: 10px 14px;

        }

        .category-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            font-size: 13px;

            margin-bottom: 8px;

        }

        .category-header strong {

            color: #181715;

        }

        .category-header span {

            color: #6E6A63;

            font-size: 12px;

        }

        .category-bar {

            width: 100%;

            height: 7px;

            background-color: #EFECE4;

            border-radius: 4px;

            overflow: hidden;

        }

        .category-fill {

            height: 100%;

            background-color: #E04F16;

            border-radius: 4px;

        }

    </style>

</head>

<body>

<div class="layout">

    <?php include "sidebar.php"; ?>

    <main class="content">

        <div class="topbar-beranda">

            <div class="topbar-left">

                <h1 class="topbar-greeting">Laporan & Rekapitulasi</h1>

                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>

            </div>

            <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>"><img src="admin-avatar.jpg" alt="Admin"></div>

        </div>

        <div class="nav-tabs no-print">

            <a href="laporan.php?tab=rekap" class="tab-btn <?= ($tab === 'rekap') ? 'active' : '' ?>">

                Rekap Data Barang

            </a>

            <a href="laporan.php?tab=log" class="tab-btn <?= ($tab === 'log') ? 'active' : '' ?>">

                Log Aktivitas Sistem

            </a>

        </div>

        <div class="print-header" style="display: none;">

            <h2 style="font-size: 18px; margin-bottom: 4px;">LAPORAN REKAPITULASI PENITIPAN BARANG LOKER SEKOLAH</h2>

            <p style="font-size: 12px; color: #555;">Dicetak pada: <?= date('d/m/Y H:i') ?> WIB | Oleh: <?= htmlspecialchars($nama_admin) ?></p>

        </div>

        <?php if ($tab === 'rekap'): ?>

            <div class="statistics">

                <div class="stat-card">

                    <div class="stat-title">TOTAL REKAP</div>

                    <div class="stat-value"><?= $rekap_barang['total'] ?></div>

                </div>

                <div class="stat-card">

                    <div class="stat-title">MASIH TERSIMPAN</div>

                    <div class="stat-value"><?= $rekap_barang['tersimpan'] ?></div>

                </div>

                <div class="stat-card">

                    <div class="stat-title">SUDAH DIAMBIL</div>

                    <div class="stat-value"><?= $rekap_barang['diambil'] ?></div>

                </div>

                <div class="stat-card">

                    <div class="stat-title">SISWA PENITIP</div>

                    <div class="stat-value"><?= $rekap_barang['total_siswa'] ?></div>

                </div>

            </div>

            <?php if (!empty($rekap_barang['kategori_populer'])): ?>

                <div class="card no-print">

                    <div class="card-header">

                        <h3>Statistik Jenis Barang</h3>

                    </div>

                    <div class="category-grid">

                        <?php

                            $max_kat = max(array_values($rekap_barang['kategori_populer']));

                            if ($max_kat <= 0) $max_kat = 1;

                        ?>

                        <?php foreach ($rekap_barang['kategori_populer'] as $nama_kat => $jml_kat): ?>

                            <?php $p_kat = round(($jml_kat / $max_kat) * 100); ?>

                            <div class="category-item">

                                <div class="category-header">

                                    <strong><?= htmlspecialchars($nama_kat) ?></strong>

                                    <span><?= $jml_kat ?> barang</span>

                                </div>

                                <div class="category-bar">

                                    <div class="category-fill" style="width: <?= $p_kat ?>%;"></div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <div class="card">

                <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">

                    <form method="GET" action="laporan.php" class="filter-bar" style="margin-bottom: 0;">

                        <input type="hidden" name="tab" value="rekap">

                        <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>" title="Tanggal Mulai" onchange="this.form.submit()">

                        <span style="color: #6E6A63;">s/d</span>

                        <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>" title="Tanggal Akhir" onchange="this.form.submit()">

                        <select name="status" class="form-control" onchange="this.form.submit()">

                            <option value="">Semua Status</option>

                            <option value="Tersimpan" <?= ($status === 'Tersimpan') ? 'selected' : '' ?>>Tersimpan</option>

                            <option value="Sudah diambil" <?= ($status === 'Sudah diambil') ? 'selected' : '' ?>>Sudah diambil</option>

                        </select>

                        <select name="kategori" class="form-control" onchange="this.form.submit()">

                            <option value="">Semua Kategori</option>

                            <?php foreach (['Tas', 'Helm', 'Jaket', 'Laptop', 'Sepatu', 'Buku', 'Botol'] as $opt_k): ?>

                                <option value="<?= $opt_k ?>" <?= ($kategori === $opt_k) ? 'selected' : '' ?>><?= $opt_k ?></option>

                            <?php endforeach; ?>

                        </select>

                    </form>

                    <div style="display: flex; gap: 8px;">

                        <a href="laporan.php?tab=rekap&export=csv&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>&status=<?= urlencode($status) ?>&kategori=<?= urlencode($kategori) ?>" class="btn btn-secondary">

                            Ekspor CSV

                        </a>

                        <button type="button" onclick="window.print()" class="btn btn-primary">

                            Cetak Laporan

                        </button>

                    </div>

                </div>

                <div class="table-container">

                    <table class="table-bordered">

                        <thead>

                            <tr>

                                <th style="width: 45px; text-align: center;">No</th>

                                <th style="text-align: center;">Nama Barang</th>

                                <th style="text-align: center;">Siswa Pemilik</th>

                                <th style="text-align: center;">Loker</th>

                                <th style="text-align: center;">Waktu Penitipan</th>

                                <th style="text-align: center;">Waktu Pengambilan</th>

                                <th style="text-align: center;">Durasi</th>

                                <th style="text-align: center;">Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($rekap_barang['data'])): ?>

                                <?php $no = 1; foreach ($rekap_barang['data'] as $b): ?>

                                    <tr>

                                        <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>

                                        <td style="text-align: center;"><strong><?= htmlspecialchars($b['nama_barang']) ?></strong></td>

                                        <td style="text-align: center;"><?= htmlspecialchars($b['nama_siswa']) ?></td>

                                        <td style="text-align: center;">

                                            <span class="badge badge-kode"><?= htmlspecialchars($b['nomor_loker']) ?></span>

                                        </td>

                                        <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;"><?= htmlspecialchars($b['waktu_penitipan']) ?></td>

                                        <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;"><?= !empty($b['waktu_pengambilan']) ? htmlspecialchars($b['waktu_pengambilan']) : '-' ?></td>

                                        <td style="text-align: center; color: #6E6A63; font-size: 12px;"><?= htmlspecialchars($b['durasi'] ?? '-') ?></td>

                                        <td style="text-align: center; white-space: nowrap;">

                                            <?php if ($b['status'] === 'Tersimpan'): ?>

                                                <span class="badge badge-tersimpan">Tersimpan</span>

                                            <?php else: ?>

                                                <span class="badge badge-diambil">Sudah diambil</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="8" style="text-align: center; color: #9E988E; padding: 25px;">

                                        Tidak ada data barang pada periode filter yang dipilih.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php else: ?>

            <div class="card">

                <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">

                    <form method="GET" action="laporan.php" class="filter-bar" style="margin-bottom: 0;">

                        <input type="hidden" name="tab" value="log">

                        <select name="aksi" class="form-control" onchange="this.form.submit()">

                            <option value="">Semua Aksi</option>

                            <option value="Tambah" <?= ($filter_aksi === 'Tambah') ? 'selected' : '' ?>>Tambah</option>

                            <option value="Ubah" <?= ($filter_aksi === 'Ubah') ? 'selected' : '' ?>>Ubah</option>

                            <option value="Hapus" <?= ($filter_aksi === 'Hapus') ? 'selected' : '' ?>>Hapus</option>

                        </select>

                        <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($filter_tanggal) ?>" onchange="this.form.submit()">

                    </form>

                    <a href="laporan.php?tab=log&export=csv&aksi=<?= urlencode($filter_aksi) ?>&tanggal=<?= urlencode($filter_tanggal) ?>" class="btn btn-secondary">

                        Ekspor CSV

                    </a>

                </div>

                <div class="table-container">

                    <table class="table-bordered">

                        <thead>

                            <tr>

                                <th style="width: 70px; text-align: center;">Nomor</th>

                                <th style="text-align: center;">Pengguna</th>

                                <th style="text-align: center;">Aksi</th>

                                <th style="text-align: center;">Barang / Objek</th>

                                <th style="text-align: center;">Waktu</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($daftar_log)): ?>

                                <?php $no_lg = 1; foreach ($daftar_log as $lg): ?>

                                    <tr>

                                        <td style="text-align: center; color: #9E988E;"><?= $no_lg++ ?></td>

                                        <td style="text-align: center;">

                                            <strong><?= htmlspecialchars($lg['nama_pengguna'] ?? $lg['nama_admin'] ?? 'Administrator') ?></strong>

                                            <?php if (($lg['role'] ?? '') === 'siswa'): ?>

                                                <span class="badge-role-siswa">Siswa</span>

                                            <?php else: ?>

                                                <span class="badge-role-admin">Admin</span>

                                            <?php endif; ?>

                                        </td>

                                        <td style="text-align: center;">

                                            <?php

                                                $cls_aksi = 'badge-aksi-ubah';

                                                if ($lg['aksi'] === 'Tambah') $cls_aksi = 'badge-aksi-tambah';

                                                elseif ($lg['aksi'] === 'Hapus') $cls_aksi = 'badge-aksi-hapus';

                                            ?>

                                            <span class="badge-aksi <?= $cls_aksi ?>"><?= htmlspecialchars($lg['aksi']) ?></span>

                                        </td>

                                        <td style="text-align: center;"><strong><?= htmlspecialchars($lg['barang']) ?></strong></td>

                                        <td style="text-align: center; color: #9E988E; font-size: 12px; white-space: nowrap;"><?= htmlspecialchars($lg['waktu']) ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="5" style="text-align: center; color: #9E988E; padding: 25px;">

                                        Tidak ada catatan log aktivitas.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    </main>

</div>

</body>

</html>

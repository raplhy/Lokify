<?php
require_once "koneksi.php";
require_once "fungsi.php";
$halaman_aktif = 'beranda';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';
$stat          = ambilStatistik($koneksi, $mock_loker, $mock_barang);
$daftar_gedung = ambilRingkasanGedung($koneksi, $mock_gedung);
$log_terbaru   = array_slice(ambilLogAktivitas($koneksi, $mock_log), 0, 4);
$grafik_siswa  = ambilGrafikSiswaBulanan($koneksi);
$jam_sekarang   = (int)date('H');
$menit_sekarang = (int)date('i');
$waktu_menit    = ($jam_sekarang * 60) + $menit_sekarang;
if ($waktu_menit <= 659) {
    $sapaan = 'Selamat Pagi';
} elseif ($waktu_menit <= 900) {
    $sapaan = 'Selamat Siang';
} elseif ($waktu_menit <= 1080) {
    $sapaan = 'Selamat Sore';
} else {
    $sapaan = 'Selamat Malam';
}
$heading_sapaan = $sapaan . ', ' . $nama_admin;
$tanggal_hari_ini = tanggalIndonesia();
$daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$bulan_judul = $daftar_bulan[(int)date('n')] . ' ' . date('Y');
$poin_grafik = $grafik_siswa['data'];
$total_poin  = count($poin_grafik);
$max_siswa   = max(5, $grafik_siswa['max_val']);
$svg_w = 960;
$svg_h = 240;
$pad_l = 45;
$pad_r = 25;
$pad_t = 25;
$pad_b = 35;
$plot_w = $svg_w - $pad_l - $pad_r;
$plot_h = $svg_h - $pad_t - $pad_b;
$baseline_y = $svg_h - $pad_b;
$line_coords = [];
$svg_points = [];
for ($i = 0; $i < $total_poin; $i++) {
    $cx = $pad_l + ($i / max(1, $total_poin - 1)) * $plot_w;
    $val = $poin_grafik[$i]['jumlah_siswa'];
    $cy = $baseline_y - ($val / $max_siswa) * $plot_h;
    $line_coords[] = round($cx, 1) . ',' . round($cy, 1);
    $svg_points[] = [
        'cx'    => round($cx, 1),
        'cy'    => round($cy, 1),
        'val'   => $val,
        'hari'  => $poin_grafik[$i]['hari'],
        'label' => $poin_grafik[$i]['label'],
        'tgl'   => $poin_grafik[$i]['tanggal']
    ];
}
$polyline_points = implode(' ', $line_coords);
$area_polygon_points = round($pad_l, 1) . ',' . $baseline_y . ' ' . $polyline_points . ' ' . round($pad_l + $plot_w, 1) . ',' . $baseline_y;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LokerKu</title>
    <link rel="stylesheet" href="styleAdmin.css">
</head>
<body>
<div class="layout">
    <?php include "sidebar.php"; ?>
    <main class="content">
        <div class="topbar-beranda">
            <div class="topbar-left">
                <h1 class="topbar-greeting"><?= htmlspecialchars($heading_sapaan) ?></h1>
                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>"><img src="admin-avatar.jpg" alt="Admin"></div>
                <a href="../login/logout.php" style="background: #F9E2DF; color: #B8473E; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; border: 1px solid #DFA69F; display: inline-block;" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem Admin?')">Logout</a>
            </div>
        </div>
        <div class="statistics-5">
            <div class="stat-card">
                <div class="stat-title">TOTAL LOKER</div>
                <div class="stat-value"><?= $stat['total_loker'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER KOSONG</div>
                <div class="stat-value"><?= $stat['loker_kosong'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER TERISI</div>
                <div class="stat-value"><?= $stat['loker_terisi'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">BARANG TERSIMPAN</div>
                <div class="stat-value"><?= $stat['barang_tersimpan'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">BARANG DIAMBIL</div>
                <div class="stat-value"><?= $stat['barang_diambil'] ?></div>
            </div>
        </div>
        <div class="row-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Status Loker</h3>
                </div>
                <div class="gedung-grid">
                    <?php foreach ($daftar_gedung as $g): ?>
                        <div class="gedung-card">
                            <div class="gedung-header">
                                <span class="gedung-title"><?= htmlspecialchars($g['nama_gedung']) ?></span>
                                <a href="dataloker.php?gedung=<?= urlencode($g['nama_gedung']) ?>" class="btn-gedung-lihat">Lihat</a>
                            </div>
                            <div class="gedung-stats">
                                <div class="gedung-stat-item">
                                    <span class="gedung-stat-label">Tersedia</span>
                                    <span class="gedung-stat-val val-tersedia"><?= $g['loker_kosong'] ?></span>
                                </div>
                                <div class="gedung-stat-item">
                                    <span class="gedung-stat-label">Terisi</span>
                                    <span class="gedung-stat-val val-terisi"><?= $g['loker_terisi'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Log Aktivitas Terbaru</h3>
                    <a href="laporan.php?tab=log" class="btn-gedung-lihat">Lihat Semua</a>
                </div>
                <div class="table-container">
                    <table>
                        <tbody>
                            <?php if (!empty($log_terbaru)): ?>
                                <?php foreach ($log_terbaru as $lg): ?>
                                    <?php
                                        $cls_aksi = 'badge-aksi-ubah';
                                        if ($lg['aksi'] === 'Tambah') $cls_aksi = 'badge-aksi-tambah';
                                        elseif ($lg['aksi'] === 'Hapus') $cls_aksi = 'badge-aksi-hapus';
                                    ?>
                                    <tr>
                                        <td style="width: 80px;">
                                            <span class="badge-aksi <?= $cls_aksi ?>">
                                                <?= htmlspecialchars($lg['aksi']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($lg['barang'] ?? 'Barang') ?></strong>
                                            <div style="font-size: 11px; color: #6E6A63; margin-top: 2px;">
                                                <?= htmlspecialchars($lg['nama_pengguna'] ?? $lg['nama_admin'] ?? 'Administrator') ?>
                                                <?php if (($lg['role'] ?? '') === 'siswa'): ?>
                                                    <span class="badge-role-siswa">Siswa</span>
                                                <?php else: ?>
                                                    <span class="badge-role-admin">Admin</span>
                                                <?php endif; ?>
                                                &bull; <?= date('d/m/Y H:i', strtotime($lg['waktu'])) ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 11px; color: #9E988E; width: 65px; text-align: right;">
                                            <?= date('H:i', strtotime($lg['waktu'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #8C867D; padding: 24px 0;">
                                        Belum ada aktivitas tercatat
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card chart-card">
            <div class="card-header">
                <h3>Aktivitas Penitipan Siswa</h3>
                <div class="chart-badge">
                    <span class="chart-badge-dot"></span>
                    <span><?= htmlspecialchars($bulan_judul) ?></span>
                </div>
            </div>
            <div class="chart-wrapper">
                <svg class="chart-svg" viewBox="0 0 <?= $svg_w ?> <?= $svg_h ?>" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#E04F16" stop-opacity="0.22" />
                            <stop offset="100%" stop-color="#E04F16" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <?php for ($step = 0; $step <= 4; $step++): ?>
                        <?php
                            $step_y = $baseline_y - ($step / 4) * $plot_h;
                            $step_val = round(($step / 4) * $max_siswa);
                        ?>
                        <line x1="<?= $pad_l ?>" y1="<?= $step_y ?>" x2="<?= $pad_l + $plot_w ?>" y2="<?= $step_y ?>" stroke="#E8E3D9" stroke-width="1" stroke-dasharray="<?= ($step > 0 && $step < 4) ? '4,4' : 'none' ?>" />
                        <text x="<?= $pad_l - 8 ?>" y="<?= $step_y + 4 ?>" text-anchor="end" font-size="11" fill="#8C867D" font-family="'Plus Jakarta Sans', Arial, sans-serif"><?= $step_val ?></text>
                    <?php endfor; ?>
                    <polygon points="<?= $area_polygon_points ?>" fill="url(#chartGrad)" />
                    <polyline points="<?= $polyline_points ?>" fill="none" stroke="#E04F16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    <?php foreach ($svg_points as $pt): ?>
                        <g class="chart-point">
                            <title><?= htmlspecialchars($pt['hari']) ?>, <?= htmlspecialchars($pt['tgl']) ?>: <?= $pt['val'] ?> Siswa</title>
                            <circle cx="<?= $pt['cx'] ?>" cy="<?= $pt['cy'] ?>" r="3.5" fill="#FFFFFF" stroke="#E04F16" stroke-width="2" />
                            <text x="<?= $pt['cx'] ?>" y="<?= $baseline_y + 16 ?>" text-anchor="middle" font-size="10" fill="#6E6A63" font-family="'Plus Jakarta Sans', Arial, sans-serif"><?= $pt['hari'] ?></text>
                            <text x="<?= $pt['cx'] ?>" y="<?= $baseline_y + 27 ?>" text-anchor="middle" font-size="9" fill="#9E988E" font-family="'Plus Jakarta Sans', Arial, sans-serif"><?= explode('/', $pt['label'])[0] ?></text>
                        </g>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>
    </main>
</div>
</body>
</html>
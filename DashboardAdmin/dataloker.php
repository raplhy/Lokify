<?php
require_once "koneksi.php";
require_once "fungsi.php";

$halaman_aktif = 'dataloker';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';

$tanggal_hari_ini = tanggalIndonesia();

$filter_status = trim($_GET['status'] ?? '');
$filter_gedung = trim($_GET['gedung'] ?? '');
$cari          = trim($_GET['cari'] ?? '');

$semua_loker = ambilDaftarLoker($koneksi, $mock_loker);

$loker_tampil         = [];
$total_loker          = count($semua_loker);
$total_kosong         = 0;
$total_terisi_loker   = 0;
$total_barang         = 0;
$daftar_gedung_filter = [];

foreach ($semua_loker as $l) {
    $terisi = (int)($l['terisi'] ?? 0);
    $total_barang += $terisi;

    $lokasi = trim($l['lokasi'] ?? $l['nama_gedung'] ?? 'Gedung A');
    if (!in_array($lokasi, $daftar_gedung_filter)) {
        $daftar_gedung_filter[] = $lokasi;
    }

    if ($terisi > 0 || $l['status'] === 'Terisi') {
        $total_terisi_loker++;
    } else {
        $total_kosong++;
    }

    if ($filter_status !== '' && $l['status'] !== $filter_status) {
        continue;
    }

    if ($filter_gedung !== '' && $lokasi !== $filter_gedung) {
        continue;
    }

    if ($cari !== '') {
        $no_lk = strtolower($l['nomor_loker']);
        $c     = strtolower($cari);
        if (strpos($no_lk, $c) === false) {
            continue;
        }
    }

    $loker_tampil[] = $l;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Loker - LokerKu</title>
    <link rel="stylesheet" href="styleAdmin.css">
</head>
<body>

<div class="layout">
    <?php include "sidebar.php"; ?>

    <main class="content">
        <div class="topbar-beranda">
            <div class="topbar-left">
                <h1 class="topbar-greeting">Status Loker</h1>
                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            </div>
            <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>"><img src="admin-avatar.jpg" alt="Admin"></div>
        </div>

        <div class="statistics">
            <div class="stat-card">
                <div class="stat-title">TOTAL LOKER</div>
                <div class="stat-value"><?= $total_loker ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER KOSONG</div>
                <div class="stat-value"><?= $total_kosong ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER TERISI</div>
                <div class="stat-value"><?= $total_terisi_loker ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">BARANG TERSIMPAN</div>
                <div class="stat-value"><?= $total_barang ?></div>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="dataloker.php" class="filter-bar" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="text" id="inputCariLoker" name="cari" class="form-control" placeholder="Cari nomor loker..." value="<?= htmlspecialchars($cari) ?>" style="max-width: 200px;" autocomplete="off">

                <select name="gedung" class="form-control" onchange="this.form.submit()" style="max-width: 170px;">
                    <option value="">Semua Gedung</option>
                    <?php foreach ($daftar_gedung_filter as $g_opt): ?>
                        <option value="<?= htmlspecialchars($g_opt) ?>" <?= ($filter_gedung === $g_opt) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g_opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="form-control" onchange="this.form.submit()" style="max-width: 160px;">
                    <option value="">Semua Status</option>
                    <option value="Kosong" <?= ($filter_status === 'Kosong') ? 'selected' : '' ?>>Kosong</option>
                    <option value="Terisi" <?= ($filter_status === 'Terisi') ? 'selected' : '' ?>>Terisi</option>
                </select>
            </form>

            <div class="table-container">
                <table class="table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="text-align: center;">Nomor Loker</th>
                            <th style="text-align: center;">Gedung / Lokasi</th>
                            <th style="text-align: center;">Jumlah Barang</th>
                            <th style="text-align: center; width: 140px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="barisKosongLokerCari" style="display: none;">
                            <td colspan="5" style="text-align: center; color: #9E988E; padding: 25px;">
                                Tidak ada data loker yang sesuai dengan pencarian.
                            </td>
                        </tr>
                        <?php if (!empty($loker_tampil)): ?>
                            <?php $no = 1; foreach ($loker_tampil as $l): ?>
                                <?php
                                    $terisi       = (int)($l['terisi'] ?? 0);
                                    $is_terisi    = ($terisi > 0 || $l['status'] === 'Terisi');
                                    $badge_status = $is_terisi ? 'badge-terisi' : 'badge-kosong';
                                    $teks_status  = $is_terisi ? 'Terisi' : 'Kosong';
                                ?>
                                <tr class="baris-loker" data-loker="<?= htmlspecialchars(strtolower($l['nomor_loker'])) ?>">
                                    <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                                    <td style="text-align: center;"><span class="badge badge-kode"><?= htmlspecialchars($l['nomor_loker']) ?></span></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($l['lokasi'] ?? $l['nama_gedung'] ?? 'Gedung A') ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($terisi > 0): ?>
                                            <a href="databarang.php?cari=<?= urlencode($l['nomor_loker']) ?>" style="color: #E04F16; font-weight: 600; text-decoration: none;" title="Lihat barang di loker ini">
                                                <?= $terisi ?> Barang
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9E988E;">0 Barang</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;"><span class="badge badge-status <?= $badge_status ?>"><?= $teks_status ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="barisKosongPhp">
                                <td colspan="5" style="text-align: center; color: #9E988E; padding: 25px;">
                                    Tidak ada data loker yang sesuai.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
(function() {
    var inputCari = document.getElementById('inputCariLoker');
    if (!inputCari) return;

    var rows = document.querySelectorAll('.baris-loker');
    var emptyRow = document.getElementById('barisKosongLokerCari');
    var emptyPhp = document.getElementById('barisKosongPhp');

    function filterLoker() {
        var q = inputCari.value.trim().toLowerCase();
        var visibleCount = 0;

        rows.forEach(function(row) {
            var loker = row.getAttribute('data-loker') || '';
            if (q === '' || loker.indexOf(q) !== -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
        if (emptyPhp && rows.length === 0) {
            emptyPhp.style.display = '';
        }
    }

    inputCari.addEventListener('input', filterLoker);
    inputCari.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    if (inputCari.value.trim() !== '') {
        filterLoker();
    }
})();
</script>

</body>
</html>

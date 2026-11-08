<?php
require_once "connectBarang.php";

$id_siswa_login = (int)($_SESSION['id_user'] ?? 0);
$role = $_SESSION['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_massal'])) {
    $aksi = $_POST['aksi_massal'];
    $ids = array_map('intval', $_POST['id_barang'] ?? []);
    $ids = array_values(array_unique(array_filter($ids, function ($id) {
        return $id > 0;
    })));

    if ($aksi === 'hapus' && $role === 'siswa') {
        header("Location: indexBarang.php?pesan=akses_ditolak");
        exit();
    }

    if (empty($ids)) {
        header("Location: indexBarang.php?pesan=tidak_ada_pilihan");
        exit();
    }

    $id_list = implode(',', $ids);
    $jumlah = count($ids);
    $batas_siswa = ($role === 'siswa') ? " AND id_siswa = '$id_siswa_login'" : "";

    $queryData = mysqli_query($koneksi, "SELECT foto_barang, id_loker FROM barang
        WHERE id_barang IN ($id_list) $batas_siswa");
    $loker_terpengaruh = [];
    $foto_terpilih = [];

    while ($data = mysqli_fetch_assoc($queryData)) {
        if (!empty($data['id_loker'])) {
            $loker_terpengaruh[] = (int)$data['id_loker'];
        }

        if (!empty($data['foto_barang'])) {
            $foto_terpilih[] = $data['foto_barang'];
        }
    }

    $id_admin = (int)($_SESSION['id_user'] ?? 30);

    if ($aksi === 'verifikasi') {
        mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW()
            WHERE id_barang IN ($id_list) AND status = 'Tersimpan' $batas_siswa");
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ('$id_admin', 'Ubah', NULL, NOW())");
        $pesan = "sukses_verifikasi_massal";
    } elseif ($aksi === 'hapus') {
        mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang IN ($id_list) $batas_siswa");

        foreach (array_unique($foto_terpilih) as $foto) {
            $foto_sql = mysqli_real_escape_string($koneksi, $foto);
            $cekFoto = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE foto_barang = '$foto_sql'");
            if (mysqli_fetch_assoc($cekFoto)['total'] == 0 && file_exists("uploads/" . $foto)) {
                unlink("uploads/" . $foto);
            }
        }

        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ('$id_admin', 'Hapus', NULL, NOW())");
        $pesan = "sukses_hapus_massal";
    } else {
        header("Location: indexBarang.php");
        exit();
    }

    foreach (array_unique($loker_terpengaruh) as $id_loker) {
        $cekLoker = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang
            WHERE id_loker = '$id_loker' AND status = 'Tersimpan'");

        if (mysqli_fetch_assoc($cekLoker)['total'] == 0) {
            mysqli_query($koneksi, "UPDATE loker SET status = 'Kosong' WHERE id_loker = '$id_loker'");
        }
    }

    header("Location: indexBarang.php?pesan=$pesan&total=$jumlah");
    exit();
}

$kata_kunci = trim($_GET['cari'] ?? '');
$filter_status = $_GET['status'] ?? '';
$kondisi = "WHERE 1=1";

if ($role === 'siswa') {
    $kondisi .= " AND b.id_siswa = '$id_siswa_login'";
}

if ($kata_kunci !== '') {
    $cari = mysqli_real_escape_string($koneksi, $kata_kunci);
    $kondisi .= " AND (b.nama_barang LIKE '%$cari%'
        OR u.nama_lengkap LIKE '%$cari%'
        OR l.nomor_loker LIKE '%$cari%')";
}

if (in_array($filter_status, ['Tersimpan', 'Sudah diambil'])) {
    $status = mysqli_real_escape_string($koneksi, $filter_status);
    $kondisi .= " AND b.status = '$status'";
} else {
    $filter_status = '';
}

$querySql = "SELECT b.*, u.nama_lengkap AS nama_siswa, u.username AS nis_siswa,
        l.nomor_loker, g.nama_gedung AS lokasi
    FROM barang b
    LEFT JOIN users u ON b.id_siswa = u.id_user
    LEFT JOIN loker l ON b.id_loker = l.id_loker
    LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
    $kondisi
    ORDER BY b.id_barang DESC";
$hasilData = mysqli_query($koneksi, $querySql);

$where_siswa = ($role === 'siswa') ? " WHERE id_siswa = '$id_siswa_login'" : "";
$queryJumlah = mysqli_query($koneksi, "SELECT
        COUNT(*) AS semua,
        SUM(status = 'Tersimpan') AS tersimpan,
        SUM(status = 'Sudah diambil') AS diambil
    FROM barang $where_siswa");
$jumlahBarang = mysqli_fetch_assoc($queryJumlah);
$c_semua = (int)($jumlahBarang['semua'] ?? 0);
$c_tersimpan = (int)($jumlahBarang['tersimpan'] ?? 0);
$c_diambil = (int)($jumlahBarang['diambil'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Penitipan Loker</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Penitipan Barang Siswa</h2>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="createBarang.php" class="btn btn-primary">Tambah Barang</a>
        </div>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <div id="notifikasiAlert">
            <?php $p = $_GET['pesan']; $total = (int)($_GET['total'] ?? 0); ?>
            <?php if ($p == 'sukses_tambah'): ?>
                <div class="alert alert-success">Data barang berhasil ditambahkan ke loker.</div>
            <?php elseif ($p == 'sukses_edit'): ?>
                <div class="alert alert-success">Data barang berhasil diperbarui.</div>
            <?php elseif ($p == 'sukses_hapus'): ?>
                <div class="alert alert-info">Data barang berhasil dihapus.</div>
            <?php elseif ($p == 'sukses_ambil'): ?>
                <div class="alert alert-success">Status barang berhasil diubah menjadi Sudah diambil.</div>
            <?php elseif ($p == 'sukses_verifikasi_massal'): ?>
                <div class="alert alert-success">Berhasil memverifikasi <strong><?= $total ?></strong> barang terpilih sudah diambil.</div>
            <?php elseif ($p == 'sukses_hapus_massal'): ?>
                <div class="alert alert-info">Berhasil menghapus <strong><?= $total ?></strong> data barang terpilih beserta fotonya.</div>
            <?php elseif ($p == 'tidak_ada_pilihan'): ?>
                <div class="alert alert-danger">Silakan pilih minimal 1 barang terlebih dahulu dengan mencentang kotak.</div>
            <?php elseif ($p == 'akses_ditolak'): ?>
                <div class="alert alert-danger">Akses ditolak: Siswa hanya dapat menambahkan barang dan memverifikasi pengambilan (tidak diizinkan mengedit atau menghapus).</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="indexBarang.php" class="search-form" onsubmit="return false;">
                <input type="text" id="inputCariBarang" name="cari" class="search-input" placeholder="Cari barang atau nomor loker..." value="<?= htmlspecialchars($kata_kunci) ?>" autocomplete="off" style="width: 270px;">
            </form>

            <?php $paramCari = ($kata_kunci !== '') ? '&cari=' . urlencode($kata_kunci) : ''; ?>
            <div class="filter-tabs">
                <a href="indexBarang.php<?= ($kata_kunci !== '') ? '?cari=' . urlencode($kata_kunci) : '' ?>" 
                   class="filter-tab <?= ($filter_status == '') ? 'active' : '' ?>">
                    Semua <span class="tab-count">(<?= $c_semua ?>)</span>
                </a>
                <a href="indexBarang.php?status=Tersimpan<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Tersimpan') ? 'active' : '' ?>">
                    Tersimpan <span class="tab-count">(<?= $c_tersimpan ?>)</span>
                </a>
                <a href="indexBarang.php?status=Sudah diambil<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Sudah diambil') ? 'active' : '' ?>">
                    Sudah Diambil <span class="tab-count">(<?= $c_diambil ?>)</span>
                </a>
            </div>
        </div>

        <form method="POST" action="indexBarang.php" id="formAksiMassal">
            <div class="bulk-action-bar">
                <div class="bulk-select-area">
                    <label class="label-select-all">
                        <input type="checkbox" id="checkAll" onchange="toggleSelectAll(this)">
                        <span>Pilih Semua Barang</span>
                    </label>
                    <span class="bulk-divider">|</span>
                    <span class="bulk-count">Terpilih: <strong id="jumlahTerpilih" style="color: #C85A17;">0</strong> data</span>
                </div>
                <div class="bulk-buttons">
                    <button type="submit" name="aksi_massal" value="verifikasi" class="btn btn-sm btn-success" onclick="return konfirmasiAksi('verifikasi')">
                        Tandai Sudah Diambil
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                <input type="checkbox" id="checkAllHead" onchange="toggleSelectAll(this)" title="Pilih Semua" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                            </th>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 65px; text-align: center;">Foto</th>
                            <th style="text-align: center;">Nama Barang</th>
                            <th style="width: 90px; text-align: center;">Loker</th>
                            <th style="width: 115px; text-align: center;">Status</th>
                            <th style="width: 145px; text-align: center;">Waktu Penitipan</th>
                            <th style="width: 145px; text-align: center;">Waktu Pengambilan</th>
                            <th style="width: 130px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="barisKosongCari" style="display: none;">
                            <td colspan="9" style="text-align: center; color: #9E988E; padding: 30px;">
                                Tidak ada data barang yang sesuai dengan pencarian.
                            </td>
                        </tr>
                        <?php if (mysqli_num_rows($hasilData) > 0): ?>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($hasilData)): 
                                $path_foto = 'uploads/' . $row['foto_barang'];
                                $ada_foto = (!empty($row['foto_barang']) && file_exists($path_foto));
                            ?>
                            <tr class="baris-barang" data-nama="<?= htmlspecialchars(mb_strtolower($row['nama_barang'], 'UTF-8')) ?>" data-loker="<?= htmlspecialchars(mb_strtolower($row['nomor_loker'] ?? '', 'UTF-8')) ?>" data-siswa="<?= htmlspecialchars(mb_strtolower($row['nama_siswa'] ?? '', 'UTF-8')) ?>">
                                <td style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                    <input type="checkbox" name="id_barang[]" value="<?= $row['id_barang'] ?>" class="item-checkbox" onchange="hitungTerpilih()" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                                </td>
                                <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                                <td style="text-align: center;">
                                    <?php if ($ada_foto): ?>
                                        <a href="<?= $path_foto ?>" target="_blank" style="display: inline-block;">
                                            <img src="<?= $path_foto ?>" alt="Foto" class="thumb-img">
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #9E988E;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <strong><?= htmlspecialchars($row['nama_barang']) ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($row['nomor_loker'])): ?>
                                        <span class="badge badge-kode"><?= htmlspecialchars($row['nomor_loker']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if ($row['status'] == 'Tersimpan'): ?>
                                        <span class="badge badge-tersimpan">Tersimpan</span>
                                    <?php else: ?>
                                        <span class="badge badge-diambil">Sudah diambil</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($row['waktu_penitipan'])) ?>
                                </td>
                                <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                    <?= !empty($row['waktu_pengambilan']) ? date('d/m/Y H:i', strtotime($row['waktu_pengambilan'])) : '-' ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if ($row['status'] === 'Tersimpan'): ?>
                                        <a href="verificationBarang.php?id=<?= $row['id_barang'] ?>" class="btn btn-sm btn-primary">Verifikasi</a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #8C867D; font-weight: 600;">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="barisKosongPhp">
                                <td colspan="9" style="text-align: center; padding: 30px; color: #6E6A63;">
                                    Belum ada data barang. Silakan klik tombol <strong>Tambah Barang</strong>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
function checkboxTerlihat() {
    return Array.from(document.querySelectorAll('.item-checkbox')).filter(checkbox => {
        return checkbox.closest('tr').style.display !== 'none';
    });
}

function toggleSelectAll(master) {
    checkboxTerlihat().forEach(checkbox => {
        checkbox.checked = master.checked;
    });
    hitungTerpilih();
}

function hitungTerpilih() {
    const semua = checkboxTerlihat();
    const terpilih = semua.filter(checkbox => checkbox.checked).length;
    const pilihSemua = semua.length > 0 && terpilih === semua.length;

    document.getElementById('jumlahTerpilih').textContent = terpilih;
    document.getElementById('checkAll').checked = pilihSemua;
    document.getElementById('checkAllHead').checked = pilihSemua;
}

function konfirmasiAksi(jenisAksi) {
    const jumlah = document.querySelectorAll('.item-checkbox:checked').length;

    if (jumlah === 0) {
        alert('Pilih minimal satu barang terlebih dahulu.');
        return false;
    }

    if (jenisAksi === 'verifikasi') {
        return confirm('Tandai ' + jumlah + ' barang sebagai sudah diambil?');
    }

    if (jenisAksi === 'hapus') {
        return confirm('Hapus ' + jumlah + ' data barang beserta fotonya?');
    }

    return false;
}

const inputCari = document.getElementById('inputCariBarang');
if (inputCari) {
    const barisBarang = document.querySelectorAll('.baris-barang');
    const barisKosong = document.getElementById('barisKosongCari');

    function filterBarang() {
        const kata = inputCari.value.trim().toLowerCase();
        let jumlahTampil = 0;

        barisBarang.forEach(baris => {
            const cocok =
                (baris.dataset.nama || '').includes(kata) ||
                (baris.dataset.loker || '').includes(kata) ||
                (baris.dataset.siswa || '').includes(kata);

            baris.style.display = cocok ? '' : 'none';
            if (cocok) {
                jumlahTampil++;
            } else {
                const checkbox = baris.querySelector('.item-checkbox');
                if (checkbox) checkbox.checked = false;
            }
        });

        barisKosong.style.display = (jumlahTampil === 0 && barisBarang.length > 0) ? '' : 'none';
        hitungTerpilih();
    }

    inputCari.addEventListener('input', filterBarang);
    inputCari.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') event.preventDefault();
    });

    if (inputCari.value.trim() !== '') filterBarang();
}

const notifikasi = document.getElementById('notifikasiAlert');
if (notifikasi) {
    setTimeout(() => notifikasi.style.display = 'none', 4000);
}
</script>

</body>
</html>
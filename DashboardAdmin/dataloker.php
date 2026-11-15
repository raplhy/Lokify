<?php
require_once "koneksi.php";
require_once "fungsi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halaman_aktif = 'dataloker';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';
$tanggal_hari_ini = tanggalIndonesia();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $nomor_loker = trim(mysqli_real_escape_string($koneksi, $_POST['nomor_loker'] ?? ''));
    $lokasi = (int)($_POST['id_gedung'] ?? 0);

    if (empty($nomor_loker) || $lokasi <= 0) {
        header("Location: dataloker.php?pesan=kosong");
        exit();
    }

    $cek_gedung = mysqli_query($koneksi, "SELECT id_gedung FROM gedung WHERE id_gedung = $lokasi");
    if (mysqli_num_rows($cek_gedung) == 0) {
        header("Location: dataloker.php");
        exit();
    }

    $cek = mysqli_query($koneksi, "SELECT id_loker FROM loker WHERE nomor_loker = '$nomor_loker'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: dataloker.php?pesan=duplikat");
        exit();
    }

    $simpan = mysqli_query($koneksi, "INSERT INTO loker (nomor_loker, id_gedung, status) VALUES ('$nomor_loker', $lokasi, 'Kosong')");
    if ($simpan) {
        header("Location: dataloker.php?pesan=tambah");
        exit();
    } else {
        header("Location: dataloker.php?pesan=gagal");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id_loker = (int)($_POST['id_loker'] ?? 0);
    $nomor_loker = trim(mysqli_real_escape_string($koneksi, $_POST['nomor_loker'] ?? ''));
    $lokasi = (int)($_POST['id_gedung'] ?? 0);

    if ($id_loker <= 0 || empty($nomor_loker) || $lokasi <= 0) {
        header("Location: dataloker.php?pesan=kosong");
        exit();
    }

    $cek_gedung = mysqli_query($koneksi, "SELECT id_gedung FROM gedung WHERE id_gedung = $lokasi");
    if (mysqli_num_rows($cek_gedung) == 0) {
        header("Location: dataloker.php");
        exit();
    }

    $cek = mysqli_query($koneksi, "SELECT id_loker FROM loker WHERE nomor_loker = '$nomor_loker' AND id_loker != $id_loker");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: dataloker.php?pesan=duplikat");
        exit();
    }

    $update = mysqli_query($koneksi, "UPDATE loker SET nomor_loker = '$nomor_loker', id_gedung = $lokasi WHERE id_loker = $id_loker");
    if ($update) {
        header("Location: dataloker.php?pesan=edit");
        exit();
    } else {
        header("Location: dataloker.php?pesan=gagal");
        exit();
    }
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id_loker'])) {
    $id = (int)$_GET['id_loker'];
    if ($id > 0) {
        $cek_loker = mysqli_query($koneksi, "SELECT status FROM loker WHERE id_loker = $id");
        $data_loker = mysqli_fetch_assoc($cek_loker);
        if ($data_loker && $data_loker['status'] !== 'Kosong') {
            header("Location: dataloker.php?pesan=terisi");
            exit();
        }

        $hapus = mysqli_query($koneksi, "DELETE FROM loker WHERE id_loker = $id");
        if ($hapus) {
            header("Location: dataloker.php?pesan=hapus");
            exit();
        } else {
            header("Location: dataloker.php?pesan=gagal");
            exit();
        }
    }
    header("Location: dataloker.php");
    exit();
}

$gedung_get = trim($_GET['gedung'] ?? '');
if ($gedung_get !== '' && !ctype_digit($gedung_get)) {
    $gedung_aman = mysqli_real_escape_string($koneksi, $gedung_get);
    $cek_gedung = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_gedung FROM gedung WHERE nama_gedung = '$gedung_aman' LIMIT 1"));
    $filter_gedung = (int)($cek_gedung['id_gedung'] ?? 0);
} else {
    $filter_gedung = (int)$gedung_get;
}

$cari = trim($_GET['cari'] ?? '');
$cari_aman = mysqli_real_escape_string($koneksi, $cari);

/* Filter status loker: '' = semua, 'Kosong' = tidak terisi, 'Terisi' = terisi */
$filter_status = trim($_GET['status'] ?? '');
if (!in_array($filter_status, ['Kosong', 'Terisi'], true)) {
    $filter_status = '';
}

$kondisi = [];
if ($filter_gedung) {
    $kondisi[] = "loker.id_gedung = $filter_gedung";
}
if ($cari !== '') {
    $kondisi[] = "loker.nomor_loker LIKE '%$cari_aman%'";
}
if ($filter_status === 'Kosong') {
    $kondisi[] = "loker.status = 'Kosong'";
} elseif ($filter_status === 'Terisi') {
    $kondisi[] = "loker.status != 'Kosong'";
}
$where = $kondisi ? "WHERE " . implode(" AND ", $kondisi) : "";

$data = mysqli_query($koneksi, "
    SELECT 
        loker.id_loker,
        loker.nomor_loker,
        loker.id_gedung,
        gedung.nama_gedung,
        loker.status
    FROM loker
    INNER JOIN gedung ON loker.id_gedung = gedung.id_gedung
    $where
    ORDER BY loker.nomor_loker ASC
");

$total = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_loker FROM loker"));
$kosong = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_loker FROM loker WHERE status = 'Kosong'"));
$terisi = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_loker FROM loker WHERE status != 'Kosong'"));

$daftar_gedung = mysqli_query($koneksi, "SELECT id_gedung, nama_gedung FROM gedung ORDER BY id_gedung ASC");
$daftar_gedung_edit = mysqli_query($koneksi, "SELECT id_gedung, nama_gedung FROM gedung ORDER BY id_gedung ASC");
$daftar_gedung_filter = mysqli_query($koneksi, "SELECT id_gedung, nama_gedung FROM gedung ORDER BY id_gedung ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Loker - Lokify</title>
    <link rel="stylesheet" href="styleAdmin.css?v=<?= file_exists('styleAdmin.css') ? filemtime('styleAdmin.css') : time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        .loker-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid #E8E3D9;
        }
        .loker-head h3 {
            margin: 0;
            font-size: 16px;
        }
        .loker-head p {
            margin: 3px 0 0;
            font-size: 12.5px;
            color: #8C867D;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header-actions form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin: 0;
        }
        .btn-add,
        .btn-cari,
        .btn-reset,
        .btn-edit,
        .btn-delete,
        .btn-save,
        .btn-cancel,
        .btn-delete-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: filter .15s, background .15s;
        }
        .btn-add,
        .btn-save {
            color: #fff;
            background: #E04F16;
        }
        .btn-add:hover,
        .btn-save:hover { filter: brightness(.93); }
        .btn-cari,
        .btn-cancel,
        .btn-reset,
        .btn-edit {
            color: #3A3732;
            background: #F3F0E8;
            border-color: #E8E3D9;
        }
        .btn-cari:hover,
        .btn-cancel:hover,
        .btn-reset:hover,
        .btn-edit:hover { background: #E8E3D9; }
        .btn-delete {
            color: #B8473E;
            background: #F9E2DF;
            border-color: #DFA69F;
        }
        .btn-delete:hover { background: #F3D0CB; }
        .btn-delete-confirm {
            color: #fff;
            background: #B8473E;
        }
        .btn-delete-confirm:hover { filter: brightness(.93); }
        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .btn-edit,
        .btn-delete { padding: 6px 12px; }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(20, 18, 14, .45);
        }
        .modal-overlay.active { display: flex; }
        .modal-card {
            width: 100%;
            max-width: 440px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            background: #fff;
            border: 1px solid #E8E3D9;
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .18);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .modal-header h2 { margin: 0; font-size: 18px; }
        .modal-header p {
            margin: 3px 0 0;
            font-size: 12.5px;
            color: #8C867D;
        }
        .modal-close {
            width: 32px;
            height: 32px;
            font-size: 22px;
            line-height: 1;
            color: #6E6A63;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .modal-close:hover { background: #F3F0E8; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #3A3732;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 14px;
            background: #fff;
            border: 1px solid #E8E3D9;
            border-radius: 8px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #E04F16;
            box-shadow: 0 0 0 3px rgba(224, 79, 22, .12);
        }
        .status-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            background: #FAF9F5;
            border: 1px solid #E8E3D9;
            border-radius: 10px;
        }
        .status-info p {
            margin: 0;
            font-size: 12.5px;
            line-height: 1.5;
            color: #6E6A63;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .delete-card { text-align: center; }
        .delete-card .modal-actions { justify-content: center; }
        .delete-card h2 { margin: 12px 0 8px; font-size: 18px; }
        .delete-card p { margin: 0 0 20px; font-size: 14px; color: #6E6A63; }
        .delete-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: #B8473E;
            background: #F9E2DF;
            border-radius: 50%;
        }
        .table-container .dataTables_wrapper {
            font-size: 14px;
            color: #6E6A63;
        }
        .dataTables_wrapper .dataTables_length {
            margin: 4px 0 16px;
        }
        .dataTables_wrapper .dataTables_length select {
            appearance: none;
            -webkit-appearance: none;
            height: 38px;
            margin: 0 8px;
            padding: 0 34px 0 14px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #1F1D1A;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%231F1D1A' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 12px center;
            border: 1px solid #E8E3D9;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
        }
        .dataTables_wrapper .dataTables_length select:hover { border-color: #CFC9BA; }
        .dataTables_wrapper .dataTables_length select:focus {
            outline: none;
            border-color: #E04F16;
            box-shadow: 0 0 0 3px rgba(224, 79, 22, .12);
        }
        table.dataTable.no-footer { border-bottom: 1px solid #E8E3D9; }
        .dataTables_wrapper .dataTables_info {
            padding-top: 22px;
            font-size: 13.5px;
            color: #9E988E;
        }
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dataTables_wrapper .dataTables_paginate span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            min-width: 38px;
            height: 38px;
            padding: 0 12px !important;
            margin: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: #3A3732 !important;
            background: #fff !important;
            background-image: none !important;
            border: 1px solid #E8E3D9 !important;
            border-radius: 10px !important;
            box-shadow: none !important;
            cursor: pointer;
            transition: background .15s, border-color .15s, color .15s, transform .15s;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: #1F1D1A !important;
            background: #F3F0E8 !important;
            border-color: #D9D3C4 !important;
            transform: translateY(-1px);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #fff !important;
            background: #E04F16 !important;
            border-color: #E04F16 !important;
            transform: none;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: #C2BEAD !important;
            background: #FAF9F5 !important;
            border-color: #EFECE3 !important;
            cursor: not-allowed;
            transform: none;
        }
        .dataTables_wrapper .dataTables_paginate .ellipsis {
            min-width: 20px;
            color: #A8A393;
            text-align: center;
        }
        @media (max-width: 768px) {
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                float: none;
                text-align: center;
                justify-content: center;
                flex-wrap: wrap;
            }
        }
        .status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            white-space: nowrap;
        }
        .status.kosong { color: #1E6B3A; background: #E4F4EA; }
        .status.terisi { color: #B8473E; background: #F9E2DF; }
    </style>
</head>
<body>

<div class="layout">
    <?php include "sidebar.php"; ?>

    <main class="content">
        <div class="topbar-beranda">
            <div class="topbar-left">
                <h1 class="topbar-greeting">Manajemen Loker</h1>
                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            </div>
            <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>">
                <img src="admin-avatar.jpg?v=<?= file_exists('admin-avatar.jpg') ? filemtime('admin-avatar.jpg') : time() ?>" alt="Admin">
            </div>
        </div>

        <div class="statistics">
            <div class="stat-card">
                <div class="stat-title">TOTAL LOKER</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER KOSONG</div>
                <div class="stat-value"><?= $kosong ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">LOKER TERISI</div>
                <div class="stat-value"><?= $terisi ?></div>
            </div>
        </div>

        <div class="card">
            <div class="loker-head">
                <div>
                    <h3>Data Loker</h3>
                    <p>Daftar seluruh loker yang tersedia di sekolah</p>
                </div>
                <div class="header-actions">
                    <form method="GET" action="dataloker.php">
                        <select name="gedung" class="form-control filter-gedung" onchange="this.form.submit()" style="max-width: 170px;">
                            <option value="0">Semua Gedung</option>
                            <?php while ($g = mysqli_fetch_assoc($daftar_gedung_filter)): ?>
                                <option value="<?= $g['id_gedung'] ?>" <?= $filter_gedung == $g['id_gedung'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_gedung']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <select name="status" class="form-control filter-status" onchange="this.form.submit()" style="max-width: 150px;">
                            <option value="">Semua Status</option>
                            <option value="Kosong" <?= $filter_status === 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                            <option value="Terisi" <?= $filter_status === 'Terisi' ? 'selected' : '' ?>>Terisi</option>
                        </select>
                        <input type="search" name="cari" class="form-control cari-loker" placeholder="Cari kode loker..." value="<?= htmlspecialchars($cari) ?>" style="max-width: 200px;" autocomplete="off">
                        <button type="submit" class="btn-cari">Cari</button>
                        <?php if ($cari !== '' || $filter_gedung || $filter_status !== ''): ?>
                            <a href="dataloker.php" class="btn-reset">Reset</a>
                        <?php endif; ?>
                    </form>
                    <button type="button" class="btn-add" onclick="bukaModalTambah()">+ Tambah Loker</button>
                </div>
            </div>

            <div class="table-container">
                <table class="table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="text-align: center;">KODE LOKER</th>
                            <th style="text-align: center;">LOKASI</th>
                            <th style="text-align: center; width: 140px;">STATUS</th>
                            <th style="text-align: center; width: 180px;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($data) == 0): ?>
                        <tr>
                            <td colspan="5" class="empty-row" style="text-align: center; color: #9E988E; padding: 25px;">
                                Tidak ada loker yang cocok.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    $no = 1;
                    mysqli_data_seek($data, 0);
                    while ($row = mysqli_fetch_assoc($data)):
                        $js_id = (int)$row['id_loker'];
                        $js_nomor = htmlspecialchars(json_encode($row['nomor_loker']), ENT_QUOTES, 'UTF-8');
                        $js_id_gedung = (int)$row['id_gedung'];
                        $js_status = htmlspecialchars(json_encode($row['status']), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-kode"><?= htmlspecialchars($row['nomor_loker']) ?></span>
                            </td>
                            <td style="text-align: center;"><?= htmlspecialchars($row['nama_gedung']) ?></td>
                            <td style="text-align: center;">
                                <?php if ($row['status'] == 'Kosong'): ?>
                                    <span class="badge badge-status badge-kosong">Kosong</span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-terisi">Terisi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn-edit" onclick="bukaModalEdit(<?= $js_id ?>, <?= $js_nomor ?>, <?= $js_id_gedung ?>, <?= $js_status ?>)">Edit</button>
                                    <button type="button" class="btn-delete" onclick="bukaModalHapus(<?= $js_id ?>, <?= $js_nomor ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="modalTambah">
    <div class="modal-card">
        <div class="modal-header">
            <div>
                <h2>Tambah Loker</h2>
                <p>Tambahkan data loker baru</p>
            </div>
            <button type="button" class="modal-close" onclick="tutupModal()">×</button>
        </div>
        <form action="dataloker.php" method="POST">
            <div class="form-group">
                <label>Nomor Loker</label>
                <input type="text" name="nomor_loker" placeholder="Contoh: A001" required>
            </div>
            <div class="form-group">
                <label>Gedung</label>
                <select name="id_gedung" required>
                    <option value="" disabled selected>-- Pilih Gedung --</option>
                    <?php while ($gedung = mysqli_fetch_assoc($daftar_gedung)): ?>
                        <option value="<?= $gedung['id_gedung'] ?>">
                            <?= htmlspecialchars($gedung['nama_gedung']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="status-info">
                <span class="badge badge-status badge-kosong">Kosong</span>
                <p>
                    Loker baru otomatis berstatus <strong>Kosong</strong>. Status akan berubah otomatis ketika user mengisi barang.
                </p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="tutupModal()">Batal</button>
                <button type="submit" name="simpan" class="btn-save">Simpan Loker</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEdit">
    <div class="modal-card">
        <div class="modal-header">
            <div>
                <h2>Edit Loker</h2>
                <p>Ubah informasi loker</p>
            </div>
            <button type="button" class="modal-close" onclick="tutupEdit()">×</button>
        </div>
        <form action="dataloker.php" method="POST">
            <input type="hidden" name="id_loker" id="edit_id_loker">
            <div class="form-group">
                <label>Nomor Loker</label>
                <input type="text" name="nomor_loker" id="edit_nomor_loker" required>
            </div>
            <div class="form-group">
                <label>Gedung</label>
                <select name="id_gedung" id="edit_lokasi" required>
                    <?php while ($gedung = mysqli_fetch_assoc($daftar_gedung_edit)): ?>
                        <option value="<?= $gedung['id_gedung'] ?>">
                            <?= htmlspecialchars($gedung['nama_gedung']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="status-info">
                <span class="badge badge-status" id="edit_status_tampilan">Status</span>
                <p>
                    Status loker tidak dapat diubah oleh admin. Status akan ditentukan otomatis berdasarkan barang user.
                </p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="tutupEdit()">Batal</button>
                <button type="submit" name="edit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalHapus">
    <div class="modal-card delete-card">
        <div class="delete-icon">!</div>
        <h2>Hapus Loker?</h2>
        <p>Apakah kamu yakin ingin menghapus loker <strong id="namaLokerHapus"></strong>?</p>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="tutupHapus()">Batal</button>
            <a href="#" id="linkHapus" class="btn-delete-confirm">Ya, Hapus</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
function bukaModalTambah() {
    const modal = document.getElementById("modalTambah");
    if (modal) {
        modal.classList.add("active");
    }
}

function tutupModal() {
    const modal = document.getElementById("modalTambah");
    if (modal) {
        modal.classList.remove("active");
    }
}

function bukaModalEdit(id, nomor, lokasi, status) {
    const modal = document.getElementById("modalEdit");
    const idInput = document.getElementById("edit_id_loker");
    const nomorInput = document.getElementById("edit_nomor_loker");
    const lokasiInput = document.getElementById("edit_lokasi");
    const statusTampilan = document.getElementById("edit_status_tampilan");

    if (!modal) {
        return;
    }

    idInput.value = id;
    nomorInput.value = nomor;
    lokasiInput.value = lokasi;

    if (status === "Kosong") {
        statusTampilan.textContent = "● Kosong";
        statusTampilan.className = "status kosong";
    } else {
        statusTampilan.textContent = "● Terisi";
        statusTampilan.className = "status terisi";
    }

    modal.classList.add("active");
}

function tutupEdit() {
    const modal = document.getElementById("modalEdit");
    if (modal) {
        modal.classList.remove("active");
    }
}

function bukaModalHapus(id, nomor) {
    const modal = document.getElementById("modalHapus");
    const namaLoker = document.getElementById("namaLokerHapus");
    const linkHapus = document.getElementById("linkHapus");

    if (!modal) {
        return;
    }

    namaLoker.textContent = nomor;
    linkHapus.href = "dataloker.php?aksi=hapus&id_loker=" + id;

    modal.classList.add("active");
}

function tutupHapus() {
    const modal = document.getElementById("modalHapus");
    if (modal) {
        modal.classList.remove("active");
    }
}

document.addEventListener("click", function (event) {
    if (event.target.classList.contains("modal-overlay")) {
        event.target.classList.remove("active");
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".modal-overlay").forEach(function (modal) {
            modal.classList.remove("active");
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    if (typeof toastr === "undefined") {
        return;
    }

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    const params = new URLSearchParams(window.location.search);
    const pesan = params.get("pesan");

    if (pesan === "tambah") {
        toastr.success("Data Loker berhasil ditambahkan!", "Berhasil");
    } else if (pesan === "edit") {
        toastr.success("Data Loker berhasil diubah!", "Berhasil");
    } else if (pesan === "hapus") {
        toastr.error("Data Loker berhasil dihapus!", "Dihapus");
    } else if (pesan === "duplikat") {
        toastr.error("Kode Loker sudah digunakan!", "Gagal");
    } else if (pesan === "kosong") {
        toastr.error("Nomor loker dan gedung wajib diisi!", "Gagal");
    } else if (pesan === "terisi") {
        toastr.error("Loker yang sedang terisi tidak dapat dihapus!", "Gagal");
    } else if (pesan === "gagal") {
        toastr.error("Terjadi kesalahan saat memproses data!", "Gagal");
    }

    if (pesan) {
        window.history.replaceState({}, document.title, "dataloker.php");
    }
});

$(document).ready(function () {
    var tabel = $('.table-container table');
    tabel.find('tbody tr:has(td.empty-row)').remove();
    tabel.DataTable({
        paging: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        pagingType: 'full_numbers',
        searching: false,
        ordering: false,
        info: true,
        autoWidth: false,
        language: {
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ loker',
            infoEmpty: 'Menampilkan 0 loker',
            emptyTable: 'Tidak ada loker yang cocok.',
            zeroRecords: 'Tidak ada loker yang cocok.',
            paginate: {
                first: 'Awal',
                last: 'Akhir',
                next: '›',
                previous: '‹'
            }
        }
    });
});
</script>

</body>
</html>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$gedung = $_GET['gedung'] ?? 'Gedung A';

$daftar_gedung = ['Gedung A', 'Gedung B', 'Gedung C', 'Gedung D'];
if (!in_array($gedung, $daftar_gedung)) {
    $gedung = 'Gedung A';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_massal'])) {
    $aksi = $_POST['aksi_massal'];
    $ids = array_map('intval', $_POST['id_barang'] ?? []);
    $ids = array_values(array_unique(array_filter($ids, function ($id) {
        return $id > 0;
    })));

    $gedung_redirect = $_POST['gedung_asal'] ?? $gedung;

    if ($aksi === 'hapus') {
        header("Location: indexBarang.php?gedung=" . urlencode($gedung_redirect) . "&pesan=akses_ditolak");
        exit();
    }

    if (empty($ids)) {
        header("Location: indexBarang.php?gedung=" . urlencode($gedung_redirect) . "&pesan=tidak_ada_pilihan");
        exit();
    }

    $id_list = implode(',', $ids);
    $jumlah = count($ids);

    $queryData = mysqli_query($koneksi, "
        SELECT id_loker FROM barang
        WHERE id_barang IN ($id_list) AND id_siswa = '$id_user' AND status = 'Tersimpan'
    ");
    
    $loker_terpengaruh = [];
    while ($data = mysqli_fetch_assoc($queryData)) {
        if (!empty($data['id_loker'])) {
            $loker_terpengaruh[] = (int)$data['id_loker'];
        }
    }

    if ($aksi === 'verifikasi' && !empty($loker_terpengaruh)) {
        mysqli_query($koneksi, "
            UPDATE barang 
            SET status = 'Sudah diambil', waktu_pengambilan = NOW()
            WHERE id_barang IN ($id_list) AND id_siswa = '$id_user' AND status = 'Tersimpan'
        ");

        $id_aktor = dapatkanIdUserAktif($koneksi, 'admin');
        mysqli_query($koneksi, "
            INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu)
            VALUES ($id_aktor, 'Ubah', NULL, NOW())
        ");

        foreach (array_unique($loker_terpengaruh) as $id_lkr) {
            perbaruiStatusLoker($koneksi, $id_lkr);
        }

        header("Location: indexBarang.php?gedung=" . urlencode($gedung_redirect) . "&pesan=sukses_verifikasi_massal&total=$jumlah");
        exit();
    }

    header("Location: indexBarang.php?gedung=" . urlencode($gedung_redirect));
    exit();
}

$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);
$nama_lengkap = $data_user['nama_lengkap'] ?? 'Siswa';
$inisial = strtoupper(substr(trim($nama_lengkap), 0, 1));
$foto_user = $data_user['foto'] ?? '';
$page_title = 'Data Barang - ' . $gedung;

$queryLoker = mysqli_query($koneksi, "
    SELECT l.id_loker, l.nomor_loker, l.status, g.nama_gedung 
    FROM loker l 
    JOIN gedung g ON l.id_gedung = g.id_gedung 
    WHERE g.nama_gedung = '$gedung' AND l.status = 'Kosong'
    ORDER BY l.nomor_loker ASC
");

$daftarBarang = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol", "Lainnya"];

$q_count = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as semua,
        SUM(b.status = 'Tersimpan') as tersimpan,
        SUM(b.status = 'Sudah diambil') as diambil
    FROM barang b
    JOIN loker l ON b.id_loker = l.id_loker
    JOIN gedung g ON l.id_gedung = g.id_gedung
    WHERE b.id_siswa = '$id_user' AND g.nama_gedung = '$gedung'
");
$hitung = mysqli_fetch_assoc($q_count);
$total_semua = (int)($hitung['semua'] ?? 0);
$total_tersimpan = (int)($hitung['tersimpan'] ?? 0);
$total_diambil = (int)($hitung['diambil'] ?? 0);

$kata_kunci = trim($_GET['cari'] ?? '');
$filter_status = $_GET['status'] ?? '';
$where = "WHERE b.id_siswa = '$id_user' AND g.nama_gedung = '$gedung'";

if ($kata_kunci !== '') {
    $cari = mysqli_real_escape_string($koneksi, $kata_kunci);
    $where .= " AND (b.nama_barang LIKE '%$cari%' OR l.nomor_loker LIKE '%$cari%')";
}

if (in_array($filter_status, ['Tersimpan', 'Sudah diambil'])) {
    $status_esc = mysqli_real_escape_string($koneksi, $filter_status);
    $where .= " AND b.status = '$status_esc'";
} else {
    $filter_status = '';
}

$sql = "
    SELECT b.*, l.nomor_loker, g.nama_gedung AS lokasi
    FROM barang b
    LEFT JOIN loker l ON b.id_loker = l.id_loker
    LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
    $where
    ORDER BY b.id_barang DESC
";
$hasilData = mysqli_query($koneksi, $sql);

include __DIR__ . '/header.php';
?>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Penitipan Barang - <?= htmlspecialchars($gedung); ?></h2>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="bukaModalTambah()">Tambah Barang</button>
        </div>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <div id="notifikasiAlert">
            <?php $p = $_GET['pesan']; $total = (int)($_GET['total'] ?? 0); ?>
            <?php if ($p == 'sukses_tambah'): ?>
                <div class="alert alert-success">Data barang berhasil ditambahkan ke loker.</div>
            <?php elseif ($p == 'error'): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['teks'] ?? 'Terjadi kesalahan pada data input.') ?></div>
            <?php elseif ($p == 'sukses_ambil'): ?>
                <div class="alert alert-success">Status barang berhasil diubah menjadi Sudah diambil.</div>
            <?php elseif ($p == 'sukses_verifikasi_massal'): ?>
                <div class="alert alert-success">Berhasil memverifikasi <strong><?= $total ?></strong> barang terpilih sudah diambil.</div>
            <?php elseif ($p == 'tidak_ada_pilihan'): ?>
                <div class="alert alert-danger">Silakan pilih minimal 1 barang terlebih dahulu dengan mencentang kotak.</div>
            <?php elseif ($p == 'akses_ditolak'): ?>
                <div class="alert alert-danger">Akses ditolak: Siswa hanya dapat menambahkan barang dan memverifikasi pengambilan.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="indexBarang.php" class="search-form" onsubmit="return false;">
                <input type="hidden" name="gedung" value="<?= htmlspecialchars($gedung) ?>">
                <input type="text" id="inputCariBarang" name="cari" class="search-input" placeholder="Cari barang atau nomor loker..." value="<?= htmlspecialchars($kata_kunci) ?>" autocomplete="off" style="width: 270px;">
            </form>

            <?php 
            $paramGedung = '?gedung=' . urlencode($gedung);
            $paramCari = ($kata_kunci !== '') ? '&cari=' . urlencode($kata_kunci) : ''; 
            ?>
            <div class="filter-tabs">
                <a href="indexBarang.php<?= $paramGedung ?><?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == '') ? 'active' : '' ?>">
                    Semua <span class="tab-count">(<?= $total_semua ?>)</span>
                </a>
                <a href="indexBarang.php<?= $paramGedung ?>&status=Tersimpan<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Tersimpan') ? 'active' : '' ?>">
                    Tersimpan <span class="tab-count">(<?= $total_tersimpan ?>)</span>
                </a>
                <a href="indexBarang.php<?= $paramGedung ?>&status=Sudah diambil<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Sudah diambil') ? 'active' : '' ?>">
                    Sudah Diambil <span class="tab-count">(<?= $total_diambil ?>)</span>
                </a>
            </div>
        </div>

        <form method="POST" action="indexBarang.php" id="formAksiMassal">
            <input type="hidden" name="gedung_asal" value="<?= htmlspecialchars($gedung) ?>">

            <div class="bulk-action-bar">
                <div class="bulk-select-area">
                    <label class="label-select-all">
                        <input type="checkbox" id="checkAll" onchange="pilihSemua(this)">
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
                                <input type="checkbox" id="checkAllHead" onchange="pilihSemua(this)" title="Pilih Semua" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
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
                                $ada_foto = (!empty($row['foto_barang']) && file_exists(__DIR__ . '/' . $path_foto));
                            ?>
                            <tr class="baris-barang" data-nama="<?= htmlspecialchars(mb_strtolower($row['nama_barang'], 'UTF-8')) ?>" data-loker="<?= htmlspecialchars(mb_strtolower($row['nomor_loker'] ?? '', 'UTF-8')) ?>">
                                <td style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                    <?php if ($row['status'] === 'Tersimpan'): ?>
                                        <input type="checkbox" name="id_barang[]" value="<?= $row['id_barang'] ?>" class="item-checkbox" onchange="hitungPilihan()" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                                    <?php else: ?>
                                        <span style="color: #D8D2C7;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                                <td style="text-align: center;">
                                    <?php if ($ada_foto): ?>
                                        <a href="javascript:void(0);" onclick="lihatFotoModal('<?= htmlspecialchars($path_foto) ?>', '<?= htmlspecialchars(addslashes($row['nama_barang'])) ?>')" style="display: inline-block;">
                                            <img src="<?= htmlspecialchars($path_foto) ?>" alt="Foto" class="thumb-img">
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
                                        <a href="verificationBarang.php?id=<?= $row['id_barang'] ?>&gedung=<?= urlencode($gedung) ?>" class="btn btn-sm btn-primary">Verifikasi</a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #8C867D; font-weight: 600;">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="barisKosongPhp">
                                <td colspan="9" style="text-align: center; padding: 35px; color: #6E6A63;">
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

<div class="modal-bg" id="modalTambahBarang">
    <div class="modal-box" style="width: 580px; max-width: 95%;">
        <h3><i class="fa-solid fa-box-archive"></i> Form Titip Barang (<?= htmlspecialchars($gedung); ?>)</h3>
        
        <form action="saveBarang.php" method="POST" enctype="multipart/form-data" id="formModalBarang">
            <input type="hidden" name="gedung_asal" value="<?= htmlspecialchars($gedung) ?>">
            <input type="hidden" name="id_siswa" value="<?= $id_user ?>">

            <div class="form-group">
                <label>Siswa Pemilik:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($nama_lengkap) ?> (NIS: <?= htmlspecialchars($data_user['username'] ?? '-') ?>)" readonly style="background-color: #F7F4EE; cursor: not-allowed; color: #1E1E1E; font-weight: 500;">
            </div>

            <div class="form-group">
                <label for="id_loker_modal">Pilih Loker Kosong di <?= htmlspecialchars($gedung); ?>:</label>
                <select name="id_loker" id="id_loker_modal" class="form-control" required style="cursor: pointer;">
                    <option value="" disabled selected>-- Pilih Loker --</option>
                    <?php if ($queryLoker && mysqli_num_rows($queryLoker) > 0): ?>
                        <?php while ($lkr = mysqli_fetch_assoc($queryLoker)): ?>
                            <option value="<?= $lkr['id_loker'] ?>">Loker <?= htmlspecialchars($lkr['nomor_loker']) ?></option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada loker kosong</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_barang_modal">Foto Barang Bawaan:</label>
                <input type="file" name="foto_barang" id="foto_barang_modal" class="form-control" accept="image/*" required>
            </div>

            <hr class="divider">

            <div class="form-group">
                <label>Daftar Barang Bawaan:</label>
                <div id="daftarBarangModal">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="barang-item-box">
                            <label>Barang <?= $i ?> (Wajib):</label>
                            <select name="pilihan_barang[]" class="form-control" onchange="ubahPilihan(this)" required>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($daftarBarang as $b): ?>
                                    <option value="<?= $b ?>"><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nama_barang_custom[]" class="form-control input-custom" placeholder="Ketik nama barang..." oninput="validasiCustom(this)">
                            <small class="pesan-error-custom" style="color: #B8473E; font-size: 11px; display: none; margin-top: 4px; font-weight: bold;"></small>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarang()">Tambah Barang</button>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambah()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-bg" id="modalFotoPreview">
    <div class="modal-box" style="text-align: center; width: 450px;">
        <h3 id="modalFotoJudul"><i class="fa-solid fa-image"></i> Foto Barang</h3>
        <div style="margin: 15px 0;">
            <img id="modalFotoImg" src="" alt="Foto Barang" style="max-width: 100%; max-height: 380px; border-radius: 6px; border: 1px solid #E8E3D9; object-fit: contain;">
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="btn btn-secondary" onclick="tutupFotoModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
const barangStandar = ["tas", "jaket", "helm", "laptop", "sepatu", "buku", "botol"];

function bukaModalTambah() {
    const modal = document.getElementById('modalTambahBarang');
    if (modal) modal.style.display = 'flex';
}

function tutupModalTambah() {
    const modal = document.getElementById('modalTambahBarang');
    if (modal) modal.style.display = 'none';
}

function ubahPilihan(select) {
    const box = select.closest('.barang-item-box');
    const input = box.querySelector('.input-custom');
    const pesan = box.querySelector('.pesan-error-custom');

    if (select.value === 'Lainnya') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
        validasiCustom(input);
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
        input.style.borderColor = '';
        pesan.style.display = 'none';
    }

    updateDropdown();
}

function updateDropdown() {
    const semuaSelect = document.querySelectorAll('#daftarBarangModal select[name="pilihan_barang[]"]');
    const terpilih = [];

    semuaSelect.forEach(select => {
        if (select.value && select.value !== 'Lainnya') {
            terpilih.push(select.value);
        }
    });

    semuaSelect.forEach(select => {
        Array.from(select.options).forEach(option => {
            const sudahDipilih = terpilih.includes(option.value);
            option.disabled = sudahDipilih && option.value !== select.value;
            option.hidden = option.disabled;
        });
    });
}

function validasiCustom(input) {
    const box = input.closest('.barang-item-box');
    const select = box.querySelector('select');
    const pesan = box.querySelector('.pesan-error-custom');
    const nama = input.value.trim();
    const namaKecil = nama.toLowerCase();
    let error = '';

    if (select.value !== 'Lainnya') return true;

    if (nama.length < 3 || nama.length > 50) {
        error = 'Nama barang harus 3 sampai 50 karakter.';
    } else if (!/^[a-zA-Z0-9\s-]+$/.test(nama)) {
        error = 'Gunakan huruf, angka, spasi, dan tanda hubung (-).';
    } else if ((nama.match(/[a-zA-Z]/g) || []).length < 3) {
        error = 'Nama barang harus memiliki minimal 3 huruf.';
    } else if (/(.)\1{2,}/i.test(nama)) {
        error = 'Tidak boleh mengulang karakter 3 kali berturut-turut.';
    }

    if (!error) {
        for (const item of barangStandar) {
            if (namaKecil.includes(item)) {
                error = 'Nama barang sudah tersedia pada pilihan barang.';
                break;
            }
        }
    }

    if (!error) {
        document.querySelectorAll('#daftarBarangModal .barang-item-box').forEach(item => {
            if (item !== box) {
                const inputLain = item.querySelector('.input-custom');
                if (inputLain) {
                    const namaLain = inputLain.value.trim().toLowerCase();
                    if (namaLain && (namaKecil === namaLain || namaKecil.includes(namaLain) || namaLain.includes(namaKecil))) {
                        error = 'Nama barang tidak boleh sama dengan barang lainnya.';
                    }
                }
            }
        });
    }

    input.style.borderColor = error ? '#B8473E' : '#181715';
    pesan.textContent = error;
    pesan.style.display = error ? 'block' : 'none';
    return error === '';
}

function tambahBarang() {
    const daftar = document.getElementById('daftarBarangModal');
    const box = daftar.firstElementChild.cloneNode(true);
    const select = box.querySelector('select');
    const input = box.querySelector('.input-custom');
    const pesan = box.querySelector('.pesan-error-custom');

    box.querySelector('label').outerHTML = `
        <div class="toolbar" style="margin-bottom: 5px;">
            <label class="judul-tambahan" style="margin: 0;"></label>
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusBarang(this)">Hapus</button>
        </div>`;

    select.value = '';
    select.required = false;
    select.options[0].text = 'Pilih Barang (Opsional)';
    input.value = '';
    input.style.display = 'none';
    input.style.borderColor = '';
    pesan.textContent = '';
    pesan.style.display = 'none';

    daftar.appendChild(box);
    urutkanNomor();
    updateDropdown();
}

function hapusBarang(button) {
    button.closest('.barang-item-box').remove();
    urutkanNomor();
    updateDropdown();
}

function urutkanNomor() {
    document.querySelectorAll('#daftarBarangModal .judul-tambahan').forEach((label, index) => {
        label.textContent = `Barang ${index + 4} (Tambahan):`;
    });
}

const formModal = document.getElementById('formModalBarang');
if (formModal) {
    formModal.addEventListener('submit', function(event) {
        const semuaInput = formModal.querySelectorAll('.input-custom');
        for (const input of semuaInput) {
            if (!validasiCustom(input)) {
                event.preventDefault();
                input.focus();
                alert('Periksa kembali nama barang yang diisi.');
                return;
            }
        }
    });
}

function checkboxTerlihat() {
    return Array.from(document.querySelectorAll('.item-checkbox')).filter(checkbox => {
        return checkbox.closest('tr').style.display !== 'none';
    });
}

function pilihSemua(master) {
    checkboxTerlihat().forEach(checkbox => {
        checkbox.checked = master.checked;
    });
    hitungPilihan();
}

function hitungPilihan() {
    const semua = checkboxTerlihat();
    const terpilih = semua.filter(checkbox => checkbox.checked).length;
    const semuaTerpilih = semua.length > 0 && terpilih === semua.length;

    const jumlEl = document.getElementById('jumlahTerpilih');
    if (jumlEl) jumlEl.textContent = terpilih;
    
    const checkAll = document.getElementById('checkAll');
    const checkAllHead = document.getElementById('checkAllHead');
    if (checkAll) checkAll.checked = semuaTerpilih;
    if (checkAllHead) checkAllHead.checked = semuaTerpilih;
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
                (baris.dataset.loker || '').includes(kata);

            baris.style.display = cocok ? '' : 'none';
            if (cocok) {
                jumlahTampil++;
            } else {
                const checkbox = baris.querySelector('.item-checkbox');
                if (checkbox) checkbox.checked = false;
            }
        });

        if (barisKosong) {
            barisKosong.style.display = (jumlahTampil === 0 && barisBarang.length > 0) ? '' : 'none';
        }
        hitungPilihan();
    }

    inputCari.addEventListener('input', filterBarang);
    inputCari.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') event.preventDefault();
    });

    if (inputCari.value.trim() !== '') filterBarang();
}

function lihatFotoModal(src, judul) {
    const modal = document.getElementById('modalFotoPreview');
    const img = document.getElementById('modalFotoImg');
    const title = document.getElementById('modalFotoJudul');
    if (modal && img) {
        img.src = src;
        if (title) title.innerHTML = '<i class="fa-solid fa-image"></i> ' + judul;
        modal.style.display = 'flex';
    }
}

function tutupFotoModal() {
    const modal = document.getElementById('modalFotoPreview');
    if (modal) modal.style.display = 'none';
}

const notifikasi = document.getElementById('notifikasiAlert');
if (notifikasi) {
    setTimeout(() => {
        notifikasi.style.display = 'none';
    }, 4000);
}

<?php if (isset($_GET['open']) && $_GET['open'] === 'modal'): ?>
document.addEventListener('DOMContentLoaded', function() {
    bukaModalTambah();
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/footer.php'; ?>

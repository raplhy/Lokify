<?php
require_once "connectBarang.php";

$id_user = (int)($_SESSION['id_user'] ?? 0);
$role = $_SESSION['role'] ?? '';

$semuaSiswa = [];
$querySemuaSiswa = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'siswa' ORDER BY nama_lengkap ASC");
if ($querySemuaSiswa) {
    while ($s = mysqli_fetch_assoc($querySemuaSiswa)) {
        $semuaSiswa[] = $s;
    }
}

if ($role === 'siswa' && $id_user > 0) {
    $querySiswa = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users
        WHERE id_user = '$id_user' AND role = 'siswa'");
    $siswa = mysqli_fetch_assoc($querySiswa);
} else {
    $siswa = !empty($semuaSiswa) ? $semuaSiswa[0] : null;
}

$id_siswa = (int)($siswa['id_user'] ?? 0);
$nama_siswa = $siswa['nama_lengkap'] ?? 'Siswa';
$nis_siswa = $siswa['username'] ?? '-';

$queryLoker = mysqli_query($koneksi, "SELECT l.id_loker, l.nomor_loker, l.status, g.nama_gedung 
    FROM loker l 
    LEFT JOIN gedung g ON l.id_gedung = g.id_gedung 
    ORDER BY l.nomor_loker ASC");
$daftarBarang = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol", "Lainnya"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Barang</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Tambah Penitipan Barang</h2>
        </div>
        <div>
            <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <?php if (($_GET['pesan'] ?? '') === 'error'): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['teks'] ?? 'Terjadi kesalahan pada data input.') ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form action="saveBarang.php" method="POST" enctype="multipart/form-data" id="formBarang">

            <div class="form-group">
                <label for="id_siswa_select">Siswa Pemilik:</label>
                <?php if ($role === 'siswa'): ?>
                    <select id="id_siswa_select" class="form-control" disabled style="background-color: #F7F4EE; cursor: not-allowed; color: #1E1E1E; font-weight: 500;">
                        <option value="<?= $id_siswa ?>" selected>
                            <?= htmlspecialchars($nama_siswa) ?> (NIS: <?= htmlspecialchars($nis_siswa) ?>)
                        </option>
                    </select>
                    <input type="hidden" name="id_siswa" value="<?= $id_siswa ?>">
                <?php else: ?>
                    <select name="id_siswa" id="id_siswa_select" class="form-control" required>
                        <option value="">Pilih Siswa</option>
                        <?php foreach ($semuaSiswa as $sw): ?>
                            <option value="<?= $sw['id_user'] ?>" <?= ($sw['id_user'] == $id_siswa) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sw['nama_lengkap']) ?> (NIS: <?= htmlspecialchars($sw['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="id_loker">Loker Tujuan:</label>
                <select name="id_loker" id="id_loker" class="form-control" required>
                    <option value="">Pilih Loker</option>
                    <?php if ($queryLoker): while ($l = mysqli_fetch_assoc($queryLoker)): ?>
                        <option value="<?= $l['id_loker'] ?>">
                            Loker <?= htmlspecialchars($l['nomor_loker']) ?> - <?= htmlspecialchars($l['nama_gedung'] ?? '') ?> (<?= htmlspecialchars($l['status']) ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_barang">Foto Barang Bawaan:</label>
                <input type="file" name="foto_barang" id="foto_barang" class="form-control" accept="image/*" required>
            </div>

            <hr class="divider">

            <div class="form-group">
                <label>Daftar Barang Bawaan:</label>
                <div id="daftarBarang">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="barang-item-box">
                            <label>Barang <?= $i ?> (Wajib):</label>
                            <select name="pilihan_barang[]" class="form-control" onchange="ubahPilihan(this)" required>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($daftarBarang as $barang): ?>
                                    <option value="<?= $barang ?>"><?= $barang ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nama_barang_custom[]" class="form-control input-custom" placeholder="Ketik nama barang..." oninput="validasiCustom(this)">
                            <small class="pesan-error-custom" style="color: #B8473E; font-size: 11px; display: none; margin-top: 4px; font-weight: bold;"></small>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarang()">Tambah Barang</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Data Barang</button>
                <a href="indexBarang.php" class="btn btn-secondary">Batal</a>
            </div>

        </form>
    </div>
</div>

<script>
const barangStandar = ["tas", "jaket", "helm", "laptop", "sepatu", "buku", "botol"];

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
    const semuaSelect = document.querySelectorAll('select[name="pilihan_barang[]"]');
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
        document.querySelectorAll('.barang-item-box').forEach(item => {
            if (item !== box) {
                const namaLain = item.querySelector('.input-custom').value.trim().toLowerCase();
                if (namaLain && (namaKecil === namaLain || namaKecil.includes(namaLain) || namaLain.includes(namaKecil))) {
                    error = 'Nama barang tidak boleh sama dengan barang lainnya.';
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
    const daftar = document.getElementById('daftarBarang');
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
    document.querySelectorAll('.judul-tambahan').forEach((label, index) => {
        label.textContent = `Barang ${index + 4} (Tambahan):`;
    });
}

document.getElementById('formBarang').addEventListener('submit', function(event) {
    const semuaInput = document.querySelectorAll('.input-custom');

    for (const input of semuaInput) {
        if (!validasiCustom(input)) {
            event.preventDefault();
            input.focus();
            alert('Periksa kembali nama barang yang diisi.');
            return;
        }
    }
});

updateDropdown();
</script>

</body>
</html>
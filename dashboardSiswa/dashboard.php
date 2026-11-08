<?php
session_start();
require_once "../login/koneksi.php";

// ==========================================
// 1. SATPAM LOGIN
// ==========================================
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ==========================================
// 2. PROSES SIMPAN EDIT PROFIL (JIKA ADA POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    
    // Ambil data lama dulu untuk pembanding
    $q_lama = mysqli_query($koneksi, "SELECT email, no_hp, foto FROM users WHERE id_user = '$id_user'");
    $data_lama = mysqli_fetch_assoc($q_lama);

    $email         = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $no_hp         = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    
    $update_foto   = ""; 
    $foto_diupload = false;

    // Cek apakah ada foto baru yang di-upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
        $folder_upload = "uploads/";
        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0777, true);
        }

        $nama_file = time() . '_' . basename($_FILES["foto_profil"]["name"]);
        $target_file = $folder_upload . $nama_file;
        $ekstensi = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $update_foto = ", foto = '$nama_file'"; 
                $foto_diupload = true;
            }
        }
    }

    // LOGIKA PESAN NOTIFIKASI YANG LEBIH CERDAS & SPESIFIK
    $pesan_sukses = [];
    
    // Cek apakah email berubah
    if (!empty($email) && $email !== $data_lama['email']) {
        $pesan_sukses[] = "Email berhasil ditambahkan/diperbarui";
    }
    
    // Cek apakah no_hp berubah
    if (!empty($no_hp) && $no_hp !== $data_lama['no_hp']) {
        $pesan_sukses[] = "No. WhatsApp berhasil ditambahkan/diperbarui";
    }

    // Cek apakah password diisi
    if (!empty($password_baru)) {
        $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update_pass = ", password = '$pass_hash'";
        $pesan_sukses[] = "Password berhasil diubah";
    } else {
        $update_pass = "";
    }

    // Cek apakah foto baru diupload
    if ($foto_diupload) {
        $pesan_sukses[] = "Foto profil berhasil diperbarui";
    }

    // Jika tidak ada yang diubah sama sekali
    if (empty($pesan_sukses)) {
        $pesan_sukses[] = "Profil berhasil diperbarui";
    }

    // Gabungkan pesan dengan enter (untuk ditampilkan)
    $pesan_final = implode(", ", $pesan_sukses);

    // Susun Query
    $query = "UPDATE users SET email = '$email', no_hp = '$no_hp' $update_pass $update_foto WHERE id_user = '$id_user'";

    // Eksekusi dan set notifikasi
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash_success'] = $pesan_final;
    } else {
        $_SESSION['flash_error'] = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
    }

    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 3. AMBIL DATA USER & DATA GEDUNG
// ==========================================
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);

$nama_lengkap = $data_user['nama_lengkap'] ?? 'Siswa';
$role         = $_SESSION['role'] ?? 'siswa';
$inisial      = strtoupper(substr(trim($nama_lengkap), 0, 1));
$foto_user    = $data_user['foto'] ?? ''; 

$gedung_aktif = isset($_GET['gedung']) ? $_GET['gedung'] : 'Gedung A';
$kode_gedung_prefix = strtoupper(trim(str_replace('Gedung', '', $gedung_aktif)));
$page_title = 'Dashboard Siswa - ' . $gedung_aktif;

include __DIR__ . '/header.php';
?>

<!-- NOTIFIKASI SUKSES / GAGAL (Diberi ID agar bisa dihilangkan JS) -->
<?php if (isset($_SESSION['flash_success'])): ?>
    <div id="notif-success" style="background: #FDF3E7; color: #B85D19; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: bold; border: 1px solid #E5A970; transition: opacity 0.5s ease;">
        <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['flash_success']; ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div id="notif-error" style="background: #F9E2DF; color: #B8473E; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: bold; border: 1px solid #DFA69F; transition: opacity 0.5s ease;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= $_SESSION['flash_error']; ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- 3 KARTU STATISTIK -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
        <div class="stat-data">
            <h4>Barang Tersimpan</h4>
            <h2>-</h2>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
        <div class="stat-data">
            <h4>Sisa Loker Kosong</h4>
            <h2>-</h2>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
            <h4>Sudah Diambil</h4>
            <h2>-</h2>
        </div>
    </div>
</div>

<!-- TABEL -->
<div class="panel">
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div class="panel-title">Daftar Barang Tersimpan - <?php echo htmlspecialchars($gedung_aktif); ?></div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="dashboard.php" class="search-box" onsubmit="event.preventDefault();">
                <input type="hidden" name="gedung" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
                <input type="text" name="search" class="search-input" placeholder="Cari barang / loker...">
                <button type="submit" class="btn-primary" style="padding: 8px 12px;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button type="button" class="btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> + Titip Barang</button>
        </div>
    </div>

    <table class="table-custom">
        <thead>
            <tr>
                <th>NO</th>
                <th>NAMA BARANG</th>
                <th>KETERANGAN</th>
                <th>NO. LOKER</th>
                <th>TANGGAL SIMPAN</th>
                <th>STATUS</th>
                <th>AKSI</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align: center; color: #9E988E; padding: 30px;">Belum ada data barang.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- MODAL 1: TITIP BARANG -->
<div class="modal-bg" id="modalInput">
    <div class="modal-box">
        <h3><i class="fa-solid fa-box-archive"></i> Form Titip Barang (<?php echo htmlspecialchars($gedung_aktif); ?>)</h3>
        <form onsubmit="event.preventDefault();">
            <input type="hidden" name="gedung" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
            <div class="form-group">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" class="form-input" placeholder="Misal: Helm KYT / Tas Laptop">
            </div>
            <div class="form-group">
                <label>Nomor Loker Kosong di <?php echo htmlspecialchars($gedung_aktif); ?> (1-140)</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: bold; font-size: 14px; background: #EFECE4; color: #1E1D1B; padding: 7px 12px; border-radius: 6px; border: 1px solid #D8D2C7;">
                        <?php echo $kode_gedung_prefix; ?>
                    </span>
                    <input type="number" name="no_loker" class="form-input" placeholder="Contoh: 1" min="1" max="140">
                </div>
                <div class="loker-info-box">
                    <strong><i class="fa-solid fa-info-circle"></i> Daftar Loker Kosong:</strong><br>
                    <span>Data loker akan tampil setelah integrasi backend.</span>
                </div>
            </div>
            <div class="form-group">
                <label>Keterangan / Ciri Barang (Opsional)</label>
                <input type="text" name="keterangan" class="form-input" placeholder="Misal: Warna hitam, casing gambar anime">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: PROFIL (DENGAN FITUR PREVIEW FOTO) -->
<div class="modal-bg" id="modalProfile">
    <div class="modal-box">
        <h3><i class="fa-solid fa-user-gear"></i> Pengaturan Profil Saya</h3>
        
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="avatar-circle" style="width: 70px; height: 70px; margin: 0 auto 10px; font-size: 24px; overflow: hidden;">
                    <img id="preview-foto" src="<?= (!empty($foto_user) && file_exists('uploads/' . $foto_user)) ? 'uploads/' . htmlspecialchars($foto_user) : '' ?>" 
                         style="width:100%; height:100%; object-fit:cover; display: <?= (!empty($foto_user) && file_exists('uploads/' . $foto_user)) ? 'block' : 'none' ?>;">
                    
                    <span id="preview-inisial" style="display: <?= (!empty($foto_user) && file_exists('uploads/' . $foto_user)) ? 'none' : 'block' ?>;">
                        <?php echo $inisial; ?>
                    </span>
                </div>
                
                <label style="cursor: pointer; font-size: 12px; color: #C85A17; font-weight: 600;">
                    <i class="fa-solid fa-camera"></i> Ubah Foto Profil
                    <input type="file" name="foto_profil" id="foto_profil" accept="image/*" style="display: none;">
                </label>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-input" value="<?php echo htmlspecialchars($nama_lengkap); ?>" readonly style="background: #FAF8F5; cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($data_user['email'] ?? ''); ?>" placeholder="alamat@email.com">
            </div>
            <div class="form-group">
                <label>No. WhatsApp / Telepon</label>
                <input type="text" name="no_hp" class="form-input" value="<?php echo htmlspecialchars($data_user['no_hp'] ?? ''); ?>" placeholder="08123456789">
            </div>
            <div class="form-group">
                <label>Password Baru (Kosongkan jika tidak ingin diubah)</label>
                <input type="password" name="password_baru" class="form-input" placeholder="Ketik password baru..." autocomplete="new-password">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModalProfile()">Batal</button>
                <button type="submit" name="update_profil" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: RIWAYAT -->
<div class="modal-bg" id="modalHistory">
    <div class="modal-box modal-large">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penitipan Barang</h3>
        <div style="overflow-x: auto; max-height: 400px;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NAMA BARANG</th>
                        <th>GEDUNG</th>
                        <th>NO. LOKER</th>
                        <th>TGL SIMPAN</th>
                        <th>TGL AMBIL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9E988E; padding: 20px;">Belum ada data riwayat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModalHistory()">Tutup</button>
        </div>
    </div>
</div>

<!-- SCRIPT UNTUK PREVIEW FOTO & HILANGKAN NOTIFIKASI OTOMATIS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Script Preview Foto
    const inputFoto = document.getElementById('foto_profil');
    if (inputFoto) {
        inputFoto.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImg = document.getElementById('preview-foto');
                    const previewInisial = document.getElementById('preview-inisial');
                    
                    if (previewImg && previewInisial) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                        previewInisial.style.display = 'none';
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Script Hilangkan Notifikasi Otomatis Setelah 3 Detik
    const notifSuccess = document.getElementById('notif-success');
    const notifError = document.getElementById('notif-error');

    if (notifSuccess) {
        setTimeout(function() {
            notifSuccess.style.opacity = '0'; 
            setTimeout(() => notifSuccess.remove(), 500); 
        }, 3000); 
    }

    if (notifError) {
        setTimeout(function() {
            notifError.style.opacity = '0';
            setTimeout(() => notifError.remove(), 500);
        }, 3000);
    }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
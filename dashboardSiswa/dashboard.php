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
$gedung_aktif = isset($_GET['gedung']) ? $_GET['gedung'] : 'Gedung A';

// ==========================================
// 2. PROSES SIMPAN TITIP BARANG
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_barang'])) {
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang'] ?? '');
    $id_loker    = mysqli_real_escape_string($koneksi, $_POST['id_loker'] ?? '');
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

    if (!empty($nama_barang) && !empty($id_loker)) {
        $nama_final = $nama_barang;
        if (!empty($keterangan)) {
            $nama_final .= " (" . $keterangan . ")";
        }

        $query_insert = "INSERT INTO barang (id_siswa, id_loker, nama_barang, status, waktu_penitipan) 
                         VALUES ('$id_user', '$id_loker', '$nama_final', 'Tersimpan', NOW())";
        
        if (mysqli_query($koneksi, $query_insert)) {
            mysqli_query($koneksi, "UPDATE loker SET status = 'Terisi' WHERE id_loker = '$id_loker'");
            $_SESSION['flash_success'] = 'Barang berhasil dititipkan!';
        } else {
            $_SESSION['flash_error'] = 'Gagal menitipkan barang: ' . mysqli_error($koneksi);
        }
    } else {
        $_SESSION['flash_error'] = 'Nama barang dan Loker wajib diisi!';
    }
    header("Location: dashboard.php?gedung=" . urlencode($_POST['gedung_asal']));
    exit;
}

// ==========================================
// 3. PROSES AMBIL BARANG
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil_barang'])) {
    $id_barang = mysqli_real_escape_string($koneksi, $_POST['id_barang'] ?? '');
    
    $q_brg = mysqli_query($koneksi, "SELECT id_loker FROM barang WHERE id_barang = '$id_barang' AND id_siswa = '$id_user'");
    $data_brg = mysqli_fetch_assoc($q_brg);
    
    if ($data_brg) {
        $id_loker = $data_brg['id_loker'];
        mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW() WHERE id_barang = '$id_barang'");
        mysqli_query($koneksi, "UPDATE loker SET status = 'Kosong' WHERE id_loker = '$id_loker'");
        $_SESSION['flash_success'] = 'Barang berhasil diambil! Loker sekarang kosong.';
    } else {
        $_SESSION['flash_error'] = 'Data barang tidak ditemukan.';
    }
    
    header("Location: dashboard.php?gedung=" . urlencode($_POST['gedung_asal']));
    exit;
}

// ==========================================
// 4. PROSES SIMPAN EDIT PROFIL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $q_lama = mysqli_query($koneksi, "SELECT email, no_hp, foto FROM users WHERE id_user = '$id_user'");
    $data_lama = mysqli_fetch_assoc($q_lama);

    $email         = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $no_hp         = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    
    $update_foto   = ""; 
    $foto_diupload = false;

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

    $pesan_sukses = [];
    
    if (!empty($email) && $email !== $data_lama['email']) {
        $pesan_sukses[] = "Email berhasil ditambahkan/diperbarui";
    }
    if (!empty($no_hp) && $no_hp !== $data_lama['no_hp']) {
        $pesan_sukses[] = "No. WhatsApp berhasil ditambahkan/diperbarui";
    }
    if (!empty($password_baru)) {
        $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update_pass = ", password = '$pass_hash'";
        $pesan_sukses[] = "Password berhasil diubah";
    } else {
        $update_pass = "";
    }
    if ($foto_diupload) {
        $pesan_sukses[] = "Foto profil berhasil diperbarui";
    }
    if (empty($pesan_sukses)) {
        $pesan_sukses[] = "Profil berhasil diperbarui";
    }

    $pesan_final = implode(", ", $pesan_sukses);
    $query = "UPDATE users SET email = '$email', no_hp = '$no_hp' $update_pass $update_foto WHERE id_user = '$id_user'";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash_success'] = $pesan_final;
    } else {
        $_SESSION['flash_error'] = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
    }

    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 5. AMBIL DATA USER
// ==========================================
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);

$nama_lengkap = $data_user['nama_lengkap'] ?? 'Siswa';
$role         = $_SESSION['role'] ?? 'siswa';
$inisial      = strtoupper(substr(trim($nama_lengkap), 0, 1));
$foto_user    = $data_user['foto'] ?? ''; 

$kode_gedung_prefix = strtoupper(trim(str_replace('Gedung', '', $gedung_aktif)));
$page_title = 'Dashboard Siswa - ' . $gedung_aktif;

// ==========================================
// 6. LOGIKA STATISTIK
// ==========================================
$q_stored = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang b JOIN loker l ON b.id_loker = l.id_loker JOIN gedung g ON l.id_gedung = g.id_gedung WHERE b.id_siswa = '$id_user' AND b.status = 'Tersimpan' AND g.nama_gedung = '$gedung_aktif'");
$stat_stored = mysqli_fetch_assoc($q_stored)['total'];

$q_empty = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loker l JOIN gedung g ON l.id_gedung = g.id_gedung WHERE l.status = 'Kosong' AND g.nama_gedung = '$gedung_aktif'");
$stat_empty = mysqli_fetch_assoc($q_empty)['total'];

$q_taken = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang b JOIN loker l ON b.id_loker = l.id_loker JOIN gedung g ON l.id_gedung = g.id_gedung WHERE b.id_siswa = '$id_user' AND b.status = 'Sudah diambil' AND g.nama_gedung = '$gedung_aktif'");
$stat_taken = mysqli_fetch_assoc($q_taken)['total'];

// ==========================================
// 7. LOGIKA PENCARIAN TABEL
// ==========================================
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$query_tabel_str = "
    SELECT b.*, l.nomor_loker 
    FROM barang b
    JOIN loker l ON b.id_loker = l.id_loker
    JOIN gedung g ON l.id_gedung = g.id_gedung
    WHERE b.id_siswa = '$id_user' 
    AND g.nama_gedung = '$gedung_aktif'
";

if (!empty($search_keyword)) {
    $keyword_escaped = mysqli_real_escape_string($koneksi, $search_keyword);
    $query_tabel_str .= " AND (b.nama_barang LIKE '%$keyword_escaped%' OR l.nomor_loker LIKE '%$keyword_escaped%')";
}

$query_tabel_str .= " ORDER BY b.waktu_penitipan DESC";
$query_tabel = mysqli_query($koneksi, $query_tabel_str);

// ==========================================
// 8. LOGIKA RIWAYAT PENITIPAN (FIX: SEMUA GEDUNG)
// ==========================================
// Query ini TIDAK memfilter berdasarkan gedung_aktif. 
// Jadi dia narik semua riwayat siswa dari Gedung A, B, C, dan D.
$query_riwayat = mysqli_query($koneksi, "
    SELECT b.*, l.nomor_loker, g.nama_gedung 
    FROM barang b
    JOIN loker l ON b.id_loker = l.id_loker
    JOIN gedung g ON l.id_gedung = g.id_gedung
    WHERE b.id_siswa = '$id_user' 
    AND b.status = 'Sudah diambil'
    ORDER BY b.waktu_pengambilan DESC
");

include __DIR__ . '/header.php';
?>

<!-- NOTIFIKASI SUKSES / GAGAL -->
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
            <h2><?= $stat_stored ?></h2>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
        <div class="stat-data">
            <h4>Sisa Loker Kosong</h4>
            <h2><?= $stat_empty ?></h2>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
            <h4>Sudah Diambil</h4>
            <h2><?= $stat_taken ?></h2>
        </div>
    </div>
</div>

<!-- TABEL DENGAN DATA DINAMIS & SEARCH -->
<div class="panel">
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div class="panel-title">Daftar Barang Tersimpan - <?php echo htmlspecialchars($gedung_aktif); ?></div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="dashboard.php" class="search-box" style="margin: 0;">
                <input type="hidden" name="gedung" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
                <input type="text" name="search" class="search-input" placeholder="Cari barang / loker..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit" class="btn-primary" style="padding: 8px 12px;" title="Cari"><i class="fa-solid fa-magnifying-glass"></i></button>
                
                <?php if (!empty($search_keyword)): ?>
                    <a href="dashboard.php?gedung=<?php echo urlencode($gedung_aktif); ?>" class="btn-secondary" style="padding: 8px 12px; display: flex; align-items: center; justify-content: center;" title="Hapus Pencarian">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
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
            <?php 
            if (mysqli_num_rows($query_tabel) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($query_tabel)) {
                    
                    $nama_tampil = $row['nama_barang'];
                    $keterangan_tampil = "-";
                    
                    if (preg_match('/^(.*)\s\((.*)\)$/', $row['nama_barang'], $matches)) {
                        $nama_tampil = trim($matches[1]);
                        $keterangan_tampil = trim($matches[2]);
                    }
            ?>
                <tr>
                    <td style="text-align: center; color: #6E6A63;"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($nama_tampil) ?></strong></td>
                    <td style="color: #9E988E;"><?= htmlspecialchars($keterangan_tampil) ?></td>
                    <td><span class="badge badge-kode" style="background: #EFECE4; padding: 4px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($row['nomor_loker']) ?></span></td>
                    <td style="color: #6E6A63; font-size: 12px;"><?= date('d/m/Y H:i', strtotime($row['waktu_penitipan'])) ?></td>
                    <td>
                        <?php if ($row['status'] === 'Tersimpan'): ?>
                            <span class="badge badge-tersimpan" style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;">Tersimpan</span>
                        <?php else: ?>
                            <span class="badge badge-diambil" style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;">Sudah diambil</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Tersimpan'): ?>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin mengambil barang ini? Loker akan menjadi kosong kembali.');">
                                <input type="hidden" name="gedung_asal" value="<?= htmlspecialchars($gedung_aktif) ?>">
                                <input type="hidden" name="id_barang" value="<?= $row['id_barang'] ?>">
                                <button type="submit" name="ambil_barang" class="btn-primary" style="font-size: 11px; padding: 5px 10px; background: #dc2626; border-color: #dc2626;">Ambil</button>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 11px; color: #9E988E; font-style: italic;">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
                } 
            } else { 
            ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #9E988E; padding: 30px;">
                        <?php if (!empty($search_keyword)): ?>
                            Data barang dengan kata kunci "<strong><?= htmlspecialchars($search_keyword) ?></strong>" tidak ditemukan.
                        <?php else: ?>
                            Belum ada data barang.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- MODAL 1: TITIP BARANG -->
<div class="modal-bg" id="modalInput">
    <div class="modal-box">
        <h3><i class="fa-solid fa-box-archive"></i> Form Titip Barang (<?php echo htmlspecialchars($gedung_aktif); ?>)</h3>
        
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="gedung_asal" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
            
            <div class="form-group">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" class="form-input" placeholder="Misal: Helm KYT / Tas Laptop" required>
            </div>
            
            <div class="form-group">
                <label>Pilih Loker Kosong di <?php echo htmlspecialchars($gedung_aktif); ?></label>
                <select name="id_loker" class="form-input" required style="cursor: pointer;">
                    <option value="" disabled selected>-- Pilih Loker --</option>
                    <?php
                    $query_loker = mysqli_query($koneksi, "
                        SELECT loker.id_loker, loker.nomor_loker 
                        FROM loker 
                        JOIN gedung ON loker.id_gedung = gedung.id_gedung 
                        WHERE gedung.nama_gedung = '$gedung_aktif' 
                        AND loker.status = 'Kosong'
                        ORDER BY loker.nomor_loker ASC
                    ");
                    
                    if (mysqli_num_rows($query_loker) > 0) {
                        while ($lkr = mysqli_fetch_assoc($query_loker)) {
                            echo "<option value='" . $lkr['id_loker'] . "'>Loker " . $lkr['nomor_loker'] . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Tidak ada loker kosong</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Keterangan / Ciri Barang (Opsional)</label>
                <input type="text" name="keterangan" class="form-input" placeholder="Misal: Warna hitam, casing gambar anime">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" name="simpan_barang" class="btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: PROFIL -->
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
                <input type="text" name="no_hp" class="form-input" value="<?= htmlspecialchars($data_user['no_hp'] ?? ''); ?>" placeholder="08123456789">
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

<!-- MODAL 3: RIWAYAT (SUDAH GLOBAL SEMUA GEDUNG) -->
<div class="modal-bg" id="modalHistory">
    <div class="modal-box modal-large">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penitipan Barang (Seluruh Gedung)</h3>
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
                    <?php 
                    if (mysqli_num_rows($query_riwayat) > 0) {
                        $no_riw = 1;
                        while ($riw = mysqli_fetch_assoc($query_riwayat)) {
                            
                            $nama_riw = $riw['nama_barang'];
                            if (preg_match('/^(.*)\s\((.*)\)$/', $riw['nama_barang'], $match_riw)) {
                                $nama_riw = trim($match_riw[1]);
                            }
                    ?>
                        <tr>
                            <td style="text-align: center; color: #6E6A63;"><?= $no_riw++ ?></td>
                            <td><strong><?= htmlspecialchars($nama_riw) ?></strong></td>
                            <td><?= htmlspecialchars($riw['nama_gedung']) ?></td>
                            <td><span class="badge badge-kode" style="background: #EFECE4; padding: 4px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($riw['nomor_loker']) ?></span></td>
                            <td style="color: #6E6A63; font-size: 12px;"><?= date('d/m/Y H:i', strtotime($riw['waktu_penitipan'])) ?></td>
                            <td style="color: #6E6A63; font-size: 12px;"><?= date('d/m/Y H:i', strtotime($riw['waktu_pengambilan'])) ?></td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9E988E; padding: 20px;">Belum ada data riwayat penitipan yang selesai.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModalHistory()">Tutup</button>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function() {
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
<?php
require_once "koneksi.php";
require_once "fungsi.php";

$halaman_aktif = 'setelan';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';

$tanggal_hari_ini = tanggalIndonesia();

$tab = $_GET['tab'] ?? 'profil';
if (!in_array($tab, ['profil', 'keamanan', 'siswa'])) {
    $tab = 'profil';
}

$id_admin_session = dapatkanIdAdminAktif($koneksi);
$username_admin   = $_SESSION['username'] ?? 'admin';

$admin_db = [
    'id_user'      => $id_admin_session,
    'nama_lengkap' => $nama_admin,
    'username'     => $username_admin,
    'password'     => ''
];

if ($koneksi) {
    $qAdm = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username, password FROM users WHERE id_user = $id_admin_session OR role = 'admin' ORDER BY (id_user = $id_admin_session) DESC LIMIT 1");
    if ($qAdm && $rAdm = mysqli_fetch_assoc($qAdm)) {
        $admin_db = $rAdm;
        $id_admin_session = (int)$admin_db['id_user'];
        $nama_admin = $admin_db['nama_lengkap'];
        $username_admin = $admin_db['username'];
        $_SESSION['id_user']      = $id_admin_session;
        $_SESSION['nama_lengkap'] = $nama_admin;
        $_SESSION['username']     = $username_admin;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sub_aksi = $_POST['sub_aksi'] ?? '';

    if ($sub_aksi === 'profil' && $koneksi) {
        $nama_input = trim($_POST['nama_lengkap'] ?? '');

        if ($nama_input === '') {
            header("Location: setelan.php?tab=profil&pesan=error_nama_kosong");
            exit();
        }

        $nama_clean = mysqli_real_escape_string($koneksi, $nama_input);
        mysqli_query($koneksi, "UPDATE users SET nama_lengkap = '$nama_clean' WHERE id_user = $id_admin_session");
        $_SESSION['nama_lengkap'] = $nama_input;

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin_session, 'Ubah', NULL, NOW())");

        header("Location: setelan.php?tab=profil&pesan=sukses_profil");
        exit();
    }

    if ($sub_aksi === 'keamanan' && $koneksi) {
        $pass_lama = $_POST['password_lama'] ?? '';
        $pass_baru = $_POST['password_baru'] ?? '';
        $pass_konf = $_POST['konfirmasi_password'] ?? '';

        $valid_lama = password_verify($pass_lama, $admin_db['password'])
                   || md5($pass_lama) === $admin_db['password']
                   || $pass_lama === $admin_db['password'];

        if (!$valid_lama) {
            header("Location: setelan.php?tab=keamanan&pesan=error_password_lama");
            exit();
        }

        if (strlen($pass_baru) < 6) {
            header("Location: setelan.php?tab=keamanan&pesan=error_password_pendek");
            exit();
        }

        if ($pass_baru !== $pass_konf) {
            header("Location: setelan.php?tab=keamanan&pesan=error_password_beda");
            exit();
        }

        $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password = '$hash_baru' WHERE id_user = $id_admin_session");

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin_session, 'Ubah', NULL, NOW())");

        header("Location: setelan.php?tab=keamanan&pesan=sukses_password");
        exit();
    }

    if ($sub_aksi === 'tambah_siswa' && $koneksi) {
        $nama_siswa = trim($_POST['nama_siswa'] ?? '');
        $nis_siswa  = trim($_POST['nis_siswa'] ?? '');
        $pass_siswa = trim($_POST['password_siswa'] ?? '');

        if ($nama_siswa === '' || $nis_siswa === '' || $pass_siswa === '') {
            header("Location: setelan.php?tab=siswa&pesan=error_siswa_kosong");
            exit();
        }

        $nis_clean = mysqli_real_escape_string($koneksi, $nis_siswa);
        $cekNis = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$nis_clean'");
        if (mysqli_num_rows($cekNis) > 0) {
            header("Location: setelan.php?tab=siswa&pesan=error_nis_duplikat");
            exit();
        }

        $nama_clean = mysqli_real_escape_string($koneksi, $nama_siswa);
        $hash_siswa = password_hash($pass_siswa, PASSWORD_DEFAULT);

        mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama_clean', '$nis_clean', '$hash_siswa', 'siswa')");

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin_session, 'Tambah', NULL, NOW())");

        header("Location: setelan.php?tab=siswa&pesan=sukses_tambah_siswa");
        exit();
    }
}

if (isset($_GET['aksi']) && $koneksi) {
    $aksi = $_GET['aksi'];
    $id_target = (int)($_GET['id'] ?? 0);

    if ($aksi === 'reset_sandi_siswa' && $id_target > 0) {
        $hash_def = password_hash('123456', PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password = '$hash_def' WHERE id_user = $id_target AND role = 'siswa'");

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin_session, 'Ubah', NULL, NOW())");

        header("Location: setelan.php?tab=siswa&pesan=sukses_reset_siswa");
        exit();
    }

    if ($aksi === 'hapus_siswa' && $id_target > 0) {
        $cekBarangAktif = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_siswa = $id_target AND status = 'Tersimpan'");
        $jmlAktif = (int)(mysqli_fetch_assoc($cekBarangAktif)['total'] ?? 0);

        if ($jmlAktif > 0) {
            header("Location: setelan.php?tab=siswa&pesan=error_siswa_ada_barang");
            exit();
        }

        $cekSemuaBarang = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_siswa = $id_target");
        $jmlSemua = (int)(mysqli_fetch_assoc($cekSemuaBarang)['total'] ?? 0);

        if ($jmlSemua > 0) {
            header("Location: setelan.php?tab=siswa&pesan=error_siswa_ada_riwayat");
            exit();
        }

        mysqli_query($koneksi, "DELETE FROM users WHERE id_user = $id_target AND role = 'siswa'");

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ($id_admin_session, 'Hapus', NULL, NOW())");

        header("Location: setelan.php?tab=siswa&pesan=sukses_hapus_siswa");
        exit();
    }
}

$daftar_siswa = [];
if ($koneksi && $tab === 'siswa') {
    $qSiswa = mysqli_query($koneksi, "
        SELECT u.id_user, u.username, u.nama_lengkap,
               COUNT(b.id_barang) AS total_titip,
               SUM(CASE WHEN b.status = 'Tersimpan' THEN 1 ELSE 0 END) AS barang_aktif
        FROM users u
        LEFT JOIN barang b ON u.id_user = b.id_siswa
        WHERE u.role = 'siswa'
        GROUP BY u.id_user
        ORDER BY u.nama_lengkap ASC
    ");
    if ($qSiswa) {
        while ($rs = mysqli_fetch_assoc($qSiswa)) {
            $daftar_siswa[] = $rs;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Lokify</title>
    <link rel="stylesheet" href="styleAdmin.css">
</head>
<body>

<div class="layout">
    <?php include "sidebar.php"; ?>

    <main class="content">
        <div class="topbar-beranda">
            <div class="topbar-left">
                <h1 class="topbar-greeting">Pengaturan</h1>
                <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            </div>
            <div class="topbar-profile-circle" title="<?= htmlspecialchars($nama_admin) ?>">
                <img src="admin-avatar.jpg" alt="Admin">
            </div>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <?php $p = $_GET['pesan']; ?>
            <?php if ($p === 'sukses_profil'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Profil berhasil diperbarui.</span>
                </div>
            <?php elseif ($p === 'sukses_password'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Kata sandi berhasil diperbarui.</span>
                </div>
            <?php elseif ($p === 'sukses_tambah_siswa'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Akun siswa baru berhasil ditambahkan.</span>
                </div>
            <?php elseif ($p === 'sukses_reset_siswa'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Kata sandi siswa berhasil direset menjadi <strong>123456</strong>.</span>
                </div>
            <?php elseif ($p === 'sukses_hapus_siswa'): ?>
                <div class="alert-banner alert-banner-success">
                    <span>Akun siswa berhasil dihapus.</span>
                </div>
            <?php elseif ($p === 'error_nama_kosong'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Nama lengkap wajib diisi.</span>
                </div>
            <?php elseif ($p === 'error_password_lama'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Kata sandi saat ini salah.</span>
                </div>
            <?php elseif ($p === 'error_password_pendek'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Kata sandi baru minimal 6 karakter.</span>
                </div>
            <?php elseif ($p === 'error_password_beda'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Konfirmasi kata sandi tidak cocok.</span>
                </div>
            <?php elseif ($p === 'error_siswa_kosong'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Nama siswa, NIS, dan kata sandi wajib diisi.</span>
                </div>
            <?php elseif ($p === 'error_nis_duplikat'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>NIS / Username sudah terdaftar di sistem.</span>
                </div>
            <?php elseif ($p === 'error_siswa_ada_barang'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Tidak dapat menghapus siswa karena masih memiliki barang yang tersimpan di loker.</span>
                </div>
            <?php elseif ($p === 'error_siswa_ada_riwayat'): ?>
                <div class="alert-banner alert-banner-danger">
                    <span>Tidak dapat menghapus siswa karena memiliki riwayat data penitipan barang.</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="nav-tabs">
            <a href="setelan.php?tab=profil" class="tab-btn <?= ($tab === 'profil') ? 'active' : '' ?>">
                Profil Admin
            </a>
            <a href="setelan.php?tab=keamanan" class="tab-btn <?= ($tab === 'keamanan') ? 'active' : '' ?>">
                Kata Sandi
            </a>
            <a href="setelan.php?tab=siswa" class="tab-btn <?= ($tab === 'siswa') ? 'active' : '' ?>">
                Kelola Akun Siswa
            </a>
        </div>

        <?php if ($tab === 'profil'): ?>
            <div class="card" style="max-width: 580px;">
                <div class="card-header">
                    <h3>Profil Administrator</h3>
                </div>

                <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid #E8E3D9;">
                    <img src="admin-avatar.jpg" alt="Admin" style="width: 76px; height: 76px; border-radius: 50%; object-fit: cover; border: 2px solid #E8E3D9; flex-shrink: 0;">
                    <div>
                        <strong style="font-size: 16px; color: #181715; display: block; margin-bottom: 2px;"><?= htmlspecialchars($admin_db['nama_lengkap']) ?></strong>
                        <span style="font-size: 13px; color: #6E6A63;">@<?= htmlspecialchars($admin_db['username']) ?></span>
                    </div>
                </div>

                <form method="POST" action="setelan.php">
                    <input type="hidden" name="sub_aksi" value="profil">

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($admin_db['nama_lengkap']) ?>" required>
                        </div>

                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($admin_db['username']) ?>" readonly style="background-color: #F7F4EE; cursor: not-allowed; color: #9E988E;">
                        </div>

                        <div style="margin-top: 8px; padding-top: 16px; border-top: 1px solid #E8E3D9;">
                            <button type="submit" class="btn btn-primary">Simpan Profil</button>
                        </div>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'keamanan'): ?>
            <div class="card" style="max-width: 520px;">
                <div class="card-header">
                    <h3>Ganti Kata Sandi</h3>
                </div>

                <form method="POST" action="setelan.php">
                    <input type="hidden" name="sub_aksi" value="keamanan">

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Kata Sandi Saat Ini</label>
                            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan kata sandi lama" required>
                        </div>

                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>

                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi kata sandi baru" required>
                        </div>

                        <div style="margin-top: 8px; padding-top: 16px; border-top: 1px solid #E8E3D9;">
                            <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
                        </div>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'siswa'): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3>Tambah Akun Siswa Baru</h3>
                </div>

                <form method="POST" action="setelan.php">
                    <input type="hidden" name="sub_aksi" value="tambah_siswa">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: flex-end;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Nama Lengkap Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" placeholder="Nama siswa" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">NIS / Username Login</label>
                            <input type="text" name="nis_siswa" class="form-control" placeholder="Nomor Induk Siswa" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: bold; color: #6E6A63; margin-bottom: 6px;">Kata Sandi Awal</label>
                            <input type="password" name="password_siswa" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>

                    <div style="margin-top: 16px;">
                        <button type="submit" class="btn btn-primary">+ Daftarkan Siswa</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Daftar Akun Siswa (<?= count($daftar_siswa) ?>)</h3>
                </div>

                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 140px; text-align: center;">NIS / Username</th>
                                <th style="text-align: center;">Nama Lengkap Siswa</th>
                                <th style="width: 150px; text-align: center;">Barang di Loker</th>
                                <th style="width: 140px; text-align: center;">Total Penitipan</th>
                                <th style="width: 180px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_siswa)): ?>
                                <?php $ns = 1; foreach ($daftar_siswa as $s): ?>
                                    <tr>
                                        <td style="text-align: center; color: #9E988E;"><?= $ns++ ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-kode"><?= htmlspecialchars($s['username']) ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong><?= htmlspecialchars($s['nama_lengkap']) ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ((int)$s['barang_aktif'] > 0): ?>
                                                <span class="badge badge-tersimpan"><?= $s['barang_aktif'] ?> Barang</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">0 Barang</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center; color: #6E6A63;">
                                            <?= (int)$s['total_titip'] ?> kali
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <a href="setelan.php?tab=siswa&aksi=reset_sandi_siswa&id=<?= $s['id_user'] ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Reset kata sandi siswa <?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?> menjadi 123456?')" style="font-size: 11px; padding: 4px 8px;">Reset Sandi</a>
                                            <?php if ((int)$s['total_titip'] === 0): ?>
                                                <a href="setelan.php?tab=siswa&aksi=hapus_siswa&id=<?= $s['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus akun siswa <?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>?')" style="font-size: 11px; padding: 4px 8px; margin-left: 4px;">Hapus</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #9E988E; padding: 25px;">
                                        Belum ada data siswa terdaftar.
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
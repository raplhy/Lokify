    </div>
</div>

<div class="modal-bg" id="modalProfile">
    <div class="modal-box">
        <h3><i class="fa-solid fa-user-gear"></i> Pengaturan Profil Saya</h3>
        
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="avatar-circle" style="width: 70px; height: 70px; margin: 0 auto 10px; font-size: 24px; overflow: hidden;">
                    <img id="preview-foto" src="<?= (!empty($foto_user) && file_exists(__DIR__ . '/uploads/' . $foto_user)) ? 'uploads/' . htmlspecialchars($foto_user) : '' ?>" 
                         style="width:100%; height:100%; object-fit:cover; display: <?= (!empty($foto_user) && file_exists(__DIR__ . '/uploads/' . $foto_user)) ? 'block' : 'none' ?>;">
                    
                    <span id="preview-inisial" style="display: <?= (!empty($foto_user) && file_exists(__DIR__ . '/uploads/' . $foto_user)) ? 'none' : 'block' ?>;">
                        <?= htmlspecialchars($inisial ?? 'S') ?>
                    </span>
                </div>
                
                <label style="cursor: pointer; font-size: 12px; color: #C85A17; font-weight: 600;">
                    <i class="fa-solid fa-camera"></i> Ubah Foto Profil
                    <input type="file" name="foto_profil" id="foto_profil" accept="image/*" style="display: none;">
                </label>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($nama_lengkap ?? '') ?>" readonly style="background: #FAF8F5; cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($data_user['email'] ?? '') ?>" placeholder="alamat@email.com">
            </div>
            <div class="form-group">
                <label>No. WhatsApp / Telepon</label>
                <input type="text" name="no_hp" class="form-input" value="<?= htmlspecialchars($data_user['no_hp'] ?? '') ?>" placeholder="08123456789">
            </div>
            <div class="form-group">
                <label>Password Baru (Kosongkan jika tidak ingin diubah)</label>
                <input type="password" name="password_baru" class="form-input" placeholder="Ketik password baru..." autocomplete="new-password">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModalProfile()">Batal</button>
                <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
$query_riwayat_modal = false;
if (isset($koneksi) && !empty($id_user)) {
    $query_riwayat_modal = mysqli_query($koneksi, "
        SELECT b.*, l.nomor_loker, g.nama_gedung 
        FROM barang b
        JOIN loker l ON b.id_loker = l.id_loker
        JOIN gedung g ON l.id_gedung = g.id_gedung
        WHERE b.id_siswa = '$id_user' 
        AND b.status = 'Sudah diambil'
        ORDER BY b.waktu_pengambilan DESC
    ");
}
?>
<div class="modal-bg" id="modalHistory">
    <div class="modal-box modal-large">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penitipan Barang (Seluruh Gedung)</h3>
        <div style="overflow-x: auto; max-height: 400px;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th>NAMA BARANG</th>
                        <th>GEDUNG</th>
                        <th>NO. LOKER</th>
                        <th>TGL SIMPAN</th>
                        <th>TGL AMBIL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query_riwayat_modal && mysqli_num_rows($query_riwayat_modal) > 0): ?>
                        <?php $no_riw = 1; while ($riw = mysqli_fetch_assoc($query_riwayat_modal)): ?>
                            <tr>
                                <td style="text-align: center; color: #6E6A63;"><?= $no_riw++ ?></td>
                                <td><strong><?= htmlspecialchars($riw['nama_barang']) ?></strong></td>
                                <td><?= htmlspecialchars($riw['nama_gedung']) ?></td>
                                <td><span class="badge badge-kode" style="background: #EFECE4; padding: 4px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($riw['nomor_loker']) ?></span></td>
                                <td style="color: #6E6A63; font-size: 12px;"><?= date('d/m/Y H:i', strtotime($riw['waktu_penitipan'])) ?></td>
                                <td style="color: #6E6A63; font-size: 12px;"><?= !empty($riw['waktu_pengambilan']) ? date('d/m/Y H:i', strtotime($riw['waktu_pengambilan'])) : '-' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #9E988E; padding: 25px;">Belum ada data riwayat penitipan yang selesai.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModalHistory()">Tutup</button>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    const dropdown  = document.getElementById("userMenu");
    const container = document.getElementById("profileDropdownContainer");
    if (dropdown) dropdown.classList.toggle("show");
    if (container) container.classList.toggle("active");
}

function openModalProfile() {
    const m = document.getElementById('modalProfile');
    if (m) m.style.display = 'flex';
    const d = document.getElementById("userMenu");
    if (d) d.classList.remove("show");
    const c = document.getElementById("profileDropdownContainer");
    if (c) c.classList.remove("active");
}

function closeModalProfile() {
    const m = document.getElementById('modalProfile');
    if (m) m.style.display = 'none';
}

function openModalHistory() {
    const m = document.getElementById('modalHistory');
    if (m) m.style.display = 'flex';
}

function closeModalHistory() {
    const m = document.getElementById('modalHistory');
    if (m) m.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
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
                };
                reader.readAsDataURL(file);
            }
        });
    }

    window.addEventListener('click', function(event) {
        if (!event.target.closest('.profile-dropdown-container')) {
            const dropdown  = document.getElementById("userMenu");
            const container = document.getElementById("profileDropdownContainer");
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                if (container) container.classList.remove('active');
            }
        }
    });

    document.querySelectorAll('.modal-bg').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    });
});
</script>
</body>
</html>

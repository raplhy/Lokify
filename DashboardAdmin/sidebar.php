<?php
$halaman_aktif = $halaman_aktif ?? 'beranda';
$nama_admin    = $_SESSION['nama_lengkap'] ?? 'Administrator';
?>
<aside class="sidebar" id="sidebar">
    <script>
        if (localStorage.getItem('sidebar_admin_collapsed') === '1') {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    </script>
    <div class="logo">
        <button type="button" class="logo-btn" id="sidebarLogoBtn" title="Lokify">
            <img src="lokify-emblem.png" alt="Lokify Logo" class="logo-img">
            <div class="logo-text">
                <img src="logo-text.png" alt="Lokify" class="logo-wordmark" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <h2 style="display: none;">Lokify</h2>
            </div>
        </button>
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Tutup Sidebar" aria-label="Tutup Sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="nav-menu">
        <a href="beranda.php" class="menu <?= ($halaman_aktif === 'beranda') ? 'active' : '' ?>" title="Beranda">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span class="menu-label">Beranda</span>
        </a>
        <a href="databarang.php" class="menu <?= ($halaman_aktif === 'databarang') ? 'active' : '' ?>" title="Data Barang">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span class="menu-label">Data Barang</span>
        </a>
        <a href="dataloker.php" class="menu <?= ($halaman_aktif === 'dataloker') ? 'active' : '' ?>" title="Data Loker">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="12" y1="3" x2="12" y2="21"></line>
                <circle cx="8" cy="12" r="1.5"></circle>
                <circle cx="16" cy="12" r="1.5"></circle>
            </svg>
            <span class="menu-label">Data Loker</span>
        </a>
        <a href="laporan.php" class="menu <?= ($halaman_aktif === 'laporan') ? 'active' : '' ?>" title="Laporan">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span class="menu-label">Laporan</span>
        </a>
        <a href="setelan.php" class="menu <?= ($halaman_aktif === 'setelan') ? 'active' : '' ?>" title="Pengaturan">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span class="menu-label">Pengaturan</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-profile" title="<?= htmlspecialchars($nama_admin) ?>">
            <div class="profile-icon"><img src="admin-avatar.jpg" alt="Admin"></div>
            <div class="sidebar-profile-info">
                <strong><?= htmlspecialchars($nama_admin) ?></strong>
            </div>
        </div>
    </div>
</aside>

<script>
var sidebar = document.getElementById('sidebar');
var tombolTutup = document.getElementById('sidebarToggleBtn');
var tombolLogo = document.getElementById('sidebarLogoBtn');

function perbaruiJudulLogo() {
    tombolLogo.title = sidebar.classList.contains('collapsed') ? 'Buka Sidebar' : 'Lokify';
}

perbaruiJudulLogo();

tombolTutup.addEventListener('click', function() {
    sidebar.classList.add('collapsed');
    localStorage.setItem('sidebar_admin_collapsed', '1');
    perbaruiJudulLogo();
});

tombolLogo.addEventListener('click', function() {
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebar_admin_collapsed', '0');
        perbaruiJudulLogo();
    }
});
</script>

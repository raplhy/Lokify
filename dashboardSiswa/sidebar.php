<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_gedung = $_GET['gedung'] ?? 'Gedung A';
$kode_gedung = strtoupper(str_replace('Gedung', '', trim($current_gedung)));
?>

<aside class="sidebar" id="app-sidebar">

    <!-- BRAND + LOGO -->
    <div class="sidebar-brand">
        <img src="logoLOKIFY.png" alt="LOKIFY" class="brand-logo" onerror="this.style.display='none'">
        <div class="brand-text">
            LOKER <span>SEKOLAH</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">
            <div class="nav-section-title">UTAMA</div>

            <div class="nav-item-dropdown">
                <a href="javascript:void(0)" class="nav-link" id="toggle-dashboard" title="Dashboard Siswa">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span class="nav-text">Dashboard Siswa</span>
                    <i class="fa-solid fa-chevron-down nav-arrow" id="arrow-dashboard"></i>
                </a>
                <div class="nav-submenu" id="submenu-dashboard">
                    <a href="dashboard.php?gedung=Gedung A" class="nav-sublink <?php echo ($current_gedung === 'Gedung A') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-building"></i> <span class="nav-text">Gedung A</span>
                    </a>
                    <a href="dashboard.php?gedung=Gedung B" class="nav-sublink <?php echo ($current_gedung === 'Gedung B') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-building"></i> <span class="nav-text">Gedung B</span>
                    </a>
                    <a href="dashboard.php?gedung=Gedung C" class="nav-sublink <?php echo ($current_gedung === 'Gedung C') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-building"></i> <span class="nav-text">Gedung C</span>
                    </a>
                    <a href="dashboard.php?gedung=Gedung D" class="nav-sublink <?php echo ($current_gedung === 'Gedung D') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-building"></i> <span class="nav-text">Gedung D</span>
                    </a>
                </div>
            </div>

            <a href="javascript:void(0)" class="nav-link" onclick="openModalHistory()" title="Riwayat Penitipan">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="nav-text">Riwayat Penitipan</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">PENGATURAN</div>

            <a href="javascript:void(0)" class="nav-link" onclick="openModalProfile()" title="Pengaturan Profil">
                <i class="fa-solid fa-user-gear"></i>
                <span class="nav-text">Pengaturan Profil</span>
            </a>
        </div>

    </nav>

    <div class="sidebar-footer">
        SMK PGRI 3 Malang
    </div>

</aside>

<style>
/* ====== SIDEBAR UTAMA (TEMA CREAM/ABU RAFFA) ====== */
.sidebar {
    width: 260px;
    min-height: 100vh;
    background: #FAF8F5; 
    border-right: 1px solid #E8E3D9;
    color: #1E1D1B;
    display: flex;
    flex-direction: column;
    font-family: 'Roboto', sans-serif;
    flex-shrink: 0;
    transition: width 0.3s ease;
    overflow: hidden;
    z-index: 10;
}

/* ====== BRAND / LOGO ====== */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 20px;
    border-bottom: 1px solid #E8E3D9;
    white-space: nowrap;
    min-height: 76px;
}

.brand-logo {
    height: 40px;
    width: auto;
    object-fit: contain;
    flex-shrink: 0;
}

.brand-text {
    font-size: 15px;
    font-weight: 800;
    color: #1E1D1B;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    line-height: 1.2;
    white-space: nowrap;
}

.brand-text span { color: #C85A17; }

/* ====== NAV ====== */
.sidebar-nav {
    flex: 1;
    padding: 15px 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.nav-section { margin-bottom: 20px; }

.nav-section-title {
    font-size: 11px;
    font-weight: bold;
    color: #9E988E;
    padding: 8px 20px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    white-space: nowrap;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 20px;
    color: #6E6A63;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
    white-space: nowrap;
}

.nav-link i:first-child {
    width: 22px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
}

.nav-link:hover {
    background: #EFE5D8;
    color: #1E1D1B;
}

.nav-link.active {
    background: #EFE5D8;
    color: #C85A17;
    border-left-color: #E04F16;
    font-weight: bold;
}

.nav-arrow {
    margin-left: auto;
    font-size: 11px;
    color: #9E988E;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.nav-arrow.rotate {
    transform: rotate(180deg);
    color: #C85A17;
}

.nav-submenu {
    display: flex;
    flex-direction: column;
}

.nav-submenu.hidden {
    display: none;
}

.nav-sublink {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 20px 9px 45px;
    color: #6E6A63;
    text-decoration: none;
    font-size: 12.5px;
    transition: background 0.2s, color 0.2s;
    border-left: 3px solid transparent;
    white-space: nowrap;
}

.nav-sublink i {
    font-size: 12px;
    width: 14px;
    text-align: center;
    flex-shrink: 0;
}

.nav-sublink:hover {
    background: #EFE5D8;
    color: #1E1D1B;
}

.nav-sublink.active {
    background: #EFE5D8;
    color: #C85A17;
    border-left-color: #E04F16;
    font-weight: bold;
}

.sidebar-footer {
    padding: 15px 20px;
    font-size: 12px;
    color: #9E988E;
    border-top: 1px solid #E8E3D9;
    text-align: center;
    white-space: nowrap;
}

/* ==========================================================
   MODE COLLAPSED — HANYA LOGO + IKON
   ========================================================== */
.sidebar.collapsed {
    width: 72px;
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-section-title,
.sidebar.collapsed .nav-text,
.sidebar.collapsed .nav-arrow,
.sidebar.collapsed .sidebar-footer {
    display: none !important;
}

.sidebar.collapsed .nav-submenu {
    display: none !important;
}

.sidebar.collapsed .sidebar-brand {
    justify-content: center;
    padding: 18px 8px;
    gap: 0;
}

.sidebar.collapsed .brand-logo {
    height: 38px;
}

.sidebar.collapsed .nav-link,
.sidebar.collapsed .nav-sublink {
    justify-content: center;
    padding: 14px 0;
    gap: 0;
}

.sidebar.collapsed .nav-link i:first-child {
    font-size: 18px;
    width: auto;
}

.sidebar.collapsed .nav-link:hover {
    background: #EFE5D8;
    color: #C85A17;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('app-sidebar');
    const toggleDashboard = document.getElementById('toggle-dashboard');
    const submenu = document.getElementById('submenu-dashboard');
    const arrow = document.getElementById('arrow-dashboard');

    /* ===== Default: submenu terbuka ===== */
    arrow.classList.add('rotate');

    /* ===== Klik menu "Dashboard Siswa" ===== */
    toggleDashboard.addEventListener('click', function () {
        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            return;
        }
        submenu.classList.toggle('hidden');
        arrow.classList.toggle('rotate');
    });

    /* ==========================================================
       CARI TOMBOL HAMBURGER DI HEADER (GARIS 3)
       ========================================================== */
    const selectors = [
        '.hamburger-btn',
        '#toggle-sidebar',
        '[data-toggle="sidebar"]',
        '.menu-toggle',
        '.sidebar-toggle',
        '.burger-menu'
    ];

    let hamburger = null;
    for (let sel of selectors) {
        hamburger = document.querySelector(sel);
        if (hamburger) break;
    }

    // Kalau belum ketemu, cari lewat ikon fa-bars / fa-bars-staggered
    if (!hamburger) {
        const icon = document.querySelector('.fa-bars, .fa-bars-staggered, .fa-align-left');
        if (icon) {
            hamburger = icon.closest('button') || icon.closest('a') || icon.parentElement;
        }
    }

    // Pasang listener kalau ketemu
    if (hamburger) {
        hamburger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
        });
    } else {
        console.warn('⚠️ Tombol hamburger TIDAK DITEMUKAN. Cek header.php — pastikan tombol ☰ punya class/id yang benar.');
    }
});
</script>
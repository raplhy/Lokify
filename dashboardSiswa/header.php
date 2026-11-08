<?php
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - LOKIFY</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- TAMBAHAN: Script SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function konfirmasiLogoutSiswa() {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Anda akan keluar dari sistem LOKIFY!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#E04F16',
            cancelButtonColor: '#181715', /* DIUBAH: Warna Batal jadi hitam seperti Titip Barang */
            confirmButtonText: 'Ya, Keluar!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../login/logout.php';
            }
        });
    }
    </script>
    <!-- AKHIR TAMBAHAN -->

    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', 'Roboto', sans-serif; }
    
    :root {
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 70px;
        --accent-color: #E04F16;
        --accent-text: #ffffff;
        --bg-main: #F4EFE6;
        --text-main: #1E1D1B;
        --text-muted: #6E6A63;
        --border-color: #E8E3D9;
        --card-bg: #FFFFFF;
    }

    body { background-color: var(--bg-main); color: var(--text-main); display: flex; min-height: 100vh; }

    /* MAIN */
    .main-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    
    .top-header { 
        background: #FFFFFF; 
        padding: 12px 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        border-bottom: 1px solid var(--border-color); 
    }
    
    .page-title { font-size: 18px; font-weight: 700; color: var(--text-main); }
    
    .btn-toggle-sidebar { 
        background: #FAF8F5; 
        border: 1px solid var(--border-color); 
        font-size: 16px; 
        color: var(--text-main); 
        cursor: pointer; 
        padding: 6px 12px; 
        border-radius: 6px; 
        transition: all 0.2s; 
    }
    .btn-toggle-sidebar:hover { background-color: #EFE5D8; }

    /* PROFILE */
    .profile-dropdown-container { position: relative; }
    
    .profile-btn { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding: 6px 14px 6px 8px; 
        border-radius: 6px; 
        background: #FAF8F5; 
        border: 1px solid var(--border-color); 
        cursor: pointer; 
        user-select: none; 
    }
    .profile-btn:hover { background: #EFE5D8; }
    
    .avatar-circle { 
        width: 36px; 
        height: 36px; 
        border-radius: 50%; 
        background: #EFE5D8; 
        color: #C85A17; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 700; 
        font-size: 14px; 
        overflow: hidden; 
        flex-shrink: 0; 
    }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
    
    .user-details { text-align: left; }
    .user-name { font-size: 13px; font-weight: 700; color: var(--text-main); line-height: 1.1; }
    .role-badge { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .arrow-icon { font-size: 11px; color: var(--text-muted); margin-left: 2px; transition: transform 0.2s ease; }
    .profile-dropdown-container.active .arrow-icon { transform: rotate(180deg); }

    .dropdown-menu { 
        display: none; 
        position: absolute; 
        right: 0; 
        top: calc(100% + 8px); 
        width: 210px; 
        background: #FFFFFF; 
        border: 1px solid var(--border-color); 
        border-radius: 8px; 
        box-shadow: 0 10px 25px rgba(15, 35, 58, 0.1); 
        z-index: 100; 
        overflow: hidden; 
    }
    .dropdown-menu.show { display: block; }
    .dropdown-header { padding: 12px 16px; background: #FAF8F5; border-bottom: 1px solid var(--border-color); }
    .dropdown-header p { font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
    .dropdown-header strong { font-size: 13px; color: var(--text-main); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-size: 13px; color: var(--text-main); text-decoration: none; font-weight: 500; cursor: pointer; }
    .dropdown-item:hover { background: #FAF8F5; }
    .dropdown-item.logout-item { color: #B8473E; font-weight: 600; border-top: 1px solid var(--border-color); }
    .dropdown-item.logout-item:hover { background: #F9E2DF; }

    /* CONTENT */
    .content-area { padding: 25px 30px; }
    
    .stats-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
    .stat-card { 
        flex: 1; 
        min-width: 200px; 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 8px; 
        padding: 18px; 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }
    .stat-icon { 
        width: 45px; 
        height: 45px; 
        border-radius: 6px; 
        background: #FAF8F5; 
        border: 1px solid var(--border-color); 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 18px; 
        color: var(--text-main); 
        flex-shrink: 0; 
    }
    .stat-data h4 { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
    .stat-data h2 { font-size: 22px; font-weight: 800; color: var(--text-main); }

    .panel { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; }
    .panel-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .panel-title { font-size: 14px; font-weight: 700; color: var(--text-main); text-transform: uppercase; }

    .btn-primary { 
        background: #181715; 
        color: #FFFFFF; 
        border: 1px solid #181715; 
        padding: 9px 16px; 
        border-radius: 6px; 
        font-size: 13px; 
        font-weight: bold; 
        cursor: pointer; 
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        text-decoration: none; 
        transition: all 0.2s;
    }
    .btn-primary:hover { background: var(--accent-color); border-color: var(--accent-color); }
    
    .search-box { display: flex; gap: 8px; align-items: center; }
    .search-input { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; outline: none; background: #FAF8F5; }
    .search-input:focus { border-color: var(--accent-color); background: #FFF; }

    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { background: #FAF8F5; padding: 12px 20px; text-align: left; font-size: 12px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .table-custom td { padding: 12px 20px; font-size: 13px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }

    /* MODAL */
    .modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 99; }
    .modal-box { background: #FFFFFF; width: 450px; max-width: 90%; border-radius: 8px; padding: 20px; border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto; }
    .modal-box.modal-large { width: 800px; }
    .modal-box h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; display: flex; align-items: center; gap: 8px; }
    
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: var(--text-muted); }
    .form-input { width: 100%; padding: 9px 12px; border: 1px solid #D8D2C7; border-radius: 6px; font-size: 13px; outline: none; background: #FFF; color: var(--text-main); }
    .form-input:focus { border-color: var(--accent-color); }
    
    .loker-info-box { background: #FAF8F5; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px; font-size: 12px; color: var(--text-muted); margin-top: 6px; line-height: 1.5; max-height: 90px; overflow-y: auto; }
    .modal-footer { margin-top: 18px; display: flex; justify-content: flex-end; gap: 8px; }
    
    .btn-secondary { background: #EFECE4; color: var(--text-main); border: 1px solid #D8D2C7; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; }
    .btn-secondary:hover { background: #EFE5D8; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .main-wrapper { margin-left: 0 !important; }
        .stats-row { flex-direction: column; }
        .stat-card { min-width: 100%; }
        .modal-box, .modal-box.modal-large { width: 95% !important; }
        .content-area { padding: 15px; }
        .top-header { padding: 10px 15px; }
        .page-title { font-size: 14px; }
        .user-details { display: none; }
    }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="top-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <button type="button" class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="page-title"><?php echo htmlspecialchars($page_title); ?></div>
        </div>
        
        <div class="profile-dropdown-container" id="profileDropdownContainer">
            <div class="profile-btn" onclick="toggleDropdown()">
                <div class="avatar-circle">
                    <?php if (!empty($foto_user) && file_exists(__DIR__ . '/uploads/' . $foto_user)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($foto_user); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $inisial; ?>
                    <?php endif; ?>
                </div>
                
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($nama_lengkap); ?></div>
                    <div class="role-badge"><?php echo htmlspecialchars($role); ?></div>
                </div>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </div>

            <div class="dropdown-menu" id="userMenu">
                <div class="dropdown-header">
                    <p>Masuk sebagai</p>
                    <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong>
                </div>
                <a href="javascript:void(0);" onclick="openModalProfile()" class="dropdown-item">
                    <i class="fa-solid fa-id-card"></i> Edit Profil
                </a>
                <a href="javascript:void(0);" onclick="konfirmasiLogoutSiswa()" class="dropdown-item logout-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            </div>
        </div>
    </div>

    <div class="content-area">
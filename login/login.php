<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/functions.php';

// Kalau sudah login, otomatis redirect ke dashboard sesuai role
if (is_logged_in()) {
    if (is_admin()) {
        redirect('../DashboardAdmin/index.php');
    } else {
        redirect('../dashboardSiswa/dashboard.php');
    }
}

$error_msg   = get_flash('error');
$success_msg = get_flash('success');
$filled_username = $_SESSION['form_username'] ?? '';
unset($_SESSION['form_username']);
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Siswa - LOKIFY</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Roboto', sans-serif;
    }

    body {
        background-color: #f1f5f9;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .card-login {
        width: 100%;
        max-width: 450px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-header-custom {
        padding: 35px 20px 25px 20px;
        width: 100%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .card-header-custom img {
        height: 70px;
        width: auto;
        margin-bottom: 15px;
        display: block;
        object-fit: contain;
    }

    .card-header-custom h1 {
        font-size: 18px;
        font-weight: 800;
        color: #0f233a;
        line-height: 1.3;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        text-align: center;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .card-header-custom h1 span {
        color: #FF9149;
    }

    .role-title {
        width: 100%;
        padding: 12px;
        background: #fff7ed;
        border-top: 1px solid #fed7aa;
        border-bottom: 1px solid #fed7aa;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #FF9149;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .card-body-inner {
        width: 100%;
        padding: 25px 35px 30px 35px;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 10px;
        border-radius: 6px;
        font-size: 12px;
        margin-bottom: 15px;
        text-align: center;
    }

    .alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
        padding: 10px;
        border-radius: 6px;
        font-size: 12px;
        margin-bottom: 15px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 15px;
        width: 100%;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .input-group i {
        position: absolute;
        left: 12px;
        color: #94a3b8;
        font-size: 13px;
    }

    .input-control {
        width: 100%;
        padding: 10px 12px 10px 35px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        color: #1e293b;
        background: #f8fafc;
    }

    .input-control:focus {
        border-color: #FF9149;
        background: #ffffff;
    }

    .btn-submit {
        width: 100%;
        padding: 11px;
        background: #FF9149;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.2s;
    }

    .btn-submit:hover {
        opacity: 0.9;
    }

    .btn-register-gray {
        width: 100%;
        padding: 11px;
        background: #e2e8f0;
        color: #475569;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 8px;
        text-align: center;
        display: block;
        transition: background 0.2s;
        text-decoration: none;
    }

    .btn-register-gray:hover {
        background: #cbd5e1;
        color: #1e293b;
    }

    .card-footer-custom {
        width: 100%;
        padding: 15px 35px 25px 35px;
        text-align: center;
        font-size: 11px;
        color: #94a3b8;
    }

    </style>

</head>


<body>


<div class="card-login">


    <div class="card-header-custom">

        <img
            src="logoLOKIFY.png"
            alt="Logo LOKIFY"
            onerror="this.style.display='none'"
        >

        <h1>
            SISTEM PENDATAAN<br>
            <span>BARANG LOKER</span> SEKOLAH
        </h1>

    </div>


    <div class="role-title">

        <i class="fa-solid fa-user-graduate"></i>

        Akses Siswa

    </div>


    <div class="card-body-inner">


        <?php if (!empty($error_msg)): ?>

            <div class="alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error_msg); ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($success_msg)): ?>

            <div class="alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <?php echo htmlspecialchars($success_msg); ?>

            </div>

        <?php endif; ?>


        <!-- FORM LOGIN SISWA -->

        <form
            method="POST"
            action="login_process.php"
        >

            <input
                type="hidden"
                name="role"
                value="siswa"
            >


            <div class="form-group">

                <label>
                    Username / NISN
                </label>

                <div class="input-group">

                    <i class="fa-solid fa-user"></i>

                    <input
                        type="text"
                        name="username"
                        class="input-control"
                        placeholder="Masukkan Username / NISN"
                        value="<?php echo htmlspecialchars($filled_username); ?>"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <div class="input-group">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        name="password"
                        class="input-control"
                        placeholder="Masukkan Password"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn-submit"
            >
                Login Siswa
            </button>


            <a
                href="register.php"
                class="btn-register-gray"
            >
                Register Siswa Baru
            </a>

        </form>


    </div>


    <div class="card-footer-custom">

        &copy; 2026 SMK PGRI 3 Malang. All Rights Reserved.

    </div>


</div>


</body>

</html>
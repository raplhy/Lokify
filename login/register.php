<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register Siswa - LOKIFY</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">


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

    .card-register {
        width: 100%;
        max-width: 450px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .card-header-custom {
        padding: 35px 20px 25px 20px;
        width: 100%;
        text-align: center;
    }

    .card-header-custom img {
        height: 70px;
        width: auto;
        margin-bottom: 15px;
        object-fit: contain;
    }

    .card-header-custom h1 {
        font-size: 18px;
        font-weight: 800;
        color: #0f233a;
        line-height: 1.3;
        letter-spacing: 0.5px;
        text-transform: uppercase;
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
    }

    .card-body-inner {
        width: 100%;
        padding: 25px 35px 30px 35px;
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
    }

    .btn-submit:hover {
        opacity: 0.9;
    }

    .btn-login-gray {
        width: 100%;
        padding: 11px;
        background: #e2e8f0;
        color: #475569;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 8px;
        text-align: center;
        display: block;
        text-decoration: none;
    }

    .btn-login-gray:hover {
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
         timeOut: 3000
        };
    </script>

    <?php
    if (isset($_GET['error'])) {

        if ($_GET['error'] == 'username') {
            echo "<script>
                toastr.error('Username / NIS sudah terdaftar!', 'Registrasi Gagal');
            </script>";
        }

        if ($_GET['error'] == 'empty') {
            echo "<script>
                toastr.warning('Semua data harus diisi!', 'Peringatan');
            </script>";
        }

        if ($_GET['error'] == 'failed') {
            echo "<script>
                toastr.error('Registrasi gagal, silakan coba lagi!', 'Registrasi Gagal');
            </script>";
        }
    }
    ?>

</body>

<body>


<div class="card-register">


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

        <i class="fa-solid fa-user-plus"></i>

        Register Siswa Baru

    </div>


    <div class="card-body-inner">


        <form
            method="POST"
            action="register_process.php"
        >


            <div class="form-group">

                <label>
                    Nama Lengkap
                </label>

                <div class="input-group">

                    <i class="fa-solid fa-id-card"></i>

                    <input
                        type="text"
                        name="nama"
                        class="input-control"
                        placeholder="Masukkan nama lengkap"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Username / NIS
                </label>

                <div class="input-group">

                    <i class="fa-solid fa-user"></i>

                    <input
                        type="text"
                        name="username"
                        class="input-control"
                        placeholder="Masukkan Username / NIS"
                        pattern="[0-9/.]+"
                        inputmode="text"
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
                Daftar Akun Siswa
            </button>


            <a
                href="login.php"
                class="btn-login-gray"
            >
                Kembali Ke Login
            </a>


        </form>


    </div>

    <div class="card-footer-custom">

        &copy; 2026 SMK PGRI 3 Malang. All Rights Reserved.

    </div>


</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

<script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
</script>

<?php
if (isset($_GET['error'])) {

    if ($_GET['error'] == 'username') {
        echo "<script>
            toastr.error('Username / NIS sudah terdaftar!', 'Registrasi Gagal');
        </script>";
    }

    if ($_GET['error'] == 'empty') {
        echo "<script>
            toastr.warning('Semua data harus diisi!', 'Peringatan');
        </script>";
    }

    if ($_GET['error'] == 'failed') {
        echo "<script>
            toastr.error('Registrasi gagal, silakan coba lagi!', 'Registrasi Gagal');
        </script>";
    }
}
?>

</body>

</html>

</div>


</body>

</html>
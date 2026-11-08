<?php
include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nama_lengkap = $_POST["nama"];
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Cek apakah semua data sudah diisi
    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        header("Location: register.php?error=empty");
        exit;
    }

    // Cek username / NIS
    $cek = mysqli_query(
        $koneksi,
        "SELECT * FROM users WHERE username = '$username'"
    );

    if (mysqli_num_rows($cek) > 0) {
        header("Location: register.php?error=username");
        exit;
    }

    // Mengubah password menjadi hash
    $password = password_hash($password, PASSWORD_DEFAULT);

    // Menyimpan data
    $query = mysqli_query(
        $koneksi,
        "INSERT INTO users (username, password, nama_lengkap, role)
         VALUES ('$username', '$password', '$nama_lengkap', 'siswa')"
    );

    if ($query) {

        // Membuat session setelah berhasil register
        session_start();

        $_SESSION['id_user'] = mysqli_insert_id($koneksi);
        $_SESSION['username'] = $username;
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $_SESSION['role'] = 'siswa';

        // Langsung masuk dashboard siswa
        header("Location: ../dashboardSiswa/dashboard.php");
        exit;

    } else {

        // Jika registrasi gagal
        header("Location: register.php?error=failed");
        exit;
    }
}
?>
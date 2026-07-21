<?php
session_start();
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new database();
    $db = $database->getKoneksi();

    $email = trim($_POST['email']);
    $password = $_POST['password']; // Sesuaikan dengan atribut name di form login HTML-mu

    $query = "SELECT * FROM pengguna WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi kata sandi
        if (password_verify($password, $user['kata_sandi'])) {
            
            // Set Sesi Pengguna
            $_SESSION['id_pengguna'] = $user['id_pengguna'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['poin_loyalitas'] = $user['poin_loyalitas'];
            $_SESSION['peran'] = $user['peran']; // Mengambil dari kolom 'peran'

            // LOGIKA PENGATUR LALU LINTAS (RBAC)
            // LOGIKA PENGATUR LALU LINTAS (RBAC) TERPISAH
            if ($_SESSION['peran'] == 'pemilik') {
                echo "<script>alert('Selamat datang, Owner!'); window.location.href='/o2futsal/admin/dasbor_pemilik.php';</script>";
            } elseif ($_SESSION['peran'] == 'kasir') {
                echo "<script>alert('Selamat datang, Kasir!'); window.location.href='/o2futsal/admin/index.php';</script>";
            } elseif ($_SESSION['peran'] == 'karyawan') {
                echo "<script>alert('Selamat datang, Staf Lapangan!'); window.location.href='/o2futsal/admin/dasbor_karyawan.php';</script>";
            } else {
                echo "<script>alert('Login berhasil!'); window.location.href='/o2futsal/index.php';</script>";
            }
            exit;
        } else {
            echo "<script>alert('Kata sandi salah!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar!'); window.history.back();</script>";
    }
}
?>
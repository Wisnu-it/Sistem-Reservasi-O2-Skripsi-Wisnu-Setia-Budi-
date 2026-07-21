<?php
/**
 * File: admin/cek_akses.php
 * Deskripsi: Memblokir akses pelanggan biasa ke halaman admin
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Jika belum login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /o2futsal/login.php");
    exit;
}

// 2. Jika yang login adalah pelanggan biasa
if ($_SESSION['peran'] == 'pelanggan') {
    echo "<script>alert('AKSES DITOLAK! Area ini terbatas hanya untuk staf operasional dan manajemen O2 Futsal.'); window.location.href='/o2futsal/index.php';</script>";
    exit;
}
?>
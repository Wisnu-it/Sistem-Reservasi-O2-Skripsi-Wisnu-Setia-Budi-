<?php
/**
 * File: logout.php
 * Deskripsi: Membersihkan sesi pengguna dan mengembalikan ke halaman login
 */
session_start();
session_unset();    // Menghapus semua variabel sesi
session_destroy();  // Menghancurkan sesi sepenuhnya

// Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>
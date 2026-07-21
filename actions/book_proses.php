<?php
/**
 * File: actions/book_proses.php
 * Deskripsi: Skrip logika backend untuk memproses data dari checkout.php dan menyimpannya ke database
 */
session_start();

// Validasi keamanan: Wajib login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /o2futsal/login.php");
    exit;
}

// Cek apakah data dikirim via metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once "../config/database.php";
    require_once "../classes/Reservasi.php";

    $database = new database();
    $db = $database->getKoneksi();
    $reservasi = new reservasi($db);

    // 1. Tangkap Data dari Formulir
    $id_pengguna = $_SESSION['id_pengguna'];
    $id_lapangan = $_POST['id_lapangan'];
    $tanggal_sewa = $_POST['tanggal_sewa'];
    
    // Validasi jika jam belum dipilih
    if (empty($_POST['jam_main'])) {
        echo "<script>alert('Anda belum memilih jam main!'); window.location.href='/o2futsal/reservasi.php';</script>";
        exit;
    }
    
    $jam_main_array = $_POST['jam_main'];
    $subtotal = (int) $_POST['subtotal'];
    $metode_pembayaran = $_POST['metode_pembayaran'];

    // 2. Kalkulasi Potongan Poin (Jika tombol switch diaktifkan)
    $poin_dipakai = 0;
    $diskon = 0;
    
    if (isset($_POST['gunakan_poin']) && $_POST['gunakan_poin'] == '1') {
        $poin_dipakai = (int) $_SESSION['poin_loyalitas'];
        $diskon = $poin_dipakai; // Konversi 1 Poin = Rp 1
    }

    // 3. Kalkulasi Total Akhir
    $total_bayar = $subtotal - $diskon;
    if ($total_bayar < 0) {
        $total_bayar = 0; // Mencegah total bayar menjadi minus
    }

    // 4. Eksekusi Penyimpanan ke Database melalui Class Reservasi
    $id_reservasi_baru = $reservasi->buatReservasi(
        $id_pengguna, $id_lapangan, $tanggal_sewa, $jam_main_array, 
        $subtotal, $diskon, $poin_dipakai, $total_bayar, $metode_pembayaran
    );

    // 5. Alihkan pengguna secara mutlak agar tidak tersesat
    if ($id_reservasi_baru) {
        header("Location: /o2futsal/pembayaran.php?id=" . $id_reservasi_baru);
        exit;
    } else {
        echo "<script>alert('Terjadi kesalahan sistem saat memproses pesanan Anda. Pastikan slot jadwal tersedia dan silakan coba lagi.'); window.location.href='/o2futsal/reservasi.php';</script>";
        exit;
    }
} else {
    header("Location: /o2futsal/index.php");
    exit;
}
?>
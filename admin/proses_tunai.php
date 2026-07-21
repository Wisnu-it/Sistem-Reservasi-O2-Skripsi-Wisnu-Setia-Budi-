<?php
/**
 * File: admin/proses_tunai.php
 * Deskripsi: Mengeksekusi pembayaran menjadi LUNAS dan menyuntikkan Poin Loyalitas
 */
session_start();

// PERBAIKAN 1: Tambahkan "../" agar PHP keluar dulu dari folder admin untuk mencari folder config
require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION['id_pengguna'])) {
    die("Akses ditolak!");
}

// Tangkap ID Reservasi
$id_reservasi = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id_reservasi']) ? $_POST['id_reservasi'] : 0);

if ($id_reservasi == 0) {
    echo "<script>alert('Error: ID Pesanan tidak ditemukan!'); window.history.back();</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

try {
    // Mulai Kunci Transaksi Database
    $db->beginTransaction();

    // 1. Ubah status di tabel pembayaran menjadi 'lunas'
    $stmt_bayar = $db->prepare("UPDATE pembayaran SET status_pembayaran = 'lunas' WHERE id_reservasi = ?");
    $stmt_bayar->execute([$id_reservasi]);

    // 2. Ubah status di tabel reservasi menjadi 'dikonfirmasi'
    $stmt_res = $db->prepare("UPDATE reservasi SET status_reservasi = 'dikonfirmasi' WHERE id_reservasi = ?");
    $stmt_res->execute([$id_reservasi]);

    // ==============================================================
    // 3. LOGIKA CERDAS PENAMBAHAN POIN
    // ==============================================================
    
    // Ambil data total bayar dari reservasi
    $stmt_info = $db->prepare("
        SELECT r.total_bayar, p.id_pengguna 
        FROM reservasi r 
        JOIN pengguna p ON r.id_pengguna = p.id_pengguna 
        WHERE r.id_reservasi = ?
    ");
    $stmt_info->execute([$id_reservasi]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $poin_tambahan = 0;
    if ($info) {
        $total_bayar = (int)$info['total_bayar'];
        $id_pemesan = $info['id_pengguna'];

        // Semua pelanggan mendapatkan 1 Poin per Rp 10.000
        $poin_tambahan = floor($total_bayar / 10000);

        // Suntikkan poin ke database pelanggan
        if ($poin_tambahan > 0) {
            $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas + $poin_tambahan WHERE id_pengguna = $id_pemesan");
        }
    }

    // Kunci Permanen Perubahan Database
    $db->commit();

    // PERBAIKAN 2: Kembalikan Kasir ke halaman Data Transaksi, bukan ke Profil Pelanggan
    echo "<script>alert('Pembayaran Tunai Berhasil Diverifikasi! Pelanggan mendapatkan tambahan {$poin_tambahan} Poin Loyalitas.'); window.location.href='data_transaksi.php';</script>";

} catch (Exception $e) {
    $db->rollBack(); // Batalkan semua jika ada error
    echo "<script>alert('Gagal memproses pembayaran: " . $e->getMessage() . "'); window.history.back();</script>";
}
?>
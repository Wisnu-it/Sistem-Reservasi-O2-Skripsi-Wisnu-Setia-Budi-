<?php
/**
 * File: actions/update_lunas_lokal.php
 * Deskripsi: Trik Localhost untuk mengupdate status dari frontend Midtrans + INJEKSI POIN
 */
session_start();
require_once "../config/database.php";

if (isset($_GET['id'])) {
    $id_reservasi = (int)$_GET['id'];
    $database = new database();
    $db = $database->getKoneksi();
    
    try {
        $db->beginTransaction();
        
        // Cek data reservasi untuk mengambil total bayar dan memastikan belum lunas
        $stmt_cek = $db->prepare("SELECT status_reservasi, total_bayar, id_pengguna FROM reservasi WHERE id_reservasi = ?");
        $stmt_cek->execute([$id_reservasi]);
        $info = $stmt_cek->fetch(PDO::FETCH_ASSOC);

        // Hanya proses jika statusnya BUKAN dikonfirmasi/lunas (mencegah poin ganda jika user refresh page)
        if ($info && $info['status_reservasi'] != 'dikonfirmasi' && $info['status_reservasi'] != 'lunas') {
            
            // 1. Update tabel reservasi menjadi 'dikonfirmasi'
            $stmt1 = $db->prepare("UPDATE reservasi SET status_reservasi = 'dikonfirmasi' WHERE id_reservasi = ?");
            $stmt1->execute([$id_reservasi]);
            
            // 2. Update tabel pembayaran menjadi 'lunas'
            $stmt2 = $db->prepare("UPDATE pembayaran SET status_pembayaran = 'lunas' WHERE id_reservasi = ?");
            $stmt2->execute([$id_reservasi]);

            // 3. INJEKSI POIN LOYALITAS (1 Poin setiap kelipatan Rp 10.000)
            $total_bayar = (int)$info['total_bayar'];
            $id_pemesan = $info['id_pengguna'];
            $poin_tambahan = floor($total_bayar / 10000);

            if ($poin_tambahan > 0) {
                $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas + $poin_tambahan WHERE id_pengguna = $id_pemesan");
            }
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}

// Kembalikan pelanggan ke halaman profil (bukan index) agar langsung melihat status dan poinnya bertambah
echo "<script>alert('Pembayaran Berhasil! Pesanan Anda telah Dikonfirmasi dan Poin Loyalitas Anda bertambah.'); window.location.href='../profil.php';</script>";
exit;
?>
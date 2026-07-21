<?php
/**
 * File: actions/api_callback.php
 * Deskripsi: Webhook Midtrans dengan Fitur Injeksi Poin Loyalitas & Anti-Dobel
 */
require_once "../config/database.php";
require_once "../config/midtrans.php";

// 1. Menangkap data JSON dari Midtrans
$json_result = file_get_contents('php://input');
$data = json_decode($json_result, true);

if (!$data) {
    die("Akses ditolak. Berkas ini hanya menerima data JSON dari server Midtrans.");
}

// 2. Ekstrak data transaksi
$order_id = $data['order_id'];
$status_code = $data['status_code'];
$gross_amount = $data['gross_amount'];
$transaction_status = $data['transaction_status'];
$signature_key_dari_midtrans = $data['signature_key'];

// 3. Verifikasi Keamanan Signature Key
$kunci_rahasia = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
if ($kunci_rahasia !== $signature_key_dari_midtrans) {
    die("Akses Ditolak: Signature Key Tidak Valid!");
}

// 4. Memecah Order ID
$pecah_order_id = explode("-", $order_id);
$id_reservasi = $pecah_order_id[1]; 

$database = new database(); // Pastikan huruf awal "D" kapital sesuai deklarasi class-mu
$db = $database->getKoneksi();

try {
    // Mulai Kunci Transaksi Database
    $db->beginTransaction();

    // Cek Data Reservasi Saat Ini
    $stmt_cek = $db->prepare("SELECT status_reservasi, total_bayar, id_pengguna FROM reservasi WHERE id_reservasi = ?");
    $stmt_cek->execute([$id_reservasi]);
    $info = $stmt_cek->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("Reservasi tidak ditemukan.");
    }

    $status_sekarang = $info['status_reservasi'];
    $status_baru = $status_sekarang; // Set default

    // 5. Terjemahkan Laporan Status Midtrans
    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
        $status_baru = 'lunas';
        $status_pembayaran = 'lunas';
    } else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
        $status_baru = 'dibatalkan';
        $status_pembayaran = 'gagal';
    } else if ($transaction_status == 'pending') {
        $status_baru = 'menunggu';
    }

    // ==============================================================
    // 6. EKSEKUSI DATABASE & POIN (HANYA JIKA STATUS BERUBAH)
    // Ini mencegah poin ganda jika Midtrans mengirim notif lunas 2x
    // ==============================================================
    if ($status_baru != $status_sekarang) {
        
        // A. Update Status Reservasi
        $stmt = $db->prepare("UPDATE reservasi SET status_reservasi = :status WHERE id_reservasi = :id");
        $stmt->execute([':status' => $status_baru, ':id' => $id_reservasi]);
        
        // B. Update Status Pembayaran (Jika lunas/gagal)
        if (isset($status_pembayaran)) {
            $stmt2 = $db->prepare("UPDATE pembayaran SET status_pembayaran = :status WHERE id_reservasi = :id");
            $stmt2->execute([':status' => $status_pembayaran, ':id' => $id_reservasi]);
        }

        // C. INJEKSI POIN LOYALITAS (Khusus jika status berubah menjadi lunas)
        if ($status_baru == 'lunas') {
            $total_bayar = (int)$info['total_bayar'];
            $id_pemesan = $info['id_pengguna'];
            
            // Rumus: Setiap Rp 10.000 dapat 1 Poin
            $poin_tambahan = floor($total_bayar / 10000);

            if ($poin_tambahan > 0) {
                $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas + $poin_tambahan WHERE id_pengguna = $id_pemesan");
            }
        }
    }

    // Terapkan semua perubahan ke database
    $db->commit();
    
    // Kirim respons sukses ke server Midtrans
    http_response_code(200);
    echo "Webhook sukses. Status: " . $status_baru;

} catch (Exception $e) {
    $db->rollBack(); // Batalkan jika ada error
    http_response_code(500);
    echo "Terjadi kesalahan pada database: " . $e->getMessage();
}
?>
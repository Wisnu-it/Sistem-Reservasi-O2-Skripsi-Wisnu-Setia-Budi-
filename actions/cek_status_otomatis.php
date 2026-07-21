<?php
/**
 * File: actions/cek_status_otomatis.php
 * Deskripsi: Mesin Polling Latar Belakang untuk mengecek status transaksi ke API Midtrans
 */
session_start();
require_once "../config/database.php";
require_once "../config/midtrans.php";

if (!isset($_GET['id'])) {
    exit;
}

$id_reservasi = (int)$_GET['id'];
$database = new database();
$db = $database->getKoneksi();

// 1. Ambil Order ID (id_pesanan_gateway) yang tersimpan di database
$stmt = $db->prepare("SELECT id_pesanan_gateway FROM pembayaran WHERE id_reservasi = ?");
$stmt->execute([$id_reservasi]);
$order_id = $stmt->fetchColumn();

if (!$order_id) {
    echo "NO_ORDER_ID";
    exit;
}

// 2. Hit API Status Midtrans secara langsung
$url = MIDTRANS_IS_PRODUCTION ? "https://api.midtrans.com/v2/$order_id/status" : "https://api.sandbox.midtrans.com/v2/$order_id/status";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// 3. Jika jawaban dari Midtrans adalah Sukses (settlement / capture)
if (isset($result['transaction_status'])) {
    $status = $result['transaction_status'];
    
    if ($status == 'capture' || $status == 'settlement') {
        try {
            $db->beginTransaction();
            // Eksekusi pembaruan status ke Lunas/Dikonfirmasi
            $db->query("UPDATE reservasi SET status_reservasi = 'dikonfirmasi' WHERE id_reservasi = $id_reservasi");
            $db->query("UPDATE pembayaran SET status_pembayaran = 'lunas' WHERE id_reservasi = $id_reservasi");
            $db->commit();
            
            echo "LUNAS";
        } catch (Exception $e) {
            $db->rollBack();
            echo "ERROR_DB";
        }
    } else {
        echo "PENDING";
    }
} else {
    echo "ERROR_API";
}
?>
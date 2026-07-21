<?php
/**
 * File: admin/cetak_struk.php
 * Deskripsi: Halaman khusus untuk mencetak struk/faktur pembayaran
 */
session_start();
require_once "../config/database.php";

// Pastikan ada ID yang dikirim
if (!isset($_GET['id'])) {
    die("ID Pesanan tidak ditemukan.");
}

$database = new database();
$db = $database->getKoneksi();
$id_reservasi = $_GET['id'];

// Kueri Data Utama
$query_utama = "SELECT r.*, p.metode_pembayaran, p.status_pembayaran, p.tanggal_bayar, u.nama_lengkap, l.nama_lapangan 
                FROM reservasi r 
                JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
                JOIN pengguna u ON r.id_pengguna = u.id_pengguna 
                JOIN detail_reservasi dr ON r.id_reservasi = dr.id_reservasi
                JOIN jadwal j ON dr.id_jadwal = j.id_jadwal
                JOIN lapangan l ON j.id_lapangan = l.id_lapangan
                WHERE r.id_reservasi = :id LIMIT 1";
$stmt_utama = $db->prepare($query_utama);
$stmt_utama->execute([':id' => $id_reservasi]);
$data = $stmt_utama->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data transaksi tidak valid.");
}

// Kueri Rincian Jam Main
$query_jam = "SELECT j.jam_mulai, j.jam_selesai, dr.subtotal 
              FROM detail_reservasi dr 
              JOIN jadwal j ON dr.id_jadwal = j.id_jadwal 
              WHERE dr.id_reservasi = :id ORDER BY j.jam_mulai ASC";
$stmt_jam = $db->prepare($query_jam);
$stmt_jam->execute([':id' => $id_reservasi]);
$durasi = $stmt_jam->rowCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; background: #fff; margin: 0; padding: 20px; font-size: 14px; }
        .struk-container { max-width: 400px; margin: 0 auto; border: 1px dashed #000; padding: 20px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 0; }
        .small { font-size: 12px; }
        
        /* Menyembunyikan elemen lain saat diprint */
        @media print {
            body { padding: 0; }
            .struk-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="struk-container">
    <div class="text-center">
        <h2 style="margin: 0;">O2 FUTSAL</h2>
        <p class="small" style="margin: 5px 0 0 0;">Infrastruktur Futsal Premium<br>Banjarmasin, Kalimantan Selatan</p>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td>ID Pesanan</td>
            <td class="text-right font-bold">#ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></td>
        </tr>
        <tr>
            <td>Tanggal Cetak</td>
            <td class="text-right"><?php echo date('d M Y H:i'); ?></td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td class="text-right"><?php echo strtoupper($data['nama_lengkap']); ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td class="text-right font-bold"><?php echo strtoupper($data['status_pembayaran']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="font-bold text-center mb-2">RINCIAN SEWA LAPANGAN <?php echo strtoupper($data['nama_lapangan']); ?></div>
    <div class="small text-center" style="margin-bottom: 10px;">Tgl Main: <?php echo date('d M Y', strtotime($data['tanggal_sewa'])); ?></div>
    
    <table>
        <?php while ($jam = $stmt_jam->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo substr($jam['jam_mulai'], 0, 5) . ' - ' . substr($jam['jam_selesai'], 0, 5); ?></td>
            <td class="text-right">Rp <?php echo number_format($jam['subtotal'], 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal (<?php echo $durasi; ?> Jam)</td>
            <td class="text-right">Rp <?php echo number_format($data['total_biaya'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Diskon Poin</td>
            <td class="text-right">- Rp <?php echo number_format($data['potongan_diskon'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="font-size: 16px; padding-top: 10px;">TOTAL BAYAR</td>
            <td class="text-right font-bold" style="font-size: 16px; padding-top: 10px;">Rp <?php echo number_format($data['total_bayar'], 0, ',', '.'); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center small">
        <p style="margin: 0 0 5px 0;">Pembayaran menggunakan: <strong><?php echo strtoupper(str_replace('_', ' ', $data['metode_pembayaran'])); ?></strong></p>
        <p style="margin: 0;">Terima kasih telah mempercayakan waktu olahraga Anda di O2 Futsal.</p>
    </div>
</div>

<script>
    // Otomatis membuka jendela Print bawaan browser saat halaman selesai dimuat
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
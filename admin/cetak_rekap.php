<?php
/**
 * File: admin/cetak_rekap.php
 * Deskripsi: Format cetak (print) untuk rekapitulasi laporan transaksi
 */
session_start();
require_once "../config/database.php";

$database = new database();
$db = $database->getKoneksi();

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

$whereClause = "WHERE 1=1"; 
$params = [];

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $whereClause .= " AND DATE(r.waktu_pesan) BETWEEN :tgl_awal AND :tgl_akhir";
    $params[':tgl_awal'] = $tgl_awal;
    $params[':tgl_akhir'] = $tgl_akhir;
}

if (!empty($keyword)) {
    $id_search = preg_replace('/[^0-9]/', '', $keyword);
    $whereClause .= " AND (u.nama_lengkap LIKE :keyword OR r.id_reservasi = :id_search)";
    $params[':keyword'] = "%$keyword%";
    $params[':id_search'] = $id_search != '' ? (int)$id_search : 0;
}

$query = "SELECT r.id_reservasi, r.waktu_pesan, r.tanggal_sewa, r.total_bayar, r.status_reservasi, 
                 p.metode_pembayaran, p.status_pembayaran, u.nama_lengkap 
          FROM reservasi r 
          JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
          JOIN pengguna u ON r.id_pengguna = u.id_pengguna 
          $whereClause
          ORDER BY r.waktu_pesan DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);

$total_pendapatan = 0;
$total_transaksi = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan Reservasi O2 Futsal</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; background: #fff; margin: 0; padding: 20px; font-size: 14px; }
        .header-laporan { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-laporan h2, .header-laporan h4, .header-laporan p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .periode-info { margin-bottom: 15px; font-weight: bold; }

        @media print {
            body { padding: 0; margin: 10mm; }
            button { display: none; }
        }
    </style>
</head>
<body>

<div class="header-laporan">
    <h2>LAPORAN RESERVASI LAPANGAN</h2>
    <h4>O2 FUTSAL SPORT CENTER BANJARMASIN</h4>
    <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?> | Oleh: Kasir</p>
</div>

<div class="periode-info">
    Filter Laporan: 
    <?php 
        if(!empty($tgl_awal) && !empty($tgl_akhir)) echo date('d M Y', strtotime($tgl_awal)) . " s/d " . date('d M Y', strtotime($tgl_akhir));
        else echo "Semua Waktu";
        
        if(!empty($keyword)) echo " | Pencarian: '$keyword'";
    ?>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>ID Pesanan</th>
            <th>Waktu Pemesanan</th>
            <th>Tgl Main</th>
            <th>Nama Pelanggan</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Total Bayar (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        if ($stmt->rowCount() > 0): 
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                // Hitung pendapatan hanya dari yang tidak dibatalkan
                if($row['status_reservasi'] != 'dibatalkan') {
                    $total_pendapatan += $row['total_bayar'];
                }
                $total_transaksi++;
        ?>
        <tr>
            <td class="text-center"><?php echo $no++; ?></td>
            <td class="text-center">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></td>
            <td><?php echo date('d M Y, H:i', strtotime($row['waktu_pesan'])); ?></td>
            <td><?php echo date('d M Y', strtotime($row['tanggal_sewa'])); ?></td>
            <td><?php echo $row['nama_lengkap']; ?></td>
            <td class="text-center"><?php echo strtoupper(str_replace('_', ' ', $row['metode_pembayaran'])); ?></td>
            <td class="text-center"><?php echo strtoupper($row['status_reservasi']); ?></td>
            <td class="text-right"><?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="8" class="text-center py-4">Tidak ada data transaksi pada rentang waktu ini.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" class="text-right font-bold">TOTAL PENDAPATAN (Excl. Batal):</td>
            <td class="text-right font-bold" style="font-size: 16px;">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></td>
        </tr>
    </tfoot>
</table>

<div style="float: right; text-align: center; margin-top: 50px;">
    <p>Banjarmasin, <?php echo date('d F Y'); ?></p>
    <br><br><br>
    <p class="font-bold">( ______________________ )</p>
    <p>Admin / Kasir</p>
</div>

<script>
    // Otomatis membuka jendela print
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
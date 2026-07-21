<?php
/**
 * File: admin/cetak_pelanggan.php
 * Deskripsi: Halaman cetak dokumen putih (Print-friendly) untuk data pelanggan
 */
session_start();
require_once "cek_akses.php";
require_once "../config/database.php";

if ($_SESSION['peran'] != 'pemilik') { die("Akses Ditolak."); }

$database = new database();
$db = $database->getKoneksi();

$query = "SELECT * FROM pengguna WHERE peran = 'pelanggan' ORDER BY id_pengguna DESC";
$stmt = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Data Member - O2 Futsal</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
    </style>
</head>
<body onload="window.print()"> <div class="header">
        <h2>O2 FUTSAL BANJARMASIN</h2>
        <p>Jl. Cempaka Raya No. 07, Banjarmasin Barat | Telp: 0813-5198-8842</p>
        <h3>LAPORAN DATA MEMBER TERDAFTAR</h3>
        <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Lengkap</th>
                <th width="20%">No. Telepon</th>
                <th width="25%">Email</th>
                <th width="15%">Status Member</th>
                <th width="10%">Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td class="text-left"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                <td><?php echo htmlspecialchars($row['nomor_telepon']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo ($row['is_vip'] == 1) ? 'VIP' : 'Reguler'; ?></td>
                <td><?php echo number_format($row['poin_loyalitas'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
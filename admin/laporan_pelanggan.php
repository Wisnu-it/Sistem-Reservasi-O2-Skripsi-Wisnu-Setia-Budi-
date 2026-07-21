<?php
/**
 * File: admin/laporan_pelanggan.php
 * Deskripsi: Laporan Pelanggan Terloyal (Bab 3.7.5) - Sistem Leaderboard
 */
session_start();
require_once "cek_akses.php";
require_once "../config/database.php";

// Pastikan hanya Pemilik yang bisa mengakses Laporan Eksekutif ini
if ($_SESSION['peran'] != 'pemilik') {
    echo "<script>alert('AKSES DITOLAK! Laporan analitik hanya untuk Pemilik.'); window.location.href='index.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Kueri Peringkat: Menghitung total main dan uang yang dihabiskan tiap pelanggan
$query = "SELECT u.id_pengguna, u.nama_lengkap, u.nomor_telepon, u.poin_loyalitas,
                 COUNT(r.id_reservasi) as total_transaksi,
                 SUM(r.total_bayar) as total_belanja
          FROM pengguna u
          JOIN reservasi r ON u.id_pengguna = r.id_pengguna
          JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
          WHERE u.peran = 'pelanggan' AND p.status_pembayaran = 'lunas'
          GROUP BY u.id_pengguna
          ORDER BY total_transaksi DESC, total_belanja DESC
          LIMIT 20"; // Menampilkan Top 20 Pelanggan

$stmt = $db->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Loyalitas - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/o2futsal/assets/css/style.css" rel="stylesheet">
    <style>
        /* Desain khusus Cetak */
        @media print {
            body { background-color: white !important; color: black !important; }
            .navbar, .btn, .no-print { display: none !important; }
            .card-custom { border: none !important; box-shadow: none !important; }
            .table-dark { color: black !important; }
            .table-dark th, .table-dark td { border-color: #ddd !important; }
            .badge { border: 1px solid black !important; color: black !important; background: white !important; }
        }
    </style>
</head>
<body class="bg-dark">

<div class="no-print">
    <?php include "navbar.php"; ?>
</div>

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
        <div>
            <h4 class="fw-bold text-white mb-0">Leaderboard Pelanggan VIP</h4>
            <p class="text-secondary small mb-0">Laporan Bab 3.7.5 - Analisis Frekuensi Reservasi Tertinggi</p>
        </div>
        <button onclick="window.print()" class="btn btn-light fw-bold rounded-pill px-4 no-print">🖨️ Cetak Dokumen</button>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th>Peringkat</th>
                        <th class="text-start">Nama Tim / Pelanggan</th>
                        <th>Kontak</th>
                        <th>Total Main (Lunas)</th>
                        <th>Total Kontribusi (Rp)</th>
                        <th>Status Member</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $peringkat = 1;
                    if ($stmt->rowCount() > 0): 
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                            
                            // Logika Pemberian Medali untuk Top 3
                            $medali = "";
                            $row_class = "";
                            if ($peringkat == 1) {
                                $medali = "🥇";
                                $row_class = "table-active border-warning text-warning";
                            } elseif ($peringkat == 2) {
                                $medali = "🥈";
                            } elseif ($peringkat == 3) {
                                $medali = "🥉";
                            } else {
                                $medali = "#" . $peringkat;
                            }
                    ?>
                    <tr class="<?php echo ($peringkat == 1) ? 'fw-bold' : ''; ?>">
                        <td class="fs-4"><?php echo $medali; ?></td>
                        <td class="text-start <?php echo ($peringkat == 1) ? 'text-warning' : 'text-white'; ?>">
                            <?php echo strtoupper($row['nama_lengkap']); ?>
                        </td>
                        <td class="text-secondary"><?php echo $row['nomor_telepon']; ?></td>
                        <td class="text-accent fs-5"><?php echo $row['total_transaksi']; ?> <small class="text-secondary fs-6">Kali</small></td>
                        <td class="text-white">Rp <?php echo number_format($row['total_belanja'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($row['poin_loyalitas'] >= 100): ?>
                                <span class="badge bg-warning text-dark px-3 rounded-pill">VIP Member</span>
                            <?php else: ?>
                                <span class="badge border border-secondary text-secondary px-3 rounded-pill">Reguler</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        $peringkat++;
                        endwhile; 
                    else: 
                    ?>
                    <tr><td colspan="6" class="text-center py-5 text-secondary">Belum ada data transaksi yang lunas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-secondary small no-print">
            <em>*Data diurutkan secara otomatis berdasarkan jumlah penyewaan terbanyak, disusul oleh total uang yang dibayarkan. Hanya transaksi dengan status LUNAS yang dihitung.</em>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
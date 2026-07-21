<?php
/**
 * File: admin/laporan_loyalitas.php
 * Deskripsi: Laporan Pelanggan Terloyal (Leaderboard CRM) + Fitur Cetak
 */
session_start();
require_once "../config/database.php";

// Keamanan: Hanya Pemilik yang bisa mengakses laporan analitik bisnis
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Kueri Agregasi SQL: Menghitung total reservasi dan total uang dari masing-masing pelanggan
$query = "
    SELECT p.id_pengguna, p.nama_lengkap, p.email, p.nomor_telepon, p.poin_loyalitas,
           COUNT(r.id_reservasi) as total_booking,
           SUM(r.total_bayar) as total_kontribusi_dana
    FROM pengguna p
    JOIN reservasi r ON p.id_pengguna = r.id_pengguna
    WHERE r.status_reservasi IN ('dikonfirmasi', 'lunas', 'selesai')
    GROUP BY p.id_pengguna
    ORDER BY total_booking DESC, total_kontribusi_dana DESC
    LIMIT 15
";

try {
    $stmt = $db->query($query);
} catch (PDOException $e) {
    die("<div style='background:#111; color:white; padding:20px; text-align:center; font-family:sans-serif;'>
            <h3 style='color:#dc3545;'>Oops! Error SQL Analitik Terjadi.</h3>
            <p>Pesan Error: " . $e->getMessage() . "</p>
         </div>");
}

// Fungsi Tanggal Indonesia untuk Dokumen Cetak
function getTanggalSekarangIndonesia() {
    $daftar_bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    return date('d ') . $daftar_bulan[date('m')] . date(' Y');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Pelanggan Terloyal - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        .rank-badge { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; border-radius: 50%; }
        .rank-1 { background-color: #ffd700; color: #000; } /* Emas */
        .rank-2 { background-color: #c0c0c0; color: #000; } /* Perak */
        .rank-3 { background-color: #cd7f32; color: #000; } /* Perunggu */
        
        @media print {
            @page { size: portrait; margin: 1cm; } 
            body { background-color: #fff !important; color: #000 !important; font-size: 13px; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 8px !important; }
            .rank-badge { background-color: transparent !important; color: #000 !important; border: 1px solid #000; }
            tr { page-break-inside: avoid; }
            .text-accent { color: #000 !important; }
            h2, h5 { color: #000 !important; }
        }
    </style>
</head>
<body>

    <!-- Area Navigasi -->
    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak Analisis Loyalitas</button>
        </div>
    </div>

    <!-- AREA YANG AKAN DICETAK -->
    <div class="container mb-5" id="area-cetak">
        
        <div class="text-center mb-5 pb-3 border-bottom border-secondary">
            <h2 class="fw-bold mb-1">ANALISIS LEADERSHIPS PELANGGAN TERLOYAL <span class="text-accent no-print">O2 FUTSAL</span></h2>
            <h5 class="text-secondary mb-0 fw-normal mt-2">
                Metrik Evaluasi: Frekuensi Booking Terbanyak & Kontribusi Finansial
            </h5>
            <p class="small text-muted mt-1 no-print">Menampilkan Top 15 pelanggan setia sepanjang sistem beroperasi.</p>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th width="8%" class="bg-black text-white py-3">Peringkat</th>
                        <th width="27%" class="bg-black text-white py-3">Nama Pelanggan</th>
                        <th width="20%" class="bg-black text-white py-3">Kontak / Email</th>
                        <th width="15%" class="bg-black text-accent py-3">Total Main (Frekuensi)</th>
                        <th width="15%" class="bg-black text-white py-3">Poin Loyalitas Saat Ini</th>
                        <th width="15%" class="bg-black text-white py-3">Total Transaksi (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $ranking = 1;
                    if ($stmt && $stmt->rowCount() > 0):
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            
                            // Logika badge warna untuk top 3 besar
                            $badge_class = "";
                            if ($ranking == 1) $badge_class = "rank-1";
                            elseif ($ranking == 2) $badge_class = "rank-2";
                            elseif ($ranking == 3) $badge_class = "rank-3";
                    ?>
                    <tr>
                        <td>
                            <span class="rank-badge <?php echo $badge_class; ?>">
                                <?php echo $ranking++; ?>
                            </span>
                        </td>
                        <td class="text-start ps-3 fw-bold text-white"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-start ps-3 text-secondary small">
                            📞 <?php echo htmlspecialchars($row['nomor_telepon'] ?? '-'); ?><br>
                            ✉️ <?php echo htmlspecialchars($row['email']); ?>
                        </td>
                        <td class="fw-bold text-accent fs-5"><?php echo $row['total_booking']; ?> Kali Main</td>
                        <td class="text-white"><?php echo number_format($row['poin_loyalitas'], 0, ',', '.'); ?> Poin</td>
                        <td class="text-end pe-3 fw-bold">Rp <?php echo number_format($row['total_kontribusi_dana'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="py-5 text-secondary">Belum ada data transaksi pelanggan yang terekam di sistem.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Kolom Tanda Tangan Laporan Bisnis -->
        <div class="row mt-5 pt-4 d-none d-print-flex">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p class="mb-5">Banjarmasin, <?php echo getTanggalSekarangIndonesia(); ?><br>Mengetahui,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Pemilik O2 Futsal</p>
                <p class="small text-muted mt-0">Manager Executive</p>
            </div>
        </div>

    </div>

</body>
</html>
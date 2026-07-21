<?php
/**
 * File: admin/laporan_jam_sibuk.php
 * Deskripsi: Analisis Peak Hour (Jam Sibuk) dengan Visualisasi Grafik Bar
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

// Kueri SQL Agregasi: Menghitung total penggunaan per slot jam
$query = "
    SELECT j.jam_mulai, j.jam_selesai, 
           COUNT(dr.id_jadwal) as total_booking
    FROM reservasi r
    JOIN detail_reservasi dr ON r.id_reservasi = dr.id_reservasi
    JOIN jadwal j ON dr.id_jadwal = j.id_jadwal
    WHERE r.status_reservasi IN ('dikonfirmasi', 'lunas', 'selesai')
    GROUP BY j.jam_mulai, j.jam_selesai
    ORDER BY total_booking DESC
";

try {
    $stmt = $db->query($query);
    $data_analitik = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cari nilai pemesanan tertinggi untuk dijadikan patokan 100% pada grafik visual
    $max_booking = 0;
    if (count($data_analitik) > 0) {
        $max_booking = $data_analitik[0]['total_booking']; // Karena sudah di-ORDER BY DESC, index 0 pasti yang tertinggi
    }
} catch (PDOException $e) {
    die("<div style='background:#111; color:white; padding:20px; text-align:center;'>
            <h3 style='color:#dc3545;'>Oops! Error SQL Terjadi.</h3>
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
    <title>Analisis Jam Sibuk - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        /* Modifikasi Visual Bar Chart */
        .progress { height: 25px; background-color: #222; border-radius: 4px; overflow: hidden; border: 1px solid #444; }
        .progress-bar { background: linear-gradient(90deg, #8ba800 0%, #c4ff00 100%); color: #000; font-weight: bold; text-align: left; padding-left: 10px; line-height: 25px;}
        
        @media print {
            @page { size: portrait; margin: 1cm; } 
            body { background-color: #fff !important; color: #000 !important; font-size: 13px; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 10px !important; }
            tr { page-break-inside: avoid; }
            .text-accent { color: #000 !important; }
            h2, h5 { color: #000 !important; }
            
            /* Penyesuaian Progress Bar saat dicetak agar tinta tidak boros */
            .progress { border: 1px solid #000; background-color: transparent; }
            .progress-bar { background: #ccc !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak Analisis Visual</button>
        </div>
    </div>

    <div class="container mb-5" id="area-cetak">
        
        <div class="text-center mb-5 pb-3 border-bottom border-secondary">
            <h2 class="fw-bold mb-1">ANALISIS JAM SIBUK <span class="text-accent no-print">(PEAK HOUR)</span></h2>
            <h5 class="text-secondary mb-0 fw-normal mt-2">
                Evaluasi Kepadatan Slot Waktu O2 Futsal
            </h5>
            <p class="small text-muted mt-1 no-print">Data diurutkan dari jam yang paling laris dipesan oleh pelanggan.</p>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered align-middle">
                <thead class="text-secondary text-center">
                    <tr>
                        <th width="8%" class="bg-black text-white py-3">Peringkat</th>
                        <th width="22%" class="bg-black text-accent py-3">Slot Jam (WITA)</th>
                        <th width="15%" class="bg-black text-white py-3">Total Dipesan</th>
                        <th width="55%" class="bg-black text-white py-3">Visualisasi Rasio Kepadatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $ranking = 1;
                    if (count($data_analitik) > 0):
                        foreach ($data_analitik as $row):
                            // Kalkulasi Persentase Bar Chart (Minimal 1% agar bar tetap terlihat sedikit)
                            $persentase = ($max_booking > 0) ? ($row['total_booking'] / $max_booking) * 100 : 0;
                            $persentase = max($persentase, 1); 
                            
                            $jam_format = substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5);
                    ?>
                    <tr>
                        <td class="text-center fw-bold fs-5 text-secondary">#<?php echo $ranking++; ?></td>
                        <td class="text-center fw-bold text-white fs-5"><?php echo $jam_format; ?></td>
                        <td class="text-center fw-bold text-accent fs-5"><?php echo $row['total_booking']; ?>x</td>
                        <td class="pe-4">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $persentase; ?>%;" aria-valuenow="<?php echo $persentase; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($persentase); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="py-5 text-secondary text-center">Belum ada data penyewaan yang cukup untuk dianalisis.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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
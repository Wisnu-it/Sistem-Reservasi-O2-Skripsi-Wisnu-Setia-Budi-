<?php
/**
 * File: admin/laporan_jadwal.php
 * Deskripsi: Cetak Jadwal Operasional Harian Lapangan (Lokalisasi Bahasa Indonesia)
 */
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Kueri SQL Utama (Sudah terbukti sukses)
$query = "
    SELECT r.id_reservasi, 
           p.nama_lengkap, p.nomor_telepon,
           l.nama_lapangan, 
           j.jam_mulai, j.jam_selesai
    FROM reservasi r
    JOIN pengguna p ON r.id_pengguna = p.id_pengguna
    JOIN detail_reservasi dr ON r.id_reservasi = dr.id_reservasi
    JOIN jadwal j ON dr.id_jadwal = j.id_jadwal
    JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
    WHERE r.status_reservasi IN ('dikonfirmasi', 'lunas', 'selesai')
    AND r.tanggal_sewa = :tanggal
    ORDER BY l.nama_lapangan ASC, j.jam_mulai ASC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':tanggal' => $tanggal_filter]);
} catch (PDOException $e) {
    die("<div style='background:#111; color:white; padding:20px; text-align:center; font-family:sans-serif;'>
            <h3 style='color:#dc3545;'>Oops! Error SQL Terjadi.</h3>
            <p>Pesan Error: " . $e->getMessage() . "</p>
         </div>");
}

// ==============================================================================
// FUNGSI KONVERSI TANGGAL KE BAHASA INDONESIA (SANGAT AMAN UNTUK SKRIPSI)
// ==============================================================================
function formatTanggalIndonesia($tanggal) {
    $daftar_hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $daftar_bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    $hari = $daftar_hari[date('l', $timestamp)];
    $tgl = date('d', $timestamp);
    $bulan = $daftar_bulan[date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    
    return $hari . ', ' . $tgl . ' ' . $bulan . ' ' . $tahun;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Harian Lapangan - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        @media print {
            @page { size: portrait; margin: 1cm; } 
            body { background-color: #fff !important; color: #000 !important; font-size: 13px; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 8px !important; }
            tr { page-break-inside: avoid; }
            .text-accent { color: #000 !important; }
            h2, h4 { color: #000 !important; }
        }
    </style>
</head>
<body>

    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak untuk Staf</button>
        </div>

        <div class="card-custom p-4 mb-4 border border-secondary">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label text-secondary small mb-1">Pilih Tanggal Jadwal Main</label>
                    <input type="date" name="tanggal" class="form-control bg-dark text-white border-secondary" value="<?php echo $tanggal_filter; ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-light w-100 fw-bold py-2">Tampilkan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container mb-5" id="area-cetak">
        
        <div class="text-center mb-4 pb-3 border-bottom border-secondary">
            <h2 class="fw-bold mb-1">JADWAL HARIAN LAPANGAN <span class="text-accent no-print">O2 FUTSAL</span></h2>
            <h4 class="text-white mb-0 fw-bold mt-2 text-uppercase">
                HARI/TANGGAL: <?php echo formatTanggalIndonesia($tanggal_filter); ?>
            </h4>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th width="5%" class="bg-black text-white py-3">No</th>
                        <th width="20%" class="bg-black text-white py-3">Unit Lapangan</th>
                        <th width="20%" class="bg-black text-accent py-3">Jam Main (WITA)</th>
                        <th width="30%" class="bg-black text-white py-3">Nama Penyewa</th>
                        <th width="25%" class="bg-black text-white py-3">ID Booking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $lapangan_sebelumnya = '';

                    if (isset($stmt) && $stmt->rowCount() > 0):
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            
                            if ($lapangan_sebelumnya != '' && $lapangan_sebelumnya != $row['nama_lapangan']) {
                                echo '<tr class="border-top border-3"><td colspan="5"></td></tr>';
                            }
                            $lapangan_sebelumnya = $row['nama_lapangan'];
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td class="fw-bold fs-5 text-white">Lap. <?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                        <td class="fw-bold text-accent fs-5"><?php echo substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5); ?></td>
                        <td class="text-start ps-4 fw-semibold text-white fs-5"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-secondary fw-bold">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="py-5 text-secondary">Kosong. Tidak ada jadwal lapangan yang dibooking untuk tanggal ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-5 pt-4 d-none d-print-flex">
            <div class="col-4 text-center">
                <p class="mb-5">Diserahkan Oleh,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Admin / Pemilik</p>
            </div>
            <div class="col-4">
                <p class="text-center text-dark small">Banjarmasin, <?php echo date('d ') . $daftar_bulan[date('m')] . date(' Y'); ?></p>
            </div>
            <div class="col-4 text-center">
                <p class="mb-5">Diterima & Dicek Oleh,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Petugas Lapangan</p>
            </div>
        </div>

    </div>

</body>
</html>
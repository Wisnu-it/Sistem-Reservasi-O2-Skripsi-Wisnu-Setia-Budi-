<?php
/**
 * File: admin/laporan_batal.php
 * Deskripsi: Laporan Riwayat Pembatalan Reservasi & Potensi Kehilangan Pendapatan
 */
session_start();
require_once "../config/database.php";

// Keamanan: Hanya Pemilik
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Filter Tanggal (Default: 1 Bulan Terakhir agar datanya lebih terlihat)
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d', strtotime('-1 month'));
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Kueri SQL: Mengambil data reservasi yang statusnya batal/ditolak
$query = "
    SELECT r.id_reservasi, r.waktu_pesan, r.tanggal_sewa, r.total_bayar, r.status_reservasi,
           p.nama_lengkap, p.nomor_telepon
    FROM reservasi r
    JOIN pengguna p ON r.id_pengguna = p.id_pengguna
    WHERE r.status_reservasi IN ('batal', 'dibatalkan', 'ditolak', 'kadaluarsa')
    AND DATE(r.waktu_pesan) BETWEEN :awal AND :akhir
    ORDER BY r.waktu_pesan DESC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':awal' => $tgl_awal, ':akhir' => $tgl_akhir]);
} catch (PDOException $e) {
    die("<div style='background:#111; color:white; padding:20px; text-align:center;'>
            <h3 style='color:#dc3545;'>Oops! Error SQL Terjadi.</h3>
            <p>Pesan Error: " . $e->getMessage() . "</p>
         </div>");
}

$total_kerugian = 0;

// Fungsi Tanggal Indonesia
function formatTanggalIndo($tanggal) {
    $daftar_bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    return date('d ', $timestamp) . $daftar_bulan[date('m', $timestamp)] . date(' Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembatalan - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        .badge-batal { background-color: #dc3545; color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        
        @media print {
            @page { size: landscape; margin: 1cm; } 
            body { background-color: #fff !important; color: #000 !important; font-size: 13px; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 8px !important; }
            .badge-batal { background-color: transparent !important; color: #000 !important; border: 1px solid #000; }
            tr { page-break-inside: avoid; }
            .text-accent { color: #000 !important; }
            .text-danger { color: #000 !important; }
            h2, h5 { color: #000 !important; }
        }
    </style>
</head>
<body>

    <!-- Area Filter & Navigasi -->
    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak Dokumen Pembatalan</button>
        </div>

        <div class="card-custom p-4 mb-4 border border-secondary">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-secondary small mb-1">Cari dari Tanggal</label>
                    <input type="date" name="tgl_awal" class="form-control bg-dark text-white border-secondary" value="<?php echo $tgl_awal; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary small mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" class="form-control bg-dark text-white border-secondary" value="<?php echo $tgl_akhir; ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-light w-100 fw-bold py-2">Tampilkan Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AREA YANG AKAN DICETAK -->
    <div class="container mb-5" id="area-cetak">
        
        <div class="text-center mb-4 pb-3 border-bottom border-secondary">
            <h2 class="fw-bold mb-1">LAPORAN PEMBATALAN TRANSAKSI <span class="text-accent no-print">O2 FUTSAL</span></h2>
            <h5 class="text-secondary mb-0 fw-normal mt-2 text-uppercase">
                Periode Evaluasi: <?php echo formatTanggalIndo($tgl_awal); ?> - <?php echo formatTanggalIndo($tgl_akhir); ?>
            </h5>
            <p class="small text-muted mt-1 no-print">Daftar booking yang gagal, dibatalkan, atau ditolak sistem.</p>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th width="5%" class="bg-black text-white py-3">No</th>
                        <th width="15%" class="bg-black text-white py-3">ID Pesanan</th>
                        <th width="20%" class="bg-black text-white py-3">Waktu Dipesan</th>
                        <th width="25%" class="bg-black text-white py-3">Nama Pelanggan</th>
                        <th width="15%" class="bg-black text-white py-3">Keterangan</th>
                        <th width="20%" class="bg-black text-danger py-3">Potensi Kerugian (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (isset($stmt) && $stmt->rowCount() > 0):
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $total_kerugian += $row['total_bayar'];
                            
                            $status_label = strtoupper($row['status_reservasi']);
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td class="fw-bold text-secondary">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <?php 
                                $waktu = isset($row['waktu_pesan']) ? $row['waktu_pesan'] : $row['tanggal_sewa'];
                                echo date('d M Y, H:i', strtotime($waktu)); 
                            ?>
                        </td>
                        <td class="text-start ps-3 fw-semibold text-white">
                            <?php echo htmlspecialchars($row['nama_lengkap']); ?><br>
                            <span class="small text-secondary fw-normal">📞 <?php echo htmlspecialchars($row['nomor_telepon']); ?></span>
                        </td>
                        <td><span class="badge-batal"><?php echo $status_label; ?></span></td>
                        <td class="text-end pe-3 fw-bold text-danger">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="py-5 text-secondary">Aman! Tidak ada riwayat pembatalan pesanan pada periode ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end pe-3 fw-bold bg-black text-white py-3">TOTAL POTENSI PENDAPATAN YANG HILANG :</td>
                        <td class="text-end pe-3 fw-bold bg-black text-danger py-3 fs-5">Rp <?php echo number_format($total_kerugian, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-4 d-none d-print-flex">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p class="mb-5">Banjarmasin, <?php echo formatTanggalIndo(date('Y-m-d')); ?><br>Ditinjau Oleh,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Pemilik O2 Futsal</p>
                <p class="small text-muted mt-0">Manager Executive</p>
            </div>
        </div>

    </div>

</body>
</html>
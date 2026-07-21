<?php
/**
 * File: admin/laporan_pendapatan.php
 * Deskripsi: Laporan Pendapatan Keuangan dengan Filter Tanggal & Fitur Cetak (Fix Potong Layar)
 */
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'harian';
if ($jenis == 'bulanan') {
    $default_awal = date('Y-m-01'); 
    $default_akhir = date('Y-m-t'); 
} else {
    $default_awal = date('Y-m-d'); 
    $default_akhir = date('Y-m-d'); 
}

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : $default_awal;
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : $default_akhir;

$query = "
    SELECT r.id_reservasi, r.tanggal_sewa, r.total_bayar, r.waktu_pesan, 
           p.nama_lengkap, pb.metode_pembayaran 
    FROM reservasi r
    JOIN pengguna p ON r.id_pengguna = p.id_pengguna
    JOIN pembayaran pb ON r.id_reservasi = pb.id_reservasi
    WHERE r.status_reservasi IN ('dikonfirmasi', 'lunas', 'selesai')
    AND r.tanggal_sewa BETWEEN :awal AND :akhir
    ORDER BY r.tanggal_sewa DESC, r.waktu_pesan DESC
";
$stmt = $db->prepare($query);
$stmt->execute([':awal' => $tgl_awal, ':akhir' => $tgl_akhir]);

$total_pendapatan = 0; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        /* CSS KHUSUS SAAT DICETAK (PRINT MEDIA) - SUDAH DIPERBAIKI */
        @media print {
            @page { size: landscape; margin: 1cm; } /* Rekomendasi paksa kertas ke Landscape */
            body { background-color: #fff !important; color: #000 !important; font-size: 12px; }
            .no-print { display: none !important; } 
            
            /* Paksa container menjadi 100% dan hapus margin */
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            
            /* Matikan sifat overflow dari table-responsive yang memotong tabel */
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 6px !important; word-wrap: break-word; }
            
            /* Hindari baris terpotong di tengah saat pindah halaman */
            tr { page-break-inside: avoid; }
            
            .text-accent { color: #000 !important; }
            h2, h5 { color: #000 !important; }
        }
    </style>
</head>
<body>

    <!-- Area Filter & Navigasi -->
    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak / Simpan PDF</button>
        </div>

        <div class="card-custom p-4 mb-4 border border-secondary">
            <form action="" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="jenis" value="filter">
                <div class="col-md-4">
                    <label class="form-label text-secondary small mb-1">Tanggal Awal</label>
                    <input type="date" name="tgl_awal" class="form-control bg-dark text-white border-secondary" value="<?php echo $tgl_awal; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary small mb-1">Tanggal Akhir</label>
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
            <h2 class="fw-bold mb-1">LAPORAN PENDAPATAN <span class="text-accent no-print">O2 FUTSAL</span></h2>
            <h5 class="text-secondary mb-0 fw-normal mt-2">
                Periode: <?php echo date('d M Y', strtotime($tgl_awal)); ?> s.d <?php echo date('d M Y', strtotime($tgl_akhir)); ?>
            </h5>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">ID Pesanan</th>
                        <th width="15%">Tanggal Main</th>
                        <th width="30%">Nama Pelanggan</th>
                        <th width="15%">Metode Bayar</th>
                        <th width="20%">Total Bayar (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($stmt->rowCount() > 0):
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $total_pendapatan += $row['total_bayar']; 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td class="fw-bold">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['tanggal_sewa'])); ?></td>
                        <td class="text-start ps-3"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-uppercase"><?php echo htmlspecialchars($row['metode_pembayaran']); ?></td>
                        <td class="text-end pe-3">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="py-5 text-secondary">Tidak ada transaksi yang lunas pada periode tanggal ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end pe-3 fw-bold bg-black text-white py-3">TOTAL PENDAPATAN BERSIH :</td>
                        <td class="text-end pe-3 fw-bold bg-black text-accent py-3 fs-5">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-5 d-none d-print-flex">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p class="mb-5">Banjarmasin, <?php echo date('d M Y'); ?><br>Mengetahui,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Pemilik O2 Futsal</p>
                <p class="small text-muted mt-0">Manager Executive</p>
            </div>
        </div>

    </div>

</body>
</html>
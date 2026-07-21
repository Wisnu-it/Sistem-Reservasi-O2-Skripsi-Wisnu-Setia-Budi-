<?php
/**
 * File: admin/validasi_qr.php
 * Deskripsi: Halaman Landing Page yang terbuka saat QR Code di-scan
 */

// Menangkap data dari URL (parameter GET)
$id = isset($_GET['id']) ? $_GET['id'] : '-';
$doc = isset($_GET['doc']) ? $_GET['doc'] : '-';
$per = isset($_GET['per']) ? $_GET['per'] : '-';
$pj = isset($_GET['pj']) ? $_GET['pj'] : '-';
$time = isset($_GET['time']) ? $_GET['time'] : '-';

if (!isset($_GET['id'])) {
    die("Akses Ditolak. QR Code tidak valid atau sudah rusak.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Dokumen - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .validasi-box { background-color: #111; border: 2px solid #198754; border-radius: 15px; max-width: 450px; margin: 0 auto; }
        .text-accent { color: #c4ff00; }
        
        /* Membuat checkmark hijau buatan sendiri dengan CSS */
        .circle-check {
            width: 80px; height: 80px; border-radius: 50%; background-color: rgba(25, 135, 84, 0.1);
            border: 4px solid #198754; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px auto; font-size: 40px; color: #198754;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 py-4 px-3">

    <div class="validasi-box p-4 text-center shadow-lg w-100">
        
        <!-- Ikon Sukses -->
        <div class="circle-check">✓</div>
        
        <h3 class="fw-bold mb-1 text-success">TERVERIFIKASI</h3>
        <p class="text-secondary small mb-4">Dokumen Resmi O2 Futsal</p>
        
        <div class="text-start border-top border-secondary pt-3 text-secondary small">
            <div class="mb-3">
                <span class="d-block text-secondary">ID Tanda Tangan:</span>
                <strong class="text-white fs-6"><?php echo htmlspecialchars($id); ?></strong>
            </div>
            <div class="mb-3">
                <span class="d-block text-secondary">Jenis Laporan:</span>
                <strong class="text-accent"><?php echo htmlspecialchars($doc); ?></strong>
            </div>
            <div class="mb-3">
                <span class="d-block text-secondary">Periode Data:</span>
                <strong class="text-white"><?php echo htmlspecialchars($per); ?></strong>
            </div>
            <div class="mb-3">
                <span class="d-block text-secondary">Disahkan Oleh:</span>
                <strong class="text-white"><?php echo htmlspecialchars($pj); ?></strong>
            </div>
            <div class="mb-2">
                <span class="d-block text-secondary">Waktu Cetak Sistem:</span>
                <strong class="text-white"><?php echo htmlspecialchars($time); ?></strong>
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-top border-secondary">
            <p class="text-muted" style="font-size: 11px; line-height: 1.5;">
                Halaman ini membuktikan bahwa dokumen fisik yang Anda pegang adalah dokumen asli yang dikeluarkan oleh Sistem Informasi Manajemen O2 Futsal dan belum mengalami manipulasi.
            </p>
        </div>
    </div>

</body>
</html>
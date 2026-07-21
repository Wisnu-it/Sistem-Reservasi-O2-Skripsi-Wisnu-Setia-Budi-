<?php
/**
 * File: admin/cetak_qr.php
 * Deskripsi: Generate QR Code URL untuk Verifikasi Keabsahan Laporan
 */
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$qr_image_url = "";
$dokumen = "";
$periode = "";
$pj = "Pemilik O2 Futsal";
$id_dokumen = "";

if (isset($_GET['generate'])) {
    $dokumen = trim($_GET['dokumen']);
    $periode = trim($_GET['periode']);
    $pj = trim($_GET['pj']);
    $waktu_cetak = date('d M Y - H:i:s');
    $id_dokumen = "O2-AUTH-" . strtoupper(substr(md5(time()), 0, 8));
    
    // =========================================================================
    // Generate URL yang mengarah ke validasi_qr.php menggunakan IP 192.168.1.24
    // =========================================================================
    $url_validasi = "http://172.20.10.10/o2futsal/admin/validasi_qr.php?" . 
                    "id=" . urlencode($id_dokumen) . 
                    "&doc=" . urlencode($dokumen) . 
                    "&per=" . urlencode($periode) . 
                    "&pj=" . urlencode($pj) . 
                    "&time=" . urlencode($waktu_cetak);

    // Tingkatkan resolusi menjadi 500x500 agar kotak-kotak QR Code tajam
    $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=" . urlencode($url_validasi);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Laporan - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        .qr-box { background-color: #fff; padding: 20px; border-radius: 15px; display: inline-block; border: 5px solid #222; }
        
        @media print {
            @page { size: portrait; margin: 2cm; } 
            body { background-color: #fff !important; color: #000 !important; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .qr-box { border: 3px solid #000; padding: 30px; }
            .text-accent { color: #000 !important; }
            h2, h4, h5, p { color: #000 !important; }
            
            #area-cetak { 
                display: flex !important; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center; 
                text-align: center;
                height: 80vh;
            }
        }
    </style>
</head>
<body>
    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card-custom p-4 p-md-5 mb-4 border border-secondary shadow-lg text-start">
                    <h3 class="fw-bold text-accent mb-1">PENGESAHAN DOKUMEN DIGITAL</h3>
                    <p class="text-secondary small mb-4">Lengkapi form di bawah ini untuk membuat QR Code tanda tangan digital. QR Code ini dapat dicetak sebagai lampiran validasi laporan fisik.</p>
                    
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Jenis / Nama Laporan</label>
                            <input type="text" name="dokumen" class="form-control bg-dark text-white border-secondary" placeholder="Contoh: Laporan Keuangan Bulanan" value="<?php echo htmlspecialchars($dokumen); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Periode Data (Bulan/Tahun)</label>
                            <input type="text" name="periode" class="form-control bg-dark text-white border-secondary" placeholder="Contoh: Juni 2026" value="<?php echo htmlspecialchars($periode); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small">Penanggung Jawab / Pengesah</label>
                            <input type="text" name="pj" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($pj); ?>" required>
                        </div>
                        
                        <button type="submit" name="generate" class="btn btn-accent w-100 fw-bold py-3 text-dark fs-5 rounded-pill">BUAT QR CODE SEKARANG</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($qr_image_url != ""): ?>
    <div class="container mb-5 mt-4" id="area-cetak">
        
        <div class="text-center w-100">
            <h1 class="fw-bold mb-2">LEMBAR VERIFIKASI <span class="text-accent no-print">O2 FUTSAL</span></h1>
            <p class="mb-5 no-print text-secondary">Silakan cetak halaman ini dan lampirkan bersama dokumen fisik laporan.</p>

            <div class="qr-box shadow-lg mb-5 mt-3">
                <!-- Tambahkan properti style width dan height agar proporsional -->
                <img src="<?php echo $qr_image_url; ?>" alt="QR Code Validasi" class="img-fluid" style="width: 250px; height: 250px;">
            </div>

            <h4 class="fw-bold mt-4"><?php echo strtoupper(htmlspecialchars($dokumen)); ?></h4>
            <h5 class="fw-normal mb-5">PERIODE: <?php echo strtoupper(htmlspecialchars($periode)); ?></h5>

            <div class="row mt-5 pt-4 text-center">
                <div class="col-12">
                    <p class="mb-5">Banjarmasin, <?php echo date('d M Y'); ?><br>Disahkan & Divokumentasi Secara Digital Oleh,</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline mb-0 fs-5"><?php echo htmlspecialchars($pj); ?></p>
                    <p class="text-muted mt-0">ID: <?php echo $id_dokumen; ?></p>
                </div>
            </div>

            <div class="mt-5 no-print">
                <button onclick="window.print()" class="btn btn-light px-5 py-3 fw-bold rounded-pill text-dark fs-5 shadow">🖨 CETAK LEMBAR INI</button>
            </div>
        </div>

    </div>
    <?php endif; ?>

</body>
</html>
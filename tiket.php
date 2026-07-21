<?php
/**
 * File: tiket.php
 * Deskripsi: E-Ticket dengan QR Code untuk divalidasi oleh petugas O2 Futsal
 */
require_once "config/database.php";

// Menampilkan pesan error yang lebih rapi jika diakses tanpa ID
if (!isset($_GET['id'])) {
    die("<div style='background:#0a0a0a; color:white; text-align:center; padding:50px; font-family:sans-serif;'>
            <h2 style='color:#dc3545;'>Akses Ditolak!</h2>
            <p style='color:gray;'>Tiket tidak valid atau Anda mengakses halaman ini tanpa ID pesanan.</p>
         </div>");
}

$id_reservasi = (int)$_GET['id'];
$database = new database();
$db = $database->getKoneksi();

// Mengambil data lengkap reservasi
$query = "SELECT r.*, p.nama_lengkap, p.nomor_telepon, l.nama_lapangan 
          FROM reservasi r 
          JOIN pengguna p ON r.id_pengguna = p.id_pengguna 
          JOIN detail_reservasi dr ON r.id_reservasi = dr.id_reservasi
          JOIN jadwal j ON dr.id_jadwal = j.id_jadwal
          JOIN lapangan l ON j.id_lapangan = l.id_lapangan
          WHERE r.id_reservasi = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([$id_reservasi]);
$tiket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tiket) { 
    die("<div style='background:#0a0a0a; color:white; text-align:center; padding:50px; font-family:sans-serif;'>
            <h2 style='color:#dc3545;'>Error!</h2>
            <p style='color:gray;'>Data tiket tidak ditemukan di sistem kami.</p>
         </div>"); 
}

// Mengambil seluruh jam bermain (karena bisa lebih dari 1 jam)
$stmt_jam = $db->prepare("SELECT j.jam_mulai, j.jam_selesai FROM detail_reservasi dr JOIN jadwal j ON dr.id_jadwal = j.id_jadwal WHERE dr.id_reservasi = ? ORDER BY j.jam_mulai ASC");
$stmt_jam->execute([$id_reservasi]);
$list_jam = $stmt_jam->fetchAll(PDO::FETCH_ASSOC);

// Format Jam
$jam_awal = substr($list_jam[0]['jam_mulai'], 0, 5);
$jam_akhir = substr(end($list_jam)['jam_selesai'], 0, 5);

// =========================================================================
// Generate URL untuk QR Code menggunakan IP BARU: 192.168.1.24
// =========================================================================
$url_validasi = "http://172.20.10.10/o2futsal/tiket.php?id=" . $id_reservasi;

// Tingkatkan resolusi menjadi 500x500 agar QR Code sangat tajam dan mudah discan
$url_qr_code = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=" . urlencode($url_validasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket O2 Futsal - #ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .tiket-box { background-color: #111; border: 2px dashed #c4ff00; border-radius: 15px; max-width: 450px; margin: 0 auto; }
        .text-accent { color: #c4ff00; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 py-4">

    <div class="tiket-box p-4 text-center shadow-lg">
        <h3 class="fw-bold mb-0"><span class="text-accent">O2</span> FUTSAL</h3>
        <p class="text-secondary small mb-4">E-Ticket & Akses Masuk Lapangan</p>
        
        <div class="bg-white p-3 rounded-3 d-inline-block mb-4 shadow">
            <!-- Paksa ukuran tampilannya menjadi 200px agar tidak kebesaran di layar -->
            <img src="<?php echo $url_qr_code; ?>" alt="QR Code Tiket" class="img-fluid" style="width: 200px; height: 200px;">
        </div>

        <h4 class="text-white fw-bold mb-0">#ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></h4>
        
        <div class="badge <?php echo in_array(strtolower($tiket['status_reservasi']), ['lunas', 'dikonfirmasi', 'selesai']) ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill px-4 py-2 mt-2 mb-4">
            STATUS: <?php echo strtoupper($tiket['status_reservasi']); ?>
        </div>

        <div class="text-start border-top border-secondary pt-3 text-secondary small">
            <div class="d-flex justify-content-between mb-2">
                <span>Nama Penyewa:</span>
                <strong class="text-white"><?php echo htmlspecialchars($tiket['nama_lengkap']); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tanggal Main:</span>
                <strong class="text-accent"><?php echo date('d M Y', strtotime($tiket['tanggal_sewa'])); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Jam Main:</span>
                <strong class="text-white"><?php echo $jam_awal . " - " . $jam_akhir; ?> WITA</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Unit Lapangan:</span>
                <strong class="text-white"><?php echo htmlspecialchars($tiket['nama_lapangan']); ?></strong>
            </div>
        </div>
        
        <button onclick="window.print()" class="btn btn-sm btn-outline-light mt-4 rounded-pill px-4">🖨️ Cetak Tiket</button>
    </div>

</body>
</html>
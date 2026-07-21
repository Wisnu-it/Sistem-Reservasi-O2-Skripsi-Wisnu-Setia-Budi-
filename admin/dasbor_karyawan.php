<?php
/**
 * File: admin/dasbor_karyawan.php
 * Deskripsi: Dasbor khusus staf lapangan/wasit untuk melihat jadwal HARI INI
 */
session_start();
require_once "../config/database.php";

// Pastikan pengguna sudah login dan bukan merupakan pelanggan biasa
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) == 'pelanggan') {
    header("Location: ../index.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// 1. Logika Pengondisian Tanggal
// Tangkap tanggal dari form GET, jika kosong otomatis gunakan tanggal hari ini
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// 2. Kueri Dinamis menggunakan Prepared Statement untuk Keamanan
// (Menambahkan pemanggilan r.waktu_pesan pada SELECT)
$query = "SELECT r.id_reservasi, r.waktu_pesan, j.jam_mulai, j.jam_selesai, l.nama_lapangan, u.nama_lengkap
           FROM detail_reservasi dr
           JOIN reservasi r ON dr.id_reservasi = r.id_reservasi
           JOIN jadwal j ON dr.id_jadwal = j.id_jadwal
           JOIN lapangan l ON j.id_lapangan = l.id_lapangan
           JOIN pengguna u ON r.id_pengguna = u.id_pengguna
           WHERE r.tanggal_sewa = :tanggal AND r.status_reservasi != 'dibatalkan'
          ORDER BY j.jam_mulai ASC";

$stmt = $db->prepare($query);
$stmt->execute([':tanggal' => $tanggal_filter]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Harian - Staf O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
    </style>
</head>
<body>
<?php require_once "navbar_karyawan.php"; ?>

<div class="container px-4 pt-5 pb-5 mt-4">
    <div class="card-custom p-4 shadow">
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="fw-bold text-white mb-0">⚽ Jadwal Bermain</h4>
            
            <!-- Elemen Form Filter Tanggal -->
            <form action="dasbor_karyawan.php" method="GET" class="d-flex gap-2">
                <input type="date" name="tanggal" class="form-control bg-dark text-white border-secondary shadow-none" value="<?php echo $tanggal_filter; ?>" onchange="this.form.submit()" required>
                <noscript><button type="submit" class="btn btn-outline-light">Filter</button></noscript>
            </form>
        </div>
        
        <p class="text-secondary mb-4 border-bottom border-secondary pb-3">
            Menampilkan jadwal untuk tanggal: <span class="text-accent fw-bold"><?php echo date('d M Y', strtotime($tanggal_filter)); ?></span>
        </p>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle text-center mb-0">
                <thead class="text-secondary bg-black">
                    <tr>
                        <th class="py-3 border-bottom border-secondary">Jam Main</th>
                        <th class="py-3 border-bottom border-secondary">Unit Lapangan</th>
                        <th class="py-3 border-bottom border-secondary">Nama Penyewa (Tim)</th>
                        <th class="py-3 border-bottom border-secondary text-start ps-4">Kode Booking & Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt->rowCount() > 0): while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="text-accent fw-bold fs-5 border-secondary py-3">
                            <?php echo substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5); ?>
                        </td>
                        <td class="text-white border-secondary"><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                        <td class="text-white fw-semibold border-secondary text-uppercase"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="border-secondary small text-start ps-4">
                            <span class="fw-bold text-white">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></span><br>
                            <span class="text-secondary text-muted" style="font-size: 11px;">Pesan: <?php echo date('d/m/Y, H:i', strtotime($row['waktu_pesan'])); ?></span>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary border-secondary">
                            Belum ada jadwal penyewaan yang terverifikasi untuk tanggal ini.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
/**
 * File: admin/data_pelanggan.php
 * Deskripsi: Halaman khusus Owner untuk melihat daftar seluruh pelanggan yang mendaftar
 */
session_start();
require_once "cek_akses.php";
require_once "../config/database.php";

// Keamanan ekstra: Hanya pemilik yang bisa melihat daftar pelanggan
if ($_SESSION['peran'] != 'pemilik') {
    echo "<script>alert('AKSES DITOLAK! Halaman ini khusus Pemilik.'); window.location.href='index.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Kueri mengambil data pengguna yang perannya adalah 'pelanggan'
$query = "SELECT * FROM pengguna WHERE peran = 'pelanggan' ORDER BY id_pengguna DESC";
$stmt = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Member Terdaftar - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
    </style>
</head>
<body>

    <?php require_once "navbar_pemilik.php"; ?>

    <div class="container-fluid px-4 pt-5 py-4 mb-5 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <a href="dasbor_pemilik.php" class="btn btn-sm btn-outline-light rounded-pill px-4 mb-3">⬅ Kembali ke Dasbor</a>
                <h3 class="fw-bold text-white mb-1 text-uppercase">Data Member / Pelanggan</h3>
                <p class="text-secondary small mb-0">Daftar seluruh pengguna yang telah melakukan registrasi sebagai pelanggan di O2 Futsal.</p>
            </div>
        </div>

        <div class="card-custom p-0 overflow-hidden shadow border border-secondary">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle text-center mb-0">
                    <thead class="text-secondary bg-black">
                        <tr>
                            <th width="5%" class="py-3 border-bottom border-secondary">No</th>
                            <th width="25%" class="py-3 border-bottom border-secondary text-start">Nama Lengkap</th>
                            <th width="25%" class="py-3 border-bottom border-secondary text-start">Kontak (Telp & Email)</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">Status Member</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">Poin Loyalitas</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">Tgl Lahir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($stmt->rowCount() > 0):
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td class="border-secondary py-3 text-secondary"><?php echo $no++; ?></td>
                            <td class="border-secondary text-start ps-3 fw-bold text-white"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td class="border-secondary text-start ps-3 small text-secondary">
                                📞 <?php echo htmlspecialchars($row['nomor_telepon']); ?><br>
                                ✉️ <?php echo htmlspecialchars($row['email']); ?>
                            </td>
                            <td class="border-secondary">
                                <?php if ($row['is_vip'] == 1): ?>
                                    <span class="badge bg-warning text-dark px-3 rounded-pill fw-bold">VIP Member</span>
                                <?php else: ?>
                                    <span class="badge border border-secondary text-secondary px-3 rounded-pill">Reguler</span>
                                <?php endif; ?>
                            </td>
                            <td class="border-secondary fw-bold text-accent fs-5"><?php echo number_format($row['poin_loyalitas'], 0, ',', '.'); ?></td>
                            <td class="border-secondary text-white">
                                <?php echo !empty($row['tanggal_lahir']) ? date('d M Y', strtotime($row['tanggal_lahir'])) : '-'; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="py-5 text-secondary border-secondary">Belum ada pelanggan yang mendaftar.</td>
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
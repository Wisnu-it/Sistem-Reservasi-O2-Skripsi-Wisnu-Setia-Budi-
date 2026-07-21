<?php
/**
 * File: admin/index.php
 * Deskripsi: Dasbor utama Admin dengan Filter, Pencarian, RBAC, dan Cetak
 */
session_start();
require_once "cek_akses.php"; // GEMBOK KEAMANAN
require_once "../config/database.php";

$database = new database();
$db = $database->getKoneksi();

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

$whereClause = "WHERE 1=1"; 
$params = [];

if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $whereClause .= " AND DATE(r.waktu_pesan) BETWEEN :tgl_awal AND :tgl_akhir";
    $params[':tgl_awal'] = $tgl_awal;
    $params[':tgl_akhir'] = $tgl_akhir;
}

if (!empty($keyword)) {
    $id_search = preg_replace('/[^0-9]/', '', $keyword);
    $whereClause .= " AND (u.nama_lengkap LIKE :keyword OR r.id_reservasi = :id_search)";
    $params[':keyword'] = "%$keyword%";
    $params[':id_search'] = $id_search != '' ? (int)$id_search : 0;
}

$query_stat = "SELECT 
                SUM(CASE WHEN r.status_reservasi != 'dibatalkan' THEN r.total_bayar ELSE 0 END) as pendapatan,
                COUNT(r.id_reservasi) as total_trx,
                SUM(CASE WHEN r.status_reservasi = 'menunggu' THEN 1 ELSE 0 END) as pending
               FROM reservasi r JOIN pengguna u ON r.id_pengguna = u.id_pengguna $whereClause";
$stmt_stat = $db->prepare($query_stat);
$stmt_stat->execute($params);
$stat = $stmt_stat->fetch(PDO::FETCH_ASSOC);

$pendapatan = $stat['pendapatan'] ?? 0;
$total_trx = $stat['total_trx'] ?? 0;
$total_pending = $stat['pending'] ?? 0;

$query = "SELECT r.id_reservasi, r.waktu_pesan, r.tanggal_sewa, r.total_bayar, r.status_reservasi, 
                 p.metode_pembayaran, p.status_pembayaran, u.nama_lengkap 
          FROM reservasi r JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
          JOIN pengguna u ON r.id_pengguna = u.id_pengguna 
          $whereClause ORDER BY r.waktu_pesan DESC";
$stmt_pesanan = $db->prepare($query);
$stmt_pesanan->execute($params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/o2futsal/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark">

<?php include "navbar.php"; ?>

<div class="container-fluid px-4 pb-5">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-custom p-4 border-start border-4 border-accent">
                <p class="text-secondary mb-1 fw-semibold">Pendapatan (Sesuai Filter)</p>
                <h2 class="text-white fw-bold mb-0">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-4 border-start border-4 border-primary">
                <p class="text-secondary mb-1 fw-semibold">Total Pesanan (Sesuai Filter)</p>
                <h2 class="text-white fw-bold mb-0"><?php echo $total_trx; ?> <span class="fs-6 text-secondary fw-normal">Transaksi</span></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-4 border-start border-4 border-warning">
                <p class="text-secondary mb-1 fw-semibold">Menunggu Pembayaran</p>
                <h2 class="text-white fw-bold mb-0"><?php echo $total_pending; ?> <span class="fs-6 text-secondary fw-normal">Antrean</span></h2>
            </div>
        </div>
    </div>

    <div class="card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-white mb-0">Data Reservasi</h4>
        </div>

        <form method="GET" action="index.php" class="row g-3 mb-4 bg-black p-3 rounded-3 border border-secondary">
            <div class="col-md-3">
                <label class="form-label text-secondary small mb-1">Mulai Tanggal (Order)</label>
                <input type="date" name="tgl_awal" class="form-control bg-dark text-white border-secondary" value="<?php echo $tgl_awal; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-secondary small mb-1">Sampai Tanggal (Order)</label>
                <input type="date" name="tgl_akhir" class="form-control bg-dark text-white border-secondary" value="<?php echo $tgl_akhir; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label text-secondary small mb-1">Pencarian</label>
                <input type="text" name="keyword" class="form-control bg-dark text-white border-secondary" placeholder="Cari Nama / ID Pesanan" value="<?php echo $keyword; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Cari / Filter</button>
                <a href="index.php" class="btn btn-outline-light">Reset</a>
            </div>
        </form>

        <div class="mb-3">
            <a href="cetak_rekap.php?tgl_awal=<?php echo $tgl_awal; ?>&tgl_akhir=<?php echo $tgl_akhir; ?>&keyword=<?php echo $keyword; ?>" target="_blank" class="btn btn-light fw-bold px-4">🖨️ Cetak Rekap Laporan</a>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead class="text-secondary">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Waktu Pesan</th>
                        <th>Nama Pelanggan</th>
                        <th>Tgl Main</th>
                        <th>Metode Bayar</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt_pesanan->rowCount() > 0): while ($row = $stmt_pesanan->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="text-accent fw-bold">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="text-white small"><?php echo date('d M Y, H:i', strtotime($row['waktu_pesan'])); ?></td>
                        <td class="text-white"><?php echo $row['nama_lengkap']; ?></td>
                        <td class="text-white"><?php echo date('d M Y', strtotime($row['tanggal_sewa'])); ?></td>
                        <td>
                            <?php if($row['metode_pembayaran'] == 'tunai'): ?>
                                <span class="badge bg-secondary text-white">Tunai/Kasir</span>
                            <?php else: ?>
                                <span class="badge border border-accent text-accent">QRIS/Transfer</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-white fw-semibold">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if($row['status_reservasi'] == 'dibatalkan'): ?>
                                <span class="badge bg-danger">Dibatalkan</span>
                            <?php elseif($row['status_pembayaran'] == 'lunas'): ?>
                                <span class="badge bg-success text-dark fw-bold">Lunas</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['status_pembayaran'] != 'lunas' && $row['status_reservasi'] != 'dibatalkan' && $row['metode_pembayaran'] == 'tunai'): ?>
                                <form action="proses_tunai.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id_reservasi" value="<?php echo $row['id_reservasi']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill fw-bold" onclick="return confirm('Konfirmasi terima tunai?')">Terima Uang</button>
                                </form>
                            <?php elseif($row['status_pembayaran'] == 'lunas'): ?>
                                <a href="cetak_struk.php?id=<?php echo $row['id_reservasi']; ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3">Cetak Struk</a>
                            <?php else: ?>
                                <span class="text-secondary small"><em>Selesai</em></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-secondary">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
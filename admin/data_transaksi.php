<?php
/**
 * File: admin/data_transaksi.php
 * Deskripsi: Halaman Manajemen Data Transaksi dengan Filter Tanggal Main
 */
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id_pengguna']) || !in_array(strtolower($_SESSION['peran']), ['pemilik', 'admin', 'kasir', 'karyawan'])) {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// =========================================================
// FITUR AKSI: KONFIRMASI LUNAS & BATALKAN PESANAN
// =========================================================
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_reservasi = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    if ($aksi == 'lunas') {
        echo "<script>window.location.href='proses_tunai.php?id=$id_reservasi';</script>";
        exit;
    } elseif ($aksi == 'batal') {
        $update = $db->prepare("UPDATE reservasi SET status_reservasi = 'dibatalkan' WHERE id_reservasi = ?");
        $update2 = $db->prepare("UPDATE pembayaran SET status_pembayaran = 'gagal' WHERE id_reservasi = ?");
        if ($update->execute([$id_reservasi]) && $update2->execute([$id_reservasi])) {
            echo "<script>alert('Pesanan berhasil DIBATALKAN.'); window.location.href='data_transaksi.php';</script>";
        }
    }
}

// =========================================================
// LOGIKA FILTER TANGGAL MAIN
// =========================================================
// Tangkap parameter tanggal, jika kosong maka biarkan kosong (tampilkan semua data)
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

$whereClause = "";
$params = [];

// Jika kasir memilih tanggal, tambahkan pengondisian di SQL
if (!empty($tanggal_filter)) {
    $whereClause = "WHERE r.tanggal_sewa = :tanggal";
    $params[':tanggal'] = $tanggal_filter;
}

// =========================================================
// MENGAMBIL DATA (TERMASUK METODE PEMBAYARAN & FILTER TANGGAL)
// =========================================================
$query = "SELECT r.*, p.nama_lengkap, p.nomor_telepon, pb.metode_pembayaran, pb.status_pembayaran,
              (SELECT j.jam_mulai FROM detail_reservasi dr JOIN jadwal j ON dr.id_jadwal = j.id_jadwal WHERE dr.id_reservasi = r.id_reservasi ORDER BY j.jam_mulai ASC LIMIT 1) as jam_awal,
              (SELECT j2.jam_selesai FROM detail_reservasi dr2 JOIN jadwal j2 ON dr2.id_jadwal = j2.id_jadwal WHERE dr2.id_reservasi = r.id_reservasi ORDER BY j2.jam_selesai DESC LIMIT 1) as jam_akhir
       FROM reservasi r
       JOIN pengguna p ON r.id_pengguna = p.id_pengguna
       LEFT JOIN pembayaran pb ON r.id_reservasi = pb.id_reservasi
       $whereClause
       ORDER BY r.waktu_pesan DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; color: #000; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
        .badge-menunggu { background-color: #ffc107; color: #000; font-weight: bold; }
        .badge-lunas { background-color: #198754; color: #fff; font-weight: bold; }
        .badge-batal { background-color: #dc3545; color: #fff; font-weight: bold; }
    </style>
</head>
<body>
    <?php 
    if (strtolower($_SESSION['peran']) == 'pemilik') {
        require_once "navbar_pemilik.php"; 
    } else {
        require_once "navbar_karyawan.php"; 
    }
    ?>

    <div class="container-fluid px-4 pt-5 py-4 mb-5 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold text-white mb-1 text-uppercase">Manajemen Data Transaksi</h3>
                <p class="text-secondary small mb-0">Pantau waktu order, jadwal main, metode transaksi, dan kelola konfirmasi pembayaran pelanggan.</p>
            </div>
            
            <!-- FORM FILTER TANGGAL MAIN -->
            <form action="data_transaksi.php" method="GET" class="d-flex gap-2">
                <input type="date" name="tanggal" class="form-control bg-dark text-white border-secondary shadow-none" value="<?php echo htmlspecialchars($tanggal_filter); ?>" onchange="this.form.submit()">
                <?php if(!empty($tanggal_filter)): ?>
                    <a href="data_transaksi.php" class="btn btn-outline-danger">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-custom p-0 overflow-hidden shadow border border-secondary">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle text-center mb-0" style="min-width: 1000px;">
                    <thead class="text-secondary bg-black">
                        <tr>
                            <th width="5%" class="py-3 border-bottom border-secondary">No</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">ID & Waktu Order</th>
                            <th width="15%" class="py-3 border-bottom border-secondary text-accent">Jadwal Main</th>
                            <th width="18%" class="py-3 border-bottom border-secondary">Pelanggan</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">Total & Metode</th>
                            <th width="15%" class="py-3 border-bottom border-secondary">Status</th>
                            <th width="17%" class="py-3 border-bottom border-secondary">Aksi / Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($stmt->rowCount() > 0):
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                
                                // Deteksi Status
                                $status = strtolower($row['status_reservasi']);
                                if ($status == 'menunggu') {
                                    $badge = 'badge-menunggu';
                                    $teks_status = 'Menunggu Pembayaran';
                                } elseif (in_array($status, ['dikonfirmasi', 'selesai', 'lunas'])) {
                                    $badge = 'badge-lunas';
                                    $teks_status = 'LUNAS / SELESAI';
                                } else {
                                    $badge = 'badge-batal';
                                    $teks_status = 'Dibatalkan / Gagal';
                                }

                                // Format Waktu Order
                                $waktu_order = date('d/m/Y, H:i', strtotime($row['waktu_pesan']));

                                // Format Jadwal Main
                                $tgl_main = date('d M Y', strtotime($row['tanggal_sewa']));
                                $jam_awal = !empty($row['jam_awal']) ? substr($row['jam_awal'], 0, 5) : '--:--';
                                $jam_akhir = !empty($row['jam_akhir']) ? substr($row['jam_akhir'], 0, 5) : '--:--';
                                $jam_main = $jam_awal . ' - ' . $jam_akhir;

                                // Format Human-Friendly untuk Metode Pembayaran
                                $metode = strtoupper($row['metode_pembayaran'] ?? '');
                                if ($metode == 'PAYMENT_GATEWAY' || $metode == 'QRIS') {
                                    $metode_pembayaran_html = "<span class='text-info small'>💻 Online (Midtrans/QRIS)</span>";
                                } elseif ($metode == 'TUNAI' || $metode == 'CASH' || $metode == 'OFFLINE') {
                                    $metode_pembayaran_html = "<span class='text-warning small'>💵 Bayar di Kasir</span>";
                                } else {
                                    $metode_pembayaran_html = "<span class='text-secondary small'>-</span>";
                                }
                        ?>
                        <tr>
                            <td class="border-secondary py-3 text-secondary"><?php echo $no++; ?></td>
                            
                            <td class="border-secondary small text-start ps-3">
                                <span class="fw-bold text-white">#ORD-<?php echo str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?></span><br>
                                <!-- PERBAIKAN WARNA TEKS ADA DI BARIS BAWAH INI -->
                                <span style="color: #adb5bd; font-size: 11px;">Pesan: <?php echo $waktu_order; ?></span>
                            </td>

                            <td class="border-secondary small" style="background-color: rgba(196, 255, 0, 0.02);">
                                <span class="text-accent fw-bold fs-6"><?php echo $tgl_main; ?></span><br>
                                <span class="text-white"><?php echo $jam_main; ?> WITA</span>
                            </td>

                            <td class="border-secondary text-start ps-3">
                                <?php echo htmlspecialchars($row['nama_lengkap']); ?><br>
                                <span style="color: #adb5bd; font-size: 12px;">📞 <?php echo htmlspecialchars($row['nomor_telepon']); ?></span>
                            </td>
                            
                            <td class="border-secondary text-center">
                                <span class="fw-bold text-white">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></span><br>
                                <?php echo $metode_pembayaran_html; ?>
                            </td>
                            
                            <td class="border-secondary">
                                <span class="badge <?php echo $badge; ?> px-3 py-2 rounded-pill" style="font-size: 11px;"><?php echo $teks_status; ?></span>
                            </td>
                            
                            <td class="border-secondary">
                                <?php if ($status == 'menunggu'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="data_transaksi.php?aksi=lunas&id=<?php echo $row['id_reservasi']; ?>"
                                           class="btn btn-sm btn-success fw-bold px-3 rounded-pill"
                                           onclick="return confirm('Terima uang tunai? Yakin tandai LUNAS?');">
                                            LUNAS
                                        </a>
                                        <a href="data_transaksi.php?aksi=batal&id=<?php echo $row['id_reservasi']; ?>"
                                           class="btn btn-sm btn-outline-danger fw-bold px-3 rounded-pill"
                                           onclick="return confirm('Yakin membatalkan pesanan ini?');">
                                            TOLAK
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary small"><em>Selesai</em></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="py-5 text-secondary border-secondary">Tidak ada data transaksi ditemukan untuk tanggal tersebut.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
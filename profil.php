<?php
/**
 * File: profil.php
 * Deskripsi: Halaman profil pelanggan & Riwayat Pesanan (Dilengkapi Tombol Dinamis E-Ticket)
 */
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran'] != 'pelanggan') {
    header("Location: login.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();
$id_pengguna = $_SESSION['id_pengguna'];

// 1. Ambil Data Profil Pengguna
$stmt_user = $db->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt_user->execute([$id_pengguna]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 2. Ambil Riwayat Pesanan (Dengan Sub-query untuk jam mulai)
$query_history = "
    SELECT r.*, 
           (SELECT j.jam_mulai FROM detail_reservasi dr JOIN jadwal j ON dr.id_jadwal = j.id_jadwal WHERE dr.id_reservasi = r.id_reservasi ORDER BY j.jam_mulai ASC LIMIT 1) as jam_awal
    FROM reservasi r 
    WHERE r.id_pengguna = ? 
    ORDER BY r.id_reservasi DESC
";
$stmt_history = $db->prepare($query_history);
$stmt_history->execute([$id_pengguna]);

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card-custom p-4 text-center h-100 border border-secondary">
                <div class="d-inline-block bg-accent text-dark fw-bold rounded-circle mb-3 d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 40px;">
                    <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                </div>
                <h3 class="text-white fw-bold text-uppercase mb-1"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h3>
                
                <?php if(isset($user['is_vip']) && $user['is_vip'] == 1): ?>
                    <p class="text-warning small mb-4 fw-bold">👑 VIP Member O2 Futsal</p>
                <?php else: ?>
                    <p class="text-secondary small mb-4">Member Reguler</p>
                <?php endif; ?>

                <div class="border-top border-secondary pt-4 mt-2">
                    <p class="text-secondary small mb-1">Total Poin Loyalitas</p>
                    <h1 class="text-accent fw-bold display-4"><?php echo number_format($user['poin_loyalitas'], 0, ',', '.'); ?></h1>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary">
                    <a href="ganti_password.php" class="btn btn-outline-secondary w-100 rounded-pill small py-2 fw-semibold text-white hover-accent">
                        🔒 Ganti Kata Sandi
                    </a>
                </div>

            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-custom p-4 h-100 border border-secondary border-start border-4 border-start-accent">
                <h4 class="fw-bold text-white mb-4">RIWAYAT <span class="text-accent">PESANAN</span></h4>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle text-center">
                        <thead class="text-secondary">
                            <tr>
                                <th>No.</th>
                                <th>Waktu Order</th>
                                <th>Tgl Main</th>
                                <th>Jam</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $nomor = 1; 
                            if ($stmt_history->rowCount() > 0): 
                                while ($row = $stmt_history->fetch(PDO::FETCH_ASSOC)): 
                                    
                                    // Pengecekan kolom waktu pesan di database
                                    $waktu_order = "-";
                                    if(isset($row['waktu_pesan'])) {
                                        $waktu_order = date('d M Y, H:i', strtotime($row['waktu_pesan']));
                                    } elseif(isset($row['created_at'])) {
                                        $waktu_order = date('d M Y, H:i', strtotime($row['created_at']));
                                    }
                                    
                                    $tgl_main = date('d M Y', strtotime($row['tanggal_sewa']));
                                    $jam_main = isset($row['jam_awal']) ? substr($row['jam_awal'], 0, 5) : '-';

                                    // Render Badge Status & Tombol Dinamis
                                    $status = strtolower($row['status_reservasi']);
                                    $badge = "";
                                    $btn_aksi = "";

                                    if ($status == 'lunas' || $status == 'dikonfirmasi' || $status == 'selesai') {
                                        $badge = '<span class="badge bg-success text-white rounded-pill px-3">Lunas</span>';
                                        $btn_aksi = '<a href="tiket.php?id='.$row['id_reservasi'].'" class="btn btn-sm btn-accent fw-bold text-dark rounded-pill px-3">🎟️ E-Ticket</a>';
                                    } elseif ($status == 'dibatalkan') {
                                        $badge = '<span class="badge bg-danger text-white rounded-pill px-3">Dibatalkan</span>';
                                        $btn_aksi = '<span class="text-secondary small"><i>-</i></span>';
                                    } else {
                                        $badge = '<span class="badge bg-warning text-dark rounded-pill px-3">Menunggu</span>';
                                        $btn_aksi = '<a href="pembayaran.php?id='.$row['id_reservasi'].'" class="btn btn-sm btn-outline-warning rounded-pill px-3">💳 Bayar</a>';
                                    }
                            ?>
                            <tr>
                                <td class="text-white fw-bold"><?php echo $nomor++; ?></td>
                                <td class="text-secondary small"><?php echo $waktu_order; ?></td>
                                <td class="text-white"><?php echo $tgl_main; ?></td>
                                <td class="text-accent fw-bold"><?php echo $jam_main; ?></td>
                                <td class="text-white">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                <td><?php echo $badge; ?></td>
                                <td><?php echo $btn_aksi; ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr><td colspan="7" class="text-secondary py-5">Belum ada riwayat transaksi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    /* Sedikit efek hover agar tombol ganti sandi terlihat interaktif */
    .hover-accent:hover {
        background-color: #c4ff00 !important;
        color: #000 !important;
        border-color: #c4ff00 !important;
    }
    .border-start-accent { border-left-color: #c4ff00 !important; }
</style>

<?php require_once "layout/footer.php"; ?>
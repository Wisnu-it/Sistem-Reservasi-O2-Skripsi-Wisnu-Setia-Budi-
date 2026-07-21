<?php
/**
 * File: tukar_poin.php
 * Deskripsi: Penukaran poin loyalitas dengan penyesuaian harga (Balancing Economy)
 */
session_start();
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran'] != 'pelanggan') {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$database = new database();
$db = $database->getKoneksi();
$id_pengguna = $_SESSION['id_pengguna'];

// PROSES PENUKARAN POIN DENGAN HARGA BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    
    $stmt_cek = $db->prepare("SELECT poin_loyalitas, is_vip FROM pengguna WHERE id_pengguna = :id");
    $stmt_cek->execute([':id' => $id_pengguna]);
    $user_data = $stmt_cek->fetch(PDO::FETCH_ASSOC);
    
    $poin_sekarang = (int)$user_data['poin_loyalitas'];
    $is_vip = $user_data['is_vip'];

    try {
        $db->beginTransaction();

        // HARGA BARU: VOUCHER 50K = 80 POIN
        if ($aksi == 'tukar_voucher' && $poin_sekarang >= 80) {
            $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas - 80, voucher_50k = voucher_50k + 1 WHERE id_pengguna = $id_pengguna");
            $_SESSION['poin_loyalitas'] -= 80;
            echo "<script>alert('Berhasil! Voucher Rp 50.000 telah ditambahkan.'); window.location.href='tukar_poin.php';</script>";
        } 
        // HARGA BARU: UPGRADE VIP = 150 POIN
        elseif ($aksi == 'tukar_vip' && $poin_sekarang >= 150 && $is_vip == 0) {
            $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas - 150, is_vip = 1 WHERE id_pengguna = $id_pengguna");
            $_SESSION['poin_loyalitas'] -= 150;
            echo "<script>alert('Selamat! Kamu sekarang VIP Member.'); window.location.href='tukar_poin.php';</script>";
        }
        // HARGA BARU: MINUMAN = 10 POIN
        elseif ($aksi == 'tukar_minuman' && $poin_sekarang >= 10) {
            $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas - 10, voucher_minuman = voucher_minuman + 1 WHERE id_pengguna = $id_pengguna");
            $_SESSION['poin_loyalitas'] -= 10;
            echo "<script>alert('Berhasil! Tunjukkan voucher ini ke Kasir untuk klaim Minuman Gratis.'); window.location.href='tukar_poin.php';</script>";
        }
        // HARGA BARU: SEPATU = 20 POIN
        elseif ($aksi == 'tukar_sepatu' && $poin_sekarang >= 20) {
            $db->query("UPDATE pengguna SET poin_loyalitas = poin_loyalitas - 20, voucher_sepatu = voucher_sepatu + 1 WHERE id_pengguna = $id_pengguna");
            $_SESSION['poin_loyalitas'] -= 20;
            echo "<script>alert('Berhasil! Tunjukkan voucher ini ke Kasir untuk klaim Sewa Sepatu Gratis.'); window.location.href='tukar_poin.php';</script>";
        } else {
            echo "<script>alert('Poin tidak mencukupi atau aksi tidak valid.');</script>";
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        echo "<script>alert('Gagal mengeksekusi penukaran.');</script>";
    }
}

// AMBIL DATA INVENTARIS TERBARU
$stmt_tampil = $db->prepare("SELECT * FROM pengguna WHERE id_pengguna = :id");
$stmt_tampil->execute([':id' => $id_pengguna]);
$user_info = $stmt_tampil->fetch(PDO::FETCH_ASSOC);

require_once "layout/header.php"; 
?>

<div class="container my-5 pb-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-white mb-2">REWARD <span class="text-accent">MEMBER</span></h2>
        <p class="text-secondary">Tukarkan poin loyalitasmu dengan keuntungan eksklusif di O2 Futsal.</p>
    </div>

    <!-- TAMPILAN SALDO POIN & INVENTARIS -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
            <div class="card-custom p-4 border border-secondary bg-black rounded-4">
                <h5 class="text-secondary mb-2">Total Poin Loyalitas:</h5>
                <h1 class="display-3 text-accent fw-bold mb-0"><?php echo $user_info['poin_loyalitas']; ?> <span class="fs-5 text-white fw-normal">Pts</span></h1>
                
                <div class="row mt-4 pt-4 border-top border-secondary text-white">
                    <div class="col-3 border-end border-secondary">
                        <small class="text-secondary d-block">Voucher Rp50k</small>
                        <span class="fs-4 fw-bold"><?php echo $user_info['voucher_50k']; ?></span>
                    </div>
                    <div class="col-3 border-end border-secondary">
                        <small class="text-secondary d-block">Voucher Minum</small>
                        <span class="fs-4 fw-bold"><?php echo $user_info['voucher_minuman']; ?></span>
                    </div>
                    <div class="col-3 border-end border-secondary">
                        <small class="text-secondary d-block">Voucher Sepatu</small>
                        <span class="fs-4 fw-bold"><?php echo $user_info['voucher_sepatu']; ?></span>
                    </div>
                    <div class="col-3">
                        <small class="text-secondary d-block">Status</small>
                        <?php echo ($user_info['is_vip'] == 1) ? '<span class="text-warning fw-bold fs-5">👑 VIP</span>' : '<span>Reguler</span>'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KATALOG PENUKARAN -->
    <div class="row g-4">
        <!-- KATALOG 1: Minuman -->
        <div class="col-md-3">
            <div class="card-custom p-4 h-100 d-flex flex-column text-center">
                <h1 class="display-4 mb-3">🥤</h1>
                <h5 class="text-white fw-bold mb-2">Gratis Minuman</h5>
                <p class="text-secondary small mb-4">1 Botol Air Mineral / Minuman Olahraga.</p>
                <div class="mt-auto">
                    <form action="" method="POST">
                        <input type="hidden" name="aksi" value="tukar_minuman">
                        <button type="submit" class="btn btn-outline-light w-100 rounded-pill" <?php echo ($user_info['poin_loyalitas'] < 10) ? 'disabled' : ''; ?> onclick="return confirm('Tukar 10 poin?')">TUKAR (10 Pts)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- KATALOG 2: Sepatu -->
        <div class="col-md-3">
            <div class="card-custom p-4 h-100 d-flex flex-column text-center">
                <h1 class="display-4 mb-3">👟</h1>
                <h5 class="text-white fw-bold mb-2">Sewa Sepatu</h5>
                <p class="text-secondary small mb-4">Gratis sewa 1 pasang sepatu futsal per sesi bermain.</p>
                <div class="mt-auto">
                    <form action="" method="POST">
                        <input type="hidden" name="aksi" value="tukar_sepatu">
                        <button type="submit" class="btn btn-outline-light w-100 rounded-pill" <?php echo ($user_info['poin_loyalitas'] < 20) ? 'disabled' : ''; ?> onclick="return confirm('Tukar 20 poin?')">TUKAR (20 Pts)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- KATALOG 3: Voucher 50k -->
        <div class="col-md-3">
            <div class="card-custom p-4 h-100 d-flex flex-column text-center border-start border-2 border-accent">
                <h1 class="display-4 mb-3">🎟️</h1>
                <h5 class="text-accent fw-bold mb-2">Diskon 50 Ribu</h5>
                <p class="text-secondary small mb-4">Potongan harga untuk pesanan lapangan via web.</p>
                <div class="mt-auto">
                    <form action="" method="POST">
                        <input type="hidden" name="aksi" value="tukar_voucher">
                        <button type="submit" class="btn btn-accent text-dark fw-bold w-100 rounded-pill" <?php echo ($user_info['poin_loyalitas'] < 80) ? 'disabled' : ''; ?> onclick="return confirm('Tukar 80 poin?')">TUKAR (80 Pts)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- KATALOG 4: VIP -->
        <div class="col-md-3">
            <div class="card-custom p-4 h-100 d-flex flex-column text-center border-start border-2 border-warning">
                <h1 class="display-4 mb-3">👑</h1>
                <h5 class="text-warning fw-bold mb-2">Upgrade VIP</h5>
                <p class="text-secondary small mb-4">Diskon permanen Rp 20rb/jam & Gratis main di hari Ultah!</p>
                <div class="mt-auto">
                    <form action="" method="POST">
                        <input type="hidden" name="aksi" value="tukar_vip">
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill" <?php echo ($user_info['poin_loyalitas'] < 150 || $user_info['is_vip'] == 1) ? 'disabled' : ''; ?> onclick="return confirm('Tukar 150 poin?')">
                            <?php echo ($user_info['is_vip'] == 1) ? 'SUDAH VIP' : 'TUKAR (150 Pts)'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
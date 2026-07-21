<?php
/**
 * File: checkout.php
 * Deskripsi: Halaman Ringkasan Pesanan (Dengan Logika Promo Jam Sepi / Happy Hour)
 */
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran'] != 'pelanggan') {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();
$id_pengguna = $_SESSION['id_pengguna'];

$stmt_user = $db->prepare("SELECT nama_lengkap, is_vip, voucher_50k, tanggal_lahir FROM pengguna WHERE id_pengguna = :id");
$stmt_user->execute([':id' => $id_pengguna]);
$pelanggan = $stmt_user->fetch(PDO::FETCH_ASSOC);

$id_lapangan = isset($_POST['id_lapangan']) ? $_POST['id_lapangan'] : 1;
$tanggal_sewa = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
$jadwal_dipilih = isset($_POST['id_jadwal']) ? $_POST['id_jadwal'] : [];
$jumlah_jam_dipesan = count($jadwal_dipilih);

if ($jumlah_jam_dipesan == 0) {
    echo "<script>alert('Error: Kamu belum memilih jam bermain!'); window.location.href='reservasi.php';</script>";
    exit;
}

// Ambil harga asli lapangan dari database
$stmt_lap = $db->prepare("SELECT harga_per_jam FROM lapangan WHERE id_lapangan = ?");
$stmt_lap->execute([$id_lapangan]);
$harga_per_jam = $stmt_lap->fetchColumn() ?: 200000;
$harga_kotor = $jumlah_jam_dipesan * $harga_per_jam;

$total_diskon = 0;
$pesan_promo = [];
$pakai_voucher = isset($_POST['pakai_voucher']) ? true : false;
$hari_ini = date('m-d');

// =========================================================================
// 1. LOGIKA PROMO JAM SEPI (HAPPY HOUR) - DISKON RP 30.000/JAM
// =========================================================================
$hari_main = date('N', strtotime($tanggal_sewa)); // Angka 1 (Senin) sampai 7 (Minggu)
$diskon_jam_sepi_per_jam = 30000;
$total_diskon_sepi = 0;
$jumlah_jam_sepi = 0;

// Sistem mengecek satu per satu jam yang dipilih oleh pelanggan
foreach ($jadwal_dipilih as $id_j) {
    $stmt_cek_jam = $db->prepare("SELECT jam_mulai FROM jadwal WHERE id_jadwal = ?");
    $stmt_cek_jam->execute([$id_j]);
    $jam_mulai_angka = (int) substr($stmt_cek_jam->fetchColumn(), 0, 2);

    // ATURAN: Jika hari Senin-Jumat (1 s.d 5) DAN jam main antara 08:00 s.d 15:00
    if ($hari_main >= 1 && $hari_main <= 5 && $jam_mulai_angka >= 8 && $jam_mulai_angka <= 15) {
        $total_diskon_sepi += $diskon_jam_sepi_per_jam;
        $jumlah_jam_sepi++;
    }
}

// Jika ada jam sepi yang terdeteksi, masukkan ke struk potongan harga
if ($jumlah_jam_sepi > 0) {
    $total_diskon += $total_diskon_sepi;
    $pesan_promo[] = "🔥 Promo Happy Hour ($jumlah_jam_sepi Jam) (-Rp " . number_format($total_diskon_sepi, 0, ',', '.') . ")";
}

// =========================================================================
// 2. LOGIKA REWARD ULANG TAHUN & VIP
// =========================================================================
if ($pelanggan['is_vip'] == 1 && !empty($pelanggan['tanggal_lahir'])) {
    if (date('m-d', strtotime($pelanggan['tanggal_lahir'])) == $hari_ini) {
        $total_diskon += $harga_per_jam;
        $pesan_promo[] = "🎂 HBD! Gratis Bermain 1 Jam (-Rp ".number_format($harga_per_jam, 0, ',', '.').")";
    }
}

if ($pelanggan['is_vip'] == 1) {
    $diskon_vip = 20000 * $jumlah_jam_dipesan;
    $total_diskon += $diskon_vip;
    $pesan_promo[] = "👑 Benefit VIP Member (-Rp " . number_format($diskon_vip, 0, ',', '.') . ")";
}

// =========================================================================
// 3. LOGIKA VOUCHER LOYALITAS
// =========================================================================
if ($pakai_voucher && $pelanggan['voucher_50k'] > 0) {
    $total_diskon += 50000;
    $pesan_promo[] = "🎟️ Voucher Diskon 50k Diaktifkan";
    $db->query("UPDATE pengguna SET voucher_50k = voucher_50k - 1 WHERE id_pengguna = $id_pengguna");
    $pelanggan['voucher_50k'] -= 1; 
}

$total_bayar_bersih = $harga_kotor - $total_diskon;
if ($total_bayar_bersih < 0) { $total_bayar_bersih = 0; }

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="fw-bold text-white mb-4">Ringkasan <span class="text-accent">Pesanan</span></h2>

            <div class="card-custom p-4 border border-secondary mb-4">
                <h5 class="text-white border-bottom border-secondary pb-3 mb-3">Rincian Harga Dasar</h5>
                <div class="d-flex justify-content-between text-secondary mb-2">
                    <span>Sewa Lapangan (<?php echo $jumlah_jam_dipesan; ?> Jam x Rp <?php echo number_format($harga_per_jam, 0, ',', '.'); ?>)</span>
                    <span class="text-white">Rp <?php echo number_format($harga_kotor, 0, ',', '.'); ?></span>
                </div>

                <?php if ($pelanggan['voucher_50k'] > 0 && !$pakai_voucher): ?>
                    <div class="mt-4 p-3 bg-black border border-accent rounded text-center">
                        <span class="text-accent d-block mb-2">Kamu memiliki <?php echo $pelanggan['voucher_50k']; ?> Voucher Rp 50.000!</span>
                        <form action="" method="POST">
                            <input type="hidden" name="id_lapangan" value="<?php echo $id_lapangan; ?>">
                            <input type="hidden" name="tanggal" value="<?php echo $tanggal_sewa; ?>">
                            <?php foreach($jadwal_dipilih as $j): ?>
                                <input type="hidden" name="id_jadwal[]" value="<?php echo $j; ?>">
                            <?php endforeach; ?>
                            <input type="hidden" name="pakai_voucher" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-accent rounded-pill px-4">Gunakan Voucher</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($pesan_promo) > 0): ?>
            <div class="card-custom p-4 border border-warning mb-4 bg-black">
                <h5 class="text-warning border-bottom border-secondary pb-3 mb-3">Promo & Diskon Loyalitas</h5>
                <?php foreach ($pesan_promo as $promo): ?>
                    <div class="d-flex justify-content-between text-secondary mb-2">
                        <span class="text-warning"><?php echo $promo; ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between border-top border-secondary pt-2 mt-2">
                    <span class="text-white">Total Potongan Harga:</span>
                    <strong class="text-warning">- Rp <?php echo number_format($total_diskon, 0, ',', '.'); ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-custom p-4 border border-accent mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="text-white mb-0">Total Pembayaran</h4>
                    <h2 class="text-accent fw-bold mb-0">Rp <?php echo number_format($total_bayar_bersih, 0, ',', '.'); ?></h2>
                </div>
            </div>

            <form action="proses_bayar.php" method="POST">
                <input type="hidden" name="id_lapangan" value="<?php echo $id_lapangan; ?>">
                <input type="hidden" name="tanggal_sewa" value="<?php echo $tanggal_sewa; ?>">
                <input type="hidden" name="total_bayar_bersih" value="<?php echo $total_bayar_bersih; ?>">
                <?php foreach($jadwal_dipilih as $id_j): ?>
                    <input type="hidden" name="id_jadwal[]" value="<?php echo $id_j; ?>">
                <?php endforeach; ?>
                
                <div class="card-custom p-4 border border-secondary mb-4 text-start bg-black">
                    <h5 class="text-white border-bottom border-secondary pb-3 mb-3">Pilih Metode Pembayaran</h5>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input bg-dark border-secondary mt-2" type="radio" name="metode_pembayaran" id="qris" value="payment_gateway" checked>
                        <label class="form-check-label text-white fw-bold" for="qris">
                            📱 QRIS / E-Wallet <br><span class="text-secondary small fw-normal">Scan barcode menggunakan GoPay, DANA, ShopeePay, atau M-Banking.</span>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input bg-dark border-secondary mt-2" type="radio" name="metode_pembayaran" id="tunai" value="tunai">
                        <label class="form-check-label text-white fw-bold" for="tunai">
                            💵 Bayar Tunai (Di Kasir) <br><span class="text-secondary small fw-normal">Selesaikan pembayaran secara tunai saat tiba di lokasi O2 Futsal.</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-accent text-dark fw-bold w-100 py-3 fs-5 rounded-pill">
                    Lanjutkan Pembayaran
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
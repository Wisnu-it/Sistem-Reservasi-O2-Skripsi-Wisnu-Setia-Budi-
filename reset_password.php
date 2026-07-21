<?php
/**
 * File: reset_password.php
 * Deskripsi: Tahap 2 - Validasi Token dan Proses Perubahan Sandi Baru
 */
session_start();
require_once "config/database.php";

$database = new database();
$db = $database->getKoneksi();
$pesan_error = "";
$pesan_sukses = "";

// Cek apakah ada token di URL
if (!isset($_GET['token']) && !isset($_POST['token'])) {
    die("<div style='color:white;text-align:center;margin-top:50px;'><h3>Akses Ditolak. Token tidak ditemukan.</h3></div>");
}

$token = isset($_GET['token']) ? $_GET['token'] : $_POST['token'];

// Validasi Token di Database (Apakah ada? Apakah sudah lewat 15 menit?)
$stmt_cek = $db->prepare("SELECT email, dibuat_pada FROM password_resets WHERE token = ? LIMIT 1");
$stmt_cek->execute([$token]);
$data_reset = $stmt_cek->fetch(PDO::FETCH_ASSOC);

if (!$data_reset) {
    die("<div style='color:white;text-align:center;margin-top:50px;'><h3>Token tidak valid atau sudah digunakan. Silakan minta link baru.</h3></div>");
}

// Cek Kadaluarsa (15 Menit)
$waktu_dibuat = strtotime($data_reset['dibuat_pada']);
$waktu_sekarang = time();
if (($waktu_sekarang - $waktu_dibuat) > 900) { // 900 detik = 15 menit
    $db->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    die("<div style='color:white;text-align:center;margin-top:50px;'><h3>Token ini sudah kedaluwarsa (Lebih dari 15 Menit). Silakan minta link baru.</h3></div>");
}

// PROSES SIMPAN PASSWORD BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password_baru'])) {
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $email = $data_reset['email'];

    if ($password_baru !== $konfirmasi) {
        $pesan_error = "Konfirmasi kata sandi tidak cocok!";
    } else {
        // Enkripsi dan Simpan
        $password_enkripsi = password_hash($password_baru, PASSWORD_DEFAULT);
        
        $stmt_update = $db->prepare("UPDATE pengguna SET kata_sandi = ? WHERE email = ?");
        try {
            $stmt_update->execute([$password_enkripsi, $email]);
        } catch (Exception $e) {
            // Fallback jika nama kolomnya 'password'
            $stmt_update_alt = $db->prepare("UPDATE pengguna SET password = ? WHERE email = ?");
            $stmt_update_alt->execute([$password_enkripsi, $email]);
        }

        // Hapus Token agar tidak bisa dipakai 2 kali
        $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        $pesan_sukses = "Kata sandi Anda berhasil diperbarui!";
    }
}

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card-custom p-5 text-center border border-secondary shadow-lg">
                
                <h2 class="fw-bold text-white mb-1">BUAT SANDI BARU</h2>
                <p class="text-accent mb-4" style="font-size: 0.8rem; letter-spacing: 2px;">UNTUK: <?php echo strtoupper($data_reset['email']); ?></p>

                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger py-2 small border-danger bg-transparent text-danger mb-4">
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_sukses): ?>
                    <div class="alert alert-success py-3 border-success bg-transparent text-success mb-4">
                        <strong>✅ BERHASIL!</strong><br> <?php echo $pesan_sukses; ?>
                    </div>
                    <a href="login.php" class="btn btn-accent w-100 py-3 fw-bold rounded-pill text-dark">LANJUT LOG MASUK</a>
                <?php else: ?>

                    <form action="" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3 text-start">
                            <label class="form-label text-secondary small">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="Minimal 6 karakter" minlength="6" required>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label text-secondary small">Konfirmasi Kata Sandi</label>
                            <input type="password" name="konfirmasi_password" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="Ketik ulang sandi baru" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-accent w-100 py-3 mt-2 fw-bold rounded-pill text-dark">SIMPAN KATA SANDI</button>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
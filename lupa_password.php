<?php
/**
 * File: lupa_password.php
 * Deskripsi: Tahap 1 - Meminta reset sandi (Hanya butuh Email)
 */
session_start();
require_once "config/database.php";

if (isset($_SESSION['id_pengguna'])) {
    header("Location: index.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();
$pesan_error = "";
$link_reset_simulasi = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Cek apakah email terdaftar
    $stmt_cek = $db->prepare("SELECT id_pengguna FROM pengguna WHERE email = :email LIMIT 1");
    $stmt_cek->execute([':email' => $email]);
    
    if ($stmt_cek->rowCount() > 0) {
        // 1. Buat Token Acak (Sangat Aman)
        $token = bin2hex(random_bytes(32)); 
        
        // 2. Hapus token lama jika ada (agar tidak menumpuk)
        $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        
        // 3. Simpan token baru ke database
        $stmt_insert = $db->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt_insert->execute([$email, $token]);

        // 4. SIMULASI KIRIM EMAIL (Di dunia nyata, kita pakai fungsi mail() di sini)
        $url_reset = "http://localhost/o2futsal/reset_password.php?token=" . $token;
        $link_reset_simulasi = $url_reset;

    } else {
        $pesan_error = "Alamat email tidak terdaftar di sistem kami.";
    }
}

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card-custom p-5 text-center border border-secondary shadow-lg">
                
                <h2 class="fw-bold text-white mb-1">LUPA SANDI?</h2>
                <p class="text-secondary mb-4 small">Masukkan alamat email Anda, dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>

                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger py-2 small border-danger bg-transparent text-danger mb-4">
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($link_reset_simulasi): ?>
                    <div class="alert alert-warning text-start border-warning bg-transparent mb-4">
                        <span class="badge bg-warning text-dark mb-2">Simulasi Pengiriman Email</span>
                        <p class="small text-white mb-2">Dalam mode produksi, tautan ini akan dikirim ke <strong><?php echo htmlspecialchars($email); ?></strong> secara rahasia.</p>
                        <p class="small text-secondary mb-1">Klik tautan di bawah ini untuk mereset sandi Anda (Berlaku 15 Menit):</p>
                        <a href="<?php echo $link_reset_simulasi; ?>" class="text-accent text-break" style="font-size: 13px;"><?php echo $link_reset_simulasi; ?></a>
                    </div>
                <?php else: ?>

                    <form action="" method="POST">
                        <div class="mb-4 text-start">
                            <label class="form-label text-secondary small">Alamat Email Terdaftar</label>
                            <input type="email" name="email" class="form-control bg-transparent text-white border-secondary shadow-none py-3" placeholder="contoh: nama@email.com" required>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-3 mt-2 fw-bold rounded-pill text-dark">KIRIM LINK RESET</button>
                    </form>

                <?php endif; ?>

                <div class="mt-4 text-center">
                    <span class="text-secondary small">Kembali ke <a href="login.php" class="text-accent text-decoration-none">Log Masuk</a></span>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
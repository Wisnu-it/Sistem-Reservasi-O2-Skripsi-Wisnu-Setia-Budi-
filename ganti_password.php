<?php
/**
 * File: ganti_password.php
 * Deskripsi: Halaman bagi pelanggan yang sudah login untuk mengubah kata sandi mereka
 */
session_start();
require_once "config/database.php";

// Wajib login untuk bisa ganti password
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: login.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();
$id_pengguna = $_SESSION['id_pengguna'];

$pesan_error = "";
$pesan_sukses = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if ($password_baru !== $konfirmasi_password) {
        $pesan_error = "Konfirmasi kata sandi baru tidak cocok!";
    } else {
        // Ambil data sandi lama dari database
        $stmt_user = $db->prepare("SELECT kata_sandi, password FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        $stmt_user->execute([$id_pengguna]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // Deteksi nama kolom otomatis
        $db_password = isset($user['kata_sandi']) ? $user['kata_sandi'] : (isset($user['password']) ? $user['password'] : '');

        // Verifikasi kecocokan sandi lama
        $password_lama_cocok = false;
        if (password_verify($password_lama, $db_password)) {
            $password_lama_cocok = true;
        } elseif (md5($password_lama) === $db_password) {
            $password_lama_cocok = true;
        } elseif ($password_lama === $db_password) {
            $password_lama_cocok = true;
        }

        if (!$password_lama_cocok) {
            $pesan_error = "Kata sandi saat ini (Sandi Lama) yang Anda masukkan salah!";
        } else {
            // Jika sandi lama benar, enkripsi sandi baru
            $password_enkripsi = password_hash($password_baru, PASSWORD_DEFAULT);
            
            // Simpan ke database
            try {
                $stmt_update = $db->prepare("UPDATE pengguna SET kata_sandi = ? WHERE id_pengguna = ?");
                $stmt_update->execute([$password_enkripsi, $id_pengguna]);
                $pesan_sukses = "Kata sandi berhasil diperbarui! Keamanan akun Anda kini lebih terjaga.";
            } catch (Exception $e) {
                // Fallback jika nama kolomnya 'password'
                $stmt_update_alt = $db->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
                $stmt_update_alt->execute([$password_enkripsi, $id_pengguna]);
                $pesan_sukses = "Kata sandi berhasil diperbarui! Keamanan akun Anda kini lebih terjaga.";
            }
        }
    }
}

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card-custom p-5 text-center border border-secondary shadow-lg">
                
                <h2 class="fw-bold text-white mb-1">GANTI <span class="text-accent">SANDI</span></h2>
                <p class="text-secondary mb-4 small">Pastikan akun Anda tetap aman dengan rutin memperbarui kata sandi.</p>

                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger py-2 small border-danger bg-transparent text-danger mb-4">
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_sukses): ?>
                    <div class="alert alert-success py-3 border-success bg-transparent text-success mb-4 text-start">
                        <strong>✅ BERHASIL!</strong><br> <?php echo $pesan_sukses; ?>
                    </div>
                    <a href="profil.php" class="btn btn-outline-light w-100 py-3 fw-bold rounded-pill">KEMBALI KE PROFIL</a>
                <?php else: ?>

                    <form action="" method="POST">
                        <div class="mb-4 text-start">
                            <label class="form-label text-secondary small">Kata Sandi Saat Ini (Sandi Lama)</label>
                            <input type="password" name="password_lama" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="Masukkan sandi Anda saat ini" required>
                        </div>

                        <hr class="border-secondary mb-4">

                        <div class="mb-3 text-start">
                            <label class="form-label text-secondary small">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="Minimal 6 karakter" minlength="6" required>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label text-secondary small">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="konfirmasi_password" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="Ketik ulang sandi baru" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-accent w-100 py-3 mt-2 fw-bold rounded-pill text-dark">SIMPAN PERUBAHAN</button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="profil.php" class="text-secondary small text-decoration-none">Batal dan kembali ke Profil</a>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
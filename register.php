<?php
/**
 * File: register.php
 * Deskripsi: Halaman pendaftaran akun dengan Tema Dark Mode
 */
session_start();
if (isset($_SESSION['id_pengguna'])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";
require_once "classes/Pengguna.php";

$pesan = "";
$tipe_pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new database();
    $db = $database->getKoneksi();
    $pengguna = new pengguna($db);

    $nama = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $password = $_POST['kata_sandi'];
    $telepon = $_POST['nomor_telepon'];

    if (empty($nama) || empty($email) || empty($password) || empty($telepon)) {
        $pesan = "Semua kolom formulir wajib diisi!";
        $tipe_pesan = "danger";
    } else {
        $hasil = $pengguna->registrasi($nama, $email, $password, $telepon);

        if ($hasil === true) {
            $pesan = "Pendaftaran sukses! Silakan log masuk ke akun Anda.";
            $tipe_pesan = "success";
        } else {
            $pesan = $hasil; 
            $tipe_pesan = "danger";
        }
    }
}

// Memanggil Header
require_once "layout/header.php";
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card-custom p-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-white mb-1">DAFTAR AKUN</h2>
                    <p class="text-accent text-uppercase" style="letter-spacing: 2px; font-size: 0.85rem;">Bergabung dengan O2 Futsal</p>
                </div>

                <?php if (!empty($pesan)): ?>
                    <div class="alert alert-<?php echo $tipe_pesan; ?> alert-dismissible fade show bg-transparent border-<?php echo $tipe_pesan; ?> text-<?php echo $tipe_pesan === 'success' ? 'accent' : 'danger'; ?>" role="alert">
                        <?php echo $pesan; ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nama_lengkap" class="form-label text-secondary fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control bg-transparent text-white border-secondary shadow-none" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="email" class="form-label text-secondary fw-semibold">Alamat Email</label>
                            <input type="email" class="form-control bg-transparent text-white border-secondary shadow-none" id="email" name="email" placeholder="nama@email.com" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="nomor_telepon" class="form-label text-secondary fw-semibold">Nomor WhatsApp</label>
                            <input type="tel" class="form-control bg-transparent text-white border-secondary shadow-none" id="nomor_telepon" name="nomor_telepon" placeholder="08123456789" required>
                        </div>
                        <div class="col-md-12 mb-5">
                            <label for="kata_sandi" class="form-label text-secondary fw-semibold">Kata Sandi</label>
                            <input type="password" class="form-control bg-transparent text-white border-secondary shadow-none" id="kata_sandi" name="kata_sandi" placeholder="Gunakan sandi yang kuat" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent w-100 py-3 rounded-pill">DAFTARKAN SAYA</button>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0 text-secondary">Sudah memiliki akun? <a href="login.php" class="text-accent fw-bold text-decoration-none">Log Masuk di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Memanggil Footer
require_once "layout/footer.php";
?>
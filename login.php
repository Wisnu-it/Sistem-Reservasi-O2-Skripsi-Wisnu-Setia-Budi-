<?php
/**
 * File: login.php
 * Deskripsi: Halaman Log Masuk dengan Pengalihan Multi-Dasbor (Pelanggan, Kasir, Pemilik)
 */
session_start();
require_once "config/database.php";

// 1. CEK SESI (Jika sudah login dan tidak sengaja membuka halaman login lagi)
if (isset($_SESSION['id_pengguna'])) {
    $peran_aktif = strtolower($_SESSION['peran']);
    if ($peran_aktif == 'pemilik') {
        header("Location: admin/dasbor_pemilik.php");
    } elseif ($peran_aktif == 'kasir' || $peran_aktif == 'admin') {
        header("Location: admin/dasbor_karyawan.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new database();
    $db = $database->getKoneksi();
    
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM pengguna WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $db_password = isset($user['kata_sandi']) ? $user['kata_sandi'] : (isset($user['password']) ? $user['password'] : '');

        $password_cocok = false;
        if (password_verify($password_input, $db_password)) {
            $password_cocok = true;
        } elseif (md5($password_input) === $db_password) {
            $password_cocok = true;
        } elseif ($password_input === $db_password) { 
            $password_cocok = true;
        }

        if ($password_cocok) {
            // Daftarkan Sesi Pelanggan/Karyawan
            $_SESSION['id_pengguna'] = $user['id_pengguna'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['peran'] = strtolower($user['peran'] ?? 'pelanggan'); 
            $_SESSION['poin_loyalitas'] = $user['poin_loyalitas'] ?? 0;
            
            // =======================================================
            // 2. POLISI LALU LINTAS SAAT BERHASIL LOGIN
            // =======================================================
            $peran_login = $_SESSION['peran'];
            if ($peran_login == 'pemilik') {
                header("Location: admin/dasbor_pemilik.php");
            } elseif ($peran_login == 'kasir' || $peran_login == 'admin') {
                header("Location: admin/dasbor_karyawan.php");
            } else {
                header("Location: index.php");
            }
            exit;
            // =======================================================

        } else {
            $pesan_error = "Kata sandi yang Anda masukkan salah!";
        }
    } else {
        $pesan_error = "Alamat email tidak terdaftar di sistem kami!";
    }
}

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card-custom p-5 text-center border border-secondary shadow-lg">
                
                <h2 class="fw-bold text-white mb-1">LOG MASUK</h2>
                <p class="text-accent mb-4" style="font-size: 0.8rem; letter-spacing: 2px;">OTORISASI AKSES SISTEM</p>

                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger py-2 small border-danger bg-transparent text-danger mb-4">
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label text-secondary small">Alamat Email</label>
                        <input type="email" name="email" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="nama@email.com" required>
                    </div>
                    
                    <div class="mb-3 text-start">
                        <label class="form-label text-secondary small">Kata Sandi</label>
                        <input type="password" name="password" class="form-control bg-transparent text-white border-secondary shadow-none py-2" placeholder="••••••••" required>
                    </div>

                    <div class="text-end mb-4">
                        <a href="lupa_password.php" class="text-secondary small text-decoration-none" onmouseover="this.style.color='#c4ff00'" onmouseout="this.style.color='#6c757d'" style="transition: 0.2s;">Lupa Kata Sandi?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-accent w-100 py-3 mt-2 fw-bold rounded-pill text-dark">MASUK SEKARANG</button>
                </form>

                <div class="mt-4 text-center">
                    <span class="text-secondary small">Belum terdaftar? <a href="register.php" class="text-accent text-decoration-none fw-bold">Buat Akun Baru</a></span>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
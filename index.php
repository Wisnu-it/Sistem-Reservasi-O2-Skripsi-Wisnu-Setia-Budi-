<?php
/**
 * File: index.php
 * Deskripsi: Halaman Beranda Utama / Landing Page Pelanggan (Dengan Efek Hover Interaktif)
 */
session_start();
require_once "config/database.php";
require_once "classes/Lapangan.php";

$database = new database();
$db = $database->getKoneksi();

// Instansiasi class Lapangan untuk mengambil data dari database
$objLapangan = new lapangan($db);
$stmtLapangan = $objLapangan->tampilLapangan();

// Sisipkan Header
require_once "layout/header.php";
?>

<style>
    /* ========================================================
       EFEK HOVER KARTU LAPANGAN (INTERAKSI VISUAL)
       ======================================================== */
    .card-lapangan {
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    /* Saat kartu disorot mouse */
    .card-lapangan:hover {
        transform: translateY(-8px);
        border-color: #555 !important;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.8) !important;
        z-index: 10;
    }

    .img-lapangan-wrapper {
        overflow: hidden;
    }

    .img-lapangan {
        transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Efek Zoom-in perlahan pada gambar saat kartu disorot */
    .card-lapangan:hover .img-lapangan {
        transform: scale(1.08); 
    }

    .btn-pilih-lapangan {
        transition: all 0.3s ease;
    }

    /* Tombol otomatis berubah menjadi putih solid saat kartu disorot (Sesuai Gambar) */
    .card-lapangan:hover .btn-pilih-lapangan {
        background-color: #f8f9fa !important;
        color: #000 !important;
        border-color: #f8f9fa !important;
        font-weight: 700 !important;
    }
</style>

<div class="py-5 text-center bg-black border-bottom border-secondary" style="background: linear-gradient(180deg, #111 0%, #0a0a0a 100%);">
    <div class="container py-5">
        <h1 class="display-4 fw-bold text-white mb-3">Selamat Datang di <span class="text-accent">O2 FUTSAL</span></h1>
        <p class="fs-5 text-secondary mb-5 mx-auto" style="max-width: 600px;">
            Infrastruktur futsal premium di Banjarmasin. Nikmati kemudahan memesan lapangan secara online, kumpulkan poin loyalitas, dan rasakan pengalaman bermain terbaik.
        </p>
        
        <?php if(isset($_SESSION['id_pengguna'])): ?>
            <a href="reservasi.php" class="btn btn-accent btn-lg rounded-pill px-5 fw-bold shadow-lg">RESERVASI SEKARANG</a>
        <?php else: ?>
            <div class="d-flex justify-content-center gap-3">
                <a href="login.php" class="btn btn-accent btn-lg rounded-pill px-5 fw-bold">LOG MASUK</a>
                <a href="register.php" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold">DAFTAR BARU</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-white mb-2">FASILITAS <span class="text-accent">LAPANGAN</span></h2>
        <p class="text-secondary">Pilih lapangan favoritmu dan jadilah juara di atas arena.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php 
        if ($stmtLapangan->rowCount() > 0):
            while ($row = $stmtLapangan->fetch(PDO::FETCH_ASSOC)): 
                $foto = !empty($row['foto_lapangan']) ? "/o2futsal/assets/images/lapangan/" . $row['foto_lapangan'] : "https://placehold.co/800x400?text=Foto+Belum+Tersedia";
        ?>
        
        <div class="col-md-6 col-lg-4">
            <div class="card bg-dark border-secondary h-100 card-custom card-lapangan overflow-hidden">
                
                <div class="img-lapangan-wrapper border-bottom border-secondary">
                    <img src="<?php echo $foto; ?>" class="card-img-top img-lapangan" alt="<?php echo $row['nama_lapangan']; ?>" style="height: 220px; object-fit: cover;">
                </div>
                
                <div class="card-body p-4 text-start">
                    <h4 class="card-title text-white fw-bold mb-1"><?php echo $row['nama_lapangan']; ?></h4>
                    <h5 class="text-accent fw-bold mb-3">Rp <?php echo number_format($row['harga_per_jam'], 0, ',', '.'); ?> <span class="text-secondary fw-normal fs-6">/ Jam</span></h5>
                    
                    <p class="card-text text-secondary small mb-4">
                        <?php echo !empty($row['deskripsi']) ? $row['deskripsi'] : "Fasilitas standar O2 Futsal."; ?>
                    </p>
                </div>

                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                    <?php if(isset($_SESSION['id_pengguna'])): ?>
                        <a href="reservasi.php" class="btn btn-outline-secondary text-white w-100 rounded-pill fw-bold py-2 btn-pilih-lapangan">Pilih Lapangan Ini</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-secondary w-100 rounded-pill py-2 btn-pilih-lapangan">Login untuk Memesan</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php 
            endwhile; 
        else: 
        ?>
            <div class="col-12 text-center py-5">
                <h5 class="text-secondary">Saat ini belum ada data lapangan yang tersedia.</h5>
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="bg-black py-5 border-top border-secondary">
    <div class="container my-4">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4 border border-secondary rounded-3 bg-dark h-100 card-custom">
                    <h1 class="text-accent mb-3">🏟️</h1>
                    <h5 class="text-white fw-bold">Buka Setiap Hari</h5>
                    <p class="text-secondary small mb-0">Melayani penyewaan dari pagi hingga malam hari tanpa hari libur.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border border-secondary rounded-3 bg-dark h-100 card-custom">
                    <h1 class="text-accent mb-3">💳</h1>
                    <h5 class="text-white fw-bold">Pembayaran Mudah</h5>
                    <p class="text-secondary small mb-0">Mendukung pembayaran Tunai di kasir maupun digital menggunakan QRIS.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border border-secondary rounded-3 bg-dark h-100 card-custom">
                    <h1 class="text-accent mb-3">🎁</h1>
                    <h5 class="text-white fw-bold">Poin Loyalitas</h5>
                    <p class="text-secondary small mb-0">Kumpulkan poin dari setiap transaksimu dan tukarkan sebagai diskon di penyewaan berikutnya.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="py-5 border-top border-secondary" style="background-color: #0a0a0a;">
    <div class="container my-5">
        <div class="row align-items-center g-5">
            
            <div class="col-lg-6 text-start">
                <h2 class="fw-bold text-white mb-3">TENTANG <span class="text-accent">O2 FUTSAL</span></h2>
                <p class="text-secondary mb-4" style="line-height: 1.8;">
                    O2 Futsal adalah penyedia layanan fasilitas olahraga premium di Banjarmasin. Kami berkomitmen untuk memberikan pengalaman bermain futsal terbaik dengan kondisi lapangan yang selalu terawat, pelayanan ramah, serta sistem reservasi digital yang memudahkan Anda mengatur jadwal bermain kapan saja dan di mana saja.
                </p>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-dark p-3 rounded-circle border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 20px;">
                        📍
                    </div>
                    <div>
                        <h6 class="text-white mb-0 fw-bold">Lokasi Kami</h6>
                        <span class="text-secondary small">Jl. Cempaka Raya No. 07, Kel. Basirih, Telaga Biru, Kec. Banjarmasin Barat, Kota Banjarmasin, Kalimantan Selatan 70119</span>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-dark p-3 rounded-circle border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 20px;">
                        📞
                    </div>
                    <div>
                        <h6 class="text-white mb-0 fw-bold">Kontak Telepon</h6>
                        <span class="text-secondary small">0813-5198-8842</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom p-4 p-md-5 border border-secondary shadow-lg text-start bg-black">
                    <h4 class="text-white fw-bold mb-3">Butuh Bantuan?</h4>
                    <p class="text-secondary small mb-4">Jika Anda mengalami kendala saat melakukan pembayaran, pemesanan, atau memiliki pertanyaan lain seputar fasilitas kami, silakan hubungi kami melalui WhatsApp.</p>

                    <a href="https://wa.me/6281351988842?text=Halo%20Admin%20O2%20Futsal,%20saya%20butuh%20bantuan%20terkait%20sistem%20reservasi." target="_blank" class="btn btn-accent w-100 py-3 fw-bold rounded-pill text-dark mb-3 d-flex align-items-center justify-content-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                        </svg>
                        Hubungi Admin (0813-5198-8842)
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php 
require_once "layout/footer.php"; 
?>
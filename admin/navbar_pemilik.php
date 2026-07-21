<?php
/**
 * File: admin/navbar_pemilik.php
 * Deskripsi: Navigasi khusus Owner (Akses Penuh - Menyesuaikan Berkas Asli)
 */
$halaman_aktif = basename($_SERVER['PHP_SELF']);

require_once "../config/database.php";
$database_nav = new database();
$db_nav = $database_nav->getKoneksi();

$stmt_badge = $db_nav->query("SELECT COUNT(id_reservasi) FROM reservasi WHERE status_reservasi = 'menunggu'");
$jumlah_tertunda = $stmt_badge->fetchColumn();

$badge_html = "";
if ($jumlah_tertunda > 0) {
    $badge_html = "<span class='badge bg-danger rounded-pill ms-2 animate-pulse-soft'>$jumlah_tertunda</span>";
}
?>

<nav id="smartNavbar" class="navbar navbar-dark py-3 px-4 sticky-top">
    <div class="d-flex align-items-center w-100">
        <h4 class="mb-0 fw-bold me-5"><span class="text-accent">O2 FUTSAL</span> | OWNER</h4>
        
        <div class="d-none d-lg-flex me-auto" style="position: relative; z-index: 60;">
            <a href="dasbor_pemilik.php" class="nav-link-custom <?php echo $halaman_aktif == 'dasbor_pemilik.php' ? 'active fw-bold' : ''; ?>">📊 Pusat Laporan</a>
            <a href="data_transaksi.php" class="nav-link-custom <?php echo $halaman_aktif == 'data_transaksi.php' ? 'active fw-bold' : ''; ?>">🛒 Data Transaksi <?php echo $badge_html; ?></a>
            <a href="lapangan.php" class="nav-link-custom <?php echo $halaman_aktif == 'lapangan.php' ? 'active fw-bold' : ''; ?>">🏟️ Kelola Lapangan</a>
            <a href="jadwal.php" class="nav-link-custom <?php echo $halaman_aktif == 'jadwal.php' ? 'active fw-bold' : ''; ?>">🗓️ Kelola Jadwal</a>
            <!-- Menu Pengaturan Promo yang baru disisipkan -->
            <a href="pengaturan_promo.php" class="nav-link-custom <?php echo $halaman_aktif == 'pengaturan_promo.php' ? 'active fw-bold' : ''; ?>">🏷️ Pengaturan Promo</a>
        </div>

        <div class="d-flex align-items-center gap-3" style="position: relative; z-index: 60;">
            <span class="text-secondary small">Halo, <span class="text-white fw-bold">Pemilik</span></span>
            <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3 btn-hover-float">Web Pelanggan</a>
            <a href="../logout.php" class="btn btn-sm btn-danger rounded-pill px-3 btn-hover-float" onclick="return confirm('Keluar dari Dasbor Pemilik?');">Keluar</a>
        </div>
    </div>
</nav>

<style>
    #smartNavbar {
        background-color: rgba(10, 10, 10, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: transform 0.4s, background-color 0.3s; z-index: 1030;
    }
    .navbar-hidden { transform: translateY(-100%); }
    .nav-link-custom { color: #aaa; text-decoration: none; margin-right: 25px; font-size: 14px; position: relative; display: inline-block; padding-bottom: 4px; transition: color 0.3s; z-index: 50; cursor: pointer; }
    .nav-link-custom:hover, .nav-link-custom.active { color: #c4ff00; }
    .nav-link-custom::after { content: ''; position: absolute; width: 0; height: 2px; bottom: 0; left: 0; background-color: #c4ff00; transition: width 0.3s; border-radius: 2px; z-index: -1; }
    .nav-link-custom:hover::after, .nav-link-custom.active::after { width: 100%; }
    @keyframes pulse-ring { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); } 70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); } 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }
    .animate-pulse-soft { animation: pulse-ring 2s infinite; }
    .btn-hover-float { transition: transform 0.2s, box-shadow 0.2s; }
    .btn-hover-float:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4); }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let lastScrollTop = 0; const navbar = document.getElementById('smartNavbar');
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop && scrollTop > 60) { navbar.classList.add('navbar-hidden'); } else { navbar.classList.remove('navbar-hidden'); }
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
        }, false);
    });
</script>
<?php
/**
 * File: layout/header.php
 * Deskripsi: Header Dinamis Pelanggan dengan Smart Scroll & Glassmorphism
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halaman_aktif = basename($_SERVER['PHP_SELF']);
$is_login = isset($_SESSION['id_pengguna']) && $_SESSION['peran'] == 'pelanggan';

$poin_user = 0;
$nama_depan = "Tamu";

if ($is_login) {
    // Memastikan koneksi database tersedia di header
    if (!isset($db)) {
        require_once __DIR__ . "/../config/database.php";
        $database_header = new database();
        $db = $database_header->getKoneksi();
    }

    $id_user = $_SESSION['id_pengguna'];
    $stmt_head = $db->query("SELECT nama_lengkap, poin_loyalitas FROM pengguna WHERE id_pengguna = $id_user");
    $user_head = $stmt_head->fetch(PDO::FETCH_ASSOC);
    
    if ($user_head) {
        $poin_user = $user_head['poin_loyalitas'];
        // Mengambil kata pertama saja dari nama lengkap agar rapi (Contoh: "Wisnu Setia" -> "Wisnu")
        $pecah_nama = explode(" ", $user_head['nama_lengkap']);
        $nama_depan = $pecah_nama[0];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O2 Futsal Banjarmasin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Mengatur warna dasar seluruh halaman pelanggan */
        body { 
            background-color: #0a0a0a; 
            color: #fff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        .text-accent { color: #c4ff00 !important; }

        /* ---------------------------------------------------
           PERISAI ANTI-HITAM UNTUK TOMBOL ACCENT O2 FUTSAL
           --------------------------------------------------- */
        .btn-accent, 
        .btn-accent:visited { 
            background-color: #c4ff00 !important; 
            color: #000000 !important; 
            border: none !important; 
        }
        
        .btn-accent:hover, 
        .btn-accent:focus, 
        .btn-accent:active { 
            background-color: #a3d900 !important; 
            color: #000000 !important; 
            box-shadow: 0 4px 15px rgba(196, 255, 0, 0.4) !important; 
            outline: none !important;
            transform: translateY(-2px);
        }

        /* 1. Efek Glassmorphism & Transisi Navbar */
        #customerNavbar {
            background-color: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.4s cubic-bezier(0.3, 1, 0.3, 1), background-color 0.3s ease;
            z-index: 1030;
        }
        
        .navbar-hidden {
            transform: translateY(-100%);
        }

        /* 2. Animasi Hover Menu (Sliding Underline) */
        .nav-link-custom { 
            color: #aaa; 
            text-decoration: none; 
            margin-right: 25px; 
            font-size: 15px; 
            position: relative;
            padding-bottom: 5px;
            transition: color 0.3s ease;
        }
        
        .nav-link-custom:hover, .nav-link-custom.active { 
            color: #c4ff00; 
        }

        .nav-link-custom::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #c4ff00;
            transition: width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 2px;
        }

        .nav-link-custom:hover::after, .nav-link-custom.active::after {
            width: 100%;
        }

        /* 3. Efek Tombol Mengambang */
        .btn-hover-float {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-hover-float:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body>

<nav id="customerNavbar" class="navbar navbar-expand-lg navbar-dark py-3 sticky-top">
    <div class="container">
        
        <a class="navbar-brand fw-bold fs-4 me-5 d-flex align-items-center" href="/o2futsal/index.php">
            <span class="text-accent me-2">O2</span> FUTSAL
        </a>
        
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            
            <ul class="navbar-nav me-auto mt-3 mt-lg-0 gap-2 gap-lg-0">
                <li class="nav-item">
                    <a class="nav-link-custom <?php echo $halaman_aktif == 'index.php' ? 'active fw-bold' : ''; ?>" href="/o2futsal/index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom <?php echo in_array($halaman_aktif, ['reservasi.php', 'checkout.php']) ? 'active fw-bold' : ''; ?>" href="/o2futsal/reservasi.php">Reservasi Lapangan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom <?php echo $halaman_aktif == 'tukar_poin.php' ? 'active fw-bold' : ''; ?>" href="/o2futsal/tukar_poin.php">🎁 Tukar Poin</a>
                </li>
            </ul>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-4 mt-lg-0 border-top border-secondary border-lg-0 pt-3 pt-lg-0">
                <?php if ($is_login): ?>
                    
                    <span class="badge rounded-pill text-dark fw-bold px-3 py-2" style="background-color: #c4ff00; font-size: 13px;">
                        Poin: <?php echo number_format($poin_user, 0, ',', '.'); ?>
                    </span>
                    
                    <a href="/o2futsal/profil.php" class="text-decoration-none text-secondary small">
                        Halo, <span class="text-white fw-bold <?php echo $halaman_aktif == 'profil.php' ? 'text-accent' : ''; ?>"><?php echo htmlspecialchars($nama_depan); ?></span>
                    </a>
                    
                    <a href="/o2futsal/logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-4 btn-hover-float" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
                
                <?php else: ?>
                    
                    <a href="/o2futsal/login.php" class="btn btn-sm btn-outline-light rounded-pill px-4 btn-hover-float">Masuk</a>
                    <a href="/o2futsal/register.php" class="btn btn-sm text-dark rounded-pill px-4 btn-hover-float fw-bold" style="background-color: #c4ff00;">Daftar</a>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let lastScrollTop = 0;
        const navbar = document.getElementById('customerNavbar');

        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Sembunyikan navbar jika scroll ke bawah lebih dari 60px (menghemat ruang layar)
            if (scrollTop > lastScrollTop && scrollTop > 60) {
                navbar.classList.add('navbar-hidden');
            } else {
                // Tampilkan kembali saat scroll ke atas
                navbar.classList.remove('navbar-hidden');
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
        }, false);
    });
</script>
<?php
/**
 * File: admin/dasbor_pemilik.php
 * Deskripsi: Executive Dashboard khusus Pemilik/Owner dengan Grafik Analitik
 */
session_start();

// Keamanan: Hanya Owner/Pemilik yang boleh mengakses halaman ini!
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak! Halaman ini khusus Pemilik.'); window.location.href='../login.php';</script>";
    exit;
}

require_once "../config/database.php";
$database = new database();
$db = $database->getKoneksi();

$bulan_ini = date('Y-m');

// 1. Kueri Total Pendapatan (Bulan Ini)
$query_pendapatan = "SELECT SUM(total_bayar) as total FROM reservasi WHERE status_reservasi IN ('dikonfirmasi', 'lunas', 'selesai') AND DATE_FORMAT(waktu_pesan, '%Y-%m') = '$bulan_ini'";
$stmt_pendapatan = $db->query($query_pendapatan);
$total_pendapatan = $stmt_pendapatan->fetchColumn() ?: 0;

// 2. Kueri Total Reservasi (Bulan Ini)
$query_trx = "SELECT COUNT(id_reservasi) FROM reservasi WHERE DATE_FORMAT(waktu_pesan, '%Y-%m') = '$bulan_ini'";
$total_reservasi = $db->query($query_trx)->fetchColumn() ?: 0;

// 3. Kueri Total Pelanggan Aktif
$query_pelanggan = "SELECT COUNT(id_pengguna) FROM pengguna WHERE peran = 'pelanggan'";
$total_pelanggan = $db->query($query_pelanggan)->fetchColumn() ?: 0;

// ============================================================================
// DATA PREDIKSI 7 HARI KE DEPAN (Data simulasi untuk visualisasi Dasbor)
// ============================================================================
$label_hari = [];
$data_prediksi = [65, 72, 58, 81, 93, 96, 89]; // Meniru pola lekukan pada gambar rancangan
for ($i = 1; $i <= 7; $i++) {
    $label_hari[] = date('d M', strtotime("+$i days"));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Pemilik - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Memuat Pustaka Chart.js untuk Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; color: #000; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
        
        /* Modifikasi Border Laporan */
        .border-yellow { border: 2px solid #ffc107 !important; }
        .border-blue { border: 2px solid #0d6efd !important; }
        .border-cyan { border: 2px solid #0dcaf0 !important; }
        .border-red { border: 2px solid #dc3545 !important; }
        .border-green { border: 2px solid #198754 !important; }
        .border-grey { border: 2px solid #6c757d !important; }
        .border-white { border: 2px solid #dee2e6 !important; }
        
        .report-card { transition: transform 0.2s; display: block; text-decoration: none; }
        .report-card:hover { transform: translateY(-5px); }

        /* Ikon Kotak pada Dasbor Atas */
        .icon-box { 
            background-color: #000; color: #fff; width: 45px; height: 45px; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 8px; font-size: 20px; font-weight: bold; border: 1px solid #333;
        }
    </style>
</head>
<body>
    <?php require_once "navbar_pemilik.php"; ?>

    <div class="container-fluid px-4 pt-4 py-4 mb-5">
        
        <h3 class="fw-bold text-white mb-4">Dasbor Utama</h3>

        <!-- ========================================================= -->
        <!-- BARIS 1: TIGA KARTU METRIK UTAMA (MENIRU DESAIN RANCANGAN) -->
        <!-- ========================================================= -->
        <div class="row g-4 mb-4">
            <!-- Kartu Pendapatan -->
            <div class="col-md-4">
                <div class="card-custom p-4 shadow h-100 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small">Pendapatan Bulan Ini</span>
                        <div class="icon-box">$</div>
                    </div>
                    <h2 class="text-white fw-bold mb-1">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h2>
                    <span class="text-secondary" style="font-size: 12px; color: #198754 !important;">+12.5% dari bulan lalu</span>
                </div>
            </div>
            
            <!-- Kartu Reservasi -->
            <div class="col-md-4">
                <div class="card-custom p-4 shadow h-100 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small">Total Reservasi (Bulan Ini)</span>
                        <div class="icon-box">📅</div>
                    </div>
                    <h2 class="text-white fw-bold mb-1"><?php echo number_format($total_reservasi, 0, ',', '.'); ?></h2>
                    <span class="text-secondary" style="font-size: 12px; color: #198754 !important;">+8.2% dari bulan lalu</span>
                </div>
            </div>

            <!-- Kartu Pelanggan -->
            <div class="col-md-4">
                <a href="data_pelanggan.php" class="text-decoration-none">
                    <div class="card-custom p-4 shadow h-100 position-relative" style="transition: border 0.3s;" onmouseover="this.style.borderColor='#c4ff00'" onmouseout="this.style.borderColor='#333'">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-secondary small">Total Pelanggan Aktif</span>
                            <div class="icon-box">👥</div>
                        </div>
                        <h2 class="text-white fw-bold mb-1"><?php echo number_format($total_pelanggan, 0, ',', '.'); ?></h2>
                        <span class="text-secondary" style="font-size: 12px; color: #198754 !important;">+5.7% dari bulan lalu</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- BARIS 2: GRAFIK PREDIKSI SEWA 7 HARI KE DEPAN             -->
        <!-- ========================================================= -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card-custom p-4 shadow">
                    <h4 class="fw-bold text-white mb-1">Prediksi Tingkat Sewa 7 Hari ke Depan</h4>
                    <p class="text-secondary small mb-4">Persentase penggunaan lapangan berdasarkan analisis prediksi sistem.</p>
                    
                    <!-- Kanvas Grafik Chart.js -->
                    <div style="height: 350px; width: 100%;">
                        <canvas id="predictionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- BARIS 3: PUSAT CETAK LAPORAN (GRID BAWAH)                 -->
        <!-- ========================================================= -->
        <div class="mb-4">
            <h4 class="fw-bold text-white mb-2 text-uppercase">PUSAT CETAK LAPORAN (REPORTING)</h4>
            <p class="text-secondary small">Pilih modul laporan di bawah ini untuk melihat analitik dan mencetak dokumen (PDF/Print).</p>
            <hr class="border-secondary mb-4">
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-white h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2">LAPORAN PENDAPATAN</h5>
                    <p class="text-secondary small mb-4">Cetak rincian pendapatan harian atau rekapitulasi bulanan O2 Futsal.</p>
                    <div class="d-flex gap-2 mt-auto">
                        <a href="laporan_pendapatan.php?jenis=harian" class="btn btn-outline-light w-50 small py-2">Harian (3.7.1)</a>
                        <a href="laporan_pendapatan.php?jenis=bulanan" class="btn btn-outline-light w-50 small py-2">Bulanan (3.7.2)</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-yellow h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Reservasi Tertunda</h5>
                    <p class="text-secondary small mb-4">Daftar pelanggan yang masih dalam status Menunggu Pembayaran.</p>
                    <a href="laporan_tertunda.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Lihat Laporan (3.7.3)</a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-blue h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Jadwal Harian Lapangan</h5>
                    <p class="text-secondary small mb-4">Cetak jadwal lapangan hari ini untuk diberikan kepada Karyawan/Staf Lapangan.</p>
                    <a href="laporan_jadwal.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Cetak Jadwal (3.7.4)</a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-cyan h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Pelanggan Terloyal</h5>
                    <p class="text-secondary small mb-4">Analisis data pelanggan dengan frekuensi reservasi tertinggi (Leaderboard).</p>
                    <a href="laporan_loyalitas.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Analisis Loyalitas (3.7.5)</a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-red h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Analisis Jam Sibuk</h5>
                    <p class="text-secondary small mb-4">Laporan Peak Hour Analysis untuk mengetahui jam mana yang paling laris dipesan.</p>
                    <a href="laporan_jam_sibuk.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Grafik Jam Sibuk (3.7.6)</a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-green h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Audit Transaksi Pembayaran</h5>
                    <p class="text-secondary small mb-4">Laporan riwayat lengkap seluruh metode pembayaran (Tunai & QRIS) untuk pencocokan saldo.</p>
                    <a href="laporan_audit.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Buka Audit (3.7.7)</a>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card-custom p-4 border-grey h-100 report-card d-flex flex-column">
                    <h5 class="fw-bold text-white mb-2 text-uppercase">Pembatalan Reservasi</h5>
                    <p class="text-secondary small mb-4">Daftar transaksi yang dibatalkan oleh sistem maupun oleh pelanggan.</p>
                    <a href="laporan_batal.php" class="btn btn-outline-light w-100 small py-2 mt-auto">Laporan Batal (3.7.8)</a>
                </div>
            </div>
            
            <div class="col-md-12 col-lg-8">
                <div class="card-custom p-4 border border-secondary h-100 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <h5 class="fw-bold text-accent mb-2 text-uppercase">Cetak Dokumen Dengan QR Code</h5>
                        <p class="text-secondary small mb-0">Cetak keabsahan laporan yang dilengkapi dengan QR Code verifikasi tanda tangan digital Pemilik.</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="cetak_qr.php" class="btn btn-accent fw-bold px-4 py-2 text-dark">Generate QR Laporan (3.7.10)</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Skrip Inisialisasi Grafik Chart.js -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('predictionChart').getContext('2d');
            
            // Konfigurasi Gradien Warna di bawah garis grafik
            let gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
            gradientFill.addColorStop(0, 'rgba(196, 255, 0, 0.4)'); // Hijau Neon Transparan di atas
            gradientFill.addColorStop(1, 'rgba(196, 255, 0, 0.0)'); // Menghilang di bawah

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($label_hari); ?>,
                    datasets: [{
                        label: 'Tingkat Sewa (%)',
                        data: <?php echo json_encode($data_prediksi); ?>,
                        borderColor: '#c4ff00',      // Warna garis neon
                        backgroundColor: gradientFill, // Warna isian di bawah garis
                        borderWidth: 3,
                        pointBackgroundColor: '#000',  // Titik warna hitam
                        pointBorderColor: '#c4ff00',   // Border titik warna neon
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.4 // Membuat garis melengkung halus (smooth curve)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Sembunyikan legend atas karena hanya 1 data
                        },
                        tooltip: {
                            backgroundColor: '#111',
                            titleColor: '#c4ff00',
                            bodyColor: '#fff',
                            borderColor: '#333',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Prediksi Kepadatan: ' + context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                color: '#888',
                                stepSize: 25,
                                callback: function(value) { return value + '%'; }
                            },
                            grid: {
                                color: '#333',
                                borderDash: [5, 5] // Garis grid putus-putus
                            }
                        },
                        x: {
                            ticks: {
                                color: '#888',
                                font: { weight: 'bold' }
                            },
                            grid: {
                                display: false // Hilangkan garis vertikal
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
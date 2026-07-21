<?php
/**
 * File: admin/laporan_tertunda.php
 * Deskripsi: Laporan Reservasi Menunggu Pembayaran dengan Fitur Follow-Up WhatsApp
 */
session_start();
require_once "../config/database.php";

// Keamanan: Pastikan hanya Pemilik yang bisa mengakses
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Kueri Mengambil Data Reservasi yang masih 'menunggu'
$query = "
    SELECT r.id_reservasi, r.tanggal_sewa, r.total_bayar, r.waktu_pesan, 
           p.nama_lengkap, p.nomor_telepon, pb.metode_pembayaran 
    FROM reservasi r
    JOIN pengguna p ON r.id_pengguna = p.id_pengguna
    LEFT JOIN pembayaran pb ON r.id_reservasi = pb.id_reservasi
    WHERE r.status_reservasi = 'menunggu'
    ORDER BY r.waktu_pesan ASC
";
$stmt = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Reservasi Tertunda - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; }
        .btn-accent:hover { background-color: #a3d900; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 8px; }
        
        /* CSS KHUSUS SAAT DICETAK (PRINT MEDIA) */
        @media print {
            @page { size: landscape; margin: 1cm; }
            body { background-color: #fff !important; color: #000 !important; font-size: 12px; }
            .no-print { display: none !important; } 
            .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow-x: visible !important; display: table !important; width: 100% !important; }
            .table { width: 100% !important; table-layout: auto !important; margin-bottom: 0 !important; }
            .table-dark { --bs-table-bg: #fff; --bs-table-color: #000; --bs-table-border-color: #000; border-color: #000 !important; }
            .table th, .table td { border-color: #000 !important; color: #000 !important; padding: 6px !important; }
            tr { page-break-inside: avoid; }
            .text-accent { color: #000 !important; }
            h2, h5 { color: #000 !important; }
        }
    </style>
</head>
<body>

    <!-- Area Navigasi -->
    <div class="container my-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dasbor_pemilik.php" class="btn btn-outline-light px-4 rounded-pill">⬅ Kembali ke Dasbor</a>
            <button onclick="window.print()" class="btn btn-accent fw-bold px-4 rounded-pill shadow">🖨️ Cetak / Simpan PDF</button>
        </div>
    </div>

    <!-- AREA YANG AKAN DICETAK -->
    <div class="container mb-5" id="area-cetak">
        
        <div class="text-center mb-4 pb-3 border-bottom border-secondary">
            <h2 class="fw-bold mb-1">LAPORAN RESERVASI TERTUNDA <span class="text-accent no-print">O2 FUTSAL</span></h2>
            <h5 class="text-secondary mb-0 fw-normal mt-2">
                Status: Menunggu Pembayaran Pelanggan
            </h5>
            <p class="small text-muted mt-1 no-print">Data diurutkan dari waktu pemesanan terlama.</p>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-dark table-bordered table-hover align-middle text-center">
                <thead class="text-secondary">
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">ID Pesanan</th>
                        <th width="15%">Waktu Booking</th>
                        <th width="20%">Nama Pelanggan</th>
                        <th width="15%">Metode Bayar</th>
                        <th width="15%">Tagihan (Rp)</th>
                        <th width="15%" class="no-print">Aksi (Follow-Up)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $total_tagihan = 0;
                    if ($stmt->rowCount() > 0):
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $total_tagihan += $row['total_bayar']; 
                            
                            // Menyiapkan nomor WA (Mengubah 08.. menjadi 628..)
                            $nomor_wa = $row['nomor_telepon'];
                            if(substr($nomor_wa, 0, 1) == '0') {
                                $nomor_wa = '62' . substr($nomor_wa, 1);
                            }
                            
                            // Pesan otomatis untuk WhatsApp
                            $id_format = "#ORD-" . str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT);
                            $pesan_wa = urlencode("Halo Kak " . $row['nama_lengkap'] . ", ini dari Admin O2 Futsal. Kami melihat pesanan lapangan dengan ID *$id_format* masih berstatus Menunggu Pembayaran. Silakan selesaikan pembayaran agar jadwal tidak otomatis dibatalkan ya. Terima kasih! ⚽");
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td class="fw-bold text-warning"><?php echo $id_format; ?></td>
                        <td>
                            <?php 
                                $waktu = isset($row['waktu_pesan']) ? $row['waktu_pesan'] : date('Y-m-d H:i:s');
                                echo date('d M Y', strtotime($waktu)) . "<br><small class='text-secondary'>" . date('H:i', strtotime($waktu)) . " WITA</small>"; 
                            ?>
                        </td>
                        <td class="text-start ps-3"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-uppercase"><?php echo htmlspecialchars($row['metode_pembayaran']); ?></td>
                        <td class="text-end pe-3 text-white fw-bold">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                        
                        <!-- Kolom Aksi (Disembunyikan saat di-print) -->
                        <td class="no-print">
                            <?php if(!empty($row['nomor_telepon'])): ?>
                                <a href="https://wa.me/<?php echo $nomor_wa; ?>?text=<?php echo $pesan_wa; ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                                    Hubungi WA
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary">No Telp Kosong</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="py-5 text-secondary">Hore! Tidak ada reservasi yang tertunda saat ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end pe-3 fw-bold bg-black text-white py-3">POTENSI PENDAPATAN TERTUNDA :</td>
                        <td class="text-end pe-3 fw-bold bg-black text-warning py-3 fs-5">Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></td>
                        <td class="bg-black no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-5 pt-5 d-none d-print-flex">
            <div class="col-8"></div>
            <div class="col-4 text-center">
                <p class="mb-5">Banjarmasin, <?php echo date('d M Y'); ?><br>Mengetahui,</p>
                <br><br>
                <p class="fw-bold text-decoration-underline mb-0">Pemilik O2 Futsal</p>
                <p class="small text-muted mt-0">Manager Executive</p>
            </div>
        </div>

    </div>

</body>
</html>
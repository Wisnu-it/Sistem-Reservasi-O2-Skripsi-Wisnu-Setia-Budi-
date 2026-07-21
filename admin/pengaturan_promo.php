<?php
/**
 * File: admin/pengaturan_promo.php
 * Deskripsi: Halaman Khusus Pemilik untuk Switch On/Off Promo (Versi JSON - Tanpa Database)
 */
session_start();

// Keamanan: Hanya Owner yang boleh mengakses
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) != 'pemilik') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../login.php';</script>";
    exit;
}

// Path ke file JSON
$json_file = '../config/promo.json';

// Baca data JSON saat ini
$json_data = file_get_contents($json_file);
$promo_data = json_decode($json_data, true);

// Logika Pembaruan Status (Switch Toggle)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kode_promo'])) {
    $kode = $_POST['kode_promo'];
    $current_status = $_POST['current_status'];
    
    // Balikkan status (jika 1 jadi 0, jika 0 jadi 1)
    $new_status = ($current_status == '1') ? '0' : '1';
    
    // Update array
    if (isset($promo_data[$kode])) {
        $promo_data[$kode]['status'] = $new_status;
        
        // Simpan kembali ke file JSON
        file_put_contents($json_file, json_encode($promo_data, JSON_PRETTY_PRINT));
        
        // Refresh
        header("Location: pengaturan_promo.php?success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Promo - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
        .form-check-input { width: 3em !important; height: 1.5em !important; cursor: pointer; background-color: #333; border-color: #444; }
        .form-check-input:checked { background-color: #c4ff00 !important; border-color: #c4ff00 !important; }
        .form-check-input:focus { box-shadow: 0 0 0 0.25rem rgba(196, 255, 0, 0.25); }
    </style>
</head>
<body>
    <?php require_once "navbar_pemilik.php"; ?>

    <div class="container px-4 pt-4 py-5" style="max-width: 800px;">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-white mb-1">Manajemen <span class="text-accent">Promo</span></h3>
                <p class="text-secondary small">Aktifkan atau nonaktifkan promosi yang tersedia secara real-time.</p>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success bg-transparent border-success text-success py-2 text-center small mb-4">
                Status promosi berhasil diperbarui!
            </div>
        <?php endif; ?>

        <div class="card-custom p-4 shadow">
            <?php foreach ($promo_data as $kode => $promo): ?>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-secondary">
                <div>
                    <h5 class="fw-bold text-white mb-1"><?php echo $promo['nama']; ?></h5>
                    <p class="text-secondary small mb-0"><?php echo $promo['deskripsi']; ?></p>
                    <span class="badge bg-dark text-accent border border-secondary mt-2">
                        Potongan: Rp <?php echo number_format($promo['potongan'], 0, ',', '.'); ?>
                    </span>
                </div>
                
                <!-- Form Switch -->
                <form action="" method="POST" class="ms-3">
                    <input type="hidden" name="kode_promo" value="<?php echo $kode; ?>">
                    <input type="hidden" name="current_status" value="<?php echo $promo['status']; ?>">
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input shadow-none" type="checkbox" role="switch" onchange="this.form.submit()" <?php echo $promo['status'] == '1' ? 'checked' : ''; ?>>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
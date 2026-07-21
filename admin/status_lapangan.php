<?php
/**
 * File: admin/status_lapangan.php
 * Deskripsi: Halaman Karyawan untuk update status kesiapan fisik lapangan
 */
session_start();
require_once "../config/database.php";

// Hanya Karyawan/Kasir/Admin/Pemilik yang boleh masuk
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['peran']) == 'pelanggan') {
    header("Location: ../index.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();

// Jika ada request POST untuk update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_lapangan']) && isset($_POST['status_kesiapan'])) {
    $id_lap = $_POST['id_lapangan'];
    $status_baru = $_POST['status_kesiapan'];
    
    $update_stmt = $db->prepare("UPDATE lapangan SET status_kesiapan = :status WHERE id_lapangan = :id");
    if ($update_stmt->execute([':status' => $status_baru, ':id' => $id_lap])) {
        echo "<script>window.location.href='status_lapangan.php?success=1';</script>";
        exit;
    }
}

// Ambil semua data lapangan
$query = "SELECT id_lapangan, nama_lapangan, status_kesiapan FROM lapangan ORDER BY nama_lapangan ASC";
$stmt = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Kesiapan Lapangan - Staf O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
        .btn-status { transition: all 0.3s ease; }
        .btn-status.active { background-color: #c4ff00 !important; color: #000 !important; border-color: #c4ff00 !important; font-weight: bold; }
    </style>
</head>
<body>

<?php 
// Panggil navbar sesuai peran
if (strtolower($_SESSION['peran']) == 'pemilik') {
    require_once "navbar_pemilik.php"; 
} else {
    require_once "navbar_karyawan.php"; 
}
?>

<div class="container px-3 pt-4 pb-5 mt-3" style="max-width: 600px;">
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success bg-transparent border-success text-success py-2 text-center small mb-4">
            Status kesiapan lapangan berhasil diperbarui!
        </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <h3 class="fw-bold text-white mb-1">Status Kesiapan Lapangan</h3>
        <p class="text-secondary small">Update kondisi fisik area bermain secara real-time.</p>
    </div>

    <?php 
    if ($stmt->rowCount() > 0): 
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
            $status_sekarang = $row['status_kesiapan'] ?? 'Siap Digunakan';
    ?>
    <div class="card-custom p-4 mb-4 shadow">
        <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">Lapangan <?php echo htmlspecialchars($row['nama_lapangan']); ?></h5>
        
        <form action="" method="POST" class="d-flex flex-column gap-2">
            <input type="hidden" name="id_lapangan" value="<?php echo $row['id_lapangan']; ?>">
            
            <button type="submit" name="status_kesiapan" value="Siap Digunakan" class="btn btn-outline-light py-3 btn-status <?php echo ($status_sekarang == 'Siap Digunakan') ? 'active' : ''; ?>">
                Siap Digunakan
            </button>
            
            <button type="submit" name="status_kesiapan" value="Sedang Dibersihkan" class="btn btn-outline-light py-3 btn-status <?php echo ($status_sekarang == 'Sedang Dibersihkan') ? 'active' : ''; ?>">
                Sedang Dibersihkan
            </button>
            
            <button type="submit" name="status_kesiapan" value="Pemeliharaan" class="btn btn-outline-light py-3 btn-status <?php echo ($status_sekarang == 'Pemeliharaan') ? 'active' : ''; ?>">
                Pemeliharaan
            </button>
        </form>
    </div>
    <?php 
        endwhile; 
    else: 
    ?>
        <div class="text-center text-secondary py-5">Belum ada data lapangan.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <p class="text-secondary small">Update terakhir: <?php echo date('d M Y, H:i'); ?> WITA</p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
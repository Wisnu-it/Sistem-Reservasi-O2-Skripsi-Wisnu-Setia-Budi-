<?php
/**
 * File: admin/lapangan.php
 */
session_start();
require_once "cek_akses.php"; // GEMBOK KEAMANAN

// KEAMANAN TAMBAHAN: Jika yang paksa masuk lewat URL bukan pemilik, tendang!
if ($_SESSION['peran'] != 'pemilik') {
    echo "<script>alert('AKSES DITOLAK! Hanya Pemilik yang berhak mengelola Master Data Lapangan.'); window.location.href='index.php';</script>";
    exit;
}

require_once "../config/database.php";

$database = new database();
$db = $database->getKoneksi();
$query = "SELECT * FROM lapangan ORDER BY id_lapangan ASC";
$stmt = $db->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/o2futsal/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark">

<?php include "navbar_pemilik.php"; ?>

<div class="container-fluid px-4 pb-5">
    <div class="card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
            <h4 class="fw-bold text-white mb-0">Manajemen Data Lapangan</h4>
            <button type="button" class="btn btn-accent fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
                + Tambah Lapangan
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead class="text-secondary">
                    <tr>
                        <th>Foto</th>
                        <th>ID</th>
                        <th>Nama Lapangan</th>
                        <th>Deskripsi</th>
                        <th>Harga per Jam</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $modals = ''; 
                    if ($stmt->rowCount() > 0): 
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                            $foto = !empty($row['foto_lapangan']) ? "/o2futsal/assets/images/lapangan/".$row['foto_lapangan'] : "https://placehold.co/80x50?text=No+Image";
                    ?>
                    <tr>
                        <td><img src="<?php echo $foto; ?>" alt="Foto" class="rounded object-fit-cover" width="80" height="50"></td>
                        <td class="text-white">#<?php echo $row['id_lapangan']; ?></td>
                        <td class="text-accent fw-bold"><?php echo $row['nama_lapangan']; ?></td>
                        <td class="text-white small"><?php echo substr($row['deskripsi'], 0, 40); ?>...</td>
                        <td class="text-white fw-semibold">Rp <?php echo number_format($row['harga_per_jam'], 0, ',', '.'); ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['id_lapangan']; ?>">Edit</button>
                            <form action="proses_lapangan.php" method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_lapangan" value="<?php echo $row['id_lapangan']; ?>">
                                <input type="hidden" name="foto_lama" value="<?php echo $row['foto_lapangan']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Yakin ingin menghapus lapangan ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    
                    <?php 
                    $modals .= '
                    <div class="modal fade" id="modalEdit'.$row['id_lapangan'].'" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark border-secondary">
                                <form action="proses_lapangan.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-header border-secondary">
                                        <h5 class="modal-title text-white fw-bold">Edit '.$row['nama_lapangan'].'</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <input type="hidden" name="aksi" value="edit">
                                        <input type="hidden" name="id_lapangan" value="'.$row['id_lapangan'].'">
                                        <input type="hidden" name="foto_lama" value="'.$row['foto_lapangan'].'">
                                        
                                        <div class="mb-3 text-center">
                                            <img src="'.$foto.'" class="img-fluid rounded mb-2 border border-secondary" style="max-height: 150px;">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary">Ganti Foto (Opsional)</label>
                                            <input type="file" name="foto" class="form-control bg-black text-white border-secondary" accept="image/png, image/jpeg, image/jpg">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary">Nama Lapangan</label>
                                            <input type="text" name="nama_lapangan" class="form-control bg-black text-white border-secondary" value="'.$row['nama_lapangan'].'" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary">Harga per Jam (Rp)</label>
                                            <input type="number" name="harga_per_jam" class="form-control bg-black text-white border-secondary" value="'.$row['harga_per_jam'].'" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary">Deskripsi</label>
                                            <textarea name="deskripsi" class="form-control bg-black text-white border-secondary" rows="3">'.$row['deskripsi'].'</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary">
                                        <button type="button" class="btn btn-outline-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-accent fw-bold rounded-pill">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    ?>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-secondary">Belum ada data lapangan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo isset($modals) ? $modals : ''; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form action="proses_lapangan.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white fw-bold">Tambah Lapangan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Foto Lapangan (Wajib)</label>
                        <input type="file" name="foto" class="form-control bg-black text-white border-secondary" accept="image/png, image/jpeg, image/jpg" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Nama Lapangan</label>
                        <input type="text" name="nama_lapangan" class="form-control bg-black text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Harga per Jam (Rp)</label>
                        <input type="number" name="harga_per_jam" class="form-control bg-black text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control bg-black text-white border-secondary" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-accent fw-bold rounded-pill">Simpan Lapangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
/**
 * File: admin/jadwal.php
 * Deskripsi: Halaman Manajemen Jadwal & Libur (Fix Terintegrasi dengan db_o2futsal)
 */
session_start();
require_once "cek_akses.php"; // GEMBOK KEAMANAN
require_once "../config/database.php";

$database = new database();
$db = $database->getKoneksi();

$query_lapangan = "SELECT * FROM lapangan ORDER BY nama_lapangan ASC";
$stmt_lapangan = $db->query($query_lapangan);
$lapangan_list = $stmt_lapangan->fetchAll(PDO::FETCH_ASSOC);

$id_lapangan_aktif = isset($_GET['lapangan']) ? $_GET['lapangan'] : ($lapangan_list[0]['id_lapangan'] ?? 0);

// PERBAIKAN: Menarik kolom l.harga_per_jam dari tabel lapangan
$query_jadwal = "SELECT j.*, l.nama_lapangan, l.harga_per_jam 
                 FROM jadwal j 
                 JOIN lapangan l ON j.id_lapangan = l.id_lapangan 
                 WHERE j.id_lapangan = :id_lap 
                 ORDER BY j.jam_mulai ASC";
$stmt_jadwal = $db->prepare($query_jadwal);
$stmt_jadwal->execute([':id_lap' => $id_lapangan_aktif]);

$stmt_libur = $db->query("SELECT * FROM hari_libur ORDER BY tanggal ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal & Libur - Admin O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/o2futsal/assets/css/style.css" rel="stylesheet">
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .bg-accent { background-color: #c4ff00 !important; color: #000; }
        .btn-accent { background-color: #c4ff00; color: #000; border: none; font-weight: bold; }
        .btn-accent:hover { background-color: #a3d900; color: #000; }
        .card-custom { background-color: #111; border-radius: 8px; border: 1px solid #333; }
    </style>
</head>
<body class="bg-dark">

<?php include "navbar_pemilik.php"; ?>

<div class="container-fluid px-4 pb-5 mt-4">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card-custom p-4 mb-4 shadow">
                <h5 class="fw-bold text-accent mb-3 border-bottom border-secondary pb-2">⚙️ Generator Jam Operasional</h5>
                <form action="proses_jadwal.php" method="POST">
                    <input type="hidden" name="aksi" value="generate">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Pilih Lapangan</label>
                        <select name="id_lapangan" class="form-select bg-black text-white border-secondary shadow-none" required>
                            <?php foreach($lapangan_list as $lap): ?>
                                <option value="<?php echo $lap['id_lapangan']; ?>"><?php echo htmlspecialchars($lap['nama_lapangan']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Jam Buka</label>
                            <select name="jam_buka" class="form-select bg-black text-white border-secondary shadow-none" required>
                                <?php for($i=6; $i<=15; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>:00</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Jam Tutup</label>
                            <select name="jam_tutup" class="form-select bg-black text-white border-secondary shadow-none" required>
                                <?php for($i=12; $i<=24; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i==24) ? 'selected' : ''; ?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>:00</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent w-100 fw-bold rounded-pill mb-2">BUAT JADWAL OTOMATIS</button>
                    <p class="text-secondary mt-2 text-center" style="font-size: 11px;">*Harga sewa akan otomatis mengikuti harga lapangan yang dipilih.</p>
                </form>
            </div>

            <div class="card-custom p-4 border-start border-4 border-danger shadow">
                <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">⛔ Atur Hari Libur / Tutup</h5>
                <form action="proses_jadwal.php" method="POST" class="mb-4">
                    <input type="hidden" name="aksi" value="tambah_libur">
                    <div class="mb-3">
                        <label class="text-secondary small">Pilih Tanggal Libur</label>
                        <input type="date" name="tanggal" class="form-control bg-black text-white border-secondary shadow-none" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary small">Keterangan (Misal: Renovasi Lapangan)</label>
                        <input type="text" name="keterangan" class="form-control bg-black text-white border-secondary shadow-none" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold rounded-pill">Tetapkan Libur</button>
                </form>

                <h6 class="text-secondary mb-3">Daftar Tanggal Libur Aktif:</h6>
                <ul class="list-group list-group-flush">
                    <?php if($stmt_libur->rowCount() > 0): while($libur = $stmt_libur->fetch(PDO::FETCH_ASSOC)): ?>
                        <li class="list-group-item bg-transparent text-white border-secondary px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-danger"><?php echo date('d M Y', strtotime($libur['tanggal'])); ?></strong><br>
                                <small class="text-secondary"><?php echo htmlspecialchars($libur['keterangan']); ?></small>
                            </div>
                            <form action="proses_jadwal.php" method="POST">
                                <input type="hidden" name="aksi" value="hapus_libur">
                                <input type="hidden" name="id_libur" value="<?php echo $libur['id_libur']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3">Batal</button>
                            </form>
                        </li>
                    <?php endwhile; else: ?>
                        <li class="list-group-item bg-transparent text-secondary px-0 small border-0">Belum ada hari libur yang diatur.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-custom p-4 shadow h-100">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                    <h4 class="fw-bold text-white mb-0">Daftar Jadwal Tersimpan</h4>
                    
                    <form action="jadwal.php" method="GET" class="d-flex gap-2">
                        <select name="lapangan" class="form-select bg-dark text-white border-secondary shadow-none" onchange="this.form.submit()">
                            <?php foreach($lapangan_list as $lap): ?>
                                <option value="<?php echo $lap['id_lapangan']; ?>" <?php echo ($id_lapangan_aktif == $lap['id_lapangan']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lap['nama_lapangan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="mb-3 text-end">
                    <form action="proses_jadwal.php" method="POST" class="d-inline">
                        <input type="hidden" name="aksi" value="hapus_semua">
                        <input type="hidden" name="id_lapangan" value="<?php echo $id_lapangan_aktif; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger px-4 rounded-pill fw-bold" onclick="return confirm('YAKIN INGIN MENGHAPUS SELURUH JADWAL DI LAPANGAN INI?')">🗑️ Bersihkan Jadwal</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle text-center">
                        <thead class="text-secondary bg-black">
                            <tr>
                                <th class="py-3 border-secondary">No.</th>
                                <th class="py-3 border-secondary text-start">Unit Lapangan</th>
                                <th class="py-3 border-secondary text-accent">Slot Jam</th>
                                <th class="py-3 border-secondary">Harga Sewa</th>
                                <th class="py-3 border-secondary">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            if ($stmt_jadwal->rowCount() > 0): 
                                while ($row = $stmt_jadwal->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                            <tr>
                                <td class="text-secondary py-3 border-secondary"><?php echo $no++; ?></td>
                                <td class="text-white text-start fw-bold border-secondary"><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                                <td class="text-accent fw-bold fs-5 border-secondary" style="background-color: rgba(196, 255, 0, 0.05);">
                                    <?php echo substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5); ?>
                                </td>
                                <td class="text-white fw-bold border-secondary">Rp <?php echo number_format($row['harga_per_jam'], 0, ',', '.'); ?></td>
                                <td class="border-secondary">
                                    <form action="proses_jadwal.php" method="POST" class="d-inline">
                                        <input type="hidden" name="aksi" value="hapus_satu">
                                        <input type="hidden" name="id_jadwal" value="<?php echo $row['id_jadwal']; ?>">
                                        <input type="hidden" name="id_lapangan" value="<?php echo $id_lapangan_aktif; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Hapus jam ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="py-5 text-secondary border-secondary">Belum ada jadwal yang di-generate untuk lapangan ini. Silakan gunakan generator di samping.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
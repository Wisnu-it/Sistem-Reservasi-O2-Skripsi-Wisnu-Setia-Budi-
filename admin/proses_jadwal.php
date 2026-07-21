<?php
/**
 * File: admin/proses_jadwal.php
 * Deskripsi: Mengeksekusi pembuatan jadwal berantai (generator), penghapusan jadwal, dan hari libur
 */
session_start();
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    $database = new database();
    $db = $database->getKoneksi();
    $aksi = $_POST['aksi'];

    try {
        if ($aksi == 'generate') {
            $id_lapangan = $_POST['id_lapangan'];
            $jam_buka = (int)$_POST['jam_buka'];
            $jam_tutup = (int)$_POST['jam_tutup'];

            if ($jam_buka >= $jam_tutup) {
                echo "<script>alert('Jam Buka harus lebih awal dari Jam Tutup!'); window.location.href='jadwal.php?lapangan=$id_lapangan';</script>";
                exit;
            }

            // Memulai transaksi agar semua tersimpan seketika
            $db->beginTransaction();

            $query_cek = "SELECT id_jadwal FROM jadwal WHERE id_lapangan = :id_lap AND jam_mulai = :mulai LIMIT 1";
            $stmt_cek = $db->prepare($query_cek);

            $query_insert = "INSERT INTO jadwal (id_lapangan, jam_mulai, jam_selesai, status_tersedia) VALUES (:id_lap, :mulai, :selesai, 1)";
            $stmt_insert = $db->prepare($query_insert);

            $jumlah_dibuat = 0;

            // Melakukan Looping / Perulangan pembuatan jam
            for ($i = $jam_buka; $i < $jam_tutup; $i++) {
                $mulai = str_pad($i, 2, '0', STR_PAD_LEFT) . ":00:00";
                $selesai = str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ":00:00";

                // Cek apakah jam ini sudah pernah dibuat sebelumnya
                $stmt_cek->execute([':id_lap' => $id_lapangan, ':mulai' => $mulai]);
                if ($stmt_cek->rowCount() == 0) {
                    $stmt_insert->execute([
                        ':id_lap' => $id_lapangan,
                        ':mulai' => $mulai,
                        ':selesai' => $selesai
                    ]);
                    $jumlah_dibuat++;
                }
            }

            $db->commit();
            echo "<script>alert('Berhasil! $jumlah_dibuat slot jadwal baru telah di-generate.'); window.location.href='jadwal.php?lapangan=$id_lapangan';</script>";

        } elseif ($aksi == 'hapus_semua') {
            $id_lapangan = $_POST['id_lapangan'];
            
            $query = "DELETE FROM jadwal WHERE id_lapangan = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id_lapangan]);

            // Mencoba mereset Auto Increment agar kembali ke angka awal jika tabel kosong
            $db->query("ALTER TABLE jadwal AUTO_INCREMENT = 1");

            echo "<script>alert('Seluruh jadwal di lapangan ini telah dikosongkan.'); window.location.href='jadwal.php?lapangan=$id_lapangan';</script>";
            
        } elseif ($aksi == 'hapus_satu') {
            $id_jadwal = $_POST['id_jadwal'];
            $id_lapangan = $_POST['id_lapangan'];
            
            $query = "DELETE FROM jadwal WHERE id_jadwal = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id_jadwal]);

            echo "<script>alert('Satu jam berhasil dihapus.'); window.location.href='jadwal.php?lapangan=$id_lapangan';</script>";
        
        } elseif ($aksi == 'tambah_libur') {
            $tanggal = $_POST['tanggal'];
            $keterangan = $_POST['keterangan'];

            $query = "INSERT INTO hari_libur (tanggal, keterangan) VALUES (:tgl, :ket)";
            $stmt = $db->prepare($query);
            $stmt->execute([':tgl' => $tanggal, ':ket' => $keterangan]);
            
            echo "<script>alert('Hari libur berhasil ditetapkan!'); window.location.href='jadwal.php';</script>";

        } elseif ($aksi == 'hapus_libur') {
            $id_libur = $_POST['id_libur'];
            $query = "DELETE FROM hari_libur WHERE id_libur = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id_libur]);
            
            echo "<script>alert('Status libur dibatalkan.'); window.location.href='jadwal.php';</script>";
        }

    } catch(Exception $e) {
        if(isset($db) && $db->inTransaction()) $db->rollBack();
        echo "<script>alert('Gagal mengeksekusi data. Kemungkinan data ini sudah terikat dengan pesanan aktif.'); window.location.href='jadwal.php';</script>";
    }
} else {
    header("Location: jadwal.php");
}
?>
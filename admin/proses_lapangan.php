<?php
session_start();
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    $database = new database();
    $db = $database->getKoneksi();
    $aksi = $_POST['aksi'];
    
    // Konfigurasi Folder Upload
    $target_dir = "../assets/images/lapangan/";

    // ==========================================
    // KODE BARU: BUAT FOLDER OTOMATIS JIKA BELUM ADA
    // ==========================================
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    try {
        if ($aksi == 'tambah') {
            $nama = $_POST['nama_lapangan'];
            $harga = $_POST['harga_per_jam'];
            $deskripsi = $_POST['deskripsi'];
            $foto_name = null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $foto_name = time() . "_" . rand(100, 999) . "." . $ext; 
                move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto_name);
            }

            $query = "INSERT INTO lapangan (nama_lapangan, deskripsi, harga_per_jam, foto_lapangan) VALUES (:nama, :desc, :harga, :foto)";
            $stmt = $db->prepare($query);
            $stmt->execute([':nama' => $nama, ':desc' => $deskripsi, ':harga' => $harga, ':foto' => $foto_name]);
            
            echo "<script>alert('Lapangan berhasil ditambahkan!'); window.location.href='lapangan.php';</script>";
        
        } elseif ($aksi == 'edit') {
            $id = $_POST['id_lapangan'];
            $nama = $_POST['nama_lapangan'];
            $harga = $_POST['harga_per_jam'];
            $deskripsi = $_POST['deskripsi'];
            $foto_lama = $_POST['foto_lama'];
            
            $foto_name = $foto_lama; 

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $foto_name = time() . "_" . rand(100, 999) . "." . $ext;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto_name)) {
                    if (!empty($foto_lama) && file_exists($target_dir . $foto_lama)) {
                        unlink($target_dir . $foto_lama);
                    }
                }
            }

            $query = "UPDATE lapangan SET nama_lapangan = :nama, deskripsi = :desc, harga_per_jam = :harga, foto_lapangan = :foto WHERE id_lapangan = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':nama' => $nama, ':desc' => $deskripsi, ':harga' => $harga, ':foto' => $foto_name, ':id' => $id]);

            echo "<script>alert('Perubahan data lapangan berhasil disimpan!'); window.location.href='lapangan.php';</script>";
        
        } elseif ($aksi == 'hapus') {
            $id = $_POST['id_lapangan'];
            $foto_lama = $_POST['foto_lama'];

            $query = "DELETE FROM lapangan WHERE id_lapangan = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);

            if (!empty($foto_lama) && file_exists($target_dir . $foto_lama)) {
                unlink($target_dir . $foto_lama);
            }

            echo "<script>alert('Data lapangan berhasil dihapus.'); window.location.href='lapangan.php';</script>";
        }
    } catch(Exception $e) {
        echo "<script>alert('Gagal mengeksekusi data.'); window.location.href='lapangan.php';</script>";
    }
} else {
    header("Location: lapangan.php");
}
?>
<?php
/**
 * File: classes/Lapangan.php
 * Deskripsi: Kelas untuk menangani logika entitas Lapangan (Master Data)
 */

class Lapangan {
    private $koneksi;
    private $nama_tabel = "lapangan";

    // Properti Lapangan
    public $id_lapangan;
    public $nama_lapangan;
    public $foto_lapangan;
    public $deskripsi;
    public $harga_per_jam;

    public function __construct($db) {
        $this->koneksi = $db;
    }

    // Mengambil semua daftar lapangan untuk ditampilkan di Form Reservasi
    public function tampilLapangan() {
        $query = "SELECT * FROM " . $this->nama_tabel . " ORDER BY id_lapangan ASC";
        $stmt = $this->koneksi->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Mengambil detail satu lapangan berdasarkan ID
    public function detailLapangan($id_lapangan) {
        $query = "SELECT * FROM " . $this->nama_tabel . " WHERE id_lapangan = :id LIMIT 1";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bindParam(":id", $id_lapangan);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->nama_lapangan = $row['nama_lapangan'];
            $this->harga_per_jam = $row['harga_per_jam'];
            return $row;
        }
        return false;
    }
}
?>
<?php
class Database {
    // Kredensial Database lokal (XAMPP default)
    private $host = "localhost:3306";
    private $db_name = "db_o2futsal";
    private $username = "root";
    private $password = ""; 
    
    // Properti untuk menyimpan instance koneksi
    public $koneksi;

    // Fungsi untuk mendapatkan koneksi database
    public function getKoneksi() {
        $this->koneksi = null;

        try {
            // Membuat koneksi PDO baru
            $this->koneksi = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            
            // Konfigurasi PDO untuk menampilkan error Exception secara detail
            $this->koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Konfigurasi karakter set agar mendukung Bahasa Indonesia dengan baik
            $this->koneksi->exec("set names utf8mb4");
            
        } catch(PDOException $exception) {
            // Menangkap dan menampilkan pesan error jika koneksi gagal
            echo "Koneksi Database Gagal: " . $exception->getMessage();
        }

        // Mengembalikan objek koneksi untuk digunakan oleh Class lain
        return $this->koneksi;
    }
}
?>
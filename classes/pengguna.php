<?php
/**
 * File: classes/Pengguna.php
 * Deskripsi: Kelas untuk menangani logika entitas Pengguna (Registrasi, Login, Poin)
 */

class Pengguna {
    private $koneksi;
    private $nama_tabel = "pengguna";

    // Properti objek pengguna
    public $id_pengguna;
    public $nama_lengkap;
    public $email;
    public $kata_sandi;
    public $peran;
    public $nomor_telepon;
    public $poin_loyalitas;
    public $status_verifikasi;

    // Konstruktor untuk menerima koneksi database
    public function __construct($db) {
        $this->koneksi = $db;
    }

    // Fungsi Registrasi Pelanggan Baru
    public function registrasi($nama, $email, $password, $telepon) {
        try {
            // Cek apakah email sudah terdaftar
            $cek_query = "SELECT id_pengguna FROM " . $this->nama_tabel . " WHERE email = :email LIMIT 1";
            $stmt_cek = $this->koneksi->prepare($cek_query);
            $stmt_cek->bindParam(":email", $email);
            $stmt_cek->execute();

            if ($stmt_cek->rowCount() > 0) {
                return "Email sudah terdaftar!";
            }

            // Enkripsi kata sandi menggunakan BCRYPT
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Peran default untuk pendaftaran dari FrontEND adalah 'pelanggan'
            $peran_default = 'pelanggan';

            // Query insert data
            $query = "INSERT INTO " . $this->nama_tabel . " 
                      (nama_lengkap, email, kata_sandi, peran, nomor_telepon) 
                      VALUES (:nama, :email, :sandi, :peran, :telepon)";
            
            $stmt = $this->koneksi->prepare($query);
            
            // Binding parameter untuk mencegah SQL Injection
            $stmt->bindParam(":nama", $nama);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":sandi", $password_hash);
            $stmt->bindParam(":peran", $peran_default);
            $stmt->bindParam(":telepon", $telepon);
            
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // Fungsi Log Masuk (Login)
    public function logMasuk($email, $password_input) {
        $query = "SELECT * FROM " . $this->nama_tabel . " WHERE email = :email LIMIT 1";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifikasi kecocokan kata sandi
            if (password_verify($password_input, $row['kata_sandi'])) {
                // Set properti objek dengan data dari database
                $this->id_pengguna = $row['id_pengguna'];
                $this->nama_lengkap = $row['nama_lengkap'];
                $this->peran = $row['peran'];
                $this->poin_loyalitas = $row['poin_loyalitas'];
                
                return $row; // Mengembalikan array data pengguna untuk disimpan di Session
            }
        }
        return false; // Login gagal
    }

    // Fungsi Tambah Poin Loyalitas (Dipanggil setelah transaksi berhasil)
    public function tambahPoin($id_pengguna, $jumlah_tambahan) {
        $query = "UPDATE " . $this->nama_tabel . " 
                  SET poin_loyalitas = poin_loyalitas + :tambahan 
                  WHERE id_pengguna = :id";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bindParam(":tambahan", $jumlah_tambahan);
        $stmt->bindParam(":id", $id_pengguna);
        return $stmt->execute();
    }
}
?>
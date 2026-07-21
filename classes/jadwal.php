<?php
/**
 * File: classes/Jadwal.php
 * Deskripsi: Kelas untuk mengelola alokasi waktu dan validasi Double Booking
 */

class Jadwal {
    private $koneksi;
    private $nama_tabel = "jadwal";

    public function __construct($db) {
        $this->koneksi = $db;
    }

    /**
     * Fungsi krusial untuk mengecek ketersediaan jadwal pada HARI dan LAPANGAN tertentu.
     * Ini akan mencegah terjadinya jadwal bentrok (Double Booking).
     */
    public function cekJadwalTersedia($id_lapangan, $tanggal) {
        /* * Logika SQL: 
         * Ambil semua jadwal untuk lapangan X.
         * Hitung apakah jadwal tersebut sudah ada di tabel detail_reservasi 
         * pada tanggal Y, dengan status reservasi yang BUKAN dibatalkan.
         */
        $query = "SELECT j.*, 
                    (SELECT COUNT(*) FROM detail_reservasi dr 
                     JOIN reservasi r ON dr.id_reservasi = r.id_reservasi 
                     WHERE dr.id_jadwal = j.id_jadwal 
                     AND r.tanggal_sewa = :tanggal 
                     AND r.status_reservasi != 'dibatalkan') as is_booked
                  FROM " . $this->nama_tabel . " j
                  WHERE j.id_lapangan = :id_lapangan 
                  ORDER BY j.jam_mulai ASC";
        
        $stmt = $this->koneksi->prepare($query);
        $stmt->bindParam(":id_lapangan", $id_lapangan);
        $stmt->bindParam(":tanggal", $tanggal);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
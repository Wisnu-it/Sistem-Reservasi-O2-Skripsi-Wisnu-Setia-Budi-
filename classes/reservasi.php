<?php
/**
 * File: classes/Reservasi.php
 * Deskripsi: Kelas untuk menangani logika transaksi pemesanan dan pembayaran
 */

class Reservasi {
    private $koneksi;

    public function __construct($db) {
        $this->koneksi = $db;
    }

    // Fungsi utama untuk memproses pembuatan pesanan baru
    public function buatReservasi($id_pengguna, $id_lapangan, $tanggal, $jam_array, $subtotal, $diskon, $poin_dipakai, $total_bayar, $metode_bayar) {
        try {
            $this->koneksi->beginTransaction();

            // 1. Simpan ke tabel Induk 'reservasi' (waktu_pesan otomatis terisi oleh database)
            $query_res = "INSERT INTO reservasi (id_pengguna, tanggal_sewa, total_biaya, potongan_diskon, poin_yang_digunakan, total_bayar, status_reservasi) 
                          VALUES (:id_user, :tgl, :biaya, :diskon, :poin, :total, 'menunggu')";
            $stmt_res = $this->koneksi->prepare($query_res);
            $stmt_res->execute([
                ':id_user' => $id_pengguna,
                ':tgl' => $tanggal,
                ':biaya' => $subtotal,
                ':diskon' => $diskon,
                ':poin' => $poin_dipakai,
                ':total' => $total_bayar
            ]);
            
            $id_reservasi = $this->koneksi->lastInsertId();

            // 2. Simpan ke tabel Pivot 'detail_reservasi'
            $query_jadwal = "SELECT id_jadwal FROM jadwal WHERE id_lapangan = :id_lap AND jam_mulai = :jam LIMIT 1";
            $stmt_jadwal = $this->koneksi->prepare($query_jadwal);

            $query_detail = "INSERT INTO detail_reservasi (id_reservasi, id_jadwal, subtotal) VALUES (:id_res, :id_jad, :sub)";
            $stmt_detail = $this->koneksi->prepare($query_detail);

            $harga_per_sesi = $subtotal / count($jam_array);

            foreach($jam_array as $jam) {
                $stmt_jadwal->execute([':id_lap' => $id_lapangan, ':jam' => $jam]);
                $row_jadwal = $stmt_jadwal->fetch(PDO::FETCH_ASSOC);
                
                if(!$row_jadwal) {
                    throw new Exception("Slot Jadwal tidak ditemukan!");
                }
                
                $id_jadwal = $row_jadwal['id_jadwal'];
                
                $stmt_detail->execute([
                    ':id_res' => $id_reservasi, 
                    ':id_jad' => $id_jadwal, 
                    ':sub' => $harga_per_sesi
                ]);
            }

            // 3. Simpan rekam jejak awal ke tabel 'pembayaran'
            $query_bayar = "INSERT INTO pembayaran (id_reservasi, metode_pembayaran, jumlah_bayar, status_pembayaran) 
                            VALUES (:id_res, :metode, :jumlah, 'belum_bayar')";
            $stmt_bayar = $this->koneksi->prepare($query_bayar);
            $stmt_bayar->execute([
                ':id_res' => $id_reservasi,
                ':metode' => $metode_bayar,
                ':jumlah' => $total_bayar
            ]);

            $this->koneksi->commit();
            return $id_reservasi; 

        } catch(Exception $e) {
            $this->koneksi->rollBack();
            return false;
        }
    }

    // =========================================================================
    // FITUR BARU: Membatalkan pesanan yang lewat dari 30 menit & belum dibayar
    // =========================================================================
    public function batalkanPesananKadaluarsa() {
        try {
            $query = "UPDATE reservasi r
                      JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                      SET r.status_reservasi = 'dibatalkan',
                          p.status_pembayaran = 'gagal'
                      WHERE r.status_reservasi = 'menunggu' 
                      AND r.waktu_pesan < (NOW() - INTERVAL 30 MINUTE)";
            
            $stmt = $this->koneksi->prepare($query);
            $stmt->execute();
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
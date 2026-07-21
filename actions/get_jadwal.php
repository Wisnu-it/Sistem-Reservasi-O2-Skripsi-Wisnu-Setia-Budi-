<?php
/**
 * File: actions/get_jadwal.php
 * Deskripsi: API Real-time untuk memeriksa jadwal operasional dan status hari libur
 */
session_start();
require_once "../config/database.php";
require_once "../classes/Jadwal.php";

$database = new database();
$db = $database->getKoneksi();

$id_lapangan = isset($_GET['id_lapangan']) ? (int)$_GET['id_lapangan'] : 0;
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// 1. CEK STATUS HARI LIBUR
$stmt_libur = $db->prepare("SELECT keterangan FROM hari_libur WHERE tanggal = :tgl LIMIT 1");
$stmt_libur->execute([':tgl' => $tanggal]);

if ($stmt_libur->rowCount() > 0) {
    $libur = $stmt_libur->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['is_libur' => true, 'keterangan' => $libur['keterangan']]);
    exit;
}

// 2. JIKA BUKAN HARI LIBUR, AMBIL DATA JADWAL
$objJadwal = new jadwal($db);
$stmt = $objJadwal->cekJadwalTersedia($id_lapangan, $tanggal);

$hasil = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hasil[] = [
        'id_jadwal'   => $row['id_jadwal'], // Menyertakan ID Jadwal untuk value checkbox
        'jam_mulai'   => substr($row['jam_mulai'], 0, 5), 
        'jam_selesai' => substr($row['jam_selesai'], 0, 5),
        'is_booked'   => ($row['is_booked'] > 0) ? true : false 
    ];
}

header('Content-Type: application/json');
echo json_encode($hasil);
exit;
<?php
/**
 * File: proses_bayar.php
 * Deskripsi: Alur Pemrosesan Transaksi Hybrid (Sesuai Struktur Database db_o2futsal.sql)
 */
session_start();
require_once "config/database.php";
require_once "config/midtrans.php"; 

if (!isset($_SESSION['id_pengguna'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$database = new database();
$db = $database->getKoneksi();

$id_pengguna = $_SESSION['id_pengguna'];
$id_lapangan = (int)$_POST['id_lapangan']; 
$tanggal_sewa = $_POST['tanggal_sewa'];
$total_bayar = (int)$_POST['total_bayar_bersih'];
$metode_pembayaran = $_POST['metode_pembayaran'];
$jadwal_dipilih = isset($_POST['id_jadwal']) ? $_POST['id_jadwal'] : [];

if (empty($jadwal_dipilih)) {
    echo "<script>alert('Jadwal bermain tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$stmt_user = $db->prepare("SELECT nama_lengkap, email, nomor_telepon FROM pengguna WHERE id_pengguna = ?");
$stmt_user->execute([$id_pengguna]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

try {
    $db->beginTransaction();

    // 1. INSERT KE TABEL RESERVASI (Menambahkan total_biaya sesuai skema)
    $stmt_res = $db->prepare("INSERT INTO reservasi (id_pengguna, tanggal_sewa, total_biaya, total_bayar, status_reservasi, waktu_pesan) VALUES (?, ?, ?, ?, 'menunggu', NOW())");
    $stmt_res->execute([$id_pengguna, $tanggal_sewa, $total_bayar, $total_bayar]);
    $id_reservasi = $db->lastInsertId();

    // 2. INSERT KE TABEL DETAIL_RESERVASI (Menambahkan subtotal sesuai skema)
    $stmt_detail = $db->prepare("INSERT INTO detail_reservasi (id_reservasi, id_jadwal, subtotal) VALUES (?, ?, 0)");
    foreach ($jadwal_dipilih as $id_jadwal) {
        $stmt_detail->execute([$id_reservasi, (int)$id_jadwal]);
    }

    // 3. INSERT KE TABEL PEMBAYARAN (Mengganti waktu_pembayaran menjadi tanggal_bayar)
    $stmt_pem = $db->prepare("INSERT INTO pembayaran (id_reservasi, metode_pembayaran, jumlah_bayar, tanggal_bayar) VALUES (?, ?, ?, NOW())");
    $stmt_pem->execute([$id_reservasi, $metode_pembayaran, $total_bayar]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    die("Gagal memproses pesanan: " . $e->getMessage());
}

// ==============================================================================
// PERCABANGAN JALUR HYBRID (MIDTRANS VS TUNAI)
// ==============================================================================

if ($metode_pembayaran == 'payment_gateway') {
    $order_id = "O2F-" . $id_reservasi . "-" . time(); 
    
    $customer_details = [
        'first_name'    => $user['nama_lengkap'],
        'email'         => $user['email'],
        'phone'         => $user['nomor_telepon'] ?: '08123456789'
    ];

    $item_details = [
        [
            'id'       => 'RES-' . $id_reservasi,
            'price'    => $total_bayar,
            'quantity' => 1,
            'name'     => 'Booking Lapangan O2 Futsal'
        ]
    ];

    $snap_token = getMidtransSnapToken($order_id, $total_bayar, $customer_details, $item_details);
    
    if (!$snap_token) {
        die("Gagal terhubung ke server Midtrans. Periksa konfigurasi Access Key Anda.");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Pembayaran - O2 Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-2pcspWFvGZagEEwP"></script>
    <style>
        body { background-color: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .text-accent { color: #c4ff00 !important; }
        .card-custom { background-color: #111; border: 1px solid #333; border-radius: 12px; }
    </style>
</head>
<body>

    <div class="container my-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card-custom p-5 shadow-lg">
                    
                    <?php if ($metode_pembayaran == 'payment_gateway'): ?>
                        <h3 class="fw-bold mb-3">SISTEM GERBANG PEMBAYARAN</h3>
                        <p class="text-secondary mb-4">Mohon tunggu, jendela transaksi Midtrans aman sedang disiapkan untuk Anda.</p>
                        <div class="spinner-border text-accent mb-4" role="status"></div>
                        <br>
                        <button id="pay-button" class="btn fw-bold px-5 py-3 rounded-pill text-dark w-100" style="background-color: #c4ff00; border:none;">
                            KLIK UNTUK BAYAR SEKARANG
                        </button>

                    <?php else: ?>
                        <h2 class="display-4 mb-3">🎉</h2>
                        <h3 class="fw-bold text-accent mb-2">RESERVASI BERHASIL DICATAT!</h3>
                        <p class="text-secondary mb-4">Kode Pemesanan Anda adalah: <strong class="text-white">#ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></strong></p>
                        
                        <div class="alert alert-warning bg-transparent border-warning text-warning small text-start p-3 mb-4">
                            <strong>Pemberitahuan Staf Kasir:</strong><br>
                            Silakan datang ke pos administrasi O2 Futsal tepat waktu dan selesaikan pelunasan pembayaran tunai Anda di kasir agar jadwal bermain tidak hangus.
                        </div>

                        <a href="index.php" class="btn btn-outline-light px-4 rounded-pill w-100 py-2">Kembali ke Beranda</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <?php if ($metode_pembayaran == 'payment_gateway'): ?>
    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        
        window.addEventListener('DOMContentLoaded', function () {
            triggerSnap();
        });

        payButton.onclick = function() {
            triggerSnap();
        };

        function triggerSnap() {
            snap.pay('<?php echo $snap_token; ?>', {
                onSuccess: function(result){
                    window.location.href = 'actions/update_lunas_lokal.php?id=<?php echo $id_reservasi; ?>';
                },
                onPending: function(result){
                    alert("Menunggu Pembayaran. Segera selesaikan transaksi Anda.");
                    window.location.href = 'index.php';
                },
                onError: function(result){
                    alert("Pembayaran Gagal! Silakan coba kembali.");
                    window.location.href = 'checkout.php';
                },
                onClose: function(){
                    alert('Anda menutup halaman pembayaran sebelum transaksi selesai.');
                }
            });
        }
    </script>
    <?php endif; ?>

</body>
</html>
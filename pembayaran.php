<?php
/**
 * File: pembayaran.php
 * Deskripsi: Antarmuka Pembayaran Pelanggan (Dengan Real-Time AJAX Polling Midtrans)
 */
ob_start();
session_start();

if (!isset($_SESSION['id_pengguna']) || !isset($_GET['id'])) {
    header("Location: /o2futsal/index.php");
    exit;
}

require_once "config/database.php";
require_once "config/midtrans.php"; 

$database = new database();
$db = $database->getKoneksi();

$id_reservasi = (int)$_GET['id'];
$id_pengguna = (int)$_SESSION['id_pengguna'];

// =======================================================================
// FITUR PEMBATALAN MANDIRI
// =======================================================================
if (isset($_GET['action']) && $_GET['action'] == 'batal_user') {
    $db->query("UPDATE reservasi SET status_reservasi = 'dibatalkan' WHERE id_reservasi = $id_reservasi AND id_pengguna = $id_pengguna");
    $db->query("UPDATE pembayaran SET status_pembayaran = 'gagal' WHERE id_reservasi = $id_reservasi");
    echo "<script>alert('Pesanan Anda berhasil dibatalkan.'); window.location.href='profil.php';</script>";
    exit;
}

// =======================================================================
// MENGAMBIL DATA TRANSAKSI
// =======================================================================
$query = "SELECT p.*, r.tanggal_sewa, r.total_bayar, r.status_reservasi, r.waktu_pesan,
                 TIMESTAMPDIFF(SECOND, r.waktu_pesan, NOW()) AS waktu_berjalan_detik
          FROM pembayaran p 
          JOIN reservasi r ON p.id_reservasi = r.id_reservasi 
          WHERE p.id_reservasi = :id_res AND r.id_pengguna = :id_user LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':id_res' => $id_reservasi, ':id_user' => $id_pengguna]);
$data_bayar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data_bayar) {
    echo "Data pesanan tidak ditemukan atau Anda tidak memiliki akses.";
    exit;
}

// =======================================================================
// LOGIKA PEMBATALAN OTOMATIS (30 Menit)
// =======================================================================
$waktu_berjalan = (int)$data_bayar['waktu_berjalan_detik'];
$sisa_waktu_detik = 1800 - $waktu_berjalan;

if ($sisa_waktu_detik <= 0 && strtolower($data_bayar['status_reservasi']) == 'menunggu') {
    $db->query("UPDATE reservasi SET status_reservasi = 'dibatalkan' WHERE id_reservasi = $id_reservasi");
    $db->query("UPDATE pembayaran SET status_pembayaran = 'gagal' WHERE id_reservasi = $id_reservasi");
    
    $data_bayar['status_reservasi'] = 'dibatalkan';
    $data_bayar['status_pembayaran'] = 'gagal';
    $sisa_waktu_detik = 0;
}

// =======================================================================
// GENERATE SNAP TOKEN & SIMPAN ORDER ID KE DATABASE
// =======================================================================
$snap_token = "";
if (strtolower($data_bayar['status_reservasi']) == 'menunggu' && $data_bayar['metode_pembayaran'] == 'payment_gateway') {
    $stmt_user = $db->prepare("SELECT nama_lengkap, email, nomor_telepon FROM pengguna WHERE id_pengguna = ?");
    $stmt_user->execute([$id_pengguna]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // KITA BUAT ORDER ID LALU SIMPAN KE DATABASE AGAR BISA DILACAK
    $order_id = "O2F-" . $id_reservasi . "-" . time(); 
    $db->query("UPDATE pembayaran SET id_pesanan_gateway = '$order_id' WHERE id_reservasi = $id_reservasi");

    $customer_details = [
        'first_name' => $user['nama_lengkap'],
        'email'      => $user['email'],
        'phone'      => $user['nomor_telepon'] ?: '08123456789'
    ];
    $item_details = [[
        'id'       => 'RES-' . $id_reservasi,
        'price'    => (int)$data_bayar['total_bayar'],
        'quantity' => 1,
        'name'     => 'Sewa Lapangan O2 Futsal'
    ]];

    $snap_token = getMidtransSnapToken($order_id, $data_bayar['total_bayar'], $customer_details, $item_details);
}

require_once "layout/header.php";
?>

<div class="container my-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card-custom p-5 text-center border border-secondary shadow-lg" style="background-color: #111; border-radius: 12px;">
                
                <h2 class="fw-bold text-white mb-1">PROSES RESERVASI</h2>
                <p class="text-secondary mb-4">ID Transaksi: #ORD-<?php echo str_pad($id_reservasi, 3, '0', STR_PAD_LEFT); ?></p>
                
                <div class="bg-black p-4 rounded-3 border border-secondary mb-4">
                    <span class="text-secondary d-block mb-2 small">Total yang Harus Dibayar</span>
                    <h1 class="text-accent fw-bold mb-0" style="color: #c4ff00;">Rp <?php echo number_format($data_bayar['total_bayar'], 0, ',', '.'); ?></h1>
                </div>

                <?php 
                if (in_array(strtolower($data_bayar['status_reservasi']), ['dikonfirmasi', 'selesai', 'lunas']) || strtolower($data_bayar['status_pembayaran']) == 'lunas'): 
                ?>
                    <div class="alert alert-success bg-transparent border-success text-success py-4">
                        <h4 class="fw-bold mb-2">✅ RESERVASI LUNAS</h4>
                        <p class="mb-0 small">Pembayaran Anda telah sukses diverifikasi. Lapangan telah berhasil dikunci untuk jadwal tanding Anda.</p>
                    </div>
                    <a href="profil.php" class="btn btn-outline-light w-100 rounded-pill mt-4 py-3 fw-bold">LIHAT RIWAYAT LAIN</a>
                
                <?php elseif (strtolower($data_bayar['status_reservasi']) == 'dibatalkan' || strtolower($data_bayar['status_pembayaran']) == 'gagal'): ?>
                    <div class="alert alert-danger bg-transparent border-danger text-danger py-4">
                        <h4 class="fw-bold mb-2">❌ RESERVASI BATAL</h4>
                        <p class="mb-0 small">Batas toleransi pelunasan waktu 30 menit telah habis, atau Anda telah melakukan pembatalan manual pada pesanan ini.</p>
                    </div>
                    <a href="profil.php" class="btn btn-outline-light w-100 rounded-pill mt-4 py-3 fw-bold">KEMBALI KE RIWAYAT</a>

                <?php else: ?>
                    <div class="mb-4">
                        <span class="text-secondary small d-block mb-2">Sisa Batas Waktu Pelunasan Anda:</span>
                        <div class="d-inline-block border border-danger rounded-pill px-4 py-2 bg-danger bg-opacity-10">
                            <h3 id="countdownTimer" class="text-danger fw-bold mb-0">--:--:--</h3>
                        </div>
                        <p id="pollingText" class="text-secondary small mt-2 d-none">⏳ Menunggu verifikasi pembayaran...</p>
                    </div>

                    <?php if ($data_bayar['metode_pembayaran'] == 'payment_gateway'): ?>
                        <div class="alert alert-info bg-transparent border-info text-info small text-start p-3 mb-4">
                            Sistem terhubung ke **Midtrans Automated Secure Gateway**. Silakan klik tombol di bawah untuk menampilkan petunjuk transfer atau scan QRIS resmi.
                        </div>
                        <button id="pay-button" class="btn fw-bold py-3 fs-5 rounded-pill w-100 mb-3 text-dark" style="background-color: #c4ff00; border:none;">
                            💳 BAYAR SEKARANG VIA MIDTRANS
                        </button>
                    <?php else: ?>
                        <div class="p-4 border border-warning rounded-3 mb-4 text-start bg-black">
                            <h5 class="text-warning fw-bold mb-2">💵 INTRUKSI BAYAR KASIR (OFFLINE)</h5>
                            <p class="text-secondary small mb-0">Segera tunjukkan bukti pesanan ini ke loket administrasi O2 Futsal untuk divalidasi oleh petugas kasir kami sebelum batas waktu 30 menit berakhir.</p>
                        </div>
                    <?php endif; ?>

                    <a href="pembayaran.php?id=<?php echo $id_reservasi; ?>&action=batal_user" 
                       class="btn btn-link text-secondary text-decoration-none small w-100 mt-2"
                       onclick="return confirm('Apakah Anda yakin ingin membatalkan booking lapangan ini?');">
                       ❌ Batalkan Pemesanan Ini
                    </a>

                    <script>
                        let sisaDetik = <?php echo $sisa_waktu_detik; ?>;
                        const displayTimer = document.getElementById('countdownTimer');
                        
                        function updateTimer() {
                            if (sisaDetik <= 0) {
                                if(displayTimer) displayTimer.innerHTML = "EXPIRED";
                                let btnPay = document.getElementById('pay-button');
                                if (btnPay) btnPay.disabled = true;
                                return;
                            }
                            let h = Math.floor(sisaDetik / 3600);
                            let m = Math.floor((sisaDetik % 3600) / 60);
                            let s = sisaDetik % 60;

                            h = h < 10 ? "0" + h : h;
                            m = m < 10 ? "0" + m : m;
                            s = s < 10 ? "0" + s : s;

                            if(displayTimer) displayTimer.innerHTML = h + ":" + m + ":" + s;
                            sisaDetik--;
                        }

                        if (displayTimer && sisaDetik > 0) {
                            updateTimer();
                            setInterval(updateTimer, 1000);
                        }
                    </script>

                    <?php if ($data_bayar['metode_pembayaran'] == 'payment_gateway'): ?>
                    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-2pcspWFvGZagEEwP"></script>
                    <script type="text/javascript">
                        var payButton = document.getElementById('pay-button');
                        if (payButton) {
                            payButton.onclick = function() {
                                snap.pay('<?php echo $snap_token; ?>', {
                                    onSuccess: function(result){
                                        window.location.reload(); // Langsung reload, biarkan background checker bekerja
                                    },
                                    onPending: function(result){
                                        document.getElementById('pollingText').classList.remove('d-none');
                                    },
                                    onError: function(result){
                                        alert("Transaksi gagal.");
                                    }
                                });
                            };
                        }

                        // =========================================================
                        // AGEN RAHASIA (AJAX POLLING) - BERTANYA KE MIDTRANS SETIAP 5 DETIK
                        // =========================================================
                        function lacakStatusPembayaran() {
                            fetch('actions/cek_status_otomatis.php?id=<?php echo $id_reservasi; ?>')
                            .then(response => response.text())
                            .then(status => {
                                if (status.trim() === 'LUNAS') {
                                    // Jika API Midtrans merespon LUNAS, otomatis refresh layar pelanggan!
                                    window.location.reload();
                                }
                            })
                            .catch(error => console.error('Error Polling:', error));
                        }

                        // Nyalakan mesin pencari status setiap 5 detik (5000 milidetik)
                        setInterval(lacakStatusPembayaran, 5000);
                    </script>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once "layout/footer.php"; ?>
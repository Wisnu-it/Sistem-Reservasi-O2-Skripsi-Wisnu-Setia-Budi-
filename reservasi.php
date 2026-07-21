<?php
/**
 * File: reservasi.php
 * Deskripsi: Halaman Formulir Multi-Step Reservasi Lapangan dengan Kalkulasi Diskon Real-Time
 */
ob_start();
session_start();
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /o2futsal/login.php");
    exit;
}

require_once "config/database.php";
$database = new database();
$db = $database->getKoneksi();

// ============================================================================
// AMBIL STATUS VIP PENGGUNA UNTUK KALKULASI DISKON DI FRONTEND
// ============================================================================
$id_pengguna = $_SESSION['id_pengguna'];
$stmt_user = $db->prepare("SELECT is_vip FROM pengguna WHERE id_pengguna = ?");
$stmt_user->execute([$id_pengguna]);
$is_vip = $stmt_user->fetchColumn() ?: 0;

// Mengambil master data lapangan
$query_lapangan = "SELECT * FROM lapangan ORDER BY nama_lapangan ASC";
$stmt_lapangan = $db->query($query_lapangan);

require_once "layout/header.php";
?>

<style>
    .jam-checkbox { display: none; }
    .jam-checkbox:checked + label {
        background-color: #c4ff00 !important;
        color: #000 !important;
        border-color: #c4ff00 !important;
        font-weight: bold;
    }
    .btn.disabled { pointer-events: none; opacity: 0.4; }
    
    /* Animasi kemunculan kotak pratinjau */
    .fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container my-5">
    <div class="row text-white">
        
        <div class="col-lg-8 mb-4">
            <div class="card-custom p-4 p-md-5">
                <h2 class="fw-bold mb-1">RESERVASI <span class="text-accent">LAPANGAN</span></h2>
                <p class="text-secondary mb-5">Selesaikan 3 tahapan mudah di bawah ini.</p>

                <form id="formReservasi" action="/o2futsal/checkout.php" method="POST">
                    
                    <div id="step1" class="step-container">
                        <h4 class="fw-bold text-accent mb-4">01. TENTUKAN TANGGAL</h4>
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-semibold">Tanggal Main</label>
                            <input type="date" class="form-control bg-transparent text-white border-secondary p-3 shadow-none" id="tanggal_sewa" name="tanggal" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button type="button" class="btn btn-accent px-5 py-2 rounded-pill fw-bold" onclick="nextStep(2)">LANJUTKAN</button>
                    </div>

                    <div id="step2" class="step-container" style="display: none;">
                        <h4 class="fw-bold text-accent mb-4">02. PILIH LAPANGAN</h4>
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-semibold">Daftar Lapangan Tersedia</label>
                            <select class="form-select bg-transparent text-white border-secondary p-3 shadow-none custom-select" id="id_lapangan" name="id_lapangan" onchange="updateInfoLapangan()" required>
                                <option value="" class="text-dark" selected disabled>-- Pilih Unit Lapangan --</option>
                                <?php 
                                while ($row = $stmt_lapangan->fetch(PDO::FETCH_ASSOC)) {
                                    $foto = !empty($row['foto_lapangan']) ? "/o2futsal/assets/images/lapangan/".$row['foto_lapangan'] : "https://placehold.co/800x400?text=Foto+Belum+Tersedia";
                                    $deskripsi = htmlspecialchars($row['deskripsi'], ENT_QUOTES);
                                    echo "<option class='text-dark' value='{$row['id_lapangan']}' data-harga='{$row['harga_per_jam']}' data-foto='{$foto}' data-desk='{$deskripsi}'>Lapangan {$row['nama_lapangan']} - Rp " . number_format($row['harga_per_jam'], 0, ',', '.') . "/jam</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div id="infoLapanganContainer" class="mb-5 fade-in-up" style="display: none;">
                            <div class="position-relative">
                                <img id="previewFoto" src="" alt="Foto Lapangan" class="img-fluid rounded-top-3 border border-secondary border-bottom-0 w-100" style="max-height: 320px; object-fit: cover;">
                                <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);">
                                    <h5 class="text-accent fw-bold mb-0" id="previewNamaLapangan">Nama Lapangan</h5>
                                </div>
                            </div>
                            <div class="bg-black p-4 rounded-bottom-3 border border-secondary text-secondary" style="line-height: 1.6;">
                                <span id="previewDeskripsi" class="text-light"></span>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-outline-light px-4 py-2 rounded-pill fw-bold" onclick="nextStep(1)">KEMBALI</button>
                            <button type="button" class="btn btn-accent px-5 py-2 rounded-pill fw-bold" onclick="nextStep(3)">LANJUTKAN</button>
                        </div>
                    </div>

                    <div id="step3" class="step-container" style="display: none;">
                        <h4 class="fw-bold text-accent mb-4">03. PILIH JAM MAIN</h4>
                        <p class="text-secondary small mb-3">Tandai jam yang ingin Anda sewa (bisa lebih dari satu). Kotak abu-abu gelap menandakan jam tersebut sudah dipesan atau waktu bermain telah berlalu.</p>
                        
                        <div class="row g-3 mb-4" id="jamContainer">
                            <!-- Diisi melalui AJAX -->
                        </div>

                        <div class="d-flex gap-3 mt-5">
                            <button type="button" class="btn btn-outline-light px-4 py-2 rounded-pill fw-bold" onclick="nextStep(2)">KEMBALI</button>
                            <button type="submit" id="btnSubmitPesanan" class="btn btn-accent px-5 py-2 rounded-pill fw-bold">PROSES PESANAN</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- PANEL RINGKASAN STICKY -->
        <div class="col-lg-4">
            <div class="card-custom p-4 sticky-top border border-secondary" style="top: 100px; background-color: #111;">
                <h4 class="fw-bold text-white mb-4 border-bottom border-secondary pb-3">RINGKASAN</h4>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-secondary">Tanggal:</span>
                    <span class="fw-bold" id="summary-tanggal">-</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-secondary">Lapangan:</span>
                    <span class="fw-bold text-accent" id="summary-lapangan">-</span>
                </div>
                <div class="d-flex justify-content-between mb-4 small border-bottom border-secondary pb-3">
                    <span class="text-secondary">Durasi:</span>
                    <span class="fw-bold" id="summary-durasi">0 Jam</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-secondary small">TOTAL BAYAR:</span>
                    <h3 class="fw-bold text-accent mb-0" id="summary-total">RP 0</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const serverTanggalHariIni = "<?php echo date('Y-m-d'); ?>";
    const serverJamSekarangWita = <?php echo (int)date('H'); ?>;
    const isVip = <?php echo $is_vip; ?>; // Variabel VIP dilempar dari PHP ke JS
    let hargaPerJam = 0;

    async function loadJadwalAJAX() {
        const tgl = document.getElementById('tanggal_sewa').value;
        const lap = document.getElementById('id_lapangan').value;
        const container = document.getElementById('jamContainer');
        const btnSubmit = document.getElementById('btnSubmitPesanan');
        
        if(!tgl || !lap) return;

        container.innerHTML = `<div class="col-12 text-center text-accent py-4">Memuat ketersediaan jadwal...</div>`;
        btnSubmit.disabled = false;

        try {
            const response = await fetch(`/o2futsal/actions/get_jadwal.php?tanggal=${tgl}&id_lapangan=${lap}`);
            if (!response.ok) throw new Error('Gagal memuat data dari server');
            const data = await response.json();
            
            let htmlHTML = '';
            
            // PECAH TANGGAL UNTUK MENDAPATKAN HARI
            let parts = tgl.split('-');
            let dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
            let hariMain = dateObj.getDay(); // 0 = Minggu, 1 = Senin, dst.

            if(data.is_libur) {
                htmlHTML = `<div class="col-12 text-center py-5 border border-danger rounded-3" style="background-color: rgba(220, 53, 69, 0.1);">
                                <h3 class="text-danger fw-bold mb-2">🚫 TUTUP / LIBUR</h3>
                                <p class="text-secondary mb-0">Alasan: ${data.keterangan}</p>
                            </div>`;
                btnSubmit.disabled = true;
            } else if(data.length === 0) {
                htmlHTML = `<div class="col-12 text-center text-warning">Belum ada jam operasional di database.</div>`;
                btnSubmit.disabled = true;
            } else {
                data.forEach((item) => {
                    const jamSlot = parseInt(item.jam_mulai.split(':')[0]);
                    const sudahLewat = (tgl === serverTanggalHariIni && jamSlot <= serverJamSekarangWita);
                    
                    // ==============================================================
                    // LOGIKA UI PROMO (Senin-Jumat, 08:00 - 15:00)
                    // ==============================================================
                    let isHappyHour = (hariMain >= 1 && hariMain <= 5 && jamSlot >= 8 && jamSlot <= 15);
                    let promoBadge = isHappyHour ? `<br><small class="text-warning fw-bold" style="font-size:11px;">🔥 Promo Happy Hour!</small>` : '';

                    if (item.is_booked || sudahLewat) {
                        htmlHTML += `
                        <div class='col-md-6 col-lg-4'>
                            <button type='button' class='btn btn-dark w-100 py-3 text-secondary border-secondary disabled' style='background-color: #161616; cursor: not-allowed;'>
                                ${item.jam_mulai} - ${item.jam_selesai} ${sudahLewat ? '<br><small style="font-size:10px;">(Waktu Lewat)</small>' : ''}
                            </button>
                        </div>`;
                    } else {
                        // Menambahkan data-jam agar bisa dibaca oleh kalkulasiTotal()
                        htmlHTML += `
                        <div class='col-md-6 col-lg-4'>
                            <input type='checkbox' class='jam-checkbox' id='jam_${item.id_jadwal}' name='id_jadwal[]' value='${item.id_jadwal}' data-jam='${jamSlot}' onchange='kalkulasiTotal()'>
                            <label class='btn btn-outline-secondary w-100 py-3 text-white border-secondary' for='jam_${item.id_jadwal}'>
                                ${item.jam_mulai} - ${item.jam_selesai} ${promoBadge}
                            </label>
                        </div>`;
                    }
                });
            }
            container.innerHTML = htmlHTML;
            kalkulasiTotal();

        } catch (error) {
            container.innerHTML = `<div class="col-12 text-danger text-center">Gagal memuat jadwal. ${error.message}</div>`;
        }
    }

    function nextStep(stepNumber) {
        if (stepNumber === 2 && !document.getElementById('tanggal_sewa').value) {
            alert("Harap tentukan tanggal bermain terlebih dahulu!"); return;
        }
        if (stepNumber === 3 && !document.getElementById('id_lapangan').value) {
            alert("Harap pilih unit lapangan terlebih dahulu!"); return;
        }

        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';

        document.getElementById('step' + stepNumber).style.display = 'block';

        if(stepNumber >= 2) {
            document.getElementById('summary-tanggal').innerText = document.getElementById('tanggal_sewa').value;
        }
        
        if (stepNumber === 3) {
            loadJadwalAJAX();
        }
    }

    function htmlDecode(input) {
        let doc = new DOMParser().parseFromString(input, "text/html");
        return doc.documentElement.textContent;
    }

    function updateInfoLapangan() {
        let select = document.getElementById('id_lapangan');
        let option = select.options[select.selectedIndex];
        
        hargaPerJam = parseInt(option.getAttribute('data-harga')) || 0;
        
        let namaUtuh = option.text.split(' - Rp')[0]; 
        document.getElementById('summary-lapangan').innerText = namaUtuh;
        document.getElementById('previewNamaLapangan').innerText = namaUtuh;
        
        let fotoURL = option.getAttribute('data-foto');
        let deskripsiEncoded = option.getAttribute('data-desk');
        
        if (fotoURL) {
            document.getElementById('previewFoto').src = fotoURL;
            document.getElementById('previewDeskripsi').innerHTML = deskripsiEncoded ? htmlDecode(deskripsiEncoded) : "Fasilitas standar premium.";
            
            let container = document.getElementById('infoLapanganContainer');
            container.style.display = 'block';
            
            container.classList.remove('fade-in-up');
            void container.offsetWidth; 
            container.classList.add('fade-in-up');
        } else {
            document.getElementById('infoLapanganContainer').style.display = 'none';
        }

        kalkulasiTotal();
    }

    function kalkulasiTotal() {
        let checkboxes = document.querySelectorAll('.jam-checkbox:checked');
        let durasi = checkboxes.length;

        // Dapatkan data hari dari input tanggal
        let tglSewa = document.getElementById('tanggal_sewa').value;
        let parts = tglSewa.split('-');
        let dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
        let hariMain = dateObj.getDay();

        let totalKotor = durasi * hargaPerJam;
        let totalDiskon = 0;

        // ==============================================================
        // LOGIKA JS: MENGHITUNG DISKON BERDASARKAN ATURAN WAKTU
        // ==============================================================
        checkboxes.forEach(function(cb) {
            let jam = parseInt(cb.getAttribute('data-jam'));

            // 1. Cek Diskon Happy Hour (Senin=1 s/d Jumat=5, Jam 08 s/d 15)
            if (hariMain >= 1 && hariMain <= 5 && jam >= 8 && jam <= 15) {
                totalDiskon += 30000;
            }

            // 2. Cek Diskon VIP
            if (isVip == 1) {
                totalDiskon += 20000;
            }
        });

        let totalBayar = totalKotor - totalDiskon;
        if (totalBayar < 0) totalBayar = 0;

        document.getElementById('summary-durasi').innerText = durasi + " Jam";
        document.getElementById('summary-total').innerText = "RP " + totalBayar.toLocaleString('id-ID');
    }

    document.getElementById('formReservasi').addEventListener('submit', function(e) {
        let jamTerpilih = document.querySelectorAll('.jam-checkbox:checked').length;
        if (jamTerpilih === 0) {
            e.preventDefault();
            alert('⚠️ ERROR: Kamu belum menandai jam bermain. Silakan klik minimal satu kotak jadwal!');
        }
    });
</script>

<?php require_once "layout/footer.php"; ?>
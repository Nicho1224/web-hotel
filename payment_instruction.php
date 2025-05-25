<?php
require 'config.php';

// Validasi autentikasi user
if (!isset($_SESSION['id_user']) || $_SESSION['lv'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Validasi parameter invoice dan metode
if (!isset($_GET['invoice']) || !isset($_GET['method'])) {
    header('Location: dashboard_user.php');
    exit;
}

$invoice_id = $_GET['invoice'];
$payment_method = $_GET['method'];

// Ambil data transaksi
$stmt = $conn->prepare("
    SELECT t.*, k.nama AS nama_kamar 
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_transaksi = ? AND t.id_user = ?
");
$stmt->execute([$invoice_id, $_SESSION['id_user']]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    header('Location: dashboard_user.php');
    exit;
}

// Generate kode pembayaran
$payment_code = strtoupper(substr($payment_method, 0, 3)) . date('Ymd') . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);

// We'll use session to store payment information instead of database columns
$_SESSION['payment_code_' . $invoice_id] = $payment_code;
$_SESSION['payment_method_' . $invoice_id] = $payment_method;

// Removed the database update since columns don't exist

// Logo untuk metode pembayaran
$payment_logos = [
    'gopay' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/220px-Gopay_logo.svg.png',
    'ovo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Logo_ovo_purple.svg/2560px-Logo_ovo_purple.svg.png',
    'dana' => 'https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg',
    'bca' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/2560px-Bank_Central_Asia.svg.png',
    'mandiri' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg',
    'bni' => 'https://upload.wikimedia.org/wikipedia/id/thumb/5/55/BNI_logo.svg/1200px-BNI_logo.svg.png'
];

// Instruksi pembayaran berdasarkan metode
$payment_instructions = [
    'gopay' => [
        'title' => 'Pembayaran via GoPay',
        'steps' => [
            'Buka aplikasi Gojek di smartphone Anda',
            'Pilih Pay atau bayar',
            'Pilih Scan QR',
            'Scan QR code yang ditampilkan',
            'Masukkan jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Masukkan PIN GoPay Anda',
            'Pembayaran selesai'
        ]
    ],
    'ovo' => [
        'title' => 'Pembayaran via OVO',
        'steps' => [
            'Buka aplikasi OVO di smartphone Anda',
            'Pilih menu Scan',
            'Scan QR code yang ditampilkan',
            'Verifikasi jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Masukkan PIN OVO Anda',
            'Pembayaran selesai'
        ]
    ],
    'dana' => [
        'title' => 'Pembayaran via DANA',
        'steps' => [
            'Buka aplikasi DANA di smartphone Anda',
            'Pilih menu Scan',
            'Scan QR code yang ditampilkan',
            'Verifikasi jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Masukkan PIN DANA Anda',
            'Pembayaran selesai'
        ]
    ],
    'bca' => [
        'title' => 'Transfer Bank BCA',
        'steps' => [
            'Login ke m-BCA atau internet banking BCA',
            'Pilih menu Transfer > Virtual Account',
            'Masukkan nomor Virtual Account: ' . $payment_code,
            'Masukkan jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Ikuti instruksi untuk menyelesaikan pembayaran',
            'Simpan bukti pembayaran'
        ]
    ],
    'mandiri' => [
        'title' => 'Transfer Bank Mandiri',
        'steps' => [
            'Login ke Livin\' by Mandiri atau internet banking Mandiri',
            'Pilih menu Pembayaran > Virtual Account',
            'Masukkan nomor Virtual Account: ' . $payment_code,
            'Masukkan jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Ikuti instruksi untuk menyelesaikan pembayaran',
            'Simpan bukti pembayaran'
        ]
    ],
    'bni' => [
        'title' => 'Transfer Bank BNI',
        'steps' => [
            'Login ke BNI Mobile Banking atau internet banking BNI',
            'Pilih menu Transfer > Virtual Account',
            'Masukkan nomor Virtual Account: ' . $payment_code,
            'Masukkan jumlah pembayaran: Rp ' . number_format($transaksi['totalharga'], 0, ',', '.'),
            'Ikuti instruksi untuk menyelesaikan pembayaran',
            'Simpan bukti pembayaran'
        ]
    ]
];

// Ambil informasi metode pembayaran yang dipilih
$payment_info = isset($payment_instructions[$payment_method]) ? $payment_instructions[$payment_method] : null;

// Jika metode tidak ditemukan, redirect
if (!$payment_info) {
    header('Location: dashboard_user.php');
    exit;
}

// Tentukan waktu kedaluwarsa pembayaran (24 jam dari booking)
$expire_time = strtotime($transaksi['tgl_booking'] . ' + 24 hours');
$current_time = time();
$time_left = $expire_time - $current_time;

// Format durasi waktu tersisa
function formatTimeLeft($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    return sprintf("%02d:%02d", $hours, $minutes);
}
?>
<!DOCTYPE html>
<html data-theme="light" data-payment="<?= $payment_method ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruksi Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --card-border: #e3e6f0;
            --text-color: #ffffff; /* Changed to white */
            --bg-color: #f8f9fc;
        }
        
        [data-theme="dark"] {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #333;
            --dark-color: #f8f9fc;
            --card-border: #4b4e54;
            --text-color: #ffffff; /* Changed to white */
            --bg-color: #222;
        }
        
        body {
            background-color: var(--bg-color);
            color: #ffffff !important; /* Forcing white text */
        }
        
        /* Make all text white for all payment methods */
        p, h1, h2, h3, h4, h5, h6, li, span, div, a, button, label, 
        .text-muted, .card-body, .card-title, .card-text, strong {
            color: #ffffff !important;
        }
        
        /* Maintain contrast for specific elements */
        .payment-code {
            color: #000000 !important;
            background: #f0f0f0;
        }
        
        .qr-code {
            background-color: #fff;
        }
        
        /* Make alerts more visible with semi-transparent backgrounds */
        .alert {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        
        .alert-info {
            background-color: rgba(54, 185, 204, 0.15) !important;
            border-color: rgba(54, 185, 204, 0.3) !important;
        }
        
        .alert-warning {
            background-color: rgba(246, 194, 62, 0.15) !important;
            border-color: rgba(246, 194, 62, 0.3) !important;
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.15) !important;
            border-color: rgba(231, 74, 59, 0.3) !important;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.15) !important;
            border-color: rgba(28, 200, 138, 0.3) !important;
        }
        
        /* Style for the timer - keep it visible */
        .timer {
            color: var(--danger-color) !important;
            font-weight: bold;
        }
        
        /* Make total amount white */
        .total-amount {
            color: #ffffff !important;
            font-weight: bold;
        }
        
        /* Step numbers should stay white */
        .step-list li::before {
            color: white !important;
        }
        
        /* Button styling */
        .btn-outline-secondary {
            color: #ffffff !important;
            border-color: #ffffff !important;
        }
        
        /* Keep other styles unchanged */
        .card {
            border-color: var(--card-border);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .payment-logo {
            max-height: 50px;
            max-width: 100px;
            object-fit: contain;
        }
        
        .payment-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-code {
            font-family: monospace;
            font-size: 1.2rem;
            background: #f0f0f0;
            padding: 8px 15px;
            border-radius: 4px;
            border: 1px dashed #ccc;
            letter-spacing: 2px;
        }
        
        .timer {
            font-family: monospace;
            font-weight: bold;
            color: var(--danger-color);
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            background-color: #fff;
            margin: 0 auto;
            position: relative;
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .step-list {
            counter-reset: step-counter;
            list-style-type: none;
            padding-left: 0;
        }
        
        .step-list li {
            position: relative;
            padding-left: 40px;
            margin-bottom: 15px;
            counter-increment: step-counter;
        }
        
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .total-amount {
            font-size: 1.8rem;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .card-bank-info {
            border-left: 5px solid var(--info-color);
        }
        
        .card-timer {
            border-left: 5px solid var(--warning-color);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="payment-header">
                                <?php if (isset($payment_logos[$payment_method])): ?>
                                    <img src="<?= $payment_logos[$payment_method] ?>" alt="<?= ucfirst($payment_method) ?>" class="payment-logo">
                                <?php endif; ?>
                                <div>
                                    <h2 class="mb-0"><?= $payment_info['title'] ?></h2>
                                    <p class="text-muted mb-0">Invoice #<?= $invoice_id ?></p>
                                </div>
                            </div>
                            <a href="riwayat_user.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card card-bank-info mb-3">
                                    <div class="card-body">
                                        <h5 class="mb-3"><i class="bi bi-credit-card me-2"></i>Informasi Pembayaran</h5>
                                        <p class="mb-1"><strong>Kamar:</strong> <?= htmlspecialchars($transaksi['nama_kamar']) ?></p>
                                        <p class="mb-1"><strong>Check-in:</strong> <?= date('d F Y', strtotime($transaksi['tgl_checkin'])) ?></p>
                                        <p class="mb-1"><strong>Check-out:</strong> <?= date('d F Y', strtotime($transaksi['tgl_checkout'])) ?></p>
                                        <p class="mb-1"><strong>Durasi:</strong> <?= floor((strtotime($transaksi['tgl_checkout']) - strtotime($transaksi['tgl_checkin'])) / (60 * 60 * 24)) ?> malam</p>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Total Pembayaran:</h6>
                                            <span class="total-amount">Rp <?= number_format($transaksi['totalharga'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card card-timer">
                                    <div class="card-body">
                                        <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Batas Waktu Pembayaran</h5>
                                        <p>Selesaikan pembayaran sebelum:</p>
                                        <h4><?= date('d F Y - H:i', $expire_time) ?> WIB</h4>
                                        
                                        <?php if ($time_left > 0): ?>
                                            <div class="alert alert-warning mt-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span><i class="bi bi-exclamation-triangle me-2"></i>Waktu tersisa:</span>
                                                    <span class="timer" id="countdown"><?= formatTimeLeft($time_left) ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">
                                                <i class="bi bi-exclamation-circle me-2"></i>Batas waktu pembayaran telah berakhir
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Cara Pembayaran</h5>
                                        
                                        <?php if (in_array($payment_method, ['gopay', 'ovo', 'dana'])): ?>
                                            <div class="text-center mb-4">
                                                <div class="qr-code">
                                                    <img src="https://chart.googleapis.com/chart?cht=qr&chl=<?= $payment_code ?>&chs=200x200&choe=UTF-8&chld=L|2" alt="QR Code">
                                                </div>
                                                <p class="text-muted mt-2">Scan QR Code ini dengan aplikasi <?= ucfirst($payment_method) ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Kode Pembayaran:</h6>
                                                <div class="d-flex align-items-center">
                                                    <span class="payment-code me-2"><?= $payment_code ?></span>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="copyPaymentCode('<?= $payment_code ?>')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        <h6>Langkah-langkah Pembayaran:</h6>
                                        <ul class="step-list mt-3">
                                            <?php foreach ($payment_info['steps'] as $step): ?>
                                                <li><?= $step ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <div class="alert alert-info mt-4">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Setelah pembayaran berhasil, status transaksi akan diperbarui oleh sistem.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button id="confirmManualBtn" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Konfirmasi Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Untuk copy paste kode pembayaran
        function copyPaymentCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Kode Pembayaran Disalin!',
                    text: 'Kode pembayaran telah disalin ke clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
        
        // Countdown timer
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            let timeLeft = <?= max(0, $time_left) ?>;
            
            const countdownInterval = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    countdownElement.innerHTML = "00:00";
                    return;
                }
                
                timeLeft--;
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.innerHTML = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }
        
        // Konfirmasi pembayaran manual
        document.getElementById('confirmManualBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Konfirmasi Pembayaran',
                html: `
                    <div class="text-start mb-4">
                        <p>Dengan mengklik tombol "Saya Sudah Bayar", Anda menyatakan bahwa:</p>
                        <ul class="text-start">
                            <li>Anda telah melakukan pembayaran sebesar <strong>Rp <?= number_format($transaksi['totalharga'], 0, ',', '.') ?></strong></li>
                            <li>Pembayaran dilakukan menggunakan <strong><?= ucfirst($payment_method) ?></strong></li>
                            <li>Kode pembayaran: <strong><?= $payment_code ?></strong></li>
                        </ul>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Sistem akan mengecek pembayaran Anda dalam waktu 5-10 menit.
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check2-circle me-2"></i>Saya Sudah Bayar',
                cancelButtonText: '<i class="bi bi-x me-2"></i>Belum',
                confirmButtonColor: '#4e73df',
                reverseButtons: true,
                focusConfirm: false,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan animasi loading
                    Swal.fire({
                        title: 'Memproses Konfirmasi',
                        text: 'Mohon tunggu sebentar...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Simulasi proses pembayaran selesai
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil!',
                            html: `
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4>Terima Kasih!</h4>
                                    <p>Pembayaran Anda telah berhasil dikonfirmasi.</p>
                                    <div class="alert alert-success">
                                        Transaksi dengan ID: <?= $invoice_id ?> telah selesai
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Lihat Riwayat Transaksi',
                            confirmButtonColor: '#4e73df',
                            allowOutsideClick: false                        }).then(() => {
                            window.location.href = 'landing_page.php?section=riwayat';
                        });
                    });
                }
            });
        });
        
        // Theme handling
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Tampilkan welcome message dengan SweetAlert
            Swal.fire({
                title: 'Instruksi Pembayaran',
                html: `
                    <div class="text-start">
                        <p>Silakan selesaikan pembayaran Anda sebelum batas waktu berakhir:</p>
                        <ul class="text-start">
                            <li>Total pembayaran: <strong>Rp <?= number_format($transaksi['totalharga'], 0, ',', '.') ?></strong></li>
                            <li>Metode: <strong><?= ucfirst($payment_method) ?></strong></li>
                            <li>Batas waktu: <strong><?= date('d F Y - H:i', $expire_time) ?> WIB</strong></li>
                        </ul>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#4e73df'
            });
        });
    </script>
</body>
</html>
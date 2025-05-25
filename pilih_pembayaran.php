<?php
require 'config.php';

// Cek autentikasi user
// Cek autentikasi user
if (!isset($_SESSION['lv'])) {
    header('Location: login.php');
    exit;
}

// Validasi parameter invoice
if (!isset($_GET['invoice'])) {
    header('Location: dashboard_user.php');
    exit;
}

// Ambil data transaksi
$stmt = $conn->prepare("
    SELECT t.*, k.nama AS nama_kamar, u.nama AS nama_user 
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    JOIN user u ON t.id_user = u.id_user
    WHERE t.id_transaksi = ? AND t.id_user = ?
");
$stmt->execute([$_GET['invoice'], $_SESSION['id_user']]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    header('Location: dashboard_user.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Serenity Haven</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #2d3748;
            --heading-color: #1a365d;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --card-hover: #f8fafc;
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --text-color: #ffffff;
            --heading-color: #ffffff;
            --border-color: #334155;
            --card-bg: #1e293b;
            --card-hover: #2d3748;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .payment-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 20px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .payment-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .payment-card.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }

        .payment-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            padding: 8px;
            background: white;
        }

        .bank-logo {
            background: #f8f9fa;
        }

        .order-summary {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1 class="mb-4">Pembayaran Reservasi</h1>
        
        <!-- Ringkasan Pesanan -->
        <div class="order-summary">
            <h3 class="mb-3">Detail Reservasi</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Kamar:</strong> <?= htmlspecialchars($transaksi['nama_kamar']) ?></p>
                    <p><strong>Check-in:</strong> <?= date('d F Y', strtotime($transaksi['tgl_checkin'])) ?></p>
                    <p><strong>Check-out:</strong> <?= date('d F Y', strtotime($transaksi['tgl_checkout'])) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Durasi:</strong> 
                        <?= $durasi = (strtotime($transaksi['tgl_checkout']) - strtotime($transaksi['tgl_checkin'])) / (60 * 60 * 24) ?> Hari
                    </p>
                    <p><strong>Total Pembayaran:</strong></p>
                    <h3 class="text-primary">Rp <?= number_format($transaksi['totalharga'], 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>

        <!-- Form Pembayaran -->
        <form id="paymentForm" action="proses_pembayaran.php" method="POST">
            <input type="hidden" name="invoice_id" value="<?= $transaksi['id_transaksi'] ?>">
            
            <h3 class="mb-4">Pilih Metode Pembayaran</h3>
            
            <div class="payment-grid">
                <!-- E-Wallet -->
                <div class="payment-card" onclick="selectPayment('gopay')">
                    <img src="assets/img/payment/gopay.png" alt="GoPay" class="payment-logo">
                    <div>
                        <h5>GoPay</h5>
                        <small class="text-muted">Bayar menggunakan GoPay</small>
                    </div>
                    <input type="radio" name="payment_method" value="gopay" class="d-none" required>
                </div>

                <div class="payment-card" onclick="selectPayment('ovo')">
                    <img src="assets/img/payment/ovo.png" alt="OVO" class="payment-logo">
                    <div>
                        <h5>OVO</h5>
                        <small class="text-muted">Bayar menggunakan OVO</small>
                    </div>
                    <input type="radio" name="payment_method" value="ovo" class="d-none">
                </div>

                <div class="payment-card" onclick="selectPayment('dana')">
                    <img src="assets/img/payment/dana.png" alt="DANA" class="payment-logo">
                    <div>
                        <h5>DANA</h5>
                        <small class="text-muted">Bayar menggunakan DANA</small>
                    </div>
                    <input type="radio" name="payment_method" value="dana" class="d-none">
                </div>

                <!-- Bank Transfer -->
                <div class="payment-card" onclick="selectPayment('bca')">
                    <img src="assets/img/payment/bca.png" alt="BCA" class="payment-logo bank-logo">
                    <div>
                        <h5>BCA Virtual Account</h5>
                        <small class="text-muted">Bank Central Asia</small>
                    </div>
                    <input type="radio" name="payment_method" value="bca" class="d-none">
                </div>

                <div class="payment-card" onclick="selectPayment('mandiri')">
                    <img src="assets/img/payment/mandiri.png" alt="Mandiri" class="payment-logo bank-logo">
                    <div>
                        <h5>Mandiri Virtual Account</h5>
                        <small class="text-muted">Bank Mandiri</small>
                    </div>
                    <input type="radio" name="payment_method" value="mandiri" class="d-none">
                </div>

                <div class="payment-card" onclick="selectPayment('bni')">
                    <img src="assets/img/payment/bni.png" alt="BNI" class="payment-logo bank-logo">
                    <div>
                        <h5>BNI Virtual Account</h5>
                        <small class="text-muted">Bank Negara Indonesia</small>
                    </div>
                    <input type="radio" name="payment_method" value="bni" class="d-none">
                </div>
            </div>

            <div class="mt-5 text-center">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                </button>
                <a href="dashboard_user.php" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>

    <script>
        function selectPayment(method) {
            // Reset semua pilihan
            document.querySelectorAll('.payment-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Tandai yang dipilih
            const selectedCard = document.querySelector(`[value="${method}"]`).closest('.payment-card');
            selectedCard.classList.add('selected');
            document.querySelector(`[value="${method}"]`).checked = true;
        }

        // Handle tema
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>
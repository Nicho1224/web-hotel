<?php
require 'config.php';

// Check if this is a modal display request
$isModalDisplay = isset($_GET['display']) && $_GET['display'] === 'modal';

// Skip authentication check for modal display
if (!$isModalDisplay) {
    if (!isset($_SESSION['lv']) || $_SESSION['lv'] !== 'user') {
        header('Location: login.php');
        exit;
    }
}

if (!isset($_GET['id'])) {
    if ($isModalDisplay) {
        echo '<div class="alert alert-danger">ID transaksi tidak ditemukan</div>';
        exit;
    } else {
        header('Location: ?page=dashboard_user');
        exit;
    }
}

// Get transaction details - no user check for modal display
if ($isModalDisplay) {
    $txQuery = $conn->prepare("
        SELECT 
            t.*, 
            k.nama AS nama_kamar,
            k.harga AS harga_kamar,
            u.nama AS nama_tamu,
            u.email AS email_tamu,
            u.alamat AS alamat_tamu
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar
        JOIN user u ON t.id_user = u.id_user
        WHERE t.id_transaksi = ?
    ");
    $txQuery->execute([$_GET['id']]);
} else {
    $txQuery = $conn->prepare("
        SELECT 
            t.*, 
            k.nama AS nama_kamar,
            k.harga AS harga_kamar,
            u.nama AS nama_tamu,
            u.email AS email_tamu,
            u.alamat AS alamat_tamu
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar
        JOIN user u ON t.id_user = u.id_user
        WHERE t.id_transaksi = ? AND t.id_user = ?
    ");
    $txQuery->execute([$_GET['id'], $_SESSION['id_user']]);
}

$invoice = $txQuery->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    if ($isModalDisplay) {
        echo '<div class="alert alert-danger">Detail transaksi tidak ditemukan</div>';
        exit;
    } else {
        header('Location: ?page=dashboard_user');
        exit;
    }
}

// Calculate stay duration
$checkin = new DateTime($invoice['tgl_checkin']);
$checkout = new DateTime($invoice['tgl_checkout']);
$duration = $checkin->diff($checkout)->days;

// Helper function for status badge
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'bg-warning';
        case 'confirmed':
        case 'siap digunakan':
        case 'berhasil':
            return 'bg-success';
        case 'cancelled':
        case 'dibatalkan':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// For modal display, only show the invoice content without the full HTML structure
if ($isModalDisplay) {
    // Simple CSS for modal
    echo '<style>
        .modal-invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-badge {
            font-size: 0.85rem;
            padding: 0.5em 0.85em;
        }
    </style>';
    
    // Invoice Header
    echo '<div class="modal-invoice-header">
        <div>
            <h5 class="mb-2">Invoice #' . str_pad($invoice['id_transaksi'], 6, '0', STR_PAD_LEFT) . '</h5>
            <p class="text-muted mb-1">Tanggal: ' . date('d F Y', strtotime($invoice['tgl_booking'])) . '</p>
        </div>
        <div class="text-end">
            <span class="badge ' . getStatusBadgeClass($invoice['status']) . ' modal-badge">
                ' . ucfirst($invoice['status']) . '
            </span>
        </div>
    </div>';
    
    // Guest Details
    echo '<div class="row mb-4">
        <div class="col-md-6">
            <h6 class="mb-2">Detail Tamu:</h6>
            <div class="mb-1"><strong>' . htmlspecialchars($invoice['nama_tamu']) . '</strong></div>
            <div class="text-muted mb-1">' . htmlspecialchars($invoice['email_tamu']) . '</div>
            <div class="text-muted">' . htmlspecialchars($invoice['alamat_tamu']) . '</div>
        </div>
        <div class="col-md-6">
            <h6 class="mb-2">Detail Kamar:</h6>
            <div class="mb-1"><strong>' . htmlspecialchars($invoice['nama_kamar']) . '</strong></div>
            <div class="text-muted mb-1">Check-in: ' . date('d/m/Y', strtotime($invoice['tgl_checkin'])) . '</div>
            <div class="text-muted">Check-out: ' . date('d/m/Y', strtotime($invoice['tgl_checkout'])) . '</div>
        </div>
    </div>';
    
    // Booking Details Table
    echo '<div class="table-responsive mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-center">Durasi</th>
                    <th class="text-end">Harga/Malam</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>' . htmlspecialchars($invoice['nama_kamar']) . '</strong></td>
                    <td class="text-center">' . $duration . ' malam</td>
                    <td class="text-end">Rp ' . number_format($invoice['harga_kamar'], 0, ',', '.') . '</td>
                    <td class="text-end">Rp ' . number_format($invoice['totalharga'], 0, ',', '.') . '</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>Rp ' . number_format($invoice['totalharga'], 0, ',', '.') . '</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>';
    
    // Payment Method if available
    if (!empty($invoice['metode_pembayaran'])) {
        echo '<div class="mb-3">
            <strong>Metode Pembayaran:</strong> ' . strtoupper($invoice['metode_pembayaran']) . '
        </div>';
    }
    

    
    exit; // End processing for modal display
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= str_pad($invoice['id_transaksi'], 6, '0', STR_PAD_LEFT) ?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --invoice-bg: #ffffff;
            --text-color: #2d3748;
            --heading-color: #1a365d;
            --border-color: #e2e8f0;
            --table-header: #f8fafc;
            --status-pending: #f59e0b;
            --status-confirmed: #10b981;
            --status-cancelled: #ef4444;
        }

        [data-theme="dark"] {
            --invoice-bg: #1e293b;
            --text-color: #ffffff; /* Changed to white */
            --heading-color: #ffffff; /* Changed to white */
            --border-color: #334155;
            --table-header: #0f172a;
            --status-pending: #fbbf24;
            --status-confirmed: #34d399;
            --status-cancelled: #f87171;
        }

        .invoice-container {
            background: var(--invoice-bg);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 8px;
            color: var(--text-color);
        }

        .table thead {
            background: var(--table-header);
            color: var(--text-color);
        }

        .table tbody td {
            color: var(--text-color);
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--heading-color) !important;
        }

        .text-muted {
            color: var(--text-color) !important;
            opacity: 0.8;
        }

        /* Make text white in dark mode */
        [data-theme="dark"] .invoice-container {
            color: #ffffff;
        }

        [data-theme="dark"] .text-muted {
            color: #ffffff !important;
            opacity: 0.8;
        }

        [data-theme="dark"] .table {
            color: #ffffff;
        }

        [data-theme="dark"] .text-primary {
            color: #ffffff !important;
        }

        [data-theme="dark"] strong {
            color: #ffffff;
        }

        /* Fix badge colors */
        .badge.bg-warning {
            background: var(--status-pending) !important;
            color: #ffffff;
        }

        .badge.bg-success {
            background: var(--status-confirmed) !important;
            color: #ffffff;
        }

        .badge.bg-danger {
            background: var(--status-cancelled) !important;
            color: #ffffff;
        }

        /* Print styles */
        @media print {
            body {
                color: #000000 !important;
            }
            .invoice-container {
                color: #000000 !important;
            }
            .text-muted {
                color: #666666 !important;
            }
        }

        /* Update table styles */
        .table thead th {
            color: var(--text-color);
            background-color: var(--table-header);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .table thead th {
            color: #ffffff;
        }

        [data-theme="dark"] .table {
            border-color: var(--border-color);
        }

        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            border-color: var(--border-color);
        }

        /* Ensure table footer is also white in dark mode */
        [data-theme="dark"] .table tfoot td {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="invoice-container shadow">
            <!-- Invoice Header -->
            <div class="row mb-4">
                <div class="col-6">
                    <h1 class="fs-2 text-primary">Invoice</h1>
                    <p class="text-muted mb-1">No. #<?= str_pad($invoice['id_transaksi'], 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="text-muted mb-1">Tanggal: <?= date('d F Y') ?></p>
                </div>
                <div class="col-6 text-end">
                    <h2 class="fs-3 mb-1">Serenity Haven Hotel</h2>
                    <p class="text-muted mb-1">123 Luxury Avenue</p>
                    <p class="text-muted mb-1">Jakarta, Indonesia 12345</p>
                    <p class="text-muted mb-1">Tel: (021) 1234-5678</p>
                </div>
            </div>

            <!-- Guest Details -->
            <div class="row mb-4">
                <div class="col-6">
                    <h5 class="mb-2">Detail Tamu:</h5>
                    <div class="mb-1">
                        <strong><?= htmlspecialchars($invoice['nama_tamu']) ?></strong>
                    </div>
                    <div class="text-muted mb-1"><?= htmlspecialchars($invoice['email_tamu']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($invoice['alamat_tamu']) ?></div>
                </div>
                <div class="col-6 text-end">
                    <h5 class="mb-2">Status Pemesanan:</h5>
                    <span class="badge <?= getStatusBadgeClass($invoice['status']) ?>">
                        <?= ucfirst($invoice['status']) ?>
                    </span>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="text-center">Check-in</th>
                            <th class="text-center">Check-out</th>
                            <th class="text-center">Durasi</th>
                            <th class="text-end">Harga/Malam</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($invoice['nama_kamar']) ?></strong>
                            </td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($invoice['tgl_checkin'])) ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($invoice['tgl_checkout'])) ?></td>
                            <td class="text-center"><?= $duration ?> malam</td>
                            <td class="text-end">Rp <?= number_format($invoice['harga_kamar'], 0, ',', '.') ?></td>
                            <td class="text-end">Rp <?= number_format($invoice['totalharga'], 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong>Rp <?= number_format($invoice['totalharga'], 0, ',', '.') ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>


            <!-- Action Buttons -->
            <div class="row mt-4 no-print">
    <div class="col-12 text-center">
        <?php if ($invoice['status'] === 'pending'): ?>
            <a href="?page=pilih_pembayaran&invoice=<?= $invoice['id_transaksi'] ?>" class="btn btn-primary">
                <i class="bi bi-credit-card me-2"></i>Lanjutkan ke Pembayaran
            </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-info ms-2">
            <i class="bi bi-printer me-2"></i>Cetak Invoice
        </button>
        <a href="?page=riwayat_user" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>
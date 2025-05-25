<?php
require 'config.php';

// Validasi session dan role admin

if (!isset($_SESSION['logged_in']) || $_SESSION['lv'] !== 'admin') {
    header('Location: login.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Validasi parameter bulan
$selectedMonth = isset($_GET['bulan']) && preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', $_GET['bulan']) 
    ? $_GET['bulan'] 
    : date('Y-m');

// Hitung periode
$startDate = date('Y-m-01', strtotime($selectedMonth));
$endDate = date('Y-m-t', strtotime($selectedMonth));

// Fungsi untuk mendapatkan data keuangan dengan error handling
function getFinancialData($conn, $start, $end) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(SUM(totalharga), 0) AS total,
                COALESCE(COUNT(DISTINCT nik), 0) AS total_customers,
                COALESCE(COUNT(*), 0) AS total_transactions
            FROM transaksi
            WHERE 
                tgl_booking BETWEEN ? AND ?
                AND status = 'berhasil'
        ");
        $stmt->execute([$start, $end]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Hitung rata-rata dengan safe division
        $data['average'] = ($data['total_transactions'] > 0)
            ? $data['total'] / $data['total_transactions']
            : 0;

        return $data;
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'total' => 0,
            'total_customers' => 0,
            'total_transactions' => 0,
            'average' => 0
        ];
    }
}

// Fungsi untuk data historis
function getHistoricalData($conn) {
    try {
        $stmt = $conn->query("
            SELECT 
                DATE_FORMAT(tgl_booking, '%Y-%m') AS bulan,
                COALESCE(SUM(totalharga), 0) AS pendapatan,
                COALESCE(COUNT(DISTINCT nik), 0) AS pelanggan
            FROM transaksi
            WHERE status = 'berhasil'
            GROUP BY bulan
            ORDER BY bulan DESC
            LIMIT 12
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk data pelanggan
function getCustomerData($conn) {
    try {
        $stmt = $conn->query("
            SELECT
                t.nik,
                tamu.nama,
                COALESCE(COUNT(*), 0) AS total_transaksi,
                COALESCE(SUM(t.totalharga), 0) AS total_pengeluaran,
                MIN(t.tgl_booking) AS pertama_transaksi,
                MAX(t.tgl_booking) AS terakhir_transaksi
            FROM transaksi t
            JOIN tamu ON t.nik = tamu.nik
            GROUP BY t.nik
            ORDER BY total_pengeluaran DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk transaksi hari ini
function getTodayTransactions($conn) {
    try {
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            SELECT
                t.id_transaksi,
                tamu.nama,
                kamar.nama AS nama_kamar,
                t.tgl_booking,
                t.totalharga,
                t.status
            FROM transaksi t
            JOIN tamu ON t.nik = tamu.nik
            JOIN kamar ON t.id_kamar = kamar.id_kamar
            WHERE DATE(t.tgl_booking) = ?
            ORDER BY t.tgl_booking DESC
        ");
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Eksekusi fungsi
$mainData = getFinancialData($conn, $startDate, $endDate);
$historicalData = getHistoricalData($conn);
$customerData = getCustomerData($conn);
$todayData = getTodayTransactions($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .no-data {
            opacity: 0.6;
            filter: grayscale(1);
        }
    </style>
    <style>
        /* Light theme (default) */
        :root {
            --text-color: #212529;
            --bg-color: #ffffff;
            --card-bg: #ffffff;
            --card-border: #dee2e6;
            --heading-color: #212529;
            --table-bg: #ffffff;
            --table-border: #dee2e6;
            --table-hover: rgba(0,0,0,0.075);
            --text-muted: #6c757d;
        }

        /* Dark theme */
        [data-theme="dark"] {
            --text-color: #e2e8f0;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --heading-color: #ffffff;
            --table-bg: #1e293b;
            --table-border: #334155;
            --table-hover: rgba(255,255,255,0.075);
            --text-muted: #94a3b8;
        }

        /* Apply theme colors */
        body {
            background-color: var(--bg-color) !important;
            color: var(--text-color);
        }

        .card {
            background-color: var(--card-bg);
            border-color: var(--card-border);
        }

        .card-title,
        .display-6 {
            color: var(--heading-color);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .table {
            color: var(--text-color);
        }

        .table-hover tbody tr:hover {
            background-color: var(--table-hover);
        }

        .table thead.table-light {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--table-border);
        }

        /* Keep stat cards colorful but adjust for dark mode */
        .stat-card.no-data {
            background-color: var(--card-bg) !important;
            color: var(--text-muted);
        }

        [data-theme="dark"] .stat-card:not(.no-data) {
            opacity: 0.9;
        }

        [data-theme="dark"] .stat-card:hover:not(.no-data) {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-6 text-primary">
                    <i class="bi bi-graph-up"></i> Laporan Keuangan
                </h1>
                <p class="text-muted">Periode: <?= date('F Y', strtotime($selectedMonth)) ?></p>
            </div>
        </div>

        <!-- Statistik Utama -->
        <div class="row g-4 mb-4">
            <!-- Pendapatan -->
            <div class="col-md-3">
                <div class="card h-100 stat-card <?= $mainData['total'] == 0 ? 'no-data bg-light' : 'bg-success text-white' ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cash-coin"></i> Total Pendapatan
                        </h5>
                        <div class="display-4 fw-bold">
                            Rp<?= number_format($mainData['total'], 0, ',', '.') ?>
                        </div>
                        <?php if($mainData['total'] > 0): ?>
                            <small class="opacity-75">
                                <?= $mainData['total_transactions'] ?> Transaksi
                            </small>
                        <?php else: ?>
                            <small class="opacity-75">Tidak ada transaksi</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Rata-rata Transaksi -->
            <div class="col-md-3">
                <div class="card h-100 stat-card <?= $mainData['average'] == 0 ? 'no-data bg-light' : 'bg-warning' ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-calculator"></i> Rata-rata/Transaksi
                        </h5>
                        <div class="display-4 fw-bold">
                            <?php if($mainData['average'] > 0): ?>
                                Rp<?= number_format($mainData['average'], 0, ',', '.') ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </div>
                        <small class="opacity-75">Berdasarkan <?= $mainData['total_transactions'] ?> transaksi</small>
                    </div>
                </div>
            </div>

            <!-- Bagian statistik lainnya tetap sama -->
        </div>

        <!-- Tabel Pelanggan -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="bi bi-people"></i> Data Pelanggan
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Total Transaksi</th>
                                <th>Total Pengeluaran</th>
                                <th>Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($customerData as $cust): 
                                $avg = $cust['total_transaksi'] > 0 
                                    ? $cust['total_pengeluaran'] / $cust['total_transaksi'] 
                                    : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($cust['nama']) ?></td>
                                    <td><?= $cust['total_transaksi'] ?></td>
                                    <td>Rp<?= number_format($cust['total_pengeluaran'], 0, ',', '.') ?></td>
                                    <td>Rp<?= number_format($avg, 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add theme handling
    document.addEventListener('DOMContentLoaded', function() {
        // Get saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    });
    </script>
</body>
</html>
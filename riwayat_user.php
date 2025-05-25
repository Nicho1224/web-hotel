<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php?redirect=riwayat_user.php');
    exit;
}

$userId = $_SESSION['id_user'];
$userStmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get all bookings for this user
$stmt = $conn->prepare("
    SELECT t.*, k.nama as nama_kamar, k.jenis as tipe_kamar, k.bed, k.harga
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_user = ?
    ORDER BY t.tgl_booking DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter by status if requested
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
if ($statusFilter) {
    $bookings = array_filter($bookings, function($b) use ($statusFilter) {
        return $b['status'] === $statusFilter;
    });
}

// Stats
$pendingCount = $activeCount = $completedCount = $canceledCount = 0;
foreach ($bookings as $b) {
    switch ($b['status']) {
        case 'pending': $pendingCount++; break;
        case 'siap digunakan': $activeCount++; break;
        case 'berhasil': $completedCount++; break;
        case 'dibatalkan': $canceledCount++; break;
    }
}

function statusBadge($status) {
    $map = [
        'pending' => ['warning', 'Menunggu Pembayaran', 'bi-hourglass-split'],
        'siap digunakan' => ['primary', 'Aktif', 'bi-calendar-check'],
        'berhasil' => ['success', 'Selesai', 'bi-check-circle'],
        'dibatalkan' => ['danger', 'Dibatalkan', 'bi-x-circle'],
    ];
    $d = $map[$status] ?? ['secondary', ucfirst($status), 'bi-info-circle'];
    return '<span class="badge bg-' . $d[0] . '"><i class="bi ' . $d[2] . ' me-1"></i>' . $d[1] . '</span>';
}

?><!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking | Serenity Haven Hotel</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body { background: var(--bg-color); color: var(--text-color); font-family: 'Montserrat', sans-serif; }
        .section-title h2 { color: var(--heading-color); }
        .booking-card {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: 0 4px 16px var(--card-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            transition: box-shadow .2s, transform .2s;
        }
        .booking-card:hover { box-shadow: 0 8px 32px var(--card-shadow); transform: translateY(-4px); }
        .booking-card .badge { font-size: 0.95rem; }
        .booking-card .booking-status { position: absolute; top: 18px; right: 18px; }
        .booking-card .room-img { width: 100%; height: 160px; object-fit: cover; border-radius: 12px 12px 0 0; }
        .booking-card .card-body { position: relative; }
        .booking-card .booking-actions { margin-top: 1.2rem; }
        .status-filter { margin-bottom: 2rem; }
        .status-pill {
            display: inline-block; padding: 0.5rem 1.2rem; border-radius: 20px; margin-right: 10px;
            cursor: pointer; background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-color); font-weight: 500; transition: all 0.3s;
        }
        .status-pill.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .status-pill:hover:not(.active) { border-color: var(--primary-color); background: rgba(43,130,195,0.1); }
        .empty-state { text-align: center; padding: 4rem 0; }
        .empty-state .bi { font-size: 3rem; color: var(--border-color); }
        @media (max-width: 768px) {
            .booking-card .room-img { height: 120px; }
        }
    </style>
</head>
<body class="index-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="landing_page.php" class="logo d-flex align-items-center">
                <h1 class="sitename">Serenity Haven</h1>
            </a>
            <nav id="navmenu" class="navmenu mx-auto">
                <ul>
                    <li><a href="landing_page.php?section=home">Home</a></li>
                    <li><a href="landing_page.php?section=rooms">Rooms</a></li>
                    <li><a href="riwayat_user.php" class="active">Riwayat</a></li>
                    <li><a href="landing_page.php?section=facilities">Facilities</a></li>
                    <li><a href="landing_page.php?section=about">About</a></li>
                    <li><a href="landing_page.php?section=contact">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons d-flex align-items-center gap-3">
                <div class="user-menu-container">
                    <button type="button" class="btn btn-outline-primary" id="userMenuButton">
                        <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($user['nama']) ?>
                        <i class="bi bi-chevron-down ms-1 small"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <main class="main py-5">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2 class="mb-3">Riwayat Booking Anda</h2>
                <p class="text-muted">Lihat dan kelola semua riwayat pemesanan Anda</p>
            </div>
            <!-- Status Filter -->
            <div class="status-filter text-center mb-4">
                <span class="status-pill<?= $statusFilter=='' ? ' active' : '' ?>" onclick="window.location='riwayat_user.php'">
                    <i class="bi bi-grid-3x3"></i> Semua
                </span>
                <span class="status-pill<?= $statusFilter=='pending' ? ' active' : '' ?>" onclick="window.location='?status=pending'">
                    <i class="bi bi-hourglass-split"></i> Menunggu
                </span>
                <span class="status-pill<?= $statusFilter=='siap digunakan' ? ' active' : '' ?>" onclick="window.location='?status=siap digunakan'">
                    <i class="bi bi-calendar-check"></i> Aktif
                </span>
                <span class="status-pill<?= $statusFilter=='berhasil' ? ' active' : '' ?>" onclick="window.location='?status=berhasil'">
                    <i class="bi bi-check-circle"></i> Selesai
                </span>
                <span class="status-pill<?= $statusFilter=='dibatalkan' ? ' active' : '' ?>" onclick="window.location='?status=dibatalkan'">
                    <i class="bi bi-x-circle"></i> Dibatalkan
                </span>
            </div>
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-start border-warning border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Menunggu</h6>
                                    <h4 class="mb-0"><?= $pendingCount ?></h4>
                                </div>
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                    <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-start border-primary border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Aktif</h6>
                                    <h4 class="mb-0"><?= $activeCount ?></h4>
                                </div>
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="bi bi-calendar-check fs-4 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-start border-success border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Selesai</h6>
                                    <h4 class="mb-0"><?= $completedCount ?></h4>
                                </div>
                                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                    <i class="bi bi-check-circle fs-4 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-start border-danger border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Dibatalkan</h6>
                                    <h4 class="mb-0"><?= $canceledCount ?></h4>
                                </div>
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                    <i class="bi bi-x-circle fs-4 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Booking Cards Grid -->
            <div class="row g-4">
                <?php if (empty($bookings)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h4 class="mt-3">Belum Ada Pemesanan</h4>
                            <p class="text-muted mb-4">Anda belum memiliki riwayat pemesanan saat ini.</p>
                            <a href="user_pemesanan.php" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-plus-circle me-2"></i>Pesan Kamar Sekarang
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="booking-card position-relative">
                            <div class="booking-status">
                                <?= statusBadge($b['status']) ?>
                            </div>
                            <img src="<?= ($b['tipe_kamar'] == 'Deluxe') ? 'hotel1.jpg' : (($b['tipe_kamar'] == 'Suite' ? 'hotel3.jpg' : 'hotel2.jpg')) ?>" class="room-img" alt="<?= htmlspecialchars($b['nama_kamar']) ?>">
                            <div class="card-body">
                                <h5 class="mb-1"><?= htmlspecialchars($b['nama_kamar']) ?></h5>
                                <div class="mb-2 text-muted small">Tipe: <?= htmlspecialchars($b['tipe_kamar']) ?> | <?= htmlspecialchars($b['bed']) ?> bed</div>
                                <div class="mb-2"><i class="bi bi-calendar-check me-1"></i>Check-in: <strong><?= date('d M Y', strtotime($b['tgl_checkin'])) ?></strong></div>
                                <div class="mb-2"><i class="bi bi-calendar-x me-1"></i>Check-out: <strong><?= date('d M Y', strtotime($b['tgl_checkout'])) ?></strong></div>
                                <div class="mb-2"><i class="bi bi-wallet2 me-1"></i>Total: <strong>Rp<?= number_format($b['totalharga'], 0, ',', '.') ?></strong></div>
                                <div class="mb-2"><i class="bi bi-credit-card me-1"></i>Metode: <span class="text-muted small"><?= strtoupper($b['metode_pembayaran']) ?: '-' ?></span></div>
                                <div class="booking-actions d-flex gap-2">
                                    <a href="invoice.php?id=<?= $b['id_transaksi'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-text"></i> Detail</a>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <a href="payment_instruction.php?id=<?= $b['id_transaksi'] ?>" class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Bayar</a>
                                    <?php endif; ?>
                                    <?php if ($b['status'] === 'pending' || ($b['status'] === 'siap digunakan' && strtotime($b['tgl_checkin']) > time())): ?>
                                        <a href="cancel_booking.php?id=<?= $b['id_transaksi'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan booking ini?')"><i class="bi bi-x-circle"></i> Batalkan</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode"><i class="bi bi-moon-fill"></i></button>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    icon.className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    });
    </script>
</body>
</html>
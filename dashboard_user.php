<?php
$currentDate = date('Y-m-d');
$userId = $_SESSION['id_user'];

// Get user's bookings stats
$activeBookings = $conn->prepare("
    SELECT COUNT(*) FROM transaksi 
    WHERE id_user = ? AND status = 'checkin'
");
$activeBookings->execute([$userId]);
$activeCount = $activeBookings->fetchColumn();

// Get pending payments
$pendingPayments = $conn->prepare("
    SELECT COUNT(*) FROM transaksi 
    WHERE id_user = ? AND status = 'pending'
");
$pendingPayments->execute([$userId]);
$pendingCount = $pendingPayments->fetchColumn();

// Get completed stays
$completedStays = $conn->prepare("
    SELECT COUNT(*) FROM transaksi 
    WHERE id_user = ? AND status = 'selesai'
");
$completedStays->execute([$userId]);
$completedCount = $completedStays->fetchColumn();

// Get upcoming bookings
$upcomingBookings = $conn->prepare("
    SELECT t.*, k.nama as nama_kamar
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_user = ? 
    AND t.tgl_checkin > CURRENT_DATE
    ORDER BY t.tgl_checkin ASC
    LIMIT 5
");
$upcomingBookings->execute([$userId]);
$upcomingList = $upcomingBookings->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="mb-0"><i class="bi bi-person-circle me-2"></i>Dashboard Tamu</h3>
        <small class="text-muted">Per tanggal: <?= date('d F Y') ?></small>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4">
        <!-- Active Bookings -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Booking Aktif</h6>
                            <h4 class="mb-0"><?= $activeCount ?></h4>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-calendar-check fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Menunggu Pembayaran</h6>
                            <h4 class="mb-0"><?= $pendingCount ?></h4>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-wallet2 fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Stays -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Menginap Selesai</h6>
                            <h4 class="mb-0"><?= $completedCount ?></h4>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Bookings -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary bg-opacity-10 py-3">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Booking Mendatang</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingList)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted fs-1"></i>
                            <p class="mt-2">Tidak ada booking mendatang</p>
                            <a href="?page=user_pemesanan" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Pesan Kamar
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kamar</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingList as $booking): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($booking['nama_kamar']) ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($booking['tgl_checkin'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['tgl_checkout'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= match($booking['status']) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'checkin' => 'success',
                                                default => 'secondary'
                                            } ?>">
                                                <?= ucwords($booking['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?page=invoice&id=<?= $booking['id_transaksi'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-file-text"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
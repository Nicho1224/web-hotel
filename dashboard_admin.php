<?php
// Halaman khusus admin
$users = $conn->query("SELECT * FROM user WHERE lv != 'admin'")->fetchAll();
$totalPegawai = $conn->query("SELECT COUNT(*) FROM user WHERE lv = 'pegawai'")->fetchColumn();

// Get statistics
$totalKamar = $conn->query("SELECT COUNT(*) FROM kamar")->fetchColumn();
$totalTamu = $conn->query("SELECT COUNT(*) FROM tamu")->fetchColumn();
$totalTransaksi = $conn->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$pendapatan = $conn->query("
    SELECT COALESCE(SUM(k.harga), 0) 
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar 
    WHERE t.status = 'selesai'
")->fetchColumn();

// Get recent transactions
$recentTransaksi = $conn->query("
    SELECT 
        t.id_transaksi, t.tgl_checkin, t.tgl_checkout, t.status,
        k.nama as nama_kamar, k.harga as harga_kamar,
        tm.nama as nama_tamu
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    JOIN tamu tm ON t.nik = tm.nik
    ORDER BY t.tgl_booking DESC
    LIMIT 5
")->fetchAll();

// Get room status summary
$statusKamar = $conn->query("
    SELECT status, COUNT(*) as jumlah
    FROM kamar
    GROUP BY status
")->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="mb-0"><i class="bi bi-graph-up me-2"></i>Dashboard Admin</h3>
        <small class="text-muted">Data per tanggal: <?= date('d F Y') ?></small>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4">
        <!-- Total Kamar -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Kamar</h6>
                            <h4 class="mb-0"><?= $totalKamar ?></h4>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-door-closed fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Tamu -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Tamu</h6>
                            <h4 class="mb-0"><?= $totalTamu ?></h4>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-people fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Transaksi</h6>
                            <h4 class="mb-0"><?= $totalTransaksi ?></h4>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-receipt fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pendapatan -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Pendapatan</h6>
                            <h4 class="mb-0">Rp <?= number_format($pendapatan, 0, ',', '.') ?></h4>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-cash-stack fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary bg-opacity-10 py-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Transaksi Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Tamu</th>
                                    <th>Kamar</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransaksi as $trans): ?>
                                <tr>
                                    <td>#<?= $trans['id_transaksi'] ?></td>
                                    <td><?= htmlspecialchars($trans['nama_tamu']) ?></td>
                                    <td><?= htmlspecialchars($trans['nama_kamar']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($trans['tgl_checkin'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($trans['tgl_checkout'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= match($trans['status']) {
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'checkin' => 'primary',
                                            'checkout' => 'secondary',
                                            'selesai' => 'success',
                                            default => 'secondary'
                                        } ?>">
                                            <?= ucwords($trans['status']) ?>
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format($trans['harga_kamar'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
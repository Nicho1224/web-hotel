<?php
// Check admin access
if (!isset($_SESSION['id_user']) || $_SESSION['lv'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!-- Add SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<!-- Add SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Update the verification handler section
if (isset($_POST['verify_booking'])) {
    try {
        $conn->beginTransaction();
        
        // Get kamar_id for the booking
        $stmt = $conn->prepare("
            SELECT id_kamar 
            FROM transaksi 
            WHERE id_transaksi = ?
        ");
        $stmt->execute([$_POST['booking_id']]);
        $kamar_id = $stmt->fetchColumn();
        
        // Update transaksi status
        $stmt = $conn->prepare("
            UPDATE transaksi 
            SET status = 'siap digunakan'
            WHERE id_transaksi = ?
        ");
        $stmt->execute([$_POST['booking_id']]);
        
        // Update kamar status to tidak tersedia
        $stmt = $conn->prepare("
            UPDATE kamar 
            SET status = 'tidak tersedia'
            WHERE id_kamar = ?
        ");
        $stmt->execute([$kamar_id]);
        
        $conn->commit();
        
        echo "<script>
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pesanan telah diverifikasi dan status kamar diperbarui',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(function() {
                window.location.href = '?page=verifikasi';
            });
        </script>";
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<script>
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan: " . addslashes($e->getMessage()) . "',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Get all active bookings
$activeBookings = $conn->query("
    SELECT 
        t.id_transaksi,
        t.tgl_checkin,
        t.tgl_checkout,
        t.status,
        t.tgl_booking,
        t.totalharga,
        k.nama as nama_kamar,
        k.jenis,
        k.harga,
        k.bed,
        tm.nama as nama_tamu,
        tm.email as telepon,  /* Changed from tm.telepon to tm.email */
        (DATEDIFF(t.tgl_checkout, t.tgl_checkin) * k.harga) as total_harga
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    JOIN tamu tm ON t.nik = tm.nik
    WHERE t.status = 'pending'
    ORDER BY t.tgl_booking DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Update the room status query to include more booking details
$roomStatus = $conn->query("
    SELECT 
        k.id_kamar,
        k.nama,
        k.status,
        k.jenis,
        k.harga,
        k.bed,
        t.id_transaksi,
        t.tgl_checkin,
        t.tgl_checkout,
        t.status as booking_status,
        tm.nama as nama_tamu,
        tm.email as telepon  /* Changed from tm.telepon to tm.email */
    FROM kamar k
    LEFT JOIN transaksi t ON k.id_kamar = t.id_kamar 
        AND t.status IN ('pending', 'siap digunakan', 'checkin')
    LEFT JOIN tamu tm ON t.nik = tm.nik
    ORDER BY k.id_kamar ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <!-- Pending Bookings Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning border-opacity-25">
                <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history text-warning"></i> 
                        Pesanan Menunggu Verifikasi
                    </h5>
                    <span class="badge bg-warning">
                        <?= count($activeBookings) ?> Pesanan
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($activeBookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success fs-1"></i>
                            <p class="mt-2 text-muted">Tidak ada pesanan yang perlu diverifikasi</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tamu</th>
                                        <th>Kamar</th>
                                        <th>Periode</th>
                                        <th>Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeBookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($booking['nama_tamu']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $booking['telepon'] ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($booking['nama_kamar']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $booking['jenis'] ?> - <?= $booking['bed'] ?></small>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($booking['tgl_checkin'])) ?> 
                                            <i class="bi bi-arrow-right mx-1"></i>
                                            <?= date('d/m/Y', strtotime($booking['tgl_checkout'])) ?>
                                        </td>
                                        <td>
                                            <strong>Rp <?= number_format($booking['totalharga'], 0, ',', '.') ?></strong>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm verify-btn" 
                                                    data-booking-id="<?= $booking['id_transaksi'] ?>"
                                                    data-booking-name="<?= htmlspecialchars($booking['nama_tamu']) ?>"
                                                    data-room-name="<?= htmlspecialchars($booking['nama_kamar']) ?>">
                                                <i class="bi bi-check-circle"></i> Verifikasi
                                            </button>
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

    <!-- Room Status Grid -->
    <div class="row g-4">
        <?php foreach ($roomStatus as $room): ?>
            <!-- Ubah dari col-xl-3 col-md-6 menjadi col-12 col-md-6 col-xl-3 dan tambahkan style width tetap -->
            <div class="col-12 col-md-6 col-xl-3">
                <!-- Room Status Card dengan fixed height -->
                <div class="card h-100 <?= $room['nama_tamu'] ? 'border-primary' : '' ?>" style="min-height: 200px;">
                    <div class="card-header bg-<?php echo match($room['status']) {
                        'tersedia' => 'success',
                        'tidak tersedia' => 'warning',
                        'maintenance' => 'danger',
                        'sedang dibersihkan' => 'info',
                        default => 'secondary'
                    } ?> bg-opacity-10 d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 text-truncate" style="max-width: 150px;"><?= htmlspecialchars($room['nama']) ?></h6>
                        <span class="badge bg-<?php echo match($room['status']) {
                            'tersedia' => 'success',
                            'tidak tersedia' => 'warning',
                            'maintenance' => 'danger',
                            'sedang dibersihkan' => 'info',
                            default => 'secondary'
                        } ?>"><?= ucwords($room['status']) ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><?= $room['jenis'] ?> - <?= $room['bed'] ?></span>
                            <strong class="text-primary">Rp <?= number_format($room['harga'], 0, ',', '.') ?></strong>
                        </div>
                        
                        <?php if ($room['nama_tamu']): ?>
                            <div class="alert alert-<?= $room['booking_status'] === 'pending' ? 'warning' : 'info' ?> alert-sm p-2 mb-0 mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-truncate" style="max-width: 150px;">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($room['nama_tamu']) ?>
                                    </strong>
                                    <span class="badge bg-<?= $room['booking_status'] === 'pending' ? 'warning' : 'info' ?> ms-1">
                                        <?= ucwords($room['booking_status']) ?>
                                    </span>
                                </div>
                                <p class="mb-1 small text-truncate"><i class="bi bi-envelope"></i> <?= $room['telepon'] ?? '-' ?></p>
                                <p class="mb-0 small">
                                    <i class="bi bi-calendar-check"></i> 
                                    <?= date('d/m/Y', strtotime($room['tgl_checkin'])) ?> - 
                                    <?= date('d/m/Y', strtotime($room['tgl_checkout'])) ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0 small mt-auto">
                                <i class="bi bi-info-circle"></i> Belum ada pemesanan
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle verification buttons
    document.querySelectorAll('.verify-btn').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            const guestName = this.getAttribute('data-booking-name');
            const roomName = this.getAttribute('data-room-name');

            Swal.fire({
                title: 'Verifikasi Pesanan',
                html: `
                    <div class="text-start">
                        <p><strong>ID Pesanan:</strong> #${bookingId}</p>
                        <p><strong>Nama Tamu:</strong> ${guestName}</p>
                        <p><strong>Kamar:</strong> ${roomName}</p>
                    </div>
                    <p class="mt-3">Apakah Anda yakin ingin memverifikasi pesanan ini?</p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Verifikasi',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="booking_id" value="${bookingId}">
                        <input type="hidden" name="verify_booking" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});
</script>
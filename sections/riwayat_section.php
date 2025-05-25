<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php?redirect=landing_page.php?section=riwayat');
    exit;
}

$userId = $_SESSION['id_user'];
$userStmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get all bookings for this user (exclude deleted records)
// Di bagian query booking
$stmt = $conn->prepare("
    SELECT t.*, k.nama as nama_kamar, k.jenis as tipe_kamar, k.bed, k.harga
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_user = ? AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
      AND t.status != 'berhasil'
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
$pendingCount = $activeCount = $canceledCount = 0;
foreach ($bookings as $b) {
    switch ($b['status']) {
        case 'pending': $pendingCount++; break;
        case 'siap digunakan': $activeCount++; break;
        case 'dibatalkan': $canceledCount++; break;
    }
}

function statusBadge($status) {
    $map = [
        'pending' => ['warning', 'Menunggu Pembayaran', 'bi-hourglass-split'],
        'siap digunakan' => ['primary', 'Aktif', 'bi-calendar-check'],
        'dibatalkan' => ['danger', 'Dibatalkan', 'bi-x-circle'],
    ];
    $d = $map[$status] ?? ['secondary', ucfirst($status), 'bi-info-circle'];
    return '<span class="badge bg-' . $d[0] . '"><i class="bi ' . $d[2] . ' me-1"></i>' . $d[1] . '</span>';
}
?>
<section id="riwayat" class="section">  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
      <div class="section-title text-center flex-grow-1">
        <h2 class="mb-3">Riwayat Booking Anda</h2>
        <p class="text-muted">Lihat dan kelola semua riwayat pemesanan Anda</p>
      </div>
      <?php if(!empty($bookings)): ?>
      <div>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllHistoryModal">
          <i class="bi bi-trash me-2"></i>Hapus Semua Riwayat
        </button>
      </div>
      <?php endif; ?>
    </div>
    <!-- Status Filter -->    <div class="status-filter text-center mb-4">
      <span class="status-pill<?= $statusFilter=='' ? ' active' : '' ?>" onclick="window.location='landing_page.php?section=riwayat'">
        <i class="bi bi-grid-3x3"></i> Semua
      </span>
      <span class="status-pill<?= $statusFilter=='pending' ? ' active' : '' ?>" onclick="window.location='landing_page.php?section=riwayat&status=pending'">
        <i class="bi bi-hourglass-split"></i> Menunggu
      </span>
      <span class="status-pill<?= $statusFilter=='siap digunakan' ? ' active' : '' ?>" onclick="window.location='landing_page.php?section=riwayat&status=siap digunakan'">
        <i class="bi bi-calendar-check"></i> Aktif
      </span>
      <span class="status-pill<?= $statusFilter=='dibatalkan' ? ' active' : '' ?>" onclick="window.location='landing_page.php?section=riwayat&status=dibatalkan'">
        <i class="bi bi-x-circle"></i> Dibatalkan
      </span>
    </div>    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-4 col-6">
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
      <div class="col-md-4 col-6">
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
      <div class="col-md-4 col-6">
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
            <a href="landing_page.php?section=rooms" class="btn btn-primary px-4 py-2">
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
              <div class="mb-2 text-muted small">Tipe: <?= htmlspecialchars($b['tipe_kamar']) ?> | <?= htmlspecialchars($b['bed']) ?> bed</div>              <div class="mb-2"><i class="bi bi-calendar-check me-1"></i>Check-in: <strong><?= date('d M Y', strtotime($b['tgl_checkin'])) ?></strong></div>
              <div class="mb-2"><i class="bi bi-calendar-x me-1"></i>Check-out: <strong><?= date('d M Y', strtotime($b['tgl_checkout'])) ?></strong></div>
              <div class="mb-2"><i class="bi bi-wallet2 me-1"></i>Total: <strong>Rp<?= number_format($b['totalharga'], 0, ',', '.') ?></strong></div>
              <div class="mb-2"><i class="bi bi-credit-card me-1"></i>Metode: <span class="text-muted small"><?= strtoupper($b['metode_pembayaran']) ?: '-' ?></span></div>              <div class="booking-actions d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary view-detail" data-id="<?= $b['id_transaksi'] ?>">
                  <i class="bi bi-file-text"></i> Detail
                </button>
                
                <?php if ($b['status'] === 'pending' || ($b['status'] === 'siap digunakan' && strtotime($b['tgl_checkin']) > time())): ?>
                    <!-- Tombol Edit Tanggal -->
                    <button type="button" class="btn btn-sm btn-outline-info edit-dates" 
                            data-id="<?= $b['id_transaksi'] ?>"
                            data-checkin="<?= $b['tgl_checkin'] ?>"
                            data-checkout="<?= $b['tgl_checkout'] ?>">
                        <i class="bi bi-calendar-plus"></i> Edit Tanggal
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-outline-danger cancel-booking" data-id="<?= $b['id_transaksi'] ?>">
                      <i class="bi bi-x-circle"></i> Batalkan
                    </button>
                <?php endif; ?>
                
                <button type="button" class="btn btn-sm btn-outline-secondary delete-booking" data-id="<?= $b['id_transaksi'] ?>">
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>    </div>
  </div>
</section>
<style>
:root {
  --bg-color: #ffffff;
  --text-color: #2d3748;
  --heading-color: #1a365d;
  --card-bg: #ffffff;
  --card-shadow: rgba(0,0,0,0.1);
  --border-color: #e2e8f0;
  --price-color: #2b82c3;
  --disabled-opacity: 0.5;
  --selected-border: #2b82c3;
  --input-bg: #ffffff;
  --input-border: #ced4da;
  --btn-primary: #2b82c3;
  --btn-hover: #1a6298;
}
[data-theme="dark"] {
  --bg-color: #0f172a;
  --text-color: #e2e8f0;
  --heading-color: #f8fafc;
  --card-bg: #1e293b;
  --card-shadow: rgba(0,0,0,0.3);
  --border-color: #334155;
  --price-color: #38bdf8;
  --disabled-opacity: 0.4;
  --selected-border: #38bdf8;
  --input-bg: #1e293b;
  --input-border: #475569;
  --btn-primary: #38bdf8;
  --btn-hover: #0ea5e9;
}
/* Base styles */
body, .section, .container {
  background-color: var(--bg-color);
  color: var(--text-color);
  transition: all 0.3s ease;
}

/* Navbar styles */
.navbar, .navbar-main, .navbar-brand, .navbar-nav, .navbar-collapse, .navbar .dropdown-menu {
  background-color: var(--card-bg) !important;
  color: var(--text-color) !important;
  border-bottom: 2px solid var(--btn-primary) !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.navbar .nav-link, .navbar .navbar-brand, .navbar .dropdown-item {
  color: var(--text-color) !important;
  font-weight: 500;
  transition: all 0.3s ease;
}

.navbar .nav-link.active, .navbar .nav-link:focus, .navbar .nav-link:hover, 
.navbar .dropdown-item.active, .navbar .dropdown-item:focus, .navbar .dropdown-item:hover {
  color: var(--btn-primary) !important;
  background-color: rgba(43,130,195,0.08) !important;
}

/* Section title */
.section-title h2 {
  color: var(--heading-color);
  font-weight: 600;
  position: relative;
  margin-bottom: 1rem;
}

.section-title p {
  color: var(--text-color);
  opacity: 0.8;
}

/* Status filter styling */
.status-pill {
  display: inline-block;
  padding: 0.6rem 1.2rem;
  border-radius: 50px;
  margin: 0 8px 12px 0;
  cursor: pointer;
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  color: var(--text-color);
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-pill.active {
  background-color: var(--btn-primary);
  color: white;
  border-color: var(--selected-border);
}

.status-pill:hover:not(.active) {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(43,130,195,0.15);
  border-color: var(--selected-border);
}

/* Stats cards */
.card {
  background-color: var(--card-bg);
  color: var(--text-color);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px var(--card-shadow);
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 15px var(--card-shadow);
}

.rounded-circle {
  background-color: var(--card-bg) !important;
  border: 1px solid var(--border-color);
}

/* Booking cards */
.booking-card {
  background-color: var(--card-bg);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  overflow: hidden;
  box-shadow: 0 4px 6px var(--card-shadow);
  margin-bottom: 25px;
  transition: all 0.3s ease;
  color: var(--text-color);
  position: relative;
}

.booking-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 20px var(--card-shadow);
  border-color: var(--selected-border);
}

.booking-card .badge {
  font-size: 0.85rem;
  padding: 0.5em 0.85em;
  font-weight: 500;
}

.booking-card .booking-status {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 10;
}

.booking-card .room-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  transition: all 0.3s ease;
}

.booking-card .card-body {
  padding: 1.25rem;
  position: relative;
}

.booking-card h5 {
  font-weight: 600;
  color: var(--heading-color);
  margin-bottom: 0.5rem;
}

.booking-card .text-muted {
  color: var(--text-color) !important;
  opacity: 0.7;
}

.booking-card .booking-actions {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary, .btn-success {
  background-color: var(--btn-primary) !important;
  border-color: var(--btn-primary) !important;
  color: white !important;
}

.btn-primary:hover, .btn-success:hover {
  background-color: var(--btn-hover) !important;
  border-color: var(--btn-hover) !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(43,130,195,0.2);
}

.btn-outline-primary {
  background-color: transparent !important;
  color: var(--btn-primary) !important;
  border: 1px solid var(--btn-primary) !important;
}

.btn-outline-primary:hover {
  background-color: var(--btn-primary) !important;
  color: white !important;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background-color: var(--card-bg);
  border-radius: 14px;
  border: 1px dashed var(--border-color);
  max-width: 500px;
  margin: 0 auto;
}

.empty-state .bi {
  font-size: 3.5rem;
  color: var(--price-color);
  margin-bottom: 1rem;
}

.empty-state h4 {
  color: var(--heading-color);
  font-weight: 600;
}

/* Media queries */
@media (max-width: 768px) {
  .booking-card .room-img {
    height: 140px;
  }
  
  .status-pill {
    padding: 0.4rem 0.8rem;
    font-size: 0.9rem;  }
}

/* Modal styles */
.modal-content {
  background-color: var(--card-bg);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.modal-header, .modal-footer {
  border-color: var(--border-color);
}

.modal-title {
  color: var(--heading-color);
}
</style>

<!-- Detail Modal -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Pemesanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bookingDetailContent">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Memuat detail...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal for Cancellation -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Pembatalan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin membatalkan pemesanan ini?</p>
        <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
        <button type="button" class="btn btn-danger" id="confirmCancelBtn">Ya, Batalkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Dates Modal -->
<div class="modal fade" id="editDatesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Tanggal Menginap</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editDatesForm">
          <input type="hidden" id="editBookingId" name="booking_id">
          
          <div class="mb-3">
            <label for="edit-checkin" class="form-label">Tanggal Check-in</label>
            <input type="date" class="form-control" id="edit-checkin" name="checkin" required>
            <small class="text-muted">Tanggal check-in minimal hari ini</small>
          </div>
          
          <div class="mb-3">
            <label for="edit-checkout" class="form-label">Tanggal Check-out</label>
            <input type="date" class="form-control" id="edit-checkout" name="checkout" required>
            <small class="text-muted">Tanggal check-out harus setelah check-in</small>
          </div>
          
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <span>Perubahan tanggal dapat mengubah total biaya menginap sesuai durasi yang baru.</span>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="saveDatesBtn">Simpan Perubahan</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Handle booking detail view
  const bookingDetailModal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
  const cancelConfirmModal = new bootstrap.Modal(document.getElementById('cancelConfirmModal'));
  const editDatesModal = new bootstrap.Modal(document.getElementById('editDatesModal'));
  let currentBookingId = null;
  
  // View booking details
  document.querySelectorAll('.view-detail').forEach(button => {
    button.addEventListener('click', async function() {
      const bookingId = this.getAttribute('data-id');
      const contentArea = document.getElementById('bookingDetailContent');
      
      // Show loading
      contentArea.innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Memuat detail...</p>
        </div>
      `;
      
      bookingDetailModal.show();
      
      try {
        // Fetch booking details from invoice.php with a parameter to indicate it's for modal display
        const response = await fetch(`invoice.php?id=${bookingId}&display=modal`);
        const data = await response.text();
        
        // Insert the response into the modal
        contentArea.innerHTML = data;
      } catch (error) {
        contentArea.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> Gagal memuat detail: ${error.message}
          </div>
        `;
      }
    });
  });
  
  // Handle cancellation
  document.querySelectorAll('.cancel-booking').forEach(button => {
    button.addEventListener('click', function() {
      currentBookingId = this.getAttribute('data-id');
      cancelConfirmModal.show();
    });
  });
  
  // Confirm cancellation
  document.getElementById('confirmCancelBtn').addEventListener('click', async function() {
    if (!currentBookingId) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
    
    try {
      const response = await fetch('cancel_booking.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `invoice_id=${currentBookingId}`
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Close modal
        cancelConfirmModal.hide();
        
        // Show success message
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Pemesanan berhasil dibatalkan',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Reload the page with canceled filter
          window.location.href = 'landing_page.php?section=riwayat&status=dibatalkan';
        });
      } else {
        throw new Error(result.message || 'Terjadi kesalahan');
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Terjadi kesalahan saat membatalkan pemesanan'
      });
      this.disabled = false;
      this.innerHTML = 'Ya, Batalkan';
    }
  });
  // Handle individual booking deletion
  document.querySelectorAll('.delete-booking').forEach(button => {
    button.addEventListener('click', function() {
      currentBookingId = this.getAttribute('data-id');
      document.getElementById('deleteConfirmModal').querySelector('.modal-body p').textContent = 
        'Apakah Anda yakin ingin menghapus riwayat pemesanan ini?';
      
      // Show delete confirmation modal
      const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
      deleteConfirmModal.show();
    });
  });
  
  // Confirm individual deletion
  document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!currentBookingId) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
    
    try {
      const response = await fetch('delete_booking_history.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `invoice_id=${currentBookingId}&action=single`
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
        
        // Show success message
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Riwayat pemesanan berhasil dihapus',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Reload the page
          window.location.href = 'landing_page.php?section=riwayat';
        });
      } else {
        throw new Error(result.message || 'Terjadi kesalahan');
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Terjadi kesalahan saat menghapus riwayat'
      });
      this.disabled = false;
      this.innerHTML = 'Ya, Hapus';
    }
  });
  
  // Delete all history confirmation
  document.getElementById('confirmDeleteAllBtn')?.addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
    
    try {
      const response = await fetch('delete_booking_history.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=all'
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('deleteAllHistoryModal')).hide();
        
        // Show success message
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Semua riwayat pemesanan berhasil dihapus',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Reload the page
          window.location.href = 'landing_page.php?section=riwayat';
        });
      } else {
        throw new Error(result.message || 'Terjadi kesalahan');
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Terjadi kesalahan saat menghapus riwayat'
      });
      this.disabled = false;
      this.innerHTML = 'Ya, Hapus Semua';
    }
  });
  
  // Handle edit dates
  const today = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD

  document.querySelectorAll('.edit-dates').forEach(button => {
    button.addEventListener('click', function() {
      const bookingId = this.getAttribute('data-id');
      const checkin = this.getAttribute('data-checkin');
      const checkout = this.getAttribute('data-checkout');
      
      // Set values in the form
      document.getElementById('editBookingId').value = bookingId;
      document.getElementById('edit-checkin').value = checkin;
      document.getElementById('edit-checkout').value = checkout;
      
      // Set min dates
      document.getElementById('edit-checkin').min = today;
      document.getElementById('edit-checkout').min = today;
      
      // Show modal
      editDatesModal.show();
    });
  });

  // Handle checkin date change to update checkout min date
  document.getElementById('edit-checkin').addEventListener('change', function() {
    const checkinDate = this.value;
    document.getElementById('edit-checkout').min = checkinDate;
    
    // If checkout is before new checkin, update it
    const checkoutInput = document.getElementById('edit-checkout');
    if (checkoutInput.value < checkinDate) {
      // Set to day after checkin
      const nextDay = new Date(new Date(checkinDate).getTime() + 86400000).toISOString().split('T')[0];
      checkoutInput.value = nextDay;
    }
  });

  // Handle save dates
  document.getElementById('saveDatesBtn').addEventListener('click', async function() {
    // Get form data
    const bookingId = document.getElementById('editBookingId').value;
    const checkin = document.getElementById('edit-checkin').value;
    const checkout = document.getElementById('edit-checkout').value;
    
    // Validate
    if (!checkin || !checkout) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Silakan isi semua tanggal'
      });
      return;
    }
    
    if (checkout <= checkin) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Tanggal check-out harus setelah tanggal check-in'
      });
      return;
    }
    
    // Show loading state
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
    
    try {
      const response = await fetch('update_booking_dates.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `booking_id=${bookingId}&checkin=${checkin}&checkout=${checkout}`
      });
      
      if (!response.ok) {
        throw new Error('Network response was not ok: ' + response.status);
      }
      
      const result = await response.json();
      
      if (result.success) {
        // Close modal
        editDatesModal.hide();
        
        // Show success message
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Tanggal menginap berhasil diperbarui',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Reload the page
          window.location.reload();
        });
      } else {
        throw new Error(result.message || 'Terjadi kesalahan');
      }
    } catch (error) {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Terjadi kesalahan saat memperbarui tanggal'
      });
    } finally {
      this.disabled = false;
      this.innerHTML = 'Simpan Perubahan';
    }
  });
});
</script>

<!-- Delete Single Booking Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus riwayat pemesanan ini?</p>
        <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete All History Modal -->
<div class="modal fade" id="deleteAllHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus Semua Riwayat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Peringatan:</strong> Tindakan ini akan menghapus SEMUA riwayat pemesanan Anda.
        </div>
        <p>Data yang sudah dihapus tidak dapat dikembalikan. Apakah Anda yakin?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteAllBtn">Ya, Hapus Semua</button>
      </div>
    </div>
  </div>
</div>
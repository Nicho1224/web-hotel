<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

require_once 'config.php';

// Jika belum login → redirect ke login
if (!isset($_SESSION['id_user'])) {
  header('Location: login.php');
  exit;
}

// Process form submission for adding a new room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
  $nama = $_POST['nama'];
  $bed = $_POST['bed'];
  $keterangan = $_POST['keterangan'];
  $jenis = $_POST['jenis'];
  $harga = $_POST['harga'];
  $status = $_POST['status'];
  
  try {
    $stmt = $conn->prepare("INSERT INTO kamar (nama, bed, keterangan, jenis, harga, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $bed, $keterangan, $jenis, $harga, $status]);
    
    // Set success message
    $_SESSION['success_message'] = "Kamar berhasil ditambahkan!";
    
    // Set a flag to trigger JavaScript refresh instead of using header
    $refresh_page = true;
  } catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
  }
}

// Ambil semua kamar
$rooms = $conn->query("SELECT * FROM kamar ORDER BY id_kamar")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pilih Kamar - Hotel Management System</title>
  <?php if(isset($refresh_page) && $refresh_page): ?>
  <script>
    window.location.href = 'tambah_kamar.php';
  </script>
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* Light theme (default) */
    :root {
        --text-color: #2d3748;
        --bg-color: #f7fafc;
        --card-bg: #ffffff;
        --card-border: #e2e8f0;
        --card-shadow: 0 2px 4px rgba(0,0,0,0.08);
        --heading-color: #1a202c;
        --secondary-text: #4a5568;
        --hover-shadow: 0 4px 6px rgba(0,0,0,0.1);
        --selected-bg: #ebf8ff;
        --selected-border: #4299e1;
        --price-color: #2b6cb0;
        --btn-add-bg: #68d391;
        --btn-add-hover: #48bb78;
    }

    /* Dark theme */
    [data-theme="dark"] {
        --text-color: #e2e8f0;
        --bg-color: #171923;
        --card-bg: #2d3748;
        --card-border: #4a5568;
        --card-shadow: 0 2px 4px rgba(0,0,0,0.2);
        --heading-color: #f7fafc;
        --secondary-text: #a0aec0;
        --hover-shadow: 0 4px 6px rgba(0,0,0,0.3);
        --selected-bg: #2c5282;
        --selected-border: #4299e1;
        --price-color: #63b3ed;
        --btn-add-bg: #38a169;
        --btn-add-hover: #2f855a;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        min-height: 100vh;
    }

    .container-main {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .header-title {
        color: var(--heading-color);
        font-size: 2rem;
        margin-bottom: 2rem;
        font-weight: 600;
    }

    .room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .room-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: var(--card-shadow);
    }

    .room-card:hover:not(.unavailable) {
        transform: translateY(-2px);
        box-shadow: var(--hover-shadow);
        cursor: pointer;
    }

    .room-card.selected {
        background: var(--selected-bg);
        border: 2px solid var(--selected-border);
    }

    .room-status {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .available-status {
        background: #48bb78;
        color: white;
    }

    .unavailable-status {
        background: #f56565;
        color: white;
    }

    .room-title {
        color: var(--heading-color);
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .room-type {
        color: var(--secondary-text);
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .room-price {
        color: var(--price-color);
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .room-features {
        list-style: none;
        padding: 0;
        margin: 0;
        color: var(--text-color);
    }

    .room-features li {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
    }

    .room-features li:before {
        content: "•";
        color: var(--price-color);
        margin-right: 0.5rem;
    }

    .selected-count {
        color: var(--secondary-text);
        font-size: 1rem;
        margin: 1rem 0;
    }

    .btn-submit {
        background: #4299e1;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-submit:hover:not(:disabled) {
        background: #3182ce;
        transform: translateY(-1px);
    }

    .btn-submit:disabled {
        background: var(--secondary-text);
        opacity: 0.7;
    }

    .btn-add-room {
        background: var(--btn-add-bg);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
        transition: all 0.3s ease;
        margin-right: 1rem;
    }    .btn-add-room:hover {
        background: var(--btn-add-hover);
        transform: translateY(-1px);
        color: white;
    }
    
    .btn-reset-room {
        background: #f56565;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-reset-room:hover {
        background: #e53e3e;
        transform: translateY(-1px);
        color: white;
    }
    
    .modal-content {
        background-color: var(--card-bg);
        color: var(--text-color);
    }
    
    .modal-header {
        border-bottom-color: var(--card-border);
    }
    
    .modal-footer {
        border-top-color: var(--card-border);
    }
    
    .form-control, .form-select {
        background-color: var(--bg-color);
        color: var(--text-color);
        border-color: var(--card-border);
    }
    
    .form-control:focus, .form-select:focus {
        background-color: var(--bg-color);
        color: var(--text-color);
    }
    
    .label-text {
        color: var(--text-color);
    }
  </style>
</head>
<body>

<div class="container-main">  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="header-title mb-0">Pilih Kamar Anda</h1>
    
    <div>
      <!-- Add Room Button -->
      <?php if(isset($_SESSION['lv']) && ($_SESSION['lv'] == 'admin' || $_SESSION['lv'] == 'pegawai')): ?>
      <button type="button" class="btn btn-add-room" data-bs-toggle="modal" data-bs-target="#addRoomModal">
        <i class="bi bi-plus-circle me-2"></i> Tambah Kamar
      </button>
        <!-- Reset Room Status Button -->
      <button type="button" class="btn btn-reset-room" data-bs-toggle="modal" data-bs-target="#resetRoomModal">
        <i class="bi bi-arrow-repeat me-2"></i> Reset Status Kamar
      </button>
      <?php endif; ?>
    </div>
  </div>
  
  <form method="get" action="booking_kamar.php" id="bookingForm">
    <div class="room-grid">
      <?php foreach($rooms as $room): 
        $isAvailable = $room['status'] === 'tersedia';
      ?>
        <div class="room-card <?= $isAvailable ? '' : 'unavailable' ?>" 
             data-id="<?= $room['id_kamar'] ?>"
             <?= $isAvailable ? 'onclick="toggleSelection(this)"' : '' ?>>
             
          <div class="room-status <?= $isAvailable ? 'available-status' : 'unavailable-status' ?>">
            <?= $isAvailable ? 'Tersedia' : 'Terisi' ?>
          </div>
          
          <h3 class="room-title"><?= htmlspecialchars($room['nama']) ?></h3>
          <div class="room-type"><?= htmlspecialchars($room['jenis']) ?></div>
          <div class="room-price">
            Rp <?= number_format($room['harga'], 0, ',', '.') ?>/malam
          </div>
          
          <ul class="room-features">
            <?php if(!empty($room['fasilitas'])): ?>
              <?php foreach(explode(',', $room['fasilitas']) as $feature): ?>
                <li><?= htmlspecialchars(trim($feature)) ?></li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="selected-count" id="selectedCounter">
      0 Kamar Dipilih
    </div>

    <input type="hidden" name="kamar_dipilih" id="selectedRooms">
    
    <button type="submit" class="btn btn-primary btn-submit" disabled>
      <i class="bi bi-arrow-right-circle"></i> Lanjut ke Pembayaran
    </button>
  </form>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addRoomModalLabel">Tambah Kamar Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <!-- Alert for success or error messages -->
          <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $_SESSION['success_message'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
          <?php endif; ?>
          
          <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $_SESSION['error_message'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="nama" class="form-label label-text">Nama Kamar</label>
              <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="col-md-6">
              <label for="bed" class="form-label label-text">Tipe Bed</label>
              <select class="form-select" id="bed" name="bed" required>
                <option value="Single">Single</option>
                <option value="Double" selected>Double</option>
                <option value="Twin">Twin</option>
                <option value="Queen">Queen</option>
                <option value="King">King</option>
              </select>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="jenis" class="form-label label-text">Jenis Kamar</label>
              <select class="form-select" id="jenis" name="jenis" required>
                <option value="Standard" selected>Standard</option>
                <option value="Deluxe">Deluxe</option>
                <option value="Suite">Suite</option>
                <option value="Executive">Executive</option>
                <option value="Presidential">Presidential</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="harga" class="form-label label-text">Harga per Malam (Rp)</label>
              <input type="number" class="form-control" id="harga" name="harga" min="0" step="10000" required value="300000">
            </div>
          </div>
          
          <div class="mb-3">
            <label for="keterangan" class="form-label label-text">Keterangan</label>
            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
          </div>
          
          <div class="mb-3">
            <label for="status" class="form-label label-text">Status Kamar</label>
            <select class="form-select" id="status" name="status" required>
              <option value="tersedia" selected>Tersedia</option>
              <option value="tidak tersedia">Tidak Tersedia</option>
              <option value="maintenance">Maintenance</option>
              <option value="sedang dibersihkan">Sedang Dibersihkan</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="add_room" class="btn btn-success">Simpan Kamar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const selectedRooms = new Set();
  
  function toggleSelection(card) {
    const roomId = card.dataset.id;
    
    if(selectedRooms.has(roomId)) {
      selectedRooms.delete(roomId);
      card.classList.remove('selected');
    } else {
      selectedRooms.add(roomId);
      card.classList.add('selected');
    }
    
    updateSelectionDisplay();
  }

  function updateSelectionDisplay() {
    // Update hidden input
    document.getElementById('selectedRooms').value = Array.from(selectedRooms).join(',');
    
    // Update counter
    const counter = document.getElementById('selectedCounter');
    counter.textContent = `${selectedRooms.size} Kamar Dipilih`;
    
    // Toggle button state
    const submitBtn = document.querySelector('.btn-submit');
    submitBtn.disabled = selectedRooms.size === 0;
  }
  // Theme handling and modal auto-show
  document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from parent
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Auto-show modal if there's a success or error message
    <?php if(isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
      <?php if(isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'reset') !== false): ?>
        // Show reset modal if the message is about resetting rooms
        const resetRoomModal = new bootstrap.Modal(document.getElementById('resetRoomModal'));
        resetRoomModal.show();
      <?php else: ?>
        // Show add room modal for other messages
        const addRoomModal = new bootstrap.Modal(document.getElementById('addRoomModal'));
        addRoomModal.show();
      <?php endif; ?>
    <?php endif; ?>
  });
</script>

<!-- Reset Room Status Confirmation Modal -->
<div class="modal fade" id="resetRoomModal" tabindex="-1" aria-labelledby="resetRoomModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resetRoomModalLabel">Konfirmasi Reset Status Kamar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>      <div class="modal-body">
        <!-- Alert for success or error messages -->
        <?php if(isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'reset') !== false): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php elseif(isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'reset') !== false): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php else: ?>
          <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Peringatan:</strong> Tindakan ini akan mengubah status semua kamar menjadi "tersedia" tanpa memandang status saat ini. Yakin ingin melanjutkan?
          </div>
        <?php endif; ?>
        
        <?php if(!isset($_SESSION['success_message'])): ?>
          <form method="POST" action="reset_kamar_status.php" id="resetForm">
            <div class="mb-3">
              <label for="admin_username" class="form-label">Username Admin</label>
              <input type="text" class="form-control" id="admin_username" name="admin_username" required>
            </div>

            <div class="mb-3">
              <label for="admin_password" class="form-label">Password Admin</label>
              <input type="password" class="form-control" id="admin_password" name="admin_password" required>
            </div>
          </form>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <?php if(!isset($_SESSION['success_message']) && !isset($_SESSION['error_message'])): ?>
          <button type="submit" form="resetForm" name="reset_rooms" class="btn btn-danger">Ya, Reset Semua Kamar</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
<?php
// End output buffering and send the content
ob_end_flush();
?>
<?php
// This file is included in landing_page.php
// It contains the rooms section content

// Check if config file is included
if (!defined('INCLUDED_CONFIG')) {
    require_once 'config.php';
}

// Check user authentication - allow guest browsing but track login status
$isLoggedIn = isset($_SESSION['id_user']);
$userId = $isLoggedIn ? $_SESSION['id_user'] : null;

// If user is logged in, get user details
if ($isLoggedIn) {
    $userStmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
}

// Get all available rooms
$roomsStmt = $conn->prepare("SELECT * FROM kamar ORDER BY status DESC, harga ASC");
$roomsStmt->execute();
$rooms = $roomsStmt->fetchAll();

// Get room types for filtering
$typeStmt = $conn->prepare("SELECT DISTINCT jenis FROM kamar ORDER BY jenis");
$typeStmt->execute();
$roomTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle filtering
$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$minPrice = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? intval($_GET['max_price']) : 10000000;

// Get price range for the filter
$priceRangeStmt = $conn->prepare("SELECT MIN(harga) as min_price, MAX(harga) as max_price FROM kamar");
$priceRangeStmt->execute();
$priceRange = $priceRangeStmt->fetch();

// Set default values if not set
if (!isset($_GET['min_price'])) $minPrice = $priceRange['min_price'];
if (!isset($_GET['max_price'])) $maxPrice = $priceRange['max_price'];

// Filter rooms based on criteria if filters are applied
if (!empty($selectedType) || isset($_GET['min_price']) || isset($_GET['max_price'])) {
    $filteredRooms = array_filter($rooms, function($room) use ($selectedType, $minPrice, $maxPrice) {
        $typeMatch = empty($selectedType) || $room['jenis'] == $selectedType;
        $priceMatch = $room['harga'] >= $minPrice && $room['harga'] <= $maxPrice;
        return $typeMatch && $priceMatch;
    });
    $rooms = $filteredRooms;
}

// Function to calculate number of days between dates
function calculateDays($checkin, $checkout) {
    $checkInDate = new DateTime($checkin);
    $checkOutDate = new DateTime($checkout);
    $interval = $checkInDate->diff($checkOutDate);
    return $interval->days;
}
?>
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
    }    [data-theme="dark"] {
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
    
    .room-card {
        background-color: var(--card-bg);
        border: 2px solid var(--border-color);
        box-shadow: 0 4px 6px var(--card-shadow);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    /* Pulse animation for highlighting the pre-selected room */
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(43, 130, 195, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(43, 130, 195, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(43, 130, 195, 0);
        }
    }
    
    [data-theme="dark"] @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(56, 189, 248, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
        }
    }
    
    .room-card:hover {
        transform: translateY(-5px);
    }

    .room-card.disabled {
        opacity: var(--disabled-opacity);
        cursor: not-allowed;
    }

    .room-card.disabled:hover {
        transform: none;
    }

    .room-card.selected {
        border-color: var(--selected-border) !important;
        box-shadow: 0 0 0 2px var(--selected-border);
    }

    .price-tag {
        font-size: 1.25rem;
        color: var(--price-color);
        font-weight: bold;
    }

    /* Calendar date wrapper */
    .calendar-wrapper {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        background-color: var(--card-bg);
        margin-bottom: 1.5rem;
    }

    .calendar-header {
        background-color: var(--btn-primary);
        color: white;
        padding: 12px 15px;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .calendar-body {
        padding: 15px;
    }

    .calendar-footer {
        display: flex;
        justify-content: space-between;
        padding: 12px 15px;
        background-color: var(--bg-color);
        border-top: 1px solid var(--border-color);
    }

    /* Date range selection styles */
    .date-range-preview {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 15px;
        padding: 12px;
        border-radius: 8px;
        background-color: rgba(43, 130, 195, 0.1);
        border: 1px dashed var(--btn-primary);
    }

    .date-range-preview .date-block {
        padding: 8px 15px;
        background-color: var(--btn-primary);
        color: white;
        border-radius: 6px;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .date-range-arrow {
        font-size: 1.5rem;
        color: var(--btn-primary);
    }

    .payment-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
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
        border-color: var(--selected-border);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    .payment-logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        border-radius: 8px;
        padding: 8px;
        background: white;
    }

    .bank-logo {
        background: #f8f9fa;
    }

    /* Step indicator */
    .booking-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }

    .booking-steps::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 20px;
        right: 20px;
        height: 2px;
        background-color: var(--border-color);
        z-index: 1;
    }

    .step {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: var(--text-color);
        position: relative;
        z-index: 2;
    }

    .step.active {
        background-color: var(--btn-primary);
        color: white;
    }

    .step-label {
        position: absolute;
        top: 35px;
        white-space: nowrap;
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Dark mode visibility enhancements */
    [data-theme="dark"] h3, 
    [data-theme="dark"] h4,
    [data-theme="dark"] .filter-title,
    [data-theme="dark"] .step-label {
        color: #f8fafc !important;
    }
    
    [data-theme="dark"] label,
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .step-title {
        color: #e2e8f0 !important;
    }
    
    [data-theme="dark"] .room-card {
        border-color: #334155;
    }
    
    [data-theme="dark"] .room-card-body h4,
    [data-theme="dark"] .room-card-body p {
        color: #e2e8f0 !important;
    }
    
    [data-theme="dark"] .room-card-body .price {
        color: #38bdf8 !important;
    }
    
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: #1e293b;
        border-color: #475569;
        color: #e2e8f0;
    }
    
    [data-theme="dark"] .form-control:focus,
    [data-theme="dark"] .form-select:focus {
        border-color: #38bdf8;
    }
    
    [data-theme="dark"] .payment-card,
    [data-theme="dark"] .date-range-preview {
        background-color: #1e293b;
        border-color: #334155;
    }
    
    [data-theme="dark"] .payment-card.selected {
        border-color: #38bdf8;
        background-color: rgba(56, 189, 248, 0.1);
    }
    
    [data-theme="dark"] .payment-card-title,
    [data-theme="dark"] .summary-item-label,
    [data-theme="dark"] .summary-item-value {
        color: #e2e8f0 !important;
    }
    
    [data-theme="dark"] .btn {
        color: #f8fafc !important;
    }
    
    [data-theme="dark"] .btn-outline-primary {
        color: #38bdf8 !important;
    }
    
    [data-theme="dark"] .btn-outline-primary:hover {
        color: #f8fafc !important;
    }
</style>

<section class="py-5" style="background: var(--bg-color); color: var(--text-color);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0" style="color: var(--heading-color) !important;">🛌 Pesan Kamar Online</h2>
                    <?php if($isLoggedIn): ?>
                    <a href="landing_page.php?section=riwayat" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history me-2"></i>Lihat Riwayat
                    </a>
                    <?php endif; ?> 
                </div>

                <!-- Booking Steps -->
                <div class="booking-steps mb-4">
                    <div class="step active" id="step1">
                        1
                        <span class="step-label" style="color: var(--heading-color) !important;">Pilih Kamar</span>
                    </div>
                    <div class="step" id="step2">
                        2
                        <span class="step-label" style="color: var(--heading-color) !important;">Tanggal & Pembayaran</span>
                    </div>
                    <div class="step" id="step3">
                        3
                        <span class="step-label" style="color: var(--heading-color) !important;">Konfirmasi</span>
                    </div>
                </div>

                <form method="post" id="bookingForm" action="process_booking.php">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Step 1: Room Selection -->
                    <div id="roomSelection">
                        <div class="row g-4 mb-4">
                            <?php foreach ($rooms as $room): 
                                $isAvailable = $room['status'] === 'tersedia';
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="card room-card h-100 <?= $isAvailable ? '' : 'disabled' ?>" 
                                     data-id="<?= $room['id_kamar'] ?>"
                                     data-name="<?= htmlspecialchars($room['nama']) ?>"
                                     data-price="<?= $room['harga'] ?>">                                    <div class="card-body">
                                        <h5 class="card-title" style="color: var(--text-color) !important;"><?= htmlspecialchars($room['nama']) ?></h5>
                                        <p class="text-muted mb-1" style="color: var(--text-color) !important;">Tipe: <?= htmlspecialchars($room['jenis']) ?></p>
                                        <p class="price-tag mb-0">Rp<?= number_format($room['harga'],0,',','.') ?>/malam</p>
                                        <small class="text-muted">Kapasitas: <?= htmlspecialchars($room['bed']) ?></small>
                                        
                                        <?php if (!$isAvailable): ?>
                                            <div class="badge bg-danger mt-2">Tidak Tersedia</div>
                                        <?php else: ?>
                                            <div class="badge bg-success mt-2">Tersedia</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="nextToDateSelection" class="btn btn-primary btn-lg w-100 py-3" disabled>
                            Lanjut ke Pemilihan Tanggal
                        </button>
                    </div>

                    <!-- Step 2: Date Selection and Payment -->
                    <div id="dateSelection" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="mb-4">Tanggal & Pembayaran</h4>
                                
                                <div class="calendar-wrapper">
                                    <div class="calendar-header">
                                        <i class="bi bi-calendar-week"></i>
                                        <span>Pilih Tanggal Menginap</span>
                                    </div>
                                    
                                    <div class="calendar-body">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="checkin-date">📅 Tanggal Check-in</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="bi bi-calendar-check"></i>
                                                    </span>
                                                    <input type="date" id="checkin-date" name="checkin" class="form-control form-control-lg shadow-sm" 
                                                        required min="<?= date('Y-m-d') ?>" onchange="updateMinCheckoutDate()">
                                                </div>
                                                <small class="text-muted mt-1">Pilih tanggal kedatangan Anda</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="checkout-date">📅 Tanggal Check-out</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="bi bi-calendar-x"></i>
                                                    </span>
                                                    <input type="date" id="checkout-date" name="checkout" class="form-control form-control-lg shadow-sm" 
                                                        required onchange="calculateStayDetails()">
                                                </div>
                                                <small class="text-muted mt-1">Pilih tanggal kepulangan Anda</small>
                                            </div>
                                        </div>
                                        
                                        <div class="date-range-preview" id="dateRangePreview" style="display:none">
                                            <div class="date-block" id="checkinDisplay">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                <span></span>
                                            </div>
                                            <span class="date-range-arrow">→</span>
                                            <div class="date-block" id="checkoutDisplay">
                                                <i class="bi bi-box-arrow-right me-2"></i>
                                                <span></span>
                                            </div>
                                            <div class="ms-auto fw-bold">
                                                <span id="nightsDisplay">0</span> malam
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="calendar-footer">
                                        <div id="dateSummary" class="d-flex align-items-center">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <div>
                                                Total: <strong class="ms-2 fs-5">Rp <span id="pricePreview">0</span></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <!-- Payment Options (Now Embedded in Date Selection) -->
                                <h4 class="mb-4">Pilih Metode Pembayaran</h4>
                                
                                <!-- E-Wallet Options -->
                                <h5 class="mb-3">E-Wallet</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('gopay')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/220px-Gopay_logo.svg.png" alt="GoPay" class="payment-logo">
                                            <div>
                                                <h5>GoPay</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="gopay" class="d-none" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('ovo')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Logo_ovo_purple.svg/2560px-Logo_ovo_purple.svg.png" alt="OVO" class="payment-logo">
                                            <div>
                                                <h5>OVO</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="ovo" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('dana')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg" alt="DANA" class="payment-logo">
                                            <div>
                                                <h5>DANA</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="dana" class="d-none">
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Transfer Options -->
                                <h5 class="mb-3">Transfer Bank</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('bca')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/2560px-Bank_Central_Asia.svg.png" alt="BCA" class="payment-logo bank-logo">
                                            <div>
                                                <h5>BCA</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="bca" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('mandiri')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" alt="Mandiri" class="payment-logo bank-logo">
                                            <div>
                                                <h5>Mandiri</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="mandiri" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('bni')">
                                            <img src="https://upload.wikimedia.org/wikipedia/id/thumb/5/55/BNI_logo.svg/1200px-BNI_logo.svg.png" alt="BNI" class="payment-logo bank-logo">
                                            <div>
                                                <h5>BNI</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="bni" class="d-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                        <div class="d-flex justify-content-between">
                            <button type="button" id="backToRoomSelection" class="btn btn-outline-secondary btn-lg py-3" style="width: 48%; color:rgb(0, 0, 0);">
                                Kembali
                            </button>
                            <button type="button" id="nextToSummary" class="btn btn-primary btn-lg py-3" style="width: 48%;" disabled>
                                Lanjut ke Konfirmasi
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Booking Summary -->
                    <div id="bookingSummary"  style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="mb-4"></h4>
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h5>Detail Kamar</h5>
                                        <div id="selectedRoomDetails" class="mb-3">
                                            <!-- Room details will be populated dynamically -->
                                        </div>
                                        
                                        <h5>Tanggal Menginap</h5>
                                        <div class="mb-3">
                                            <p><strong>Check-in:</strong> <span id="summaryCheckin"></span></p>
                                            <p><strong>Check-out:</strong> <span id="summaryCheckout"></span></p>
                                            <p><strong>Durasi:</strong> <span id="summaryDuration"></span> malam</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5>Metode Pembayaran</h5>
                                        <div id="selectedPaymentMethod" class="mb-3">
                                            <!-- Payment method will be populated dynamically -->
                                        </div>
                                        
                                        <h5>Total Biaya</h5>
                                        <div class="mb-3">
                                            <p><strong>Harga Kamar:</strong> <span id="summaryRoomPrice"></span>/malam</p>
                                            <p><strong>Total Pembayaran:</strong></p>
                                            <h3 class="text-primary" id="summaryTotalPrice"></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                          <div class="d-flex justify-content-between">
                            <button type="button" id="backToPaymentSelection" class="btn btn-outline-secondary btn-lg py-3" style="width: 48%; color: #000000;">
                                Kembali
                            </button>
                            <button type="submit" id="confirmBooking" class="btn btn-primary btn-lg py-3" style="width: 48%;">
                                💳 Konfirmasi & Bayar
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="kamar_dipilih" id="selectedRooms">
                    <input type="hidden" name="payment_method" id="selectedPaymentMethod">
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Tambahkan theme-aware style untuk tombol dan elemen summary -->
<style>
    .btn-outline-secondary {
        background: transparent !important;
        color: var(--text-color) !important;
        border: 2px solid var(--border-color) !important;
    }
    .btn-outline-secondary:hover {
        background: var(--border-color) !important;
        color: var(--heading-color) !important;
    }
    .card, .room-card, .payment-card, .calendar-wrapper {
        background: var(--card-bg) !important;
        color: var(--text-color) !important;
        border-color: var(--border-color) !important;
    }
    .form-control, .form-select {
        background: var(--input-bg) !important;
        color: var(--text-color) !important;
        border-color: var(--input-border) !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--btn-primary) !important;
    }
    .summary-item-label, .summary-item-value, .card-title, .price-tag, .step-label {
        color: var(--text-color) !important;
    }
    .text-primary, h3.text-primary, .btn-primary {
        color: #fff !important;
        background: var(--btn-primary) !important;
        border-color: var(--btn-primary) !important;
    }
    .btn-primary:hover {
        background: var(--btn-hover) !important;
        border-color: var(--btn-hover) !important;
    }
    .alert-info {
        background: rgba(43,130,195,0.08) !important;
        color: var(--text-color) !important;
        border-color: var(--btn-primary) !important;
    }
    .swal2-modern-modal, .swal2-modern-html {
        background: var(--card-bg,#fff) !important;
        color: var(--text-color,#222) !important;
    }
    [data-theme="dark"] .swal2-modern-modal, [data-theme="dark"] .swal2-modern-html {
        background: #1e293b !important;
        color: #e2e8f0 !important;
    }
</style>
<script>
// Theme handling
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
window.addEventListener('storage', (e) => {
    if (e.key === 'theme') {
        document.documentElement.setAttribute('data-theme', e.newValue);
    }
});
</script>
<!-- Script untuk pemesanan kamar -->
<script>
    // Initialize variables
    let selectedRooms = [];
    let selectedRoom = null;
    let selectedPayment = null;
    let totalNights = 0;
    let totalPrice = 0;
    
    // Check for room selection from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedRoomId = urlParams.get('selected_room');
    
    // Function to select a room
    function selectRoom(roomId) {
        const card = document.querySelector(`.room-card[data-id="${roomId}"]`);
        if (card && !card.classList.contains('disabled')) {
            const roomName = card.getAttribute('data-name');
            const roomPrice = card.getAttribute('data-price');
            
            // Clear previous selections
            document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
            
            // Select this room
            selectedRooms = [roomId];
            card.classList.add('selected');
            selectedRoom = {
                id: roomId,
                name: roomName,
                price: roomPrice
            };
            
            // Update hidden input
            document.getElementById('selectedRooms').value = selectedRooms.join(',');
            document.getElementById('nextToDateSelection').removeAttribute('disabled');
            
            // Scroll to the next section button for better UX
            document.getElementById('nextToDateSelection').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the selected room with a pulse animation
            const selectedCard = document.querySelector(`.room-card[data-id="${preselectedRoomId}"]`);
            if (selectedCard) {
                selectedCard.style.animation = 'pulse 1.5s';
                selectedCard.style.boxShadow = '0 0 0 5px var(--selected-border)';
                setTimeout(() => {
                    selectedCard.style.boxShadow = '';
                }, 2000);
            }
            
            console.log('Selected rooms:', selectedRooms);
        }
    }
    
    // Room selection click handler
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', () => {
            if (card.classList.contains('disabled')) return;
            const roomId = card.getAttribute('data-id');
            selectRoom(roomId);
        });
    });

    // Date validation and calculation
    function validateDates() {
        const checkin = document.querySelector('input[name="checkin"]').value;
        const checkout = document.querySelector('input[name="checkout"]').value;
        
        if (!checkin || !checkout) return false;
        
        const date1 = new Date(checkin);
        const date2 = new Date(checkout);
        
        if (date1 >= date2) return false;
        
        // Calculate number of nights
        const diffTime = Math.abs(date2 - date1);
        totalNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Calculate total price
        if (selectedRoom) {
            totalPrice = selectedRoom.price * totalNights;
        }
        
        return true;
    }    // Initialize the page - select the room from URL parameter if available
    document.addEventListener('DOMContentLoaded', () => {
        if (preselectedRoomId) {
            selectRoom(preselectedRoomId);
            
            // Also automatically scroll to the room selection area
            document.getElementById('roomSelection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Highlight the selected room with a pulse animation
            const selectedCard = document.querySelector(`.room-card[data-id="${preselectedRoomId}"]`);
            if (selectedCard) {
                selectedCard.style.animation = 'pulse 1.5s';
                selectedCard.style.boxShadow = '0 0 0 5px var(--selected-border)';
                setTimeout(() => {
                    selectedCard.style.boxShadow = '';
                }, 2000);
            }
        }
    });
    
    // Payment method selection
    function selectPayment(method) {
        // Reset all selections
        document.querySelectorAll('.payment-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Select this payment method
        const selectedCard = document.querySelector(`[value="${method}"]`).closest('.payment-card');
        selectedCard.classList.add('selected');
        document.querySelector(`[value="${method}"]`).checked = true;
        
        selectedPayment = method;
        document.getElementById('selectedPaymentMethod').value = method; // This line is important
        document.getElementById('nextToSummary').removeAttribute('disabled');
        
        console.log('Selected payment method:', selectedPayment);
    }    // Update booking summary
    function updateSummary() {
        // Room details
        document.getElementById('selectedRoomDetails').innerHTML = `
            <p><strong>Nama Kamar:</strong> ${selectedRoom.name}</p>
        `;
        
        // Dates
        const checkin = document.querySelector('input[name="checkin"]').value;
        const checkout = document.querySelector('input[name="checkout"]').value;
        
        const formattedCheckin = new Date(checkin).toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        
        const formattedCheckout = new Date(checkout).toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        
        document.getElementById('summaryCheckin').textContent = formattedCheckin;
        document.getElementById('summaryCheckout').textContent = formattedCheckout;
        document.getElementById('summaryDuration').textContent = totalNights;
        
        // Ensure we have valid price data
        if (!selectedRoom || !selectedRoom.price || !totalNights) {
            console.error('Missing price data:', { selectedRoom, totalNights });
            return;
        }
        
        // Price - with proper formatting
        const roomPrice = parseInt(selectedRoom.price);
        totalPrice = roomPrice * totalNights;
        
        document.getElementById('summaryRoomPrice').textContent = 'Rp' + roomPrice.toLocaleString('id-ID');
        document.getElementById('summaryTotalPrice').textContent = 'Rp' + totalPrice.toLocaleString('id-ID');
          // Payment method
        const paymentMethodName = document.querySelector(`[value="${selectedPayment}"]`)
            .closest('.payment-card').querySelector('h5').textContent;
        
        document.getElementById('selectedPaymentMethod').innerHTML = `
            <p><strong style="color:#FFFFFF;">Metode:</strong> <span style="color:#FFFFFF;">${paymentMethodName}</span></p>
        `;
        
        console.log('Updated summary with total price:', totalPrice);
    }

    // Navigation between steps
    document.getElementById('nextToDateSelection').addEventListener('click', function() {
        document.getElementById('roomSelection').style.display = 'none';
        document.getElementById('dateSelection').style.display = 'block';
        
        // Update step indicators
        document.getElementById('step1').classList.remove('active');
        document.getElementById('step2').classList.add('active');
    });

    document.getElementById('backToRoomSelection').addEventListener('click', function() {
        document.getElementById('dateSelection').style.display = 'none';
        document.getElementById('roomSelection').style.display = 'block';
        
        // Update step indicators
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step1').classList.add('active');
    });    // Handle date input change
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (validateDates()) {
                // Check if payment method is also selected
                if (selectedPayment) {
                    document.getElementById('nextToSummary').removeAttribute('disabled');
                }
            } else {
                document.getElementById('nextToSummary').setAttribute('disabled', 'disabled');
            }
        });
    });

    // Add click handler to show message if dates not selected
    document.querySelectorAll('.payment-card').forEach(card => {
        card.addEventListener('click', function() {
            const checkin = document.querySelector('input[name="checkin"]').value;
            const checkout = document.querySelector('input[name="checkout"]').value;
            
            if (!checkin || !checkout) {
                Swal.fire({
                    title: 'Perhatian',
                    text: 'Silahkan pilih tanggal check-in dan check-out terlebih dahulu',
                    icon: 'warning',
                    confirmButtonColor: '#2b82c3'
                });
            }
        });
    });

    document.getElementById('nextToSummary').addEventListener('click', function() {
        document.getElementById('dateSelection').style.display = 'none';
        document.getElementById('bookingSummary').style.display = 'block';
        
        updateSummary();
        
        // Update step indicators
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step3').classList.add('active');
    });

    document.getElementById('backToPaymentSelection').addEventListener('click', function() {
        document.getElementById('bookingSummary').style.display = 'none';
        document.getElementById('dateSelection').style.display = 'block';
        
        // Update step indicators
        document.getElementById('step3').classList.remove('active');
        document.getElementById('step2').classList.add('active');
    });

    // Form submission with SweetAlert confirmation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedRoomsValue = document.getElementById('selectedRooms').value;
        const checkin = document.querySelector('input[name="checkin"]').value;
        const checkout = document.querySelector('input[name="checkout"]').value;
        const paymentMethod = selectedPayment;

        // Final validation
        if (!selectedRoomsValue) {
            Swal.fire('Error', 'Silakan pilih kamar terlebih dahulu', 'error');
            return;
        }
        if (!checkin || !checkout) {
            Swal.fire('Error', 'Tanggal check-in dan check-out harus diisi', 'error');
            return;
        }
        if (!paymentMethod) {
            Swal.fire('Error', 'Silakan pilih metode pembayaran', 'error');
            return;
        }
        // Kirim data ke backend sesuai field tabel transaksi:
        // id_kamar = selectedRoomsValue (hanya satu kamar)
        // tgl_checkin = checkin
        // tgl_checkout = checkout
        // metode_pembayaran = paymentMethod
        // totalharga = totalPrice
        // id_user = dari session (diambil backend)
        // status = 'pending' atau 'siap digunakan'
        // jenis_booking = 'online'
        // Show SweetAlert with detailed booking information first
        let roomName = selectedRoom.name;
        let roomPrice = parseInt(selectedRoom.price).toLocaleString('id-ID');
        let totalPriceFormatted = totalPrice.toLocaleString('id-ID');
        let paymentMethodName = getPaymentMethodName(paymentMethod);
        Swal.fire({
            title: '<span style="font-size:1.5rem;font-weight:700;letter-spacing:0.5px;">Konfirmasi Pemesanan</span>',
            html: `
                <div class="booking-modal-modern">
                    <div class="modal-header-modern mb-3">
                        <i class="bi bi-receipt-cutoff text-primary" style="font-size:2.2rem;"></i>
                        <span class="ms-2 fw-bold" style="font-size:1.2rem;">Detail Pemesanan</span>
                    </div>
                    <div class="modal-body-modern">
                        <div class="row g-2 mb-2">
                            <div class="col-6 text-start"><span class="modal-label-modern">Kamar</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">${roomName}</span></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6 text-start"><span class="modal-label-modern">Check-in</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">${new Date(checkin).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</span></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6 text-start"><span class="modal-label-modern">Check-out</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">${new Date(checkout).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</span></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6 text-start"><span class="modal-label-modern">Durasi</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">${totalNights} malam</span></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6 text-start"><span class="modal-label-modern">Metode</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">${paymentMethodName}</span></div>
                        </div>
                        <hr class="my-2">
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-6 text-start"><span class="modal-label-modern">Harga/malam</span></div>
                            <div class="col-6 text-end"><span class="modal-value-modern">Rp ${roomPrice}</span></div>
                        </div>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-6 text-start fw-bold" style="font-size:1.1rem;">Total</div>
                            <div class="col-6 text-end fw-bold text-primary" style="font-size:1.2rem;">Rp ${totalPriceFormatted}</div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0 py-2 px-3" style="font-size:0.95rem;">
                            Dengan mengklik <b>Lanjut ke Pembayaran</b>, Anda menyetujui <a href="#" style="color:#2b82c3;text-decoration:underline;">syarat & ketentuan</a> pemesanan.
                        </div>
                    </div>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-credit-card"></i> Lanjut ke Pembayaran',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2b82c3',
            reverseButtons: true,
            showCloseButton: true,
            customClass: {
                popup: 'swal2-modern-modal',
                htmlContainer: 'swal2-modern-html'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '<span style="font-size:1.2rem;">Memproses Pemesanan...</span>',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    customClass: {
                        popup: 'swal2-modern-modal'
                    }
                });
                fetch('process_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'kamar_dipilih': selectedRoomsValue, // id_kamar
                        'checkin': checkin, // tgl_checkin
                        'checkout': checkout, // tgl_checkout
                        'payment_method': paymentMethod, // metode_pembayaran
                        'total_harga': totalPrice // totalharga
                    }),
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        // Show beautiful SweetAlert with booking details
                        Swal.fire({
                            icon: 'success',
                            title: '<span style="font-size:1.5rem;font-weight:700;">Pemesanan Berhasil!</span>',
                            html: `
                                <div class="text-center mb-4 swal2-modern-html">
                                    Pemesanan Anda berhasil! Silakan cek riwayat pemesanan Anda.
                                </div>
                             `,                            confirmButtonText: '<i class="bi bi-clock-history"></i> Lihat Riwayat',
                            confirmButtonColor: '#2b82c3',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            window.location.href = 'landing_page.php?section=riwayat';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Pemesanan gagal. Silakan coba lagi.',
                            confirmButtonColor: '#d33'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: '<span style="font-size:1.2rem;">Terjadi Kesalahan</span>',
                        text: error.message || 'Gagal terhubung ke server. Silakan coba lagi.',
                        confirmButtonColor: '#d33',
                        customClass: {
                            popup: 'swal2-modern-modal'
                        }
                    });
                });
            }
        });
    });
    
    // Helper function to get payment method name
    function getPaymentMethodName(method) {
        const methods = {
            'gopay': 'GoPay',
            'ovo': 'OVO',
            'dana': 'DANA',
            'bca': 'Bank BCA',
            'mandiri': 'Bank Mandiri',
            'bni': 'Bank BNI'
        };
        
        return methods[method] || ucfirst(method);
    }
    
    // Helper function to capitalize first letter
    function ucfirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Update minimum date for checkout based on checkin date
    function updateMinCheckoutDate() {
        const checkinDate = document.getElementById('checkin-date').value;
        const checkoutInput = document.getElementById('checkout-date');
        
        if (checkinDate) {
            // Set minimum checkout date to be the day after checkin
            const nextDay = new Date(checkinDate);
            nextDay.setDate(nextDay.getDate() + 1);
            const nextDayFormatted = nextDay.toISOString().split('T')[0];
            
            checkoutInput.min = nextDayFormatted;
            
            // If current checkout date is now invalid, update it
            if (checkoutInput.value && new Date(checkoutInput.value) <= new Date(checkinDate)) {
                checkoutInput.value = nextDayFormatted;
            }
            
            // If checkout already has a value, calculate nights and price
            if (checkoutInput.value) {
                calculateStayDetails();
            }
        }
    }
    
    // Calculate and display stay details (nights and price)
    function calculateStayDetails() {
        const checkinDate = new Date(document.getElementById('checkin-date').value);
        const checkoutDate = new Date(document.getElementById('checkout-date').value);
        
        if (isNaN(checkinDate) || isNaN(checkoutDate)) return;
        
        if (checkoutDate <= checkinDate) {
            // Invalid date range
            document.getElementById('dateRangePreview').style.display = 'none';
            document.getElementById('nextToSummary').setAttribute('disabled', 'disabled');
            return;
        }
        
        // Calculate number of nights
        const diffTime = Math.abs(checkoutDate - checkinDate);
        totalNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Calculate price
        if (selectedRoom) {
            totalPrice = selectedRoom.price * totalNights;
              // Format dates for display
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            const formattedCheckin = checkinDate.toLocaleDateString('id-ID', options);
            const formattedCheckout = checkoutDate.toLocaleDateString('id-ID', options);
            
            // Update the displayed dates in the preview
            document.querySelector('#checkinDisplay span').textContent = formattedCheckin;
            document.querySelector('#checkoutDisplay span').textContent = formattedCheckout;
            document.getElementById('nightsDisplay').textContent = totalNights;
            
            // Update the summary display with formatted price
            document.getElementById('pricePreview').textContent = parseInt(totalPrice).toLocaleString('id-ID');
            
            // Show the date range preview
            document.getElementById('dateRangePreview').style.display = 'flex';
            
            // Enable the next button if payment is selected
            if (selectedPayment) {
                document.getElementById('nextToSummary').removeAttribute('disabled');
            }
        }
    }
</script>
<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire('Berhasil', '<?= $_SESSION['success'] ?>', 'success');
</script>
<?php unset($_SESSION['success']); endif; ?>
<!-- Custom CSS SweetAlert Modern (harus di dalam <script>) -->
<script>
const swalModernStyle = document.createElement('style');
swalModernStyle.innerHTML = `
.swal2-modern-modal {
    border-radius: 18px !important;
    box-shadow: 0 8px 32px rgba(43,130,195,0.12) !important;
    background: var(--card-bg,#fff) !important;
    color: var(--text-color,#222) !important;
    padding: 0 0 1.5rem 0 !important;
    max-width: 410px !important;
}
.swal2-modern-html {
    font-size: 1.05rem !important;
    color: var(--text-color,#222) !important;
    padding: 0 0.5rem !important;
}
.booking-modal-modern .modal-header-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid var(--border-color,#e2e8f0);
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}
.booking-modal-modern .modal-label-modern {
    font-weight: 500;
    color: var(--heading-color,#1a365d);
    font-size: 1rem;
}
.booking-modal-modern .modal-value-modern {
    font-weight: 600;
    color: var(--btn-primary,#2b82c3);
    font-size: 1rem;
}
.booking-modal-modern .modal-body-modern {
    padding: 0.2rem 0.1rem 0.1rem 0.1rem;
}
.booking-modal-modern .modal-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--btn-primary,#2b82c3);
    margin-top: 0.5rem;
}
.swal2-modern-invoice {
    background: var(--card-bg,#fff);
    border-radius: 10px;
    border: 1px solid var(--border-color,#e2e8f0);
    padding: 0.7rem 0.8rem 0.5rem 0.8rem;
    margin-bottom: 0.5rem;
    font-size: 1.01rem;
}
[data-theme="dark"] .swal2-modern-modal {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .swal2-modern-html {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .booking-modal-modern .modal-label-modern {
    color: #f8fafc !important;
}
[data-theme="dark"] .booking-modal-modern .modal-value-modern {
    color: #38bdf8 !important;
}
[data-theme="dark"] .swal2-modern-invoice {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
`;
document.head.appendChild(swalModernStyle);
</script>

<!-- Footer Modern Minimalist -->
<footer style="background: var(--card-bg); color: var(--text-color); border-top: 1px solid var(--border-color); margin-top: 3rem; padding: 2rem 0 1rem 0; text-align: center; font-size: 1rem; letter-spacing: 0.02em; width: 100vw; left: 50%; right: 0; transform: translateX(-50%); position: relative;">
  <div style="max-width: 100%; margin: 0 auto;">
    <span style="font-weight: 500;">&copy; <?= date('Y') ?> Sistem Hotel</span>
    <span style="color: var(--border-color); margin: 0 8px;">|</span>
    <span style="font-size: 0.97em; color: var(--text-color);">Dibuat dengan <i class="bi bi-heart-fill" style="color:#e25555;"></i> untuk kenyamanan Anda</span>
  </div>
</footer>
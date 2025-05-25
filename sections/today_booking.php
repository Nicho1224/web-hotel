<?php
// This file is for same-day bookings where check-in date is fixed to today
// filepath: c:\xampp\htdocs\project3\NiceAdmin\sections\today_booking.php

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
$roomsStmt = $conn->prepare("SELECT * FROM kamar ORDER BY harga ASC");
$roomsStmt->execute();
$rooms = $roomsStmt->fetchAll();

// Get room types for filtering
$typeStmt = $conn->prepare("SELECT DISTINCT jenis FROM kamar WHERE status = 'tersedia' ORDER BY jenis");
$typeStmt->execute();
$roomTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

// Get price range for the filter
$priceRangeStmt = $conn->prepare("SELECT MIN(harga) as min_price, MAX(harga) as max_price FROM kamar WHERE status = 'tersedia'");
$priceRangeStmt->execute();
$priceRange = $priceRangeStmt->fetch();

// Set default min and max price
$minPrice = $priceRange['min_price'] ?? 0;
$maxPrice = $priceRange['max_price'] ?? 10000000;

// Get today's date for checkin (fixed)
$today = date('Y-m-d');
// Get tomorrow's date for minimum checkout
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Function to calculate number of days between dates
function calculateDays($checkin, $checkout) {
    $checkInDate = new DateTime($checkin);
    $checkOutDate = new DateTime($checkout);
    $interval = $checkInDate->diff($checkOutDate);
    return $interval->days;
}
?>

<style>
    /* Theme-aware styles */
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
        --highlight-bg: #f8f9fa;
        --highlight-border: #ffc107;
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
        --highlight-bg: #1e293b;
        --highlight-border: #ffc107;
    }

    /* Room card styles */
    .room-card {
        background-color: var(--card-bg);
        border: 2px solid var(--border-color);
        box-shadow: 0 4px 6px var(--card-shadow);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .room-card:hover {
        transform: translateY(-5px);
    }

    .room-card.disabled {
        opacity: 0.65;
        cursor: not-allowed;
        background-color: #f5f5f5;
        border-color: #ddd;
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

    /* Today booking highlight */
    .today-booking-badge {
        position: absolute;
        top: -10px;
        right: 10px;
        background-color: #ffc107;
        color: #000;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.8rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1;
    }

    .today-booking-info {
        background-color: var(--highlight-bg);
        border: 1px dashed var(--highlight-border);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    /* Read-only input styling */
    .form-control[readonly] {
        background-color: var(--highlight-bg);
        border: 1px solid var(--highlight-border);
        font-weight: bold;
    }

    /* Calendar styles */
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

    /* Payment method cards */
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

    /* Styling untuk kamar tidak tersedia */
    .room-card.disabled {
        opacity: 0.65;
        cursor: not-allowed;
        background-color: #f5f5f5;
        border-color: #ddd;
    }

    .not-available-badge {
        position: absolute;
        top: -10px;
        right: 10px;
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.8rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1;
    }
</style>

<section class="py-5" style="background: var(--bg-color); color: var(--text-color);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0" style="color: var(--heading-color) !important;">🔥 Pesan Kamar Hari Ini</h2>
                    <?php if($isLoggedIn): ?>
                    <a href="landing_page.php?section=riwayat" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history me-2"></i>Lihat Riwayat
                    </a>
                    <?php endif; ?> 
                </div>

                <div class="today-booking-info">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-circle-fill me-2" style="color: #ffc107; font-size: 1.5rem;"></i>
                        <div>
                            <h5 class="mb-1" style="color: var(--heading-color) !important;">Booking Express untuk Hari Ini</h5>
                            <p class="mb-0" style="color: var(--text-color) !important;">Check-in tanggal <strong><?= date('d F Y', strtotime($today)) ?></strong> dan minimal menginap 1 malam</p>
                        </div>
                    </div>
                </div>

                <!-- Booking Steps -->
                <div class="booking-steps mb-4">
                    <div class="step active" id="step1">
                        1
                        <span class="step-label" style="color: var(--heading-color) !important;">Pilih Kamar</span>
                    </div>
                    <div class="step" id="step2">
                        2
                        <span class="step-label" style="color: var(--heading-color) !important;">Durasi & Pembayaran</span>
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
                            <?php foreach ($rooms as $room): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card room-card h-100 <?= $room['status'] !== 'tersedia' ? 'disabled' : '' ?>" 
                                     data-id="<?= $room['id_kamar'] ?>"
                                     data-name="<?= htmlspecialchars($room['nama']) ?>"
                                     data-price="<?= $room['harga'] ?>"
                                     data-status="<?= $room['status'] ?>">
                                    <?php if($room['status'] === 'tersedia'): ?>
                                    <div class="today-booking-badge">
                                        <i class="bi bi-lightning-fill"></i> Tersedia Hari Ini
                                    </div>
                                    <?php else: ?>
                                    <div class="not-available-badge">
                                        <i class="bi bi-x-circle"></i> Tidak Tersedia
                                    </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title" style="color: var(--text-color) !important;"><?= htmlspecialchars($room['nama']) ?></h5>
                                        <p class="text-muted mb-1" style="color: var(--text-color) !important;">Tipe: <?= htmlspecialchars($room['jenis']) ?></p>
                                        <p class="price-tag mb-0">Rp<?= number_format($room['harga'],0,',','.') ?>/malam</p>
                                        <small class="text-muted">Kapasitas: <?= htmlspecialchars($room['bed']) ?></small>
                                        <?php if($room['status'] === 'tersedia'): ?>
                                        <div class="badge bg-success mt-2">Check-in Hari Ini</div>
                                        <?php else: ?>
                                        <div class="badge bg-secondary mt-2">Tidak Tersedia</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="nextToDateSelection" class="btn btn-primary btn-lg w-100 py-3" disabled>
                            Lanjut ke Pemilihan Durasi
                        </button>
                    </div>

                    <!-- Step 2: Duration Selection and Payment -->
                    <div id="dateSelection" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="mb-4">Durasi Menginap & Pembayaran</h4>
                                
                                <div class="calendar-wrapper">
                                    <div class="calendar-header">
                                        <i class="bi bi-calendar-week"></i>
                                        <span>Durasi Menginap</span>
                                    </div>
                                    
                                    <div class="calendar-body">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="checkin-date">📅 Check-in (Hari Ini)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-warning text-dark">
                                                        <i class="bi bi-calendar-check"></i>
                                                    </span>
                                                    <input type="date" id="checkin-date" name="checkin" class="form-control form-control-lg shadow-sm" 
                                                        value="<?= $today ?>" readonly>
                                                </div>
                                                <small class="text-muted mt-1">Check-in harus dilakukan hari ini</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="checkout-date">📅 Tanggal Check-out</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="bi bi-calendar-x"></i>
                                                    </span>
                                                    <input type="date" id="checkout-date" name="checkout" class="form-control form-control-lg shadow-sm" 
                                                        required min="<?= $tomorrow ?>" value="<?= $tomorrow ?>" onchange="calculateStayDetails()">
                                                </div>
                                                <small class="text-muted mt-1">Minimal menginap 1 malam</small>
                                            </div>
                                        </div>
                                        
                                        <div class="date-range-preview" id="dateRangePreview">
                                            <div class="date-block" id="checkinDisplay">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                <span><?= date('d M Y', strtotime($today)) ?></span>
                                            </div>
                                            <span class="date-range-arrow">→</span>
                                            <div class="date-block" id="checkoutDisplay">
                                                <i class="bi bi-box-arrow-right me-2"></i>
                                                <span><?= date('d M Y', strtotime($tomorrow)) ?></span>
                                            </div>
                                            <div class="ms-auto fw-bold">
                                                <span id="nightsDisplay">1</span> malam
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
                                
                                <!-- Payment Options with Cash & Card first (for same-day bookings) -->
                                <h4 class="mb-4">Pilih Metode Pembayaran</h4>

                                <!-- Cash & Card Options (Prioritized for same-day) -->
                                <h5 class="mb-3">Pembayaran di Tempat (Recommended)</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('cash')">
                                            <div class="payment-logo d-flex align-items-center justify-content-center">
                                                <i class="bi bi-cash-stack" style="font-size: 2rem; color: #198754;"></i>
                                            </div>
                                            <div>
                                                <h5>Cash</h5>
                                                <small class="text-success">Bayar saat check-in</small>
                                            </div>
                                            <input type="radio" name="payment_method" value="cash" class="d-none" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('card')">
                                            <div class="payment-logo d-flex align-items-center justify-content-center">
                                                <i class="bi bi-credit-card" style="font-size: 2rem; color: #0d6efd;"></i>
                                            </div>
                                            <div>
                                                <h5>Kartu Debit/Kredit</h5>
                                                <small class="text-primary">Bayar saat check-in</small>
                                            </div>
                                            <input type="radio" name="payment_method" value="card" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('qris')">
                                            <div class="payment-logo d-flex align-items-center justify-content-center">
                                                <i class="bi bi-qr-code" style="font-size: 2rem; color: #6f42c1;"></i>
                                            </div>
                                            <div>
                                                <h5>QRIS</h5>
                                                <small class="text-primary">Scan QR saat check-in</small>
                                            </div>
                                            <input type="radio" name="payment_method" value="qris" class="d-none">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- E-Wallet Options -->
                                <h5 class="mb-3">E-Wallet</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="payment-card" onclick="selectPayment('gopay')">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/220px-Gopay_logo.svg.png" alt="GoPay" class="payment-logo">
                                            <div>
                                                <h5>GoPay</h5>
                                            </div>
                                            <input type="radio" name="payment_method" value="gopay" class="d-none">
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
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" id="backToRoomSelection" class="btn btn-outline-secondary btn-lg py-3" style="width: 48%; color:rgb(0, 0, 0);">
                                Kembali
                            </button>
                            <button type="button" id="nextToSummary" class="btn btn-primary btn-lg py-3" style="width: 48%;" disabled>
                                Lanjut ke Konfirmasi
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Booking Summary -->
                    <div id="bookingSummary" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h4 class="mb-4">Konfirmasi Pemesanan</h4>
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h5>Detail Kamar</h5>
                                        <div id="selectedRoomDetails" class="mb-3">
                                            <!-- Room details will be populated dynamically -->
                                        </div>
                                        
                                        <h5>Tanggal Menginap</h5>
                                        <div class="mb-3">
                                            <p><strong>Check-in (Hari Ini):</strong> <span id="summaryCheckin"><?= date('d F Y', strtotime($today)) ?></span></p>
                                            <p><strong>Check-out:</strong> <span id="summaryCheckout"></span></p>
                                            <p><strong>Durasi:</strong> <span id="summaryDuration">1</span> malam</p>
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

                                <div class="alert alert-warning mt-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Penting:</strong> Kamar ini akan siap ditempati segera setelah Anda melakukan pembayaran. Harap langsung check-in hari ini.
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
                    <input type="hidden" name="is_today_booking" value="1">
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// Theme handling
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
window.addEventListener('storage', (e) => {
    if (e.key === 'theme') {
        document.documentElement.setAttribute('data-theme', e.newValue);
    }
});

// Initialize variables
let selectedRooms = [];
let selectedRoom = null;
let selectedPayment = null;
let totalNights = 1; // Default to 1 night
let totalPrice = 0;

// Function to select a room
function selectRoom(roomId) {
    const card = document.querySelector(`.room-card[data-id="${roomId}"]`);
    if (card) {
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
        
        // Calculate initial price display
        calculateStayDetails();
    }
}

// Room selection click handler
document.querySelectorAll('.room-card').forEach(card => {
    card.addEventListener('click', () => {
        // Skip if room is not available (has disabled class)
        if (card.classList.contains('disabled')) {
            return;
        }
        
        const roomId = card.getAttribute('data-id');
        selectRoom(roomId);
    });
});

// Calculate stay details (nights and price)
function calculateStayDetails() {
    const checkinDate = new Date(document.getElementById('checkin-date').value);
    const checkoutDate = new Date(document.getElementById('checkout-date').value);
    
    if (isNaN(checkinDate) || isNaN(checkoutDate)) return;
    
    if (checkoutDate <= checkinDate) {
        // Invalid date range
        document.getElementById('nextToSummary').setAttribute('disabled', 'disabled');
        return;
    }
    
    // Calculate number of nights
    const diffTime = Math.abs(checkoutDate - checkinDate);
    totalNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Update nights display
    document.getElementById('nightsDisplay').textContent = totalNights;
    
    // Calculate price
    if (selectedRoom) {
        totalPrice = selectedRoom.price * totalNights;
        
        // Format dates for display
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        const formattedCheckout = checkoutDate.toLocaleDateString('id-ID', options);
        
        // Update the displayed dates in the preview
        document.querySelector('#checkoutDisplay span').textContent = formattedCheckout;
        
        // Update the summary display with formatted price
        document.getElementById('pricePreview').textContent = parseInt(totalPrice).toLocaleString('id-ID');
    }
}

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
    document.getElementById('selectedPaymentMethod').value = method;
    document.getElementById('nextToSummary').removeAttribute('disabled');
}

// Update booking summary
function updateSummary() {
    // Room details
    document.getElementById('selectedRoomDetails').innerHTML = `
        <p><strong>Nama Kamar:</strong> ${selectedRoom.name}</p>
    `;
    
    // Dates
    const checkout = document.querySelector('input[name="checkout"]').value;
    const formattedCheckout = new Date(checkout).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
    
    document.getElementById('summaryCheckout').textContent = formattedCheckout;
    document.getElementById('summaryDuration').textContent = totalNights;
    
    // Price - with proper formatting
    const roomPrice = parseInt(selectedRoom.price);
    totalPrice = roomPrice * totalNights;
    
    document.getElementById('summaryRoomPrice').textContent = 'Rp' + roomPrice.toLocaleString('id-ID');
    document.getElementById('summaryTotalPrice').textContent = 'Rp' + totalPrice.toLocaleString('id-ID');
    
    // Payment method
    const paymentMethodName = getPaymentMethodName(selectedPayment);
    
    document.getElementById('selectedPaymentMethod').innerHTML = `
        <p><strong>Metode:</strong> ${paymentMethodName}</p>
    `;
}

// Helper function to get payment method name
function getPaymentMethodName(method) {
    const methods = {
        'cash': 'Tunai (saat check-in)',
        'card': 'Kartu Debit/Kredit (saat check-in)',
        'qris': 'QRIS (saat check-in)',
        'gopay': 'GoPay',
        'ovo': 'OVO',
        'dana': 'DANA',
        'bca': 'Bank BCA',
        'mandiri': 'Bank Mandiri',
        'bni': 'Bank BNI'
    };
    
    return methods[method] || method.charAt(0).toUpperCase() + method.slice(1);
}

// Navigation between steps
document.getElementById('nextToDateSelection').addEventListener('click', function() {
    document.getElementById('roomSelection').style.display = 'none';
    document.getElementById('dateSelection').style.display = 'block';
    
    // Update step indicators
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');
    
    // Calculate initial stay details
    calculateStayDetails();
});

document.getElementById('backToRoomSelection').addEventListener('click', function() {
    document.getElementById('dateSelection').style.display = 'none';
    document.getElementById('roomSelection').style.display = 'block';
    
    // Update step indicators
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
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

// Handle checkout date change
document.getElementById('checkout-date').addEventListener('change', calculateStayDetails);

// Form submission
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const selectedRoomsValue = document.getElementById('selectedRooms').value;
    const checkin = document.getElementById('checkin-date').value;
    const checkout = document.getElementById('checkout-date').value;
    const paymentMethod = selectedPayment;

    // Final validation
    if (!selectedRoomsValue) {
        Swal.fire('Error', 'Silakan pilih kamar terlebih dahulu', 'error');
        return;
    }
    if (!checkout) {
        Swal.fire('Error', 'Tanggal check-out harus diisi', 'error');
        return;
    }
    if (!paymentMethod) {
        Swal.fire('Error', 'Silakan pilih metode pembayaran', 'error');
        return;
    }

    // Show confirmation
    Swal.fire({
        title: 'Konfirmasi Pemesanan Hari Ini',
        html: `
            <div class="booking-modal-modern">
                <div class="modal-header-modern mb-3">
                    <i class="bi bi-lightning-fill text-warning" style="font-size:2rem;"></i>
                    <span class="ms-2 fw-bold" style="font-size:1.2rem;">Booking Express</span>
                </div>
                <div class="modal-body-modern">
                    <div class="row g-2 mb-2">
                        <div class="col-6 text-start"><span class="modal-label-modern">Kamar</span></div>
                        <div class="col-6 text-end"><span class="modal-value-modern">${selectedRoom.name}</span></div>
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
                        <div class="col-6 text-end"><span class="modal-value-modern">${getPaymentMethodName(paymentMethod)}</span></div>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-6 text-start fw-bold" style="font-size:1.1rem;">Total</div>
                        <div class="col-6 text-end fw-bold text-primary" style="font-size:1.2rem;">Rp ${parseInt(totalPrice).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0 py-2 px-3" style="font-size:0.95rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Kamar harus ditempati <b>hari ini</b>. Silakan langsung ke resepsi untuk check-in.
                    </div>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Konfirmasi Booking',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2b82c3',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses Pemesanan...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit the form data to process_booking.php
            fetch('process_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'kamar_dipilih': selectedRoomsValue,
                    'checkin': checkin,
                    'checkout': checkout,
                    'payment_method': paymentMethod,
                    'total_harga': totalPrice,
                    'is_today_booking': 1
                }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pemesanan Berhasil!',
                        html: `
                            <div class="text-center mb-4">
                                <p>Pemesanan kamar untuk hari ini berhasil!</p>
                                <p>Silakan segera ke resepsi untuk melakukan check-in.</p>
                            </div>
                        `,
                        confirmButtonText: 'Lihat Riwayat',
                        confirmButtonColor: '#2b82c3',
                        allowOutsideClick: false
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
                    title: 'Terjadi Kesalahan',
                    text: 'Gagal terhubung ke server. Silakan coba lagi.',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
});
</script>
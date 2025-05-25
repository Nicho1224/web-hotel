<?php
require 'config.php';

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
?>

<!DOCTYPE html>
<html data-theme="light">
<head>
    <title>Pemesanan Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --heading-color: #38bdf8;
            --card-bg: #1e293b;
            --card-shadow: rgba(0,0,0,0.3);
            --border-color: #334155;
            --price-color: #38bdf8;
            --disabled-opacity: 0.4;
            --selected-border: #38bdf8;
            --input-bg: #1e293b;
            --input-border: #334155;
            --btn-primary: #38bdf8;
            --btn-hover: #0ea5e9;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .container h2 {
            color: var(--heading-color);
        }

        .room-card {
            background-color: var(--card-bg);
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 6px var(--card-shadow);
            transition: all 0.3s ease;
            cursor: pointer;
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

        .card-title {
            color: var(--heading-color);
        }

        .text-muted {
            color: var(--text-color) !important;
            opacity: 0.7;
        }

        .price-tag {
            font-size: 1.25rem;
            color: var(--price-color);
            font-weight: bold;
        }

        .form-control {
            background-color: var (--input-bg);
            border-color: var(--input-border);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--selected-border);
            color: var (--text-color);
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }

        .btn-primary {
            background-color: var(--btn-primary);
            border-color: var(--btn-primary);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--btn-hover);
            border-color: var(--btn-hover);
            transform: translateY(-2px);
        }

        /* Card styles */
        .card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            box-shadow: 0 4px 6px var(--card-shadow);
        }

        /* Form input styles */
        input[type="date"] {
            font-family: inherit;
            padding: 0.75rem;
            border-radius: 8px;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            font-size: 1rem;
        }

        /* Style the calendar icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            background-color: var(--btn-primary);
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            filter: invert(1);
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        /* Focus styles for date inputs */
        input[type="date"]:focus {
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 3px rgba(43, 130, 195, 0.25);
            outline: none;
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

        /* Label styling */
        label {
            color: var(--heading-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Payment method styling */
        .payment-methods {
            display: none; /* Hidden initially */
            margin-top: 20px;
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

        .booking-summary {
            display: none; /* Hidden initially */
            margin-top: 20px;
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
    </style>
</head>
<body>
<div class="container py-5 px-4">    <h2 class="mb-4" style="color: #000000 !important;">🛌 Pesan Kamar Online</h2>

    <!-- Booking Steps -->
    <div class="booking-steps mb-4">
        <div class="step active" id="step1">
            1
            <span class="step-label" style="color: #000000 !important;">Pilih Kamar</span>
        </div>
        <div class="step" id="step2">
            2
            <span class="step-label" style="color: #000000 !important;">Tanggal & Pembayaran</span>
        </div>
        <div class="step" id="step3">
            3
            <span class="step-label" style="color: #000000 !important;">Konfirmasi</span>
        </div>
    </div>

    <form method="post" id="bookingForm">
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
                         data-price="<?= $room['harga'] ?>">                        <div class="card-body">                            <h5 class="card-title" style="color: var(--text-color) !important;"><?= htmlspecialchars($room['nama']) ?></h5>
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
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" id="backToRoomSelection" class="btn btn-outline-secondary btn-lg py-3" style="width: 48%; color: #000000;">
                    Kembali
                </button>
                <button type="button" id="nextToSummary" class="btn btn-primary btn-lg py-3" style="width: 48%;" disabled>
                    Lanjut ke Konfirmasi
                </button>
            </div>
        </div>

        <!-- Step 4: Booking Summary -->
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
                <button type="button" id="backToPaymentSelection" class="btn btn-outline-secondary btn-lg py-3" style="width: 48%;">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize variables
    let selectedRooms = [];
    let selectedRoom = null;
    let selectedPayment = null;
    let totalNights = 0;
    let totalPrice = 0;    // Room selection
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', () => {
            if (card.classList.contains('disabled')) return;
            
            const roomId = card.getAttribute('data-id');
            const roomName = card.getAttribute('data-name');
            const roomPrice = card.getAttribute('data-price');
            
            // Toggle selection
            if (card.classList.contains('selected')) {
                // Deselect this room
                card.classList.remove('selected');
                selectedRooms = selectedRooms.filter(id => id !== roomId);
            } else {
                // Select this room
                card.classList.add('selected');
                selectedRooms.push(roomId);
            }
            
            // Update hidden input
            document.getElementById('selectedRooms').value = selectedRooms.join(',');
            
            // Enable next button if at least one room is selected
            if (selectedRooms.length > 0) {
                document.getElementById('nextToDateSelection').removeAttribute('disabled');
                
                // Use the first selected room for display purposes
                const firstSelectedCard = document.querySelector('.room-card.selected');
                selectedRoom = {
                    id: firstSelectedCard.getAttribute('data-id'),
                    name: firstSelectedCard.getAttribute('data-name'),
                    price: firstSelectedCard.getAttribute('data-price')
                };
            } else {
                document.getElementById('nextToDateSelection').setAttribute('disabled', 'disabled');
                selectedRoom = null;
            }
            
            console.log('Selected rooms:', selectedRooms);
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
        document.getElementById('selectedPaymentMethod').value = method; // This line is important
        document.getElementById('nextToSummary').removeAttribute('disabled');
        
        console.log('Selected payment method:', selectedPayment);
    }

    // Update booking summary    function updateSummary() {
        // Room details - show multiple rooms if selected
        let roomsHTML = '';
        let totalHarga = 0;
        
        // Get all selected room cards
        const selectedCards = document.querySelectorAll('.room-card.selected');
        
        selectedCards.forEach(card => {
            const roomName = card.getAttribute('data-name');
            const roomPrice = parseInt(card.getAttribute('data-price'));
            const roomType = card.querySelector('.text-muted').textContent;
            
            roomsHTML += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong style="color: #000000 !important;">${roomName}</strong><br>
                        <small>${roomType}</small>
                    </div>
                    <span class="badge bg-primary">Rp${roomPrice.toLocaleString('id-ID')}/malam</span>
                </div>
            `;
            
            totalHarga += roomPrice * totalNights;
        });
        
        document.getElementById('selectedRoomDetails').innerHTML = `
            <div class="alert alert-primary mb-3">
                <h6 class="mb-3" style="color: #000000 !important;">Kamar yang Dipilih (${selectedCards.length}):</h6>
                ${roomsHTML}
            </div>
        `;
        
        // Update total price calculation for multiple rooms
        totalPrice = totalHarga;
        
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
        
        // Price - now displays total for all selected rooms
        document.getElementById('summaryRoomPrice').textContent = 'Rp' + 
            (totalPrice / totalNights).toLocaleString('id-ID');
        document.getElementById('summaryTotalPrice').textContent = 'Rp' + 
            totalPrice.toLocaleString('id-ID');
        
        // Payment method
        const paymentMethodName = document.querySelector(`[value="${selectedPayment}"]`)
            .closest('.payment-card').querySelector('h5').textContent;
        
        document.getElementById('selectedPaymentMethod').innerHTML = `
            <p><strong>Metode:</strong> ${paymentMethodName}</p>
        `;
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
    });

    // Handle date input change
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (validateDates()) {
                // Cek juga apakah metode pembayaran sudah dipilih
                if (selectedPayment) {
                    document.getElementById('nextToSummary').removeAttribute('disabled');
                }
            } else {
                document.getElementById('nextToSummary').setAttribute('disabled', 'disabled');
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

    // Form submission with error handling
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

        // Show SweetAlert with detailed booking information first
        let roomName = selectedRoom.name;
        let roomPrice = parseInt(selectedRoom.price).toLocaleString('id-ID');
        let totalPriceFormatted = totalPrice.toLocaleString('id-ID');
        
        Swal.fire({
            title: 'Konfirmasi Pemesanan',
            html: `
                <div class="text-start">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3" style="color:#2b82c3;"><i class="bi bi-building"></i> Detail Kamar</h5>
                            <p><strong>Kamar:</strong> ${roomName}</p>
                            <p><strong>Check-in:</strong> ${new Date(checkin).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</p>
                            <p><strong>Check-out:</strong> ${new Date(checkout).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</p>
                            <p><strong>Durasi:</strong> ${totalNights} malam</p>
                            <div class="alert alert-primary">
                                <div class="d-flex justify-content-between">
                                    <strong>Metode Pembayaran:</strong>
                                    <span>${getPaymentMethodName(paymentMethod)}</span>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h6>Harga/malam:</h6>
                                <span>Rp ${roomPrice}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <h5>Total Pembayaran:</h5>
                                <h4 style="color:#2b82c3;">Rp ${totalPriceFormatted}</h4>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted">Dengan mengklik "Lanjut ke Pembayaran", Anda menyetujui persyaratan pemesanan</p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Lanjut ke Pembayaran',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2b82c3',
            reverseButtons: true,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Memproses Pemesanan...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit the form with fetch API
                fetch('process_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'kamar_dipilih': selectedRoomsValue,
                        'checkin': checkin,
                        'checkout': checkout,
                        'payment_method': paymentMethod
                    }),
                    credentials: 'same-origin' // Important for session cookies
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    Swal.close();
                    
                    if (data.success) {
                        // Show fancy success message with payment info
                        Swal.fire({
                            icon: 'success',
                            title: 'Pemesanan Berhasil!',
                            html: `
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4>Terima Kasih!</h4>
                                    <p>Pemesanan Anda telah berhasil dibuat.</p>
                                    <div class="alert alert-success">
                                        <strong>Invoice ID:</strong> #${data.invoice_id}<br>
                                        <strong>Total:</strong> Rp ${data.total_formatted}<br>
                                        <strong>Metode Pembayaran:</strong> ${getPaymentMethodName(paymentMethod)}
                                    </div>
                                    <p>Silakan lanjutkan ke halaman pembayaran untuk menyelesaikan transaksi</p>
                                </div>
                            `,
                            confirmButtonText: 'Lanjut ke Pembayaran',
                            confirmButtonColor: '#2b82c3',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = `payment_instruction.php?invoice=${data.invoice_id}&method=${paymentMethod}`;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Terjadi kesalahan saat memproses pemesanan',
                            confirmButtonColor: '#d33'
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
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

    // Theme handling
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    window.addEventListener('storage', (e) => {
        if (e.key === 'theme') {
            document.documentElement.setAttribute('data-theme', e.newValue);
        }
    });

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
            document.getElementById('pricePreview').textContent = 
                parseInt(totalPrice).toLocaleString('id-ID');
            
            // Show the date range preview
            document.getElementById('dateRangePreview').style.display = 'flex';
            
            // Enable the next button if payment is selected
            if (selectedPayment) {
                document.getElementById('nextToSummary').removeAttribute('disabled');
            }
        }
    }
</script>
</body>
</html>
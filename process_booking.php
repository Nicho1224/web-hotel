<?php
require 'config.php';
// Logging the process start
debug_log("Process booking started");
debug_log("Session data", $_SESSION);
debug_log("POST data", $_POST);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id_user']) || !isset($_SESSION['lv'])) {
    debug_log("Authentication failed - no session data");
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

try {
    // Validate required fields exist
    if (empty($_POST['kamar_dipilih']) || empty($_POST['checkin']) || empty($_POST['checkout'])) {
        debug_log("Missing required fields");
        throw new Exception('Semua field harus diisi');
    }

    // Get user ID from session
    $user_id = $_SESSION['id_user'];
    debug_log("User ID: $user_id");

    // Get user details
    $stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        debug_log("User not found in database");
        throw new Exception('User tidak ditemukan');
    }

    debug_log("User data retrieved", $user);

    // Validate NIK
    $nik = trim($user['nik']);
    if (empty($nik)) {
        debug_log("NIK empty");
        throw new Exception('NIK Anda belum terisi. Silakan perbarui profil Anda.');
    }

    // Check if tamu exists, create if not
    $cekTamu = $conn->prepare("SELECT * FROM tamu WHERE nik = ?");
    $cekTamu->execute([$nik]);
    $tamu = $cekTamu->fetch();

    if (!$tamu) {
        debug_log("Creating new tamu record");
        $insTamu = $conn->prepare(
            "INSERT INTO tamu (nik, nama, email, alamat) VALUES (?, ?, ?, ?)"
        );
        $insTamu->execute([
            $nik,
            $user['nama'],
            $user['email'],
            $user['alamat']
        ]);
    }

    // Process room selection and validate dates
    $selected_rooms = explode(',', $_POST['kamar_dipilih']);
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];

    debug_log("Selected rooms", $selected_rooms);
    debug_log("Check-in: $checkin, Check-out: $checkout");

    // Validate dates
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $today = new DateTime();

    if ($date1 < $today) {
        debug_log("Check-in date in the past");
        throw new Exception('Tanggal check-in tidak boleh kurang dari hari ini');
    }

    if ($date1 >= $date2) {
        debug_log("Invalid date range");
        throw new Exception('Tanggal check-out harus setelah tanggal check-in');
    }

    $nights = $date2->diff($date1)->days;
    if ($nights <= 0) {
        debug_log("Invalid stay duration");
        throw new Exception('Durasi menginap minimal 1 malam');
    }

    // Start database transaction
    $conn->beginTransaction();
    debug_log("Transaction started");

    $room_details = [];
    $total = 0;
    $invoice_id = null;

    // Process each room
    foreach ($selected_rooms as $room_id) {
        // Verify room exists and is available
        $roomStmt = $conn->prepare("SELECT * FROM kamar WHERE id_kamar = ?");
        $roomStmt->execute([$room_id]);
        $room = $roomStmt->fetch();

        if (!$room) {
            debug_log("Room not found: $room_id");
            throw new Exception("Kamar dengan ID $room_id tidak ditemukan");
        }

        // Check if room status is actually 'tersedia'
        if ($room['status'] !== 'tersedia') {
            debug_log("Room not available: $room_id, status: {$room['status']}");
            throw new Exception("Kamar {$room['nama']} tidak tersedia");
        }

        // Calculate price
        $subtotal = $room['harga'] * $nights;
        $total += $subtotal;

        debug_log("Room details", $room);
        debug_log("Nights: $nights, Subtotal: $subtotal");

        // Insert booking record
        $stmt = $conn->prepare(
            "INSERT INTO transaksi 
             (nik, id_kamar, id_user, tgl_booking, tgl_checkin, tgl_checkout, totalharga, status, jenis_booking, status_kamar_awal)
             VALUES (?, ?, ?, NOW(), ?, ?, ?, 'pending', 'online', ?)"
        );

        $stmt->execute([
            $nik,
            $room_id,
            $user_id,
            $checkin,
            $checkout,
            $subtotal,
            $room['status']
        ]);

        if (!$invoice_id) {
            $invoice_id = $conn->lastInsertId();
            debug_log("Created invoice ID: $invoice_id");
        }

        // Update room status to not available
        $upd = $conn->prepare("UPDATE kamar SET status = 'tidak tersedia' WHERE id_kamar = ?");
        $upd->execute([$room_id]);
        debug_log("Updated room status for room ID: $room_id");

        $room_details[] = [
            'id' => $room_id,
            'name' => $room['nama'],
            'price' => $room['harga'],
            'subtotal' => $subtotal
        ];
    }

    // Commit transaction
    $conn->commit();
    debug_log("Transaction committed successfully");

    // Generate payment code based on payment method
    $payment_method = $_POST['payment_method'];
    $payment_code = strtoupper(substr($payment_method, 0, 3)) . date('Ymd') . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
    
    // Simpan kode pembayaran ke database jika kolom tersedia
    // Jika tidak ada kolom kode_pembayaran di tabel transaksi, hapus atau sesuaikan baris di bawah ini
    /*
    if (empty($transaksi['kode_pembayaran'])) {
        $updateStmt = $conn->prepare("
            UPDATE transaksi 
            SET kode_pembayaran = ?, metode_pembayaran = ? 
            WHERE id_transaksi = ?
        ");
        $updateStmt->execute([$payment_code, $payment_method, $invoice_id]);
    }
    */
    // Update hanya metode_pembayaran
    $updateStmt = $conn->prepare("
        UPDATE transaksi 
        SET metode_pembayaran = ? 
        WHERE id_transaksi = ?
    ");
    $updateStmt->execute([$payment_method, $invoice_id]);

    $_SESSION['payment_code_' . $invoice_id] = $payment_code;
    $_SESSION['payment_method_' . $invoice_id] = $payment_method;

    // Return success response with more detailed payment info
    echo json_encode([
        'success' => true,
        'message' => 'Booking berhasil',
        'invoice_id' => $invoice_id,
        'total' => $total,
        'total_formatted' => number_format($total, 0, ',', '.'),
        'rooms' => $room_details,
        'checkin' => date('d/m/Y', strtotime($checkin)),
        'checkout' => date('d/m/Y', strtotime($checkout)),
        'nights' => $nights,
        'payment_info' => [
            'method' => $payment_method,
            'code' => $payment_code,
            'expires_in' => '24 jam',
            'payment_method_name' => getPaymentMethodName($payment_method)
        ]
    ]);

    debug_log("Process completed successfully with invoice ID: $invoice_id");

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
        debug_log("Transaction rolled back due to error");
    }
    
    $errorMessage = $e->getMessage();
    debug_log("Error: $errorMessage");
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
}

// Helper function to get payment method name
function getPaymentMethodName($method) {
    $methods = [
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'bca' => 'Bank BCA',
        'mandiri' => 'Bank Mandiri',
        'bni' => 'Bank BNI'
    ];
    
    return $methods[$method] ?? ucfirst($method);
}


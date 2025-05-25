<?php
<?php
require 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk melakukan check-in']);
    exit;
}

// Ensure booking ID is provided
if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID Booking tidak valid']);
    exit;
}

$bookingId = $_POST['booking_id'];
$userId = $_SESSION['id_user'];

try {
    // Start transaction
    $conn->beginTransaction();
    
    // First, verify that the booking belongs to this user and is valid for check-in
    $checkBooking = $conn->prepare("
        SELECT t.* 
        FROM transaksi t
        WHERE t.id_transaksi = ? 
        AND t.id_user = ? 
        AND t.status = 'siap digunakan'
        AND DATE(t.tgl_checkin) = CURRENT_DATE
        AND (t.checked_in = 0 OR t.checked_in IS NULL)
    ");
    $checkBooking->execute([$bookingId, $userId]);
    
    if ($checkBooking->rowCount() === 0) {
        // Booking not found or not eligible for check-in
        throw new Exception('Booking tidak ditemukan atau tidak memenuhi syarat untuk check-in');
    }
    
    // Update the booking status to checked in
    $updateBooking = $conn->prepare("
        UPDATE transaksi 
        SET checked_in = 1, 
            checkin_time = NOW() 
        WHERE id_transaksi = ?
    ");
    $updateBooking->execute([$bookingId]);
    
    // Add a notification for the user
    $notifStmt = $conn->prepare("
        INSERT INTO notifikasi (id_user, judul, pesan, waktu, dibaca) 
        VALUES (?, 'Check-in Berhasil', 'Anda telah berhasil melakukan check-in. Selamat menikmati penginapan Anda!', NOW(), 0)
    ");
    $notifStmt->execute([$userId]);
    
    // Add an entry to the check-in log if you want to track check-ins
    $logStmt = $conn->prepare("
        INSERT INTO checkin_log (id_transaksi, checkin_time, method) 
        VALUES (?, NOW(), 'user_self_checkin')
        ON DUPLICATE KEY UPDATE checkin_time = NOW(), method = 'user_self_checkin'
    ");
    $logStmt->execute([$bookingId]);
    
    // If all queries succeed, commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Check-in berhasil! Selamat menikmati penginapan Anda.'
    ]);
    
} catch (Exception $e) {
    // If an error occurs, roll back the transaction
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
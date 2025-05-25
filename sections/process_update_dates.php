<?php
// filepath: c:\xampp\htdocs\project3\NiceAdmin\process_update_dates.php
// Pastikan tidak ada output sebelum header
ob_start();

// Nonaktifkan pesan error agar tidak merusak JSON
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once 'config.php';

// Debug flag
$debug = true;

// Log semua request untuk debugging
if ($debug) {
    file_put_contents('update_log.txt', 
        date('Y-m-d H:i:s') . " - Request: " . print_r($_POST, true) . "\n", 
        FILE_APPEND);
}

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    $_SESSION['error'] = 'Silakan login terlebih dahulu';
    header('Location: landing_page.php');
    exit;
}

// Get POST data
$bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$checkin = isset($_POST['checkin']) ? $_POST['checkin'] : '';
$checkout = isset($_POST['checkout']) ? $_POST['checkout'] : '';

try {
    // Check if the booking exists and belongs to the current user
    $checkStmt = $conn->prepare("
        SELECT t.*, k.harga as kamar_harga 
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar 
        WHERE t.id_transaksi = ? AND t.id_user = ?
    ");
    $checkStmt->execute([$bookingId, $_SESSION['id_user']]);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $_SESSION['error'] = 'Booking tidak ditemukan atau bukan milik Anda';
        header('Location: landing_page.php?section=riwayat');
        exit;
    }
    
    // Calculate new duration and total price
    $checkInDate = new DateTime($checkin);
    $checkOutDate = new DateTime($checkout);
    $interval = $checkInDate->diff($checkOutDate);
    $days = $interval->days;
    
    // Calculate new total price
    $roomPrice = $booking['kamar_harga'];
    $totalPrice = $roomPrice * $days;
    
    // Update the booking
    $conn->beginTransaction();
    
    // Sesuaikan nama kolom dengan database Anda
    $updateStmt = $conn->prepare("
        UPDATE transaksi 
        SET tgl_checkin = ?, tgl_checkout = ?, total_harga = ? 
        WHERE id_transaksi = ?
    ");
    
    $updateStmt->execute([$checkin, $checkout, $totalPrice, $bookingId]);
    
    $conn->commit();
    
    // Set success message
    $_SESSION['success'] = 'Tanggal booking berhasil diperbarui';
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Set error message
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

// Redirect back to riwayat page
header('Location: landing_page.php?section=riwayat');
exit;
?>
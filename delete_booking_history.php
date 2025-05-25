<?php
// delete_booking_history.php - Handles deleting booking history (individual or all)
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

$userId = $_SESSION['id_user'];

// Return JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Extract posted data
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action === 'single') {
        // Delete a single booking history
        if (empty($_POST['invoice_id'])) {
            throw new Exception('ID transaksi tidak ditemukan');
        }
        
        $invoiceId = $_POST['invoice_id'];
        
        // Verify that the booking belongs to the current user
        $stmt = $conn->prepare("SELECT id_transaksi FROM transaksi WHERE id_transaksi = ? AND id_user = ?");
        $stmt->execute([$invoiceId, $userId]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Pemesanan tidak ditemukan atau bukan milik Anda');
        }
          // Start a transaction to ensure data consistency
        $conn->beginTransaction();
        
        try {
            // Get the room ID from the transaction
            $roomStmt = $conn->prepare("SELECT id_kamar FROM transaksi WHERE id_transaksi = ?");
            $roomStmt->execute([$invoiceId]);
            $roomData = $roomStmt->fetch();
            $roomId = $roomData['id_kamar'];
            
            // Update room status to "tersedia"
            $updateRoomStmt = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id_kamar = ?");
            $updateRoomStmt->execute([$roomId]);
            
            // Use a soft delete by updating is_deleted flag
            $stmt = $conn->prepare("UPDATE transaksi SET is_deleted = 1 WHERE id_transaksi = ?");
            $stmt->execute([$invoiceId]);
            
            // Commit the transaction
            $conn->commit();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Riwayat pemesanan berhasil dihapus dan kamar dikembalikan ke status tersedia'
            ]);
        } catch (Exception $e) {
            // If something goes wrong, roll back the transaction
            $conn->rollBack();
            throw $e;
        }
    }    else if ($action === 'all') {
        // Delete all booking history for this user
        
        // Start a transaction to ensure data consistency
        $conn->beginTransaction();
        
        try {
            // Get all rooms from user's transactions that are being deleted
            $roomsStmt = $conn->prepare("SELECT id_transaksi, id_kamar FROM transaksi WHERE id_user = ? AND is_deleted = 0");
            $roomsStmt->execute([$userId]);
            $transactionsToDelete = $roomsStmt->fetchAll();
            
            // Update each room's status
            foreach ($transactionsToDelete as $transaction) {
                $updateRoomStmt = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id_kamar = ?");
                $updateRoomStmt->execute([$transaction['id_kamar']]);
            }
            
            // Use a soft delete by updating is_deleted flag for all transactions
            $stmt = $conn->prepare("UPDATE transaksi SET is_deleted = 1 WHERE id_user = ? AND is_deleted = 0");
            $stmt->execute([$userId]);
            
            // Commit the transaction
            $conn->commit();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Semua riwayat pemesanan berhasil dihapus dan kamar dikembalikan ke status tersedia'
            ]);
        } catch (Exception $e) {
            // If something goes wrong, roll back the transaction
            $conn->rollBack();
            throw $e;
        }
    }
    else {
        throw new Exception('Tindakan tidak valid');
    }
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

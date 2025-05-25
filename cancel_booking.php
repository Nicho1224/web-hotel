<?php
require 'config.php';

// Function to handle the cancellation process
function processCancellation($conn, $invoiceId) {
    try {
        $conn->beginTransaction();

        // 1. Ambil data kamar
        $stmt = $conn->prepare("
            SELECT t.id_kamar, t.status_kamar_awal 
            FROM transaksi t 
            WHERE t.id_transaksi = ? 
            AND (t.status = 'pending' OR t.status = 'siap digunakan')
        ");
        $stmt->execute([$invoiceId]);
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaksi) {
            throw new Exception("Transaksi tidak valid atau tidak dapat dibatalkan");
        }

        // 2. Update status transaksi
        $conn->prepare("
            UPDATE transaksi 
            SET status = 'dibatalkan' 
            WHERE id_transaksi = ?
        ")->execute([$invoiceId]);

        // 3. Kembalikan status kamar
        $conn->prepare("
            UPDATE kamar 
            SET status = ? 
            WHERE id_kamar = ?
        ")->execute([
            $transaksi['status_kamar_awal'],
            $transaksi['id_kamar']
        ]);

        $conn->commit();
        return [
            'success' => true,
            'original_status' => $transaksi['status_kamar_awal'],
            'message' => 'Status kamar berhasil dikembalikan ke '.$transaksi['status_kamar_awal']
        ];
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Handle POST request (AJAX from payment_timeout.js)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $invoiceId = $_POST['invoice_id'];
    $result = processCancellation($conn, $invoiceId);
    
    echo json_encode($result);
    exit;
}

// Handle GET request (direct link from riwayat_user.php)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $invoiceId = $_GET['id'];
    $result = processCancellation($conn, $invoiceId);
    
    if ($result['success']) {
        // Redirect to landing page with filter for canceled bookings
        header('Location: landing_page.php?section=riwayat&status=dibatalkan');
    } else {
        // If there's an error, redirect with error message
        $_SESSION['error_message'] = $result['message'];
        header('Location: landing_page.php?section=riwayat');
    }
    exit;
}
<?php
require 'config.php';

// Add to top of config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek autentikasi user
if (!isset($_SESSION['lv']) || $_SESSION['lv'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Validasi input
if (!isset($_POST['payment_method']) || !isset($_POST['invoice_id'])) {
    $_SESSION['error'] = 'Data pembayaran tidak lengkap';
    header('Location: dashboard_user.php');
    exit;
}

$payment_method = $_POST['payment_method'];
$invoice_id = $_POST['invoice_id'];

try {
    // Validasi transaksi
    $stmt = $conn->prepare("
        SELECT t.*, k.nama AS nama_kamar 
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar
        WHERE t.id_transaksi = ? AND t.id_user = ?
    ");
    $stmt->execute([$invoice_id, $_SESSION['id_user']]);
    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaksi) {
        throw new Exception('Transaksi tidak valid');
    }

    // Generate kode pembayaran
    $payment_code = strtoupper(substr($payment_method, 0, 3)) . date('Ymd') . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if columns exist in transaksi table
    $checkColumns = $conn->query("SHOW COLUMNS FROM transaksi LIKE 'metode_pembayaran'");
    $metodeExists = $checkColumns->rowCount() > 0;
    
    if (!$metodeExists) {
        // Add metode_pembayaran column if it doesn't exist
        $conn->exec("ALTER TABLE transaksi ADD COLUMN metode_pembayaran VARCHAR(50) NULL AFTER jenis_booking");
        $conn->exec("ALTER TABLE transaksi ADD COLUMN kode_pembayaran VARCHAR(50) NULL AFTER metode_pembayaran");
        $conn->exec("ALTER TABLE transaksi ADD COLUMN waktu_pembayaran DATETIME NULL AFTER kode_pembayaran");
    }
    
    // Update status transaksi
    $stmt = $conn->prepare("
        UPDATE transaksi 
        SET status = 'menunggu verifikasi',
            metode_pembayaran = ?,
            kode_pembayaran = ?,
            waktu_pembayaran = NOW()
        WHERE id_transaksi = ?
    ");
    $stmt->execute([$payment_method, $payment_code, $invoice_id]);

    $conn->commit();
    
    // Set success message
    $_SESSION['success'] = 'Pembayaran berhasil diproses. Menunggu verifikasi dari petugas.';
    
    // Redirect ke halaman riwayat transaksi
    header('Location: riwayat_user.php');
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log('Payment error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: pilih_pembayaran.php?invoice=' . $invoice_id);
    exit;
}
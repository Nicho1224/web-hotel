<?php
// Start a session if not already started
session_start();

require_once 'config.php';

// Process form submission for resetting all room statuses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_rooms'])) {
  // Get submitted admin credentials
  $admin_username = $_POST['admin_username'] ?? '';
  $admin_password = $_POST['admin_password'] ?? '';
  
  // Verify admin credentials
  try {
    $stmt = $conn->prepare("SELECT id_user, password, lv FROM user WHERE username = ? AND (lv = 'admin' OR lv = 'pegawai')");
    $stmt->execute([$admin_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if admin exists and password is correct
    if ($admin && password_verify($admin_password, $admin['password'])) {
      // Credentials are correct, proceed with reset
      $stmt = $conn->prepare("UPDATE kamar SET status = 'tersedia'");
      $stmt->execute();
      
      // Record who made the change
      $admin_id = $admin['id_user'];
      $admin_level = $admin['lv'];
      
      // Set success message with admin info
      $_SESSION['success_message'] = "Semua kamar berhasil direset ke status tersedia oleh {$admin_username} ({$admin_level})!";
    } else {
      // Invalid credentials
      $_SESSION['error_message'] = "Reset gagal: Username atau password admin tidak valid!";
    }
  } catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
  }
}

// Redirect back to the index.php with the page parameter for tambah_kamar
header('Location: index.php?page=tambah_kamar');
exit;
?>

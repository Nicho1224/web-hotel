<?php
// Pastikan tidak ada output sebelum header
ob_start();

// Nonaktifkan pesan error agar tidak merusak JSON
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Debug log
file_put_contents('update_log.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents('update_log.txt', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu'
    ]);
    exit;
}

// Get POST data
$bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$checkin = isset($_POST['checkin']) ? $_POST['checkin'] : '';
$checkout = isset($_POST['checkout']) ? $_POST['checkout'] : '';

// Validate
if (!$bookingId || !$checkin || !$checkout) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

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
        echo json_encode([
            'success' => false,
            'message' => 'Booking tidak ditemukan atau bukan milik Anda'
        ]);
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
    
    // Update the booking - PERHATIKAN NAMA KOLOM: totalharga (bukan total_harga)
    $conn->beginTransaction();
    
    $updateStmt = $conn->prepare("
        UPDATE transaksi 
        SET tgl_checkin = ?, tgl_checkout = ?, totalharga = ? 
        WHERE id_transaksi = ?
    ");
    
    $updateStmt->execute([$checkin, $checkout, $totalPrice, $bookingId]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tanggal berhasil diperbarui',
        'new_price' => $totalPrice
    ]);
    
} catch (PDOException $e) {
    file_put_contents('update_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
<script>
async function updateBookingDates(bookingId, checkin, checkout) {
    const response = await fetch('update_booking_dates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            booking_id: bookingId,
            checkin: checkin,
            checkout: checkout
        })
    });
    
    // Debug response
    const rawText = await response.text();
    console.log('Raw response:', rawText);
    
    // Try to parse as JSON
    let result;
    try {
        result = JSON.parse(rawText);
    } catch (parseError) {
        console.error('Failed to parse JSON:', parseError);
        throw new Error('Server returned invalid JSON: ' + rawText.substring(0, 100) + '...');
    }
    
    return result;
}

// Handle save dates
document.getElementById('saveDatesBtn').addEventListener('click', async function() {
  // Get form data
  const bookingId = document.getElementById('editBookingId').value;
  const checkin = document.getElementById('edit-checkin').value;
  const checkout = document.getElementById('edit-checkout').value;
  
  // Validate
  if (!checkin || !checkout) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Silakan isi semua tanggal'
    });
    return;
  }
  
  if (checkout <= checkin) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Tanggal check-out harus setelah tanggal check-in'
    });
    return;
  }
  
  // Show loading state
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
  
  try {
    const response = await fetch('../update_booking_dates.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `booking_id=${bookingId}&checkin=${checkin}&checkout=${checkout}`
    });
    
    // Debug response
    const rawText = await response.text();
    console.log('Raw response:', rawText);
    
    // Try to parse as JSON
    let result;
    try {
        result = JSON.parse(rawText);
    } catch (parseError) {
        console.error('Failed to parse JSON:', parseError);
        throw new Error('Server returned invalid JSON: ' + rawText.substring(0, 100) + '...');
    }
    
    if (result.success) {
      // Close modal
      editDatesModal.hide();
      
      // Show success message
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Tanggal menginap berhasil diperbarui',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        // Reload the page
        window.location.reload();
      });
    } else {
      throw new Error(result.message || 'Terjadi kesalahan');
    }
  } catch (error) {
    console.error('Error updating dates:', error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.message || 'Terjadi kesalahan saat memperbarui tanggal'
    });
  } finally {
    this.disabled = false;
    this.innerHTML = 'Simpan Perubahan';
  }
});
</script>
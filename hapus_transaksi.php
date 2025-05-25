
<?php
require_once 'config.php';

if (!isset($_SESSION['id_user'])) {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus']) && is_array($_POST['hapus'])) {
  $ids = $_POST['hapus'];

  try {
    $conn->beginTransaction();

    $getKamar = $conn->prepare("SELECT id_kamar FROM transaksi WHERE id_transaksi = ?");
    $updateKamar = $conn->prepare("UPDATE kamar SET status = 'kosong' WHERE id_kamar = ?");
    $deleteTrx = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");

    foreach ($ids as $id) {
      $getKamar->execute([$id]);
      $row = $getKamar->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $updateKamar->execute([$row['id_kamar']]);
      }
      $deleteTrx->execute([$id]);
    }

    $conn->commit();
    header('Location: index.php?page=home');
    exit;
    
  } catch (Exception $e) {
    $conn->rollBack();
    echo "Terjadi kesalahan: " . $e->getMessage();
  }
} else {
  header('Location: riwayat_booking.php');
  exit;
}

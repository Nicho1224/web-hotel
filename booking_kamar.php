<?php
// debug on
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// jika belum login
if (!isset($_SESSION['id_user'])) {
  header('Location: login.php');
  exit;
}

// POST: proses booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $selected = !empty($_POST['kamar_dipilih']) ? explode(',', $_POST['kamar_dipilih']) : [];
  $nik      = trim($_POST['nik']      ?? '');
  $ci       = $_POST['checkin']      ?? '';
  $co       = $_POST['checkout']     ?? '';
  $qty      = max(1, intval($_POST['quantity'] ?? 1));

  try {
    // validasi
    if (empty($selected)) throw new Exception("Silakan pilih kamar.");
    if (empty($nik)) throw new Exception("Pilih tamu (NIK).");
    if (empty($ci) || empty($co)) throw new Exception("Tanggal belum lengkap.");
    if ($co <= $ci) throw new Exception("Check-out harus setelah check-in.");
    error_log("Checkpoint 1: validasi selesai");

    // verifikasi tamu
    $g = $conn->prepare("SELECT 1 FROM tamu WHERE nik = ?");
    $g->execute([$nik]);
    if (!$g->fetch()) throw new Exception("NIK tidak terdaftar.");
    error_log("Checkpoint 2: verifikasi tamu oke");

    // hitung lama malam
    $d1 = new DateTime($ci);
    $d2 = new DateTime($co);
    $nights = $d2->diff($d1)->days;
    if ($nights < 1) throw new Exception("Minimal 1 malam.");
    error_log("Checkpoint 3: hitung malam: $nights malam");

    // hitung total harga
    $sum = 0;
    $p = $conn->prepare("SELECT harga FROM kamar WHERE id_kamar = ?");
    foreach ($selected as $rid) {
      $p->execute([$rid]);
      $row = $p->fetch(PDO::FETCH_ASSOC);
      if (!$row) throw new Exception("Kamar ID {$rid} tidak ditemukan.");
      $sum += $row['harga'];
    }
    $total = $sum * $qty * $nights;
    $fmt   = number_format($total, 0, ',', '.');
    error_log("Checkpoint 4: total harga Rp{$fmt}");

    // simpan transaksi
    $conn->beginTransaction();
    $ins = $conn->prepare("
      INSERT INTO transaksi
        (nik, id_kamar, id_user,
         tgl_booking, tgl_checkin, tgl_checkout,
         totalharga, status)
      VALUES
        (:nik, :rid, :uid,
         CURDATE(), :ci, :co,
         :tot, 'pending')
    ");
    foreach ($selected as $rid) {
      $ins->execute([
        ':nik' => $nik,
        ':rid' => $rid,
        ':uid' => $_SESSION['id_user'],
        ':ci'  => $ci,
        ':co'  => $co,
        ':tot' => $total
      ]);
    }
    $conn->commit();
    error_log("Checkpoint 5: transaksi berhasil disimpan");

    // Jika SweetAlert gagal, tetap tampil
    echo <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Booking Berhasil!',
    text: 'Total bayar: Rp{$fmt}. Silakan tunggu verifikasi.'
  }).then(() => {
    window.location = 'index.php?page=home';
  });
</script>
<noscript>
  <h1>Booking Berhasil!</h1>
  <p>Total bayar: Rp{$fmt}. Silakan tunggu verifikasi.</p>
</noscript>
</body>
</html>
HTML;
    exit;

  } catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $msg = $e->getMessage();
    error_log("Booking gagal: $msg");

    echo <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Gagal',
    text: '{$msg}'
  });
</script>
<noscript>
  <h1>Gagal</h1>
  <p>{$msg}</p>
</noscript>
</body>
</html>
HTML;
    exit;
  }
}

// GET: tampilkan form
if (empty($_GET['kamar_dipilih'])) {
  header('Location: status_kamar.php');
  exit;
}

$selected     = explode(',', $_GET['kamar_dipilih']);
$ph           = implode(',', array_fill(0, count($selected), '?'));
$stmt         = $conn->prepare("SELECT * FROM kamar WHERE id_kamar IN ($ph)");
$stmt->execute($selected);
$chosenRooms  = $stmt->fetchAll(PDO::FETCH_ASSOC);

// load daftar tamu
$guests = $conn->query("SELECT nik,nama FROM tamu ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-theme="light">
<head>
  <meta charset="utf-8">
  <title>Form Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Light theme (default) */
    :root {
      --text-color: #212529;
      --bg-color: #ffffff;
      --input-bg: #ffffff;
      --input-border: #ced4da;
      --card-bg: #ffffff;
      --card-border: #dee2e6;
      --heading-color: #212529;
      --list-bg: #ffffff;
      --list-border: rgba(0,0,0,.125);
    }

    /* Dark theme */
    [data-theme="dark"] {
      --text-color: #e2e8f0;
      --bg-color: #0f172a;
      --input-bg: #1e293b;
      --input-border: #334155;
      --card-bg: #1e293b;
      --card-border: #334155;
      --heading-color: #ffffff;
      --list-bg: #1e293b;
      --list-border: #334155;
    }

    /* Apply theme colors */
    body {
      background-color: var(--bg-color);
      color: var(--text-color);
    }

    h2 {
      color: var(--heading-color);
    }

    .form-control,
    .form-select {
      background-color: var(--input-bg);
      border-color: var(--input-border);
      color: var(--text-color);
    }

    .form-control:focus,
    .form-select:focus {
      background-color: var(--input-bg);
      color: var(--text-color);
    }

    .form-label {
      color: var(--text-color);
    }

    .list-group-item {
      background-color: var(--list-bg);
      border-color: var(--list-border);
      color: var(--text-color);
    }

    select option {
      background-color: var(--input-bg);
      color: var(--text-color);
    }

    /* Fix date inputs in dark mode */
    input[type="date"] {
      color-scheme: dark;
    }

    /* Fix number inputs in dark mode */
    input[type="number"] {
      color-scheme: dark;
    }

    /* Sweet Alert dark mode compatibility */
    .swal2-popup {
      background-color: var(--card-bg) !important;
      color: var(--text-color) !important;
    }

    .swal2-title,
    .swal2-content {
      color: var(--text-color) !important;
    }
  </style>

  <script>
    // Inherit theme from parent
    document.addEventListener('DOMContentLoaded', function() {
      const theme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', theme);
    });
  </script>
</head>
<body class="p-4">
<div class="container">
  <h2>Form Booking</h2>
  <form method="post">
    <input type="hidden" name="kamar_dipilih"
           value="<?= htmlspecialchars($_GET['kamar_dipilih']) ?>">
    <div class="mb-3">
      <label class="form-label">Tamu (NIK)</label>
      <select name="nik" class="form-select" required>
        <option value="" disabled selected>-- Pilih Tamu --</option>
        <?php foreach ($guests as $g): ?>
        <option value="<?= $g['nik'] ?>">
          <?= htmlspecialchars($g['nama']) ?> (<?= $g['nik'] ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">Check-In</label>
        <input type="date" name="checkin" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Check-Out</label>
        <input type="date" name="checkout" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Jumlah Kamar</label>
        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Ringkasan Kamar Terpilih</label>
      <ul class="list-group">
        <?php foreach ($chosenRooms as $r): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($r['nama']) ?> — <?= htmlspecialchars($r['jenis']) ?> — Rp<?= number_format($r['harga'], 0, ',', '.') ?>/malam
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <button class="btn btn-success">Bayar &amp; Selesai</button>
  </form>
</div>
</body>
</html>

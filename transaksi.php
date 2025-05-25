<?php
// file: transaksi.php
require 'config.php';
// config.php men-start session_start() dan $conn PDO

// 1) Simpan transaksi jika form disubmit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (a) PASTIKAN USER SUDAH LOGIN
    if (!isset($_SESSION['id_user'])) {
        // kalau memang wajib login untuk booking
        header('Location: login.php');
        exit;
    }

    // (b) Baca data form
    $nik         = trim($_POST['nik'] ?? '');
    $id_kamar    = intval($_POST['id_kamar'] ?? 0);
    $checkin     = $_POST['checkin'] ?? '';
    $checkout    = $_POST['checkout'] ?? '';
    $jumlahKamar = max(1, intval($_POST['jumlah_kamar']));

    // (c) Validasi sederhana
    if ($nik === '' || $id_kamar <= 0) {
        header('Location: transaksi.php?nik=' . urlencode($nik));
        exit;
    }

    // (d) Hitung lama menginap
    $d1     = new DateTime($checkin);
    $d2     = new DateTime($checkout);
    $nights = $d2->diff($d1)->days;
    if ($nights < 1) {
        echo "<div class='alert alert-danger'>Check-out harus setelah check-in.</div>";
        exit;
    }

    // (e) Ambil harga kamar
    $stmtK = $conn->prepare("SELECT harga FROM kamar WHERE id_kamar = ?");
    $stmtK->execute([$id_kamar]);
    $rowK = $stmtK->fetch();
    if (!$rowK) {
        echo "<div class='alert alert-danger'>Kamar tidak ditemukan.</div>";
        exit;
    }

    // (f) Hitung total harga
    $totalharga = $rowK['harga'] * $jumlahKamar * $nights;

    // (g) Insert ke DB
    $stmt = $conn->prepare(
      "INSERT INTO transaksi 
         (nik, id_kamar, id_user, tgl_booking, tgl_checkin, tgl_checkout, totalharga)
       VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $nik,
        $id_kamar,
        $_SESSION['id_user'],      // PASTIKAN sudah terisi
        date('Y-m-d'),
        $checkin,
        $checkout,
        $totalharga
    ]);

    // 2) Redirect ke halaman utama setelah simpan
    header('Location: index.php');  // atau: header('Location: ?page=home');
    exit;
}

// 3) Request GET → validasi nik & tampilkan form booking
if (empty($_GET['nik'])) {
    header('Location: ?page=tambah_tamu');
    exit;
}
$nik = trim($_GET['nik']);

// Ambil data tamu
$stmtT = $conn->prepare("SELECT * FROM tamu WHERE nik = ?");
$stmtT->execute([$nik]);
$tamu = $stmtT->fetch();
if (!$tamu) {
    header('Location: ?page=tambah_tamu');
    exit;
}

// Ambil daftar kamar tersedia
$kamars = $conn
    ->query("SELECT id_kamar, nama, bed, harga FROM kamar WHERE status='tersedia'")
    ->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Transaksi Pemesanan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="text-theme">Transaksi Pemesanan</h2>
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">Data Tamu</div>
    <div class="card-body">
      <p><strong>NIK:</strong> <?= htmlspecialchars($tamu['nik']) ?></p>
      <p><strong>Nama:</strong> <?= htmlspecialchars($tamu['nama']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($tamu['email']) ?></p>
      <p><strong>Alamat:</strong> <?= nl2br(htmlspecialchars($tamu['alamat'])) ?></p>
    </div>
  </div>

  <form action="transaksi.php" method="POST">
    <input type="hidden" name="nik" value="<?= htmlspecialchars($tamu['nik']) ?>">
    <div class="mb-3">
      <label class="form-label">Pilih Kamar</label>
      <select name="id_kamar" class="form-select" required>
        <option value="">-- Pilih Kamar --</option>
        <?php foreach ($kamars as $k): ?>
          <option value="<?= $k['id_kamar'] ?>">
            <?= htmlspecialchars($k['nama']) ?>
            (Bed: <?= htmlspecialchars($k['bed']) ?>,
             Harga/malam: <?= number_format($k['harga'],0,',','.') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Check-in</label>
      <input type="date" name="checkin" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Check-out</label>
      <input type="date" name="checkout" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Jumlah Kamar</label>
      <input type="number" name="jumlah_kamar" class="form-control" min="1" value="1" required>
    </div>
    <button type="submit" class="btn btn-success">Simpan Transaksi</button>
  </form>
</div>

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
}

/* Apply theme colors */
body {
    background-color: var(--bg-color);
    color: var(--text-color);
}

.card {
    background-color: var(--card-bg);
    border-color: var(--card-border);
}

.card-body {
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

select option {
    background-color: var(--input-bg);
    color: var(--text-color);
}

/* Ensure date inputs are visible in dark mode */
input[type="date"] {
    color-scheme: dark;
}

/* Fix number input arrows in dark mode */
input[type="number"] {
    color-scheme: dark;
}
</style>

<script>
// Inherit theme from parent
document.documentElement.setAttribute('data-theme', 
    localStorage.getItem('theme') || 'light');
</script>
</body>
</html>

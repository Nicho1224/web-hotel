<?php
session_start();
require 'config.php'; // Mengambil konfigurasi dan koneksi database

// Pastikan pengguna yang login adalah admin
if ($_SESSION['lv'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$jenis_pemesanan = $_GET['jenis'] ?? 'online'; // Default adalah online jika tidak ada parameter jenis

// Query untuk mengambil pesanan berdasarkan jenis
if ($jenis_pemesanan === 'offline') {
    $sql = "SELECT * FROM pemesanan WHERE jenis_pemesanan = 'offline'";
} else {
    $sql = "SELECT * FROM pemesanan WHERE jenis_pemesanan = 'online'";
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan - Sistem Hotel</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Daftar Pesanan <?= ucfirst($jenis_pemesanan) ?></h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Nama Tamu</th>
                    <th>Tanggal Check-in</th>
                    <th>Tanggal Check-out</th>
                    <th>Status</th>
                    <th>Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pesanan as $pesan): ?>
                    <tr>
                        <td><?= htmlspecialchars($pesan['id_pemesanan']) ?></td>
                        <td><?= htmlspecialchars($pesan['nama_tamu']) ?></td>
                        <td><?= htmlspecialchars($pesan['tanggal_check_in']) ?></td>
                        <td><?= htmlspecialchars($pesan['tanggal_check_out']) ?></td>
                        <td><?= htmlspecialchars($pesan['status_pemesanan']) ?></td>
                        <td>Rp <?= number_format($pesan['total_harga'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

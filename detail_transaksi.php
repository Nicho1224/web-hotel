<?php
require 'config.php';

if (!isset($_GET['id_transaksi'])) {
    echo "ID transaksi tidak ditemukan.";
    exit;
}

$id_transaksi = $_GET['id_transaksi'];

$query = $conn->prepare("
    SELECT 
        t.id_transaksi,
        t.tgl_checkin,
        t.tgl_checkout,
        t.totalharga,
        tamu.nik,
        tamu.nama AS nama_tamu,
        tamu.alamat,
        k.nama AS nama_kamar,
        k.harga
    FROM transaksi t
    JOIN tamu ON t.nik = tamu.nik
    JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_transaksi = :id
");

$query->execute([':id' => $id_transaksi]);
$trx = $query->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    echo "Transaksi tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Detail Transaksi</h2>
    <table class="table">
        <tr><th>ID Transaksi</th><td><?= $trx['id_transaksi'] ?></td></tr>
        <tr><th>Nama Tamu</th><td><?= $trx['nama_tamu'] ?> (<?= $trx['nik'] ?>)</td></tr>
        <tr><th>Alamat Tamu</th><td><?= $trx['alamat'] ?></td></tr>
        <tr><th>Nama Kamar</th><td><?= $trx['nama_kamar'] ?> (<?= $trx['tipe'] ?>)</td></tr>
        <tr><th>Harga per Malam</th><td>Rp<?= number_format($trx['harga'], 0, ',', '.') ?></td></tr>
        <tr><th>Tanggal Check-In</th><td><?= $trx['tgl_checkin'] ?></td></tr>
        <tr><th>Tanggal Check-Out</th><td><?= $trx['tgl_checkout'] ?></td></tr>
        <tr><th>Total Harga</th><td>Rp<?= number_format($trx['totalharga'], 0, ',', '.') ?></td></tr>
    </table>
    <a href="riwayat_transaksi.php" class="btn btn-secondary">Kembali</a>
</div>
</body>
</html>

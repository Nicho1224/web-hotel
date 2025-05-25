<?php
require_once 'config.php';

if(!isset($_GET['id'])) {
    header("Location: ?page=search_room");
    exit;
}

$transaksi = $conn->query("
    SELECT t.*, k.nama AS nama_kamar, k.harga, tm.nama AS nama_tamu 
    FROM transaksi t
    JOIN kamar k ON t.id_kamar = k.id_kamar
    JOIN tamu tm ON t.nik = tm.nik
    WHERE id_transaksi = ".$_GET['id'])->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h4>Konfirmasi Pembayaran</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-success">
            <h5>Pemesanan Berhasil!</h5>
            <p>Silakan lakukan pembayaran sebesar:</p>
            <h3>Rp<?= number_format($transaksi['totalharga'], 0) ?></h3>
            <p>Ke rekening berikut:<br>
            Bank ABC - 1234567890 a.n Hotel Management</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Detail Transaksi</h5>
                <p>ID Transaksi: <?= $transaksi['id_transaksi'] ?><br>
                Nama Tamu: <?= $transaksi['nama_tamu'] ?><br>
                Kamar: <?= $transaksi['nama_kamar'] ?><br>
                Check-in: <?= $transaksi['tgl_checkin'] ?><br>
                Check-out: <?= $transaksi['tgl_checkout'] ?></p>
            </div>
            
            <div class="col-md-6">
                <h5>Instruksi Pembayaran</h5>
                <ol>
                    <li>Transfer sesuai total harga</li>
                    <li>Simpan bukti transfer</li>
                    <li>Konfirmasi pembayaran via WhatsApp ke 08123456789</li>
                    <li>Booking akan aktif setelah pembayaran diverifikasi</li>
                </ol>
            </div>
        </div>
    </div>
</div>
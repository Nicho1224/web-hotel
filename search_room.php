<?php
require_once 'config.php';

// Proses pencarian kamar
$available_rooms = [];
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    
    // Cek kamar yang tersedia
    $stmt = $conn->prepare("
        SELECT * FROM kamar 
        WHERE id_kamar NOT IN (
            SELECT id_kamar FROM transaksi 
            WHERE (tgl_checkin <= ? AND tgl_checkout >= ?)
            OR status != 'tersedia'
        )
    ");
    $stmt->bind_param("ss", $checkout, $checkin);
    $stmt->execute();
    $available_rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="card">
    <div class="card-header">
        <h4>Cari Kamar Tersedia</h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Tanggal Check-in</label>
                        <input type="date" name="checkin" class="form-control" required 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>Tanggal Check-out</label>
                        <input type="date" name="checkout" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Cari Kamar</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if(!empty($available_rooms)): ?>
        <div class="mt-4">
            <h5>Kamar Tersedia</h5>
            <div class="row">
                <?php foreach($available_rooms as $room): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5><?= $room['nama'] ?></h5>
                            <p>Tipe: <?= $room['jenis'] ?><br>
                               Tempat Tidur: <?= $room['bed'] ?><br>
                               Harga/malam: Rp<?= number_format($room['harga'], 0) ?></p>
                            <a href="?page=booking&id=<?= $room['id_kamar'] ?>&checkin=<?= $checkin ?>&checkout=<?= $checkout ?>" 
                               class="btn btn-sm btn-primary">
                                Pilih Kamar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkin = document.querySelector('input[name="checkin"]');
    const checkout = document.querySelector('input[name="checkout"]');
    
    checkin.addEventListener('change', function() {
        checkout.min = this.value;
    });
    
    checkout.addEventListener('change', function() {
        if(new Date(this.value) <= new Date(checkin.value)) {
            alert('Tanggal check-out harus setelah check-in!');
            this.value = '';
        }
    });
});
</script>
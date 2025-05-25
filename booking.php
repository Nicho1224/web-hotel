<?php
require_once 'config.php';

if(!isset($_GET['id']) || !isset($_GET['checkin']) || !isset($_GET['checkout'])) {
    header("Location: ?page=search_room");
    exit;
}

$room_id = $_GET['id'];
$checkin = $_GET['checkin'];
$checkout = $_GET['checkout'];

// Hitung total harga
$datetime1 = new DateTime($checkin);
$datetime2 = new DateTime($checkout);
$interval = $datetime1->diff($datetime2);
$total_days = $interval->days;

$room = $conn->query("SELECT * FROM kamar WHERE id_kamar = $room_id")->fetch_assoc();
$total_price = $total_days * $room['harga'];

// Proses booking
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $alamat = $_POST['alamat'];
    
    try {
        $conn->begin_transaction();
        
        // Insert data tamu
        $stmt = $conn->prepare("INSERT INTO tamu (nik, nama, email, alamat) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              nama=VALUES(nama), email=VALUES(email), alamat=VALUES(alamat)");
        $stmt->bind_param("ssss", $nik, $nama, $email, $alamat);
        $stmt->execute();
        
        // Insert transaksi
        $stmt = $conn->prepare("INSERT INTO transaksi 
                              (nik, id_kamar, tgl_checkin, tgl_checkout, totalharga) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sissd", $nik, $room_id, $checkin, $checkout, $total_price);
        $stmt->execute();
        
        // Update status kamar
        $conn->query("UPDATE kamar SET status = 'tidak' WHERE id_kamar = $room_id");
        
        $conn->commit();
        header("Location: ?page=payment&id=".$conn->insert_id);
        exit;
        
    } catch(Exception $e) {
        $conn->rollback();
        die("Error: ".$e->getMessage());
    }
}
?>

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
    --alert-bg: #cff4fc;
    --alert-text: #055160;
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
    --alert-bg: #164B60;
    --alert-text: #e2e8f0;
}

/* Apply theme styles */
.card {
    background-color: var(--card-bg);
    border-color: var(--card-border);
    color: var(--text-color);
}

.card-header {
    background-color: var(--card-bg);
    border-color: var(--card-border);
}

.card-header h4 {
    color: var(--heading-color);
}

.alert-info {
    background-color: var(--alert-bg);
    border-color: var(--alert-bg);
    color: var(--alert-text);
}

.form-control {
    background-color: var(--input-bg);
    border-color: var(--input-border);
    color: var(--text-color);
}

.form-control:focus {
    background-color: var(--input-bg);
    border-color: var(--primary);
    color: var(--text-color);
}

label {
    color: var(--text-color);
}

textarea {
    background-color: var(--input-bg) !important;
    color: var(--text-color) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from parent
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
});
</script>

<div class="card">
    <div class="card-header">
        <h4>Form Pemesanan Kamar</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info">
                    <h5>Detail Kamar</h5>
                    <p>Nama Kamar: <?= $room['nama'] ?><br>
                    Tipe: <?= $room['jenis'] ?><br>
                    Durasi: <?= $total_days ?> Malam<br>
                    Total Harga: Rp<?= number_format($total_price, 0) ?></p>
                </div>
            </div>
            
            <div class="col-md-6">
                <form method="POST">
                    <div class="mb-3">
                        <label>NIK</label>
                        <input type="text" name="nik" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Konfirmasi Pemesanan</button>
                </form>
            </div>
        </div>
    </div>
</div>
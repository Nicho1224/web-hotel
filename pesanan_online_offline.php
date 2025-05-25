<?php
session_start();
include('config.php'); // Pastikan Anda menyertakan koneksi ke database

// Memastikan hanya admin dan pegawai yang bisa mengakses halaman ini
if ($_SESSION['lv'] != 'admin' && $_SESSION['lv'] != 'pegawai') {
    header("Location: login.php");
    exit();
}

// Query untuk mengambil pesanan berdasarkan jenis pemesanan
$sql = "SELECT t.id_transaksi, t.tgl_booking, t.tgl_checkin, t.tgl_checkout, t.totalharga, t.status, t.jenis_booking, u.username, k.nama AS kamar
        FROM transaksi t
        JOIN user u ON t.id_user = u.id_user
        JOIN kamar k ON t.id_kamar = k.id_kamar";
        
// Jika ingin filter berdasarkan jenis pemesanan
if (isset($_GET['jenis_booking'])) {
    $jenis_booking = $_GET['jenis_booking'];
    $sql .= " WHERE t.jenis_booking = '$jenis_booking'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Online dan Offline</title>
    <link rel="stylesheet" href="styles.css"> <!-- Sesuaikan dengan stylesheet Anda -->
</head>
<body>
    <div class="container">
        <h1>Daftar Pesanan</h1>
        <div class="filter">
            <a href="pesanan_online_offline.php?jenis_booking=online">Pesanan Online</a> | 
            <a href="pesanan_online_offline.php?jenis_booking=offline">Pesanan Offline</a> | 
            <a href="pesanan_online_offline.php">Semua Pesanan</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Nama Pengguna</th>
                    <th>Nama Kamar</th>
                    <th>Tanggal Booking</th>
                    <th>Tanggal Check-in</th>
                    <th>Tanggal Check-out</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Jenis Pemesanan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Menampilkan hasil query
                if ($result->rowCount() > 0) { // Menggunakan rowCount() untuk jumlah baris
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) { // Menambahkan PDO::FETCH_ASSOC untuk pengambilan data
                        echo "<tr>
                            <td>" . $row['id_transaksi'] . "</td>
                            <td>" . $row['username'] . "</td>
                            <td>" . $row['kamar'] . "</td>
                            <td>" . $row['tgl_booking'] . "</td>
                            <td>" . $row['tgl_checkin'] . "</td>
                            <td>" . $row['tgl_checkout'] . "</td>
                            <td>Rp " . number_format($row['totalharga'], 2, ',', '.') . "</td>
                            <td>" . ucfirst($row['status']) . "</td>
                            <td>" . ucfirst($row['jenis_booking']) . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>Tidak ada pesanan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Tidak perlu menggunakan $conn->close(), PDO akan menutup koneksi secara otomatis
?>

<?php
// hapus_tamu.php
require 'config.php';
session_start();

if (!isset($_GET['nik'])) {
    header('Location: index.php?page=home');
    exit;
}

$nik = $_GET['nik'];

try {
    $conn->beginTransaction();

    // HAPUS transaksi yang menggunakan NIK ini dulu
    $stmt1 = $conn->prepare("DELETE FROM transaksi WHERE nik = ?");
    $stmt1->execute([$nik]);

    // Baru hapus data tamunya
    $stmt2 = $conn->prepare("DELETE FROM tamu WHERE nik = ?");
    $stmt2->execute([$nik]);

    $conn->commit();

    header('Location: index.php?page=tambah_tamu&hapus=success');
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    echo "<div class='alert alert-danger'>Gagal menghapus data: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

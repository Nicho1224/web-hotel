<?php
require 'config.php';  // Pastikan ini meng-inisialisasi $conn

// Hapus kamar jika diminta
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM kamar WHERE id_kamar = ?");  // Ganti $pdo menjadi $conn
    $stmt->execute([$id]);

    header('Location: ?page=kamar');
    exit;
}

// Add this after your existing POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    try {
        // Reset all rooms
        if (isset($_POST['reset_all_rooms'])) {
            $stmt = $conn->prepare("UPDATE kamar SET status = 'tersedia'");
            $stmt->execute();
            $_SESSION['success'] = "Semua kamar berhasil direset ke status tersedia!";
            header("Location: index.php?page=kamar");
            exit;
        }

        // Quick status update
        if (isset($_POST['update_test_status'])) {
            if (!empty($_POST['test_kamar']) && !empty($_POST['test_status'])) {
                $stmt = $conn->prepare("UPDATE kamar SET status = ? WHERE id_kamar = ?");
                $stmt->execute([$_POST['test_status'], $_POST['test_kamar']]);
                $_SESSION['success'] = "Status kamar berhasil diupdate!";
                header("Location: index.php?page=kamar");
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: index.php?page=kamar");
        exit;
    }
}

// Ambil data kamar
$stmt = $conn->query("SELECT * FROM kamar ORDER BY id_kamar");  // Ganti $pdo menjadi $conn
?>


<h2>Daftar Kamar</h2>
<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama</th>
      <th>Bed</th>
      <th>Jenis</th>
      <th>Harga</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
      <td><?= $row['id_kamar'] ?></td>
      <td><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['bed']) ?></td>
      <td><?= htmlspecialchars($row['jenis']) ?></td>
      <td><?= number_format($row['harga'], 2, ',', '.') ?></td>
      <td><?= htmlspecialchars($row['status']) ?></td>
      <td>
        <a href="?page=edit_kamar&id=<?= $row['id_kamar'] ?>" class="btn btn-sm btn-warning">Edit</a>
        <a href="?page=kamar&delete_id=<?= $row['id_kamar'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kamar ini?')">Hapus</a>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

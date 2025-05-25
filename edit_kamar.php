<?php
// Ensure $conn is a PDO instance (adjust this part if needed)
// Example PDO connection (uncomment if needed):
// $dsn = 'mysql:host=localhost;dbname=your_database';
// $user = 'username';
// $password = 'password';
// $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
// $conn = new PDO($dsn, $user, $password, $options);

$id = (int)$_GET['id']; // Sanitize ID as integer

// Fetch existing data
$res = $conn->query("SELECT * FROM kamar WHERE id_kamar = $id");
$data = $res->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "<p>Kamar tidak ditemukan.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $params = [];

    // Nama
    if (isset($_POST['nama']) && $_POST['nama'] !== $data['nama']) {
        $updates[] = 'nama = :nama';
        $params[':nama'] = $_POST['nama'];
    }

    // Bed
    if (isset($_POST['bed']) && $_POST['bed'] !== $data['bed']) {
        $updates[] = 'bed = :bed';
        $params[':bed'] = $_POST['bed'];
    }

    // Jenis
    if (isset($_POST['jenis']) && $_POST['jenis'] !== $data['jenis']) {
        $updates[] = 'jenis = :jenis';
        $params[':jenis'] = $_POST['jenis'];
    }

    // Harga
    if (isset($_POST['harga']) && (float)$_POST['harga'] !== $data['harga']) {
        $updates[] = 'harga = :harga';
        $params[':harga'] = (float)$_POST['harga'];
    }

    // Keterangan
    if (isset($_POST['keterangan']) && $_POST['keterangan'] !== $data['keterangan']) {
        $updates[] = 'keterangan = :keterangan';
        $params[':keterangan'] = $_POST['keterangan'];
    }

    // Status
    $selectedStatus = $_POST['status'] === 'tersedia' ? 'tersedia' : 'tidak';
    if ($selectedStatus !== $data['status']) {
        $updates[] = 'status = :status';
        $params[':status'] = $selectedStatus;
    }

    // Build the dynamic SET clause
    $setClause = implode(', ', $updates);

    // Only update if there are changes
    if (!empty($updates)) {
        $stmt = $conn->prepare("UPDATE kamar SET $setClause WHERE id_kamar = :id");
        $params[':id'] = $id; // Add id parameter
        $stmt->execute($params);
    }

    // Redirect after successful update
    header('Location: ?page=kamar');
    exit;
}
?>

<h2>Edit Kamar #<?= $id ?></h2>

<form method="post">
  <!-- Nama -->
  <div class="mb-3">
    <label class="form-label">Nama</label>
    <input type="text" name="nama" class="form-control"
           value="<?= htmlspecialchars($data['nama']) ?>" required>
  </div>

  <!-- Bed -->
  <div class="mb-3">
    <label class="form-label">Bed</label>
    <input type="text" name="bed" class="form-control"
           value="<?= htmlspecialchars($data['bed']) ?>" required>
  </div>

  <!-- Jenis -->
  <div class="mb-3">
    <label class="form-label">Jenis</label>
    <input type="text" name="jenis" class="form-control"
           value="<?= htmlspecialchars($data['jenis']) ?>" required>
  </div>

  <!-- Harga -->
  <div class="mb-3">
    <label class="form-label">Harga</label>
    <input type="number" name="harga" class="form-control"
           value="<?= htmlspecialchars($data['harga']) ?>" required>
  </div>

  <!-- Keterangan -->
  <div class="mb-3">
    <label class="form-label">Keterangan</label>
    <textarea name="keterangan" class="form-control"><?= htmlspecialchars($data['keterangan']) ?></textarea>
  </div>

  <!-- Status -->
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="tersedia" <?= $data['status'] === 'tersedia' ? 'selected' : '' ?>>
        Tersedia
      </option>
      <option value="tidak" <?= $data['status'] === 'tidak' ? 'selected' : '' ?>>
        Tidak
      </option>
    </select>
  </div>

  <button class="btn btn-success">Update</button>
</form>
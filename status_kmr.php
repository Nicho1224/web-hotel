<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $idTransaksi = $_POST['id_transaksi'];
    $status = $_POST['status'];
    
    try {
        $conn->beginTransaction();
        
        if ($status === 'siap_digunakan') {
            // Update status transaksi menjadi selesai
            $stmtTransaksi = $conn->prepare("UPDATE transaksi SET status = 'selesai' WHERE id_transaksi = ?");
            $stmtTransaksi->execute([$idTransaksi]);
            
            // Update status kamar menjadi tersedia
            $stmtKamar = $conn->prepare("
                UPDATE kamar k 
                JOIN transaksi t ON k.id_kamar = t.id_kamar 
                SET k.status = 'tersedia' 
                WHERE t.id_transaksi = ?
            ");
            $stmtKamar->execute([$idTransaksi]);
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Kamar siap digunakan dan transaksi selesai']);
        } elseif ($status === 'belum_siap') {
            // Hapus transaksi jika belum siap
            $stmt = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
            $stmt->execute([$idTransaksi]);
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Transaksi dibatalkan']);
        }
        
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}


// Debugging: Tampilkan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek hanya pegawai yang boleh akses
if (!isset($_SESSION['lv']) || $_SESSION['lv'] !== 'pegawai') {
    die("<script>alert('Akses ditolak!'); window.location.href='login.php';</script>");
}

try {
    // Handle semua jenis aksi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... (kode handle post tetap sama) ...
    }

    // Ambil data transaksi dan kamar
    $stmt = $conn->prepare("
        SELECT 
            t.id_transaksi,
            t.id_kamar,
            t.status,
            t.tgl_checkin,
            t.tgl_checkout,
            k.nama AS nama_kamar,
            k.status AS status_kamar_terbaru,
            tamu.nama AS nama_pemesan
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar
        JOIN tamu ON t.nik = tamu.nik
        WHERE t.status IN ('pending', 'siap digunakan', 'belum siap')
        ORDER BY t.tgl_checkin ASC
    ");
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal mengambil data: " . implode(" ", $stmt->errorInfo()));
    }
    
    $transaksis = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Cek pesan sukses dari URL
$success_message = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Status Kamar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    /* Light theme (default) */
    :root {
        --primary-color: #2a5ee8;
        --hover-color: #1e4ac4;
        --bg-color: #f8f9fa;
        --container-bg: #ffffff;
        --text-color: #212529;
        --text-muted: #6c757d;
        --border-color: #dee2e6;
        --table-stripe: #f8f9fa;
        --shadow-color: rgba(0,0,0,0.08);
        --hover-shadow: rgba(0,0,0,0.08);
    }

    /* Dark theme */
    [data-theme="dark"] {
        --primary-color: #3b82f6;
        --hover-color: #2563eb;
        --bg-color: #0f172a;
        --container-bg: #1e293b;
        --text-color: #e2e8f0;
        --text-muted: #94a3b8;
        --border-color: #334155;
        --table-stripe: #1e293b;
        --shadow-color: rgba(0,0,0,0.2);
        --hover-shadow: rgba(0,0,0,0.3);
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Segoe UI', system-ui, sans-serif;
        color: var(--text-color);
    }

    .container-main {
        background: var(--container-bg);
        border-radius: 12px;
        box-shadow: 0 4px 24px var(--shadow-color);
        padding: 2rem;
        margin-top: 2rem;
    }

    .table-custom {
        --bs-table-bg: transparent;
        --bs-table-striped-bg: var(--table-stripe);
        border-collapse: separate;
        border-spacing: 0 8px;
        color: var(--text-color);
    }

    .table-custom thead th {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem;
    }

    .table-custom tbody tr {
        background: var(--container-bg);
        border-radius: 8px;
        box-shadow: 0 2px 8px var(--shadow-color);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .table-custom tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px var(--hover-shadow);
    }

    .text-muted {
        color: var(--text-muted) !important;
    }

    .form-select {
        background-color: var(--container-bg);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .form-select:focus {
        background-color: var(--container-bg);
        border-color: var(--primary-color);
        color: var(--text-color);
    }

    .btn-light {
        background-color: var(--container-bg);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .btn-light:hover {
        background-color: var(--table-stripe);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .alert-info {
        background-color: var(--container-bg);
        border-color: var(--primary-color);
        color: var(--text-color);
    }

    .bulk-actions {
        background: var(--container-bg);
        border-color: var(--border-color);
    }
</style>

<!-- Add before closing </head> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from parent
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // ...existing DOMContentLoaded code...
});
</script>
</head>
<body>
<div class="container py-4">
    <div class="container-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0 text-primary"><i class="bi bi-building-gear"></i> Manajemen Kamar</h2>
                <p class="text-muted mb-0">Kelola status kamar dan verifikasi pesanan</p>
            </div>
            <a href="index.php?page=home" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($transaksis)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada transaksi yang perlu diproses
        </div>
        <?php else: ?>
        <form id="bulkForm" method="post">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="select-all-checkbox" id="selectAll">
                            </th>
                            <th>Kamar</th>
                            <th>Pemesan</th>
                            <th>Status Kamar</th>
                            <th>Durasi</th>
                            <th>Status Pesanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksis as $index => $t): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_transactions[]" 
                                    value="<?= $t['id_transaksi'] ?>" 
                                    class="transaction-checkbox">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary text-white rounded p-2" style="width: 40px;">
                                        <i class="bi bi-door-closed"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($t['nama_kamar']) ?></div>
                                        <small class="text-muted">ID: <?= $t['id_kamar'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($t['nama_pemesan']) ?></td>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <select name="status_kamar" class="form-select form-select-sm" 
                                        data-id-kamar="<?= $t['id_kamar'] ?>">
                                        <option value="tersedia" <?= $t['status_kamar_terbaru'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                        <option value="tidak tersedia" <?= $t['status_kamar_terbaru'] === 'tidak tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                                        <option value="maintenance" <?= $t['status_kamar_terbaru'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                        <option value="sedang dibersihkan" <?= $t['status_kamar_terbaru'] === 'sedang dibersihkan' ? 'selected' : '' ?>>Sedang Dibersihkan</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-update-status"
                                        data-id-kamar="<?= $t['id_kamar'] ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="text-nowrap">
                                    <?= date('d M', strtotime($t['tgl_checkin'])) ?> - 
                                    <?= date('d M Y', strtotime($t['tgl_checkout'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $status_class = [
                                    'pending' => 'warning',
                                    'siap digunakan' => 'success',
                                    'belum siap' => 'danger',
                                    'berhasil' => 'primary'
                                ][$t['status']];

                                $status_icon = [
                                    'pending' => 'bi-hourglass-split',
                                    'siap digunakan' => 'bi-check-circle',
                                    'belum siap' => 'bi-x-circle',
                                    'berhasil' => 'bi-check-all'
                                ][$t['status']];
                                ?>
                                <span class="status-chip bg-<?= $status_class ?>">
                                    <i class="bi <?= $status_icon ?>"></i>
                                    <?= ucwords($t['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if ($t['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-warning btn-verify"
                                        data-id-transaksi="<?= $t['id_transaksi'] ?>" 
                                        data-action="belum_siap">
                                        <i class="bi bi-x-lg"></i> Belum Siap
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success btn-verify"
                                        data-id-transaksi="<?= $t['id_transaksi'] ?>"
                                        data-action="siap_digunakan">
                                        <i class="bi bi-check-lg"></i> Siap
                                    </button>
                                    <?php elseif ($t['status'] === 'belum siap'): ?>
                                    <button type="button" class="btn btn-sm btn-success btn-verify"
                                        data-id-transaksi="<?= $t['id_transaksi'] ?>"
                                        data-action="siap_digunakan">
                                        <i class="bi bi-check-lg"></i> Tandai Siap
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="bulk-actions">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <span id="selectedCount">0</span> dipilih
                    </div>
                    <button type="submit" name="selesaikan_multiple" class="btn btn-primary" disabled>
                        <i class="bi bi-check-all"></i> Selesaikan yang Dipilih
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk selection
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    const bulkActions = document.querySelector('.bulk-actions');
    const selectedCount = document.getElementById('selectedCount');
    const submitBtn = document.querySelector('[name="selesaikan_multiple"]');

    function updateBulkActions() {
        const checked = document.querySelectorAll('.transaction-checkbox:checked');
        selectedCount.textContent = checked.length;
        submitBtn.disabled = checked.length === 0;
        bulkActions.style.display = checked.length > 0 ? 'block' : 'none';
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBulkActions();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    // Update status kamar
    document.querySelectorAll('.btn-update-status').forEach(button => {
        button.addEventListener('click', function() {
            const idKamar = this.dataset.idKamar;
            const select = this.closest('td').querySelector('select');
            const formData = new FormData();
            formData.append('ubah_status_kamar', true);
            formData.append('id_kamar', idKamar);
            formData.append('status_kamar', select.value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => window.location.reload());
        });
    });

    // Konfirmasi bulk action
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        if (!confirm('Apakah Anda yakin ingin menyelesaikan pesanan yang dipilih?')) {
            e.preventDefault();
        }
    });

    // Add this inside your existing DOMContentLoaded event listener
    document.querySelectorAll('.btn-verify').forEach(button => {
        button.addEventListener('click', function() {
            const idTransaksi = this.dataset.idTransaksi;
            const action = this.dataset.action;

            if (confirm('Apakah Anda yakin ingin mengubah status transaksi ini?')) {
                const formData = new FormData();
                formData.append('update_status', true);
                formData.append('id_transaksi', idTransaksi);
                formData.append('status', action);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(() => window.location.reload());
            }
        });
    });
});
</script>
</body>
</html>
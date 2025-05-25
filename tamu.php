<?php
require 'config.php';

// Ambil semua data tamu
$tamuQuery = $conn->query("SELECT * FROM tamu");
$tamuList = $tamuQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Light theme (default) */
:root {
    --text-color: #212529;
    --bg-color: #ffffff;
    --table-bg: #ffffff;
    --table-border: #dee2e6;
    --table-hover: rgba(0,0,0,0.075);
    --heading-color: #212529;
}

/* Dark theme */
[data-theme="dark"] {
    --text-color: #e2e8f0;
    --bg-color: #0f172a;
    --table-bg: #1e293b;
    --table-border: #334155;
    --table-hover: rgba(255,255,255,0.075);
    --heading-color: #ffffff;
}

/* Apply theme styles */
.container {
    color: var(--text-color);
}

h2 {
    color: var(--heading-color);
}

.table {
    background-color: var(--table-bg);
    color: var(--text-color);
    border-color: var(--table-border);
}

.table thead th {
    background-color: var(--table-bg);
    color: var(--text-color);
    border-color: var(--table-border);
}

.table tbody td {
    border-color: var(--table-border);
}

.table-hover tbody tr:hover {
    background-color: var(--table-hover);
}

/* Keep button colors consistent */
.btn-warning {
    color: #000;
}

.btn-danger {
    color: #fff;
}
</style>

<div class="container mt-5">
    <h2>Daftar Tamu</h2>
    <a href="?page=tambah_tamu" class="btn btn-success mb-3">Tambah Tamu</a>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tamuList as $tamu): ?>
                <tr>
                    <td><?= $tamu['nik'] ?></td>
                    <td><?= $tamu['nama'] ?></td>
                    <td><?= $tamu['email'] ?></td>
                    <td><?= $tamu['alamat'] ?></td>
                    <td>
                        <a href="?page=edit_tamu&nik=<?= $tamu['nik'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="hapus_tamu.php?nik=<?= $tamu['nik'] ?>" class="btn btn-sm btn-danger">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from parent
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});
</script>
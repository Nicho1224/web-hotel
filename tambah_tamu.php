<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

// Check if this is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $mode = $_POST['mode'] ?? '';
    
    try {
        if ($mode === 'baru') {
            $nik    = $_POST['nik'];
            $nama   = $_POST['nama'];
            $email  = $_POST['email'];
            $alamat = $_POST['alamat'];

            // Cek apakah NIK sudah ada
            $cek = $conn->prepare("SELECT COUNT(*) FROM tamu WHERE nik = ?");
            $cek->execute([$nik]);
            if ($cek->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'NIK sudah terdaftar. Silakan pilih tamu yang sudah ada.']);
            } else {
                // Simpan tamu baru
                $stmt = $conn->prepare("INSERT INTO tamu (nik, nama, email, alamat) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nik, $nama, $email, $alamat]);
                
                echo json_encode(['success' => true, 'redirect' => "?page=transaksi&nik=$nik"]);
            }
        } else {
            $nik = $_POST['nik_pilih'];
            echo json_encode(['success' => true, 'redirect' => "?page=transaksi&nik=$nik"]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get guest list for dropdown
$stmt = $conn->query("SELECT nik, nama FROM tamu ORDER BY nama");
$daftarTamu = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html data-theme="light">
<head>
    <title>Tambah Tamu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Light theme (default) */
        :root {
            --text-color: #212529;
            --bg-color: #ffffff;
            --input-bg: #ffffff;
            --input-border: #ced4da;
            --muted-text: #6c757d;
            --card-bg: #ffffff;
            --card-border: #dee2e6;
            --heading-color: #212529;
            --alert-bg: #f8d7da;
            --alert-text: #721c24;
        }

        /* Dark theme */
        [data-theme="dark"] {
            --text-color: #e2e8f0;
            --bg-color: #0f172a;
            --input-bg: #1e293b;
            --input-border: #334155;
            --muted-text: #94a3b8;
            --card-bg: #1e293b;
            --card-border: #334155;
            --heading-color: #ffffff;
            --alert-bg: #481c24;
            --alert-text: #f8d7da;
        }

        /* Apply theme colors */
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .card {
            background-color: var(--card-bg);
            border-color: var(--card-border);
        }

        .card-title {
            color: var(--heading-color);
        }

        .form-label {
            color: var(--text-color);
        }

        .form-check-label {
            color: var(--text-color);
        }

        .form-control,
        .form-select {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--text-color);
        }

        .form-control:focus,
        .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--primary);
            color: var(--text-color);
        }

        .form-text.text-muted {
            color: var(--muted-text) !important;
        }

        select option {
            background-color: var(--input-bg);
            color: var(--text-color);
        }

        .alert-danger {
            background-color: var(--alert-bg);
            border-color: var(--alert-bg);
            color: var(--alert-text);
        }

        /* Fix dropdown options in dark mode */
        .form-select option {
            background-color: var(--input-bg);
            color: var(--text-color);
        }

        /* Fix button text color */
        .btn-primary {
            color: #ffffff;
        }

        /* Ensure text input placeholder is visible */
        .form-control::placeholder {
            color: var(--muted-text);
        }
    </style>
    <script>
        // Add theme handling
        document.addEventListener('DOMContentLoaded', function() {
            // Get saved theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Your existing form toggle code
            function toggleForm() {
                const mode = document.querySelector('input[name="mode"]:checked').value;
                const formBaru = document.getElementById('form-tamu-baru');
                const formLama = document.getElementById('form-pilih-tamu');

                if (mode === 'baru') {
                    formBaru.style.display = 'block';
                    formLama.style.display = 'none';

                    // Enable input TAMU BARU
                    Array.from(formBaru.querySelectorAll('input')).forEach(el => el.disabled = false);
                    formLama.querySelector('select').disabled = true;
                } else {
                    formBaru.style.display = 'none';
                    formLama.style.display = 'block';

                    // Disable input TAMU BARU
                    Array.from(formBaru.querySelectorAll('input')).forEach(el => el.disabled = true);
                    formLama.querySelector('select').disabled = false;
                }
            }
            
            toggleForm();
        });
    </script>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <h5 class="card-title">Tambah Data Tamu / Pilih Tamu</h5>

            <form id="guestForm" method="POST">
                <div class="mb-3">
                    <label class="form-label">Mode:</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" value="lama" checked onchange="toggleForm()">
                        <label class="form-check-label">Pilih Tamu</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" value="baru" onchange="toggleForm()">
                        <label class="form-check-label">Tambah Tamu Baru</label>
                    </div>
                </div>

                <!-- Form Pilih Tamu -->
                <div id="form-pilih-tamu" class="mb-3">
                    <label class="form-label">Pilih Tamu:</label>
                    <select name="nik_pilih" class="form-select" required>
                        <option value="">-- Cari atau pilih tamu --</option>
                        <?php foreach ($daftarTamu as $t): ?>
                            <option value="<?= htmlspecialchars($t['nik']) ?>">
                                <?= htmlspecialchars($t['nik'] . ' — ' . $t['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Jika tidak menemukan tamu, silakan tambah tamu baru.</small>
                </div>

                <!-- Form Tambah Tamu Baru -->
                <div id="form-tamu-baru" class="mb-3" style="display: none;">
                    <label class="form-label">NIK:</label>
                    <input type="text" name="nik" class="form-control mb-2" disabled required>
                    <label class="form-label">Nama Tamu:</label>
                    <input type="text" name="nama" class="form-control mb-2" disabled required>
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control mb-2" disabled required>
                    <label class="form-label">Alamat:</label>
                    <input type="text" name="alamat" class="form-control mb-2" disabled required>
                </div>

                <button type="submit" class="btn btn-primary">Lanjut Booking</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission handling
    document.getElementById('guestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('tambah_tamu.php', {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.error);
            }
        });
    });

    // Initialize form toggle
    toggleForm();
});

function toggleForm() {
    const mode = document.querySelector('input[name="mode"]:checked').value;
    const formBaru = document.getElementById('form-tamu-baru');
    const formLama = document.getElementById('form-pilih-tamu');

    if (mode === 'baru') {
        formBaru.style.display = 'block';
        formLama.style.display = 'none';

        // Enable input TAMU BARU
        Array.from(formBaru.querySelectorAll('input')).forEach(el => el.disabled = false);
        formLama.querySelector('select').disabled = true;
    } else {
        formBaru.style.display = 'none';
        formLama.style.display = 'block';

        // Disable input TAMU BARU
        Array.from(formBaru.querySelectorAll('input')).forEach(el => el.disabled = true);
        formLama.querySelector('select').disabled = false;
    }
}
</script>
</body>
</html>

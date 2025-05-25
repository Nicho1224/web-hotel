<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

if (!isset($_SESSION['id_user']) || !isset($_SESSION['lv'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger'>Data pengguna tidak ditemukan.</div>";
    exit;
}

$error = [];
$success_message = '';

// Ganti Username
if (isset($_POST['ganti_username'])) {
    $new_username = $_POST['username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    if (!empty($new_username) && $new_username !== $user['username']) {
        if (password_verify($current_password, $user['password'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE username = ? AND id_user != ?");
            $stmt->execute([$new_username, $id_user]);
            if ($stmt->fetchColumn() == 0) {
                $sql = "UPDATE user SET username = ? WHERE id_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_username, $id_user]);
                $success_message = 'Username berhasil diperbarui!';
            } else {
                $error[] = "Username sudah digunakan oleh pengguna lain.";
            }
        } else {
            $error[] = "Password saat ini salah. Tidak dapat mengubah username.";
        }
    }
}

// Ganti Password
if (isset($_POST['ganti_password'])) {
    $username_input = $_POST['username_now'] ?? '';
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if ($username_input !== $user['username']) {
        $error[] = "Username tidak cocok.";
    } elseif (!password_verify($old_password, $user['password'])) {
        $error[] = "Password lama salah.";
    } elseif (empty($new_password)) {
        $error[] = "Password baru tidak boleh kosong.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET password = ? WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hashed, $id_user]);
        $success_message = 'Password berhasil diperbarui!';
    }
}

// Ganti Foto Profil
if (isset($_POST['ganti_foto']) && isset($_FILES['profile_image'])) {
    $profile_image = $_FILES['profile_image'];
    
    // Cek apakah file diupload dan valid
    if ($profile_image['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($profile_image['type'], $allowed_types)) {
            // Tentukan direktori penyimpanan dan nama file
            $upload_dir = 'uploads/';
            $new_filename = $upload_dir . basename($profile_image['name']);

            // Pindahkan file ke direktori yang diinginkan
            if (move_uploaded_file($profile_image['tmp_name'], $new_filename)) {
                // Update foto profil di database
                $sql = "UPDATE user SET profile_image = ? WHERE id_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_filename, $id_user]);

                $success_message = 'Foto profil berhasil diperbarui!';
            } else {
                $error[] = 'Terjadi kesalahan saat mengunggah gambar.';
            }
        } else {
            $error[] = 'Hanya file gambar (JPEG, PNG, GIF) yang diperbolehkan.';
        }
    } else {
        $error[] = 'Tidak ada file yang diunggah atau file terlalu besar.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-header {
            background: linear-gradient(135deg, #4a90e2, #7e57c2);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .profile-image-container {
            position: relative;
            width: 250px;
            height: 250px;
            margin: 0 auto;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .profile-change-photo {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .profile-change-photo:hover {
            transform: scale(1.1);
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .action-buttons .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal-content {
            border-radius: 15px;
        }
        
        .modal-header {
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.8rem 1rem;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }
    </style>
    <style>
    /* Light theme (default) */
    :root {
        --text-color: #212529;
        --bg-color: #ffffff;
        --card-bg: #ffffff;
        --card-border: #dee2e6;
        --modal-bg: #ffffff;
        --modal-header: #f8f9fa;
        --input-bg: #ffffff;
        --input-border: #ced4da;
        --text-muted: #6c757d;
        --badge-text: #ffffff;
    }

    /* Dark theme */
    [data-theme="dark"] {
        --text-color: #e2e8f0;
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --card-border: #334155;
        --modal-bg: #1e293b;
        --modal-header: #0f172a;
        --input-bg: #1e293b;
        --input-border: #334155;
        --text-muted: #94a3b8;
        --badge-text: #ffffff;
    }

    /* Apply theme colors */
    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .profile-card {
        background: var(--card-bg);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .modal-content {
        background-color: var(--modal-bg);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .modal-header {
        background: var(--modal-header);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .form-control {
        background-color: var(--input-bg);
        border-color: var(--input-border);
        color: var(--text-color);
    }

    .form-control:focus {
        background-color: var(--input-bg);
        border-color: var(--input-border);
        color: var(--text-color);
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
    }

    .text-muted {
        color: var(--text-muted) !important;
    }

    .btn-light {
        background-color: var(--card-bg);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .btn-light:hover {
        background-color: var(--modal-header);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .profile-change-photo {
        background: var(--card-bg);
    }

    .badge {
        color: var(--badge-text);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from parent
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});
</script>
</head>
<body>

<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="profile-image-container">
                    <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'uploads/default.jpg' ?>"
                         class="profile-image" alt="Profile Image">
                    <div class="profile-change-photo" data-bs-toggle="modal" data-bs-target="#modalFotoProfil">
                        <i class="bi bi-camera-fill text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-8 text-center text-md-start mt-4 mt-md-0">
                <h2 class="mb-1"><?= htmlspecialchars($user['username']) ?></h2>
                <p class="mb-3"><?= ucfirst($_SESSION['lv']) ?></p>
                <div class="action-buttons">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#modalUsername">
                        <i class="bi bi-person-fill me-2"></i>Ganti Username
                    </button>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalPassword">
                        <i class="bi bi-shield-lock-fill me-2"></i>Ganti Password
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="profile-card">
                <h4 class="mb-4"><i class="bi bi-person-badge me-2"></i>Informasi Profil</h4>
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <p class="text-muted mb-0">Username</p>
                    </div>
                    <div class="col-sm-8">
                        <p class="mb-0"><strong><?= htmlspecialchars($user['username']) ?></strong></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <p class="text-muted mb-0">Level Akses</p>
                    </div>
                    <div class="col-sm-8">
                        <span class="badge bg-primary"><?= ucfirst($_SESSION['lv']) ?></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <p class="text-muted mb-0">Status</p>
                    </div>
                    <div class="col-sm-8">
                        <span class="badge bg-success">Aktif</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ganti Foto Profil -->
<div class="modal fade" id="modalFotoProfil" tabindex="-1" aria-labelledby="modalFotoProfilLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Masukkan gambar baru untuk foto profil Anda.</p>
                <div class="mb-3">
                    <label>Foto Profil Baru</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="ganti_foto" class="btn btn-success">Ganti Foto Profil</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ganti Username -->
<div class="modal fade" id="modalUsername" tabindex="-1" aria-labelledby="modalUsernameLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Username</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Masukkan username baru dan verifikasi dengan password saat ini.</p>
                <div class="mb-3">
                    <label>Username Baru</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password Saat Ini</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="ganti_username" class="btn btn-primary">Ganti Username</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal fade" id="modalPassword" tabindex="-1" aria-labelledby="modalPasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Masukkan username dan password lama untuk verifikasi sebelum mengganti password baru.</p>
                <div class="mb-3">
                    <label>Username Saat Ini</label>
                    <input type="text" name="username_now" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password Lama</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="ganti_password" class="btn btn-danger">Ganti Password</button>
            </div>
        </form>
    </div>
</div>

<?php if ($success_message): ?>
<script>
    Swal.fire({
        title: 'Sukses!',
        text: '<?= $success_message ?>',
        icon: 'success',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#4a90e2'
    });
</script>
<?php elseif (!empty($error)): ?>
<script>
    Swal.fire({
        title: 'Gagal!',
        html: '<?= implode("<br>", $error) ?>',
        icon: 'error',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#4a90e2'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

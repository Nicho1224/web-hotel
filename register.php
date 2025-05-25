<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $alamat = $_POST['alamat'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password harus lebih dari 6 karakter!";
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Cek username/email sudah ada
            $check_query = "SELECT * FROM user WHERE username = :username OR email = :email";
            $stmt = $conn->prepare($check_query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Username atau email sudah digunakan!";
            } else {
                // Insert ke tabel user
                $query = "INSERT INTO user (nik, nama, email, alamat, username, password, lv) 
                          VALUES (:nik, :nama, :email, :alamat, :username, :password, 'user')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nik', $nik);
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':alamat', $alamat);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password_hash);

                if ($stmt->execute()) {
                    $lastInsertId = $conn->lastInsertId();
                    $success = "Registrasi berhasil! Silakan login.";

                    // HAPUS BLOK KODE INSERT KE CHAT & CHAT_SESSION JIKA TIDAK DIBUTUHKAN
                } else {
                    $error = "Terjadi kesalahan, coba lagi.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Register - Sistem Hotel</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">

  <style>
    /* Light theme (default) */
    :root {
        --bg-color: #f6f9ff;
        --card-bg: #ffffff;
        --text-color: #212529;
        --text-muted: #6c757d;
        --input-bg: #ffffff;
        --input-border: #ced4da;
        --input-group-bg: #e9ecef;
    }

    /* Dark theme */
    [data-theme="dark"] {
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --text-color: #e2e8f0;
        --text-muted: #94a3b8;
        --input-bg: #1e293b;
        --input-border: #334155;
        --input-group-bg: #0f172a;
    }

    /* Apply theme colors */
    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .card {
        background-color: var(--card-bg);
        border-color: var(--input-border);
    }

    .card-title, 
    .form-label {
        color: var(--text-color);
    }

    .text-center.small {
        color: var(--text-muted);
    }

    .form-control {
        background-color: var(--input-bg);
        border-color: var(--input-border);
        color: var(--text-color);
    }

    .form-control:focus {
        background-color: var(--input-bg);
        color: var(--text-color);
    }

    .input-group-text {
        background-color: var(--input-group-bg);
        border-color: var(--input-border);
        color: var(--text-color);
    }

    textarea.form-control {
        background-color: var(--input-bg);
        color: var(--text-color);
    }

    .alert {
        background-color: var(--card-bg);
        border-color: var(--input-border);
    }
</style>
</head>
<body>

<main>
  <div class="container">
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="col-lg-6 col-md-8 d-flex flex-column align-items-center justify-content-center">

        <div class="d-flex justify-content-center py-4">
          <a href="index.html" class="logo d-flex align-items-center w-auto">
            <img src="assets/img/logo.png" alt="">
            <span class="d-none d-lg-block">Sistem Hotel</span>
          </a>
        </div>

        <div class="card mb-3">
          <div class="card-body">

            <div class="pt-4 pb-2">
              <h5 class="card-title text-center pb-0 fs-4">Create an Account</h5>
              <p class="text-center small">Enter your personal details to create account</p>
            </div>

            <?php if ($error): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form class="row g-3 needs-validation" method="POST" novalidate>
              <div class="col-12">
                <label for="nik" class="form-label">NIK</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                  <input type="text" name="nik" class="form-control" id="nik" 
                         pattern="[0-9]{16}" title="16 digit number" required>
                  <div class="invalid-feedback">Please enter your 16-digit NIK!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="nama" class="form-label">Full Name</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" name="nama" class="form-control" id="nama" required>
                  <div class="invalid-feedback">Please enter your name!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="email" class="form-label">Email</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                  <input type="email" name="email" class="form-control" id="email" required>
                  <div class="invalid-feedback">Please enter a valid Email address!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="alamat" class="form-label">Address</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-house-door"></i></span>
                  <textarea class="form-control" name="alamat" id="alamat" required></textarea>
                  <div class="invalid-feedback">Please enter your address!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="username" class="form-label">Username</label>
                <div class="input-group has-validation">
                  <span class="input-group-text">@</span>
                  <input type="text" name="username" class="form-control" id="username" required>
                  <div class="invalid-feedback">Please choose a username.</div>
                </div>
              </div>

              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password" class="form-control" id="password" required>
                  <div class="invalid-feedback">Please enter your password!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group has-validation">
                  <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                  <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                  <div class="invalid-feedback">Please confirm your password!</div>
                </div>
              </div>

              <div class="col-12">
                <button class="btn btn-primary w-100" type="submit">Create Account</button>
              </div>

              <div class="col-12 text-center">
                <p class="small mb-0">Already have an account? <a href="login.php">Log in</a></p>
              </div>
            </form>

          </div>
        </div>

        <div class="credits text-center">
          Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
        </div>

      </div>
    </section>
  </div>
</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inherit theme from localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});
</script>

</body>
</html>
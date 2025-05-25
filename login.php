<?php
session_start(); // Pastikan ini di baris pertama tanpa output sebelumnya
require 'config.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['id_user'])) {
    $dashboard = match($_SESSION['lv']) {
        'admin' => 'dashboard_admin',
        'pegawai' => 'dashboard_pegawai',
        'user' => 'dashboard_user',
        default => 'dashboard_user'
    };
    header("Location: index.php?page=" . $dashboard);
    exit;
}

// Redirect user jika sudah login
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['lv'] = $user['lv'];
            $_SESSION['logged_in'] = true;

            // Redirect berdasarkan role
            $redirectUrl = match ($user['lv']) {
                'admin'    => 'index.php?page=dashboard_admin',
                'petugas'  => 'index.php?page=dashboard_petugas',
                'pegawai'  => 'index.php?page=dashboard_pegawai',
                'user'     => 'landing_page.php',
                default    => 'index.php'
            };

            // Check if there was a room selection before login
            echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <script>
    const selectedRoom = sessionStorage.getItem('selectedRoom');
    if (selectedRoom) {
        sessionStorage.removeItem('selectedRoom');
        window.location.href = 'dashboard.php?page=user_pemesanan&room=' + selectedRoom;
    } else {
        window.location.href = "$redirectUrl";
    }
  </script>
</body>
</html>
HTML;
            exit();
        } else {
            $error = 'Username atau password salah!';
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem Hotel</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    /* Add to your existing styles */
    .btn-outline-primary {
        border: 2px solid #2b82c3;
        color: #2b82c3;
        background: transparent;
        padding: 8px 20px;
        transition: all 0.3s;
    }

    .btn-outline-primary:hover {
        background: #2b82c3;
        color: white;
    }

    .btn-primary {
        background: #2b82c3;
        border: 2px solid #2b82c3;
        color: white;
        padding: 8px 20px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: #1a6298;
        border-color: #1a6298;
    }

    /* Theme variables */
    :root {
        --bg-color: #f6f9ff;
        --card-bg: #ffffff;
        --text-color: #212529;
        --input-bg: #ffffff;
        --input-border: #ced4da;
        --toggle-bg: #ffffff;
        --toggle-border: #ced4da;
    }

    [data-theme="dark"] {
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --text-color: #e2e8f0;
        --input-bg: #1e293b;
        --input-border: #334155;
        --toggle-bg: #1e293b;
        --toggle-border: #334155;
    }

    /* Apply theme colors */
    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .card {
        background-color: var(--card-bg);
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

    /* Theme toggle button */
    .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--toggle-bg);
        border: 1px solid var(--toggle-border);
        color: var(--text-color);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .theme-toggle:hover {
        transform: scale(1.1);
    }

    .theme-toggle i {
        font-size: 1.2rem;
    }
  </style>
</head>
<body>

<button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode">
    <i class="bi bi-moon-fill"></i>
</button>

<main>
  <div class="container">
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
        <div class="d-flex justify-content-center py-4">
          <a href="index.html" class="logo d-flex align-items-center w-auto">
            <img src="assets/img/logo.png" alt="">
            <span class="d-none d-lg-block">Sistem Hotel</span>
          </a>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <div class="pt-4 pb-2">
              <h5 class="card-title text-center pb-0 fs-4">Login ke Akun Anda</h5>
              <p class="text-center small">Masukkan username & password</p>
            </div>

            <form method="POST" class="row g-3 needs-validation" novalidate>
              <div class="col-12">
                <label for="username" class="form-label">Username</label>
                <div class="input-group has-validation">
                  <input type="text" name="username" class="form-control" id="username" 
                         value="<?= htmlspecialchars($username) ?>" required>
                  <div class="invalid-feedback">Harap masukkan username!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required>
                <div class="invalid-feedback">Harap masukkan password!</div>
              </div>

              <div class="col-12">
                <button class="btn btn-primary w-100" type="submit">Login</button>
              </div>

              <div class="col-12 text-center">
                <p class="small mb-0">Belum punya akun? <a href="register.php">Daftar disini</a></p>
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

<?php if (!empty($error)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Gagal Login',
    text: '<?= addslashes($error) ?>'
  });
</script>
<?php endif; ?>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    
    // Check saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme === 'dark');
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme === 'dark');
    });
    
    function updateIcon(isDark) {
        icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
});
</script>

</body>
</html>
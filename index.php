<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['id_user'], $_SESSION['lv'])) {
    header('Location: login.php');
    exit;
}

// Add these lines to get the user level and chat notifications
$level = $_SESSION['lv'] ?? '';
$user_id = $_SESSION['id_user'];

// Get unread messages count for the chat menu
$unreadMessages = 0;
if ($level === 'admin') {
    // For admin, sum all unread_admin counts
    $unreadMessages = $conn->query("SELECT SUM(unread_admin) FROM user")->fetchColumn() ?? 0;
} else {
    // For regular users, get their unread_user count
    $unreadMessages = $conn->query("SELECT unread_user FROM user WHERE id_user = " . $user_id)->fetchColumn() ?? 0;
}

// Update the allowed pages array
$allowedPages = [
    'admin' => [
        'profile', 'kamar', 'status_kmr', 'tambah_kamar', 'edit_kamar', 
        'tamu', 'tambah_tamu', 'edit_tamu', 'riwayat', 'transaksi', 'manage_users', 
        'chat', 'dashboard_admin', 'testing', 'verifikasi', 'admin_chat', 'admin_feedback'
    ],
    'pegawai' => [
        'profile', 'chat', 'dashboard_pegawai', 'kelola_kamar'
    ],
    'user' => [
        'dashboard_user',
        'user_pemesanan',
        'riwayat_user',
        'profile',
        'chat',
        'pembayaran', 'invoice', 'pilih_pembayaran' // Add pilih_pembayaran here
    ]
];

// Update the default routing logic
if (!isset($_GET['page'])) {
    $dashboard = match($_SESSION['lv']) {
        'admin' => 'dashboard_admin',
        'pegawai' => 'dashboard_pegawai',
        'user' => 'dashboard_user',
        default => 'dashboard_user'
    };
    header("Location: index.php?page=" . $dashboard);
    exit;
}

// Add this after your session start code to automatically update rooms that need cleaning
try {
    $stmt = $conn->prepare("
        UPDATE kamar k
        JOIN transaksi t ON k.id_kamar = t.id_kamar
        SET k.status = 'perlu dibersihkan'
        WHERE t.tgl_checkout <= CURDATE() 
        AND t.status = 'checkout'
        AND k.status NOT IN ('perlu dibersihkan', 'sedang dibersihkan')
    ");
    $stmt->execute();
} catch (Exception $e) {
    error_log("Error updating room status: " . $e->getMessage());
}

// Add this near the other SQL queries at the top of the file:
// Get unread messages count
$unreadMessages = 0;
if ($level === 'admin') {
    $unreadMessages = $conn->query("SELECT SUM(unread_admin) FROM user")->fetchColumn() ?? 0;
} else {
    $unreadMessages = $conn->query("SELECT unread_user FROM user WHERE id_user = " . $_SESSION['id_user'])->fetchColumn() ?? 0;
}

// Update the default page based on user level
$defaultPage = match($_SESSION['lv']) {
    'admin' => 'dashboard_admin',
    'pegawai' => 'dashboard_pegawai',
    'user' => 'dashboard_user',
    default => 'home'
};

$page = $_GET['page'] ?? $defaultPage;

// If someone tries to access 'home', redirect to appropriate dashboard
if ($page === 'home') {
    $dashboard = match($_SESSION['lv']) {
        'admin' => 'dashboard_admin',
        'pegawai' => 'dashboard_pegawai',
        'user' => 'dashboard_user',
        default => 'dashboard_user'
    };
    header("Location: index.php?page=" . $dashboard);
    exit;
}

$page = basename($_GET['page'] ?? $defaultPage);
$level = $_SESSION['lv'];

if (!in_array($page, $allowedPages[$level] ?? [])) {
    die("<div class='alert alert-danger'>Akses ditolak!</div>");
}

// PROSES UPDATE STATUS KAMAR
if ($page === 'status_kmr' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status_kamar'])) {
    $id_kamar = $_POST['id_kamar'];
    $new_status = $_POST['status_kamar'];
    
    $stmt = $conn->prepare("UPDATE kamar SET status = ? WHERE id_kamar = ?");
    if ($stmt->execute([$new_status, $id_kamar])) {
        $success_message = "Status kamar berhasil diupdate!";
        header("Location: index.php?page=status_kmr&success=" . urlencode($success_message));
        exit;
    }
}

// Update the POST handler section
if ($page === 'kelola_kamar' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_kamar = $_POST['id_kamar'];
    $new_status = $_POST['status'];
    $current_status = $_POST['current_status'];
    $id_transaksi = $_POST['id_transaksi'];
    
    try {
        $conn->beginTransaction();
        
        // Update status kamar
        $stmt = $conn->prepare("UPDATE kamar SET status = ? WHERE id_kamar = ?");
        $stmt->execute([$new_status, $id_kamar]);
        
        // Jika selesai dibersihkan
        if ($current_status === 'sedang dibersihkan' && $new_status === 'tersedia') {
            if ($id_transaksi) {
                $stmt = $conn->prepare("UPDATE transaksi SET status = 'selesai' WHERE id_transaksi = ?");
                $stmt->execute([$id_transaksi]);
            }
            $message = "Kamar berhasil dibersihkan dan siap digunakan!";
        } else {
            $message = "Status kamar berhasil diupdate menjadi " . ucwords($new_status);
        }
        
        $conn->commit();
        header("Location: index.php?page=kelola_kamar&success=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: index.php?page=kelola_kamar&error=" . urlencode($e->getMessage()));
        exit;
    }
}

// STATISTIK DASHBOARD
$kamarTersedia    = $conn->query("SELECT COUNT(*) FROM kamar WHERE status = 'tersedia'")->fetchColumn();
$jumlahTamu       = $conn->query("SELECT COUNT(*) FROM tamu")->fetchColumn();
$transaksiHariIni = $conn->query("SELECT COUNT(*) FROM transaksi WHERE DATE(tgl_booking) = CURDATE()")->fetchColumn();
$pesananPending   = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status = 'pending'")->fetchColumn();

$nama_user = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Sistem Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
        /* Light Theme (Default) */
        --primary: #4361ee;
        --primary-light: #8290f7;
        --secondary: #f7fafc;
        --accent: #11cdef;
        --dark: #2d3748;
        --light: #f8f9fa;
        --gray: #a0aec0;
        --sidebar-bg: #ffffff;
        --sidebar-text: #2d3748;
        --sidebar-hover: #f1f5f9;
        --sidebar-active: #4361ee;
        --content-bg: #f6f9ff;
        --text-color: #212529;
        --card-bg: #ffffff;
        --card-border: rgba(0, 0, 0, 0.125);
        --mobile-sidebar-width: 280px;
    }

    /* Dark Theme */
    .theme-dark {
        --primary: #6366f1;
        --primary-light: #818cf8;
        --secondary: #1e293b;
        --accent: #0ea5e9;
        --dark:rgb(36, 49, 63);
        --light:rgb(255, 255, 255);
        --gray: #94a3b8;
        --sidebar-bg: #0f172a;
        --sidebar-text: #e2e8f0;
        --sidebar-hover: #1e293b;
        --sidebar-active: #6366f1;
        --content-bg: #0f172a;
        --text-color: #e2e8f0;
        --card-bg: #1e293b;
        --card-border: #334155;
    }

    /* Blue Theme */
    .theme-blue {
        --primary: #3b82f6;
        --primary-light: #60a5fa;
        --secondary: #eff6ff;
        --accent: #06b6d4;
        --dark: #1e3a8a;
        --light: #f8fafc;
        --gray: #94a3b8;
        --sidebar-bg: #1e40af;
        --sidebar-text: #e2e8f0;
        --sidebar-hover: #1e3a8a;
        --sidebar-active: #3b82f6;
        --content-bg: #eff6ff;
        --text-color: #1e3a8a;
        --card-bg: #ffffff;
        --card-border: #bfdbfe;
    }

    /* Green Theme */
    .theme-green {
        --primary: #10b981;
        --primary-light: #34d399;
        --secondary: #ecfdf5;
        --accent: #06b6d4;
        --dark: #064e3b;
        --light: #f8fafc;
        --gray: #94a3b8;
        --sidebar-bg: #047857;
        --sidebar-text: #e2e8f0;
        --sidebar-hover: #065f46;
        --sidebar-active: #10b981;
        --content-bg: #ecfdf5;
        --text-color: #064e3b;
        --card-bg: #ffffff;
        --card-border: #a7f3d0;
    }

    body {
        display: flex;
        min-height: 100vh;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: var(--content-bg);
        color: var(--text-color);
        overflow-x: hidden;
        -webkit-text-size-adjust: 100%;
        -webkit-tap-highlight-color: transparent;
    }

    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100vh;
        background-color: var(--sidebar-bg);
        color: var(--sidebar-text);
        padding-top: 0;
        z-index: 1000;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--primary-light) var(--sidebar-hover);
    }

    /* Sticky header */
    .sidebar-header {
        position: sticky;
        top: 0;
        z-index: 1001;
        background-color: var(--sidebar-bg);
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 0;
    }

    /* Scrollable content container */
    .sidebar-nav-container {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 20px;
    }

    /* Dark theme shadow lebih terlihat */
    .theme-dark .sidebar {
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    }

    /* Blue theme shadow dengan nuansa biru */
    .theme-blue .sidebar {
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
    }

    /* Green theme shadow dengan nuansa hijau */
    .theme-green .sidebar {
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
    }

    .sidebar a {
        color: var(--sidebar-text);
        display: flex;
        align-items: center;
        padding: 12px 15px;
        text-decoration: none;
        margin-bottom: 5px;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .sidebar a:hover {
        background-color: var(--sidebar-hover);
        color: var(--primary);
    }

    .sidebar .active {
        background-color: var(--sidebar-hover);
        color: var(--primary);
        font-weight: 600;
        border-left: 3px solid var(--primary);
    }

    .sidebar .nav-link i {
        font-size: 18px;
        margin-right: 10px;
        color: var(--primary);
    }

    .sidebar .nav-link.collapsed {
        color: var(--sidebar-text);
        border-left: 3px solid transparent;
    }

    .sidebar .nav-link.collapsed i {
        color: var(--gray);
    }

    .sidebar .nav-link.collapsed:hover {
        color: var(--primary);
    }

    .sidebar .nav-link.collapsed:hover i {
        color: var(--primary);
    }

    .sidebar .nav-content {
        padding: 0;
        list-style: none;
    }

    .sidebar .nav-content a {
        padding: 10px 15px 10px 50px;
        font-size: 14px;
    }

    .sidebar .nav-item .bi-chevron-down {
        margin-left: auto;
        transition: transform 0.3s;
        font-size: 14px;
    }

    .sidebar .nav-item .collapsed .bi-chevron-down {
        transform: rotate(0deg);
    }

    .sidebar .nav-item:not(.collapsed) .bi-chevron-down {
        transform: rotate(180deg);
    }

    .sidebar .nav-heading {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--gray);
        font-weight: 600;
        letter-spacing: 1px;
        padding: 12px 15px 10px 15px;
        margin-top: 10px;
    }

    .content {
        flex-grow: 1;
        padding: 20px;
        background-color: var(--content-bg);
        margin-left: 280px;
        transition: all 0.3s ease;
        min-height: 100vh;
        overflow-y: auto;
        width: calc(100% - 280px);
        position: relative;
    }

    .sidebar .nav-item {
        margin-bottom: 5px;
    }

    .sidebar .nav-content {
        margin-left: 0;
        padding-left: 0;
    }

    .sidebar-brand {
        color: var(--dark);
        font-weight: 700;
        font-size: 1.75rem;
        display: flex;
        align-items: center;
        transition: color 0.3s ease;
    }

    .sidebar-brand i {
        margin-right: 12px;
        color: var(--primary);
        font-size: 2rem;
        transition: color 0.3s ease;
    }

    .sidebar-nav {
        padding: 0 1rem;
        list-style: none;
        margin-top: 0;
    }

    /* Penyesuaian khusus untuk tema dark */
    .theme-dark .sidebar-brand {
        color: var(--light);
    }

    /* Penyesuaian untuk tema blue dan green */
    .theme-blue .sidebar-brand,
    .theme-green .sidebar-brand {
        color: var(--sidebar-text);
    }

    /* Theme Selector */
    .theme-selector {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1999;
    }

    .theme-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid var(--primary);
        cursor: pointer;
        margin: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        transition: transform 0.2s;
    }

    .theme-btn:hover {
        transform: scale(1.1);
    }

    .theme-light-btn {
        background-color: #f8f9fa;
        color: #212529 !important;
    }

    .theme-dark-btn {
        background-color: #1e293b;
    }

    .theme-blue-btn {
        background-color: #1e40af;
    }

    .theme-green-btn {
        background-color: #047857;
    }

    /* Card styling for all themes */
    .card {
        background-color: var(--card-bg);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    .table {
        color: var(--text-color);
    }

    /* Mobile Toggle Button */
    .mobile-nav-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
        background: var(--primary);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }

    .mobile-nav-toggle:hover {
        background: var(--primary-light);
    }

    /* Overlay for mobile sidebar */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
        transition: all 0.3s;
        opacity: 0;
        visibility: hidden;
    }

    .sidebar-overlay.sidebar-mobile-show {
        display: block;
        opacity: 1;
        visibility: visible;
    }

    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: var(--sidebar-hover);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-light);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary);
    }

    /* Responsive Styles */
    @media (max-width: 1199.98px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }
        
        .sidebar.sidebar-mobile-show {
            transform: translateX(0);
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .content {
            margin-left: 0;
            width: 100%;
            padding-top: 70px;
        }
        
        .mobile-nav-toggle {
            display: flex;
        }
        
        /* Perbaikan untuk dropdown di mobile */
        .sidebar.sidebar-mobile-show .nav-content.collapse:not(.show) {
            display: none;
        }
        
        .sidebar.sidebar-mobile-show .nav-content.collapse.show {
            display: block;
        }
    }

    @media (max-width: 767.98px) {
        .content {
            padding: 15px;
        }
        
        .sidebar {
            width: 260px;
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
        }
        
        .theme-selector {
            bottom: 15px;
            right: 15px;
        }
        
        .theme-btn {
            width: 35px;
            height: 35px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 575.98px) {
        .content {
            padding: 10px;
        }
        
        .sidebar {
            width: 240px;
        }
        
        .sidebar-header {
            padding: 1rem 0.75rem;
        }
        
        .sidebar a {
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        
        .sidebar .nav-content a {
            padding: 8px 12px 8px 40px;
            font-size: 0.85rem;
        }
        
        .mobile-nav-toggle {
            width: 36px;
            height: 36px;
            top: 15px;
            left: 15px;
        }
    }

    @media (max-width: 360px) {
        .sidebar {
            width: 220px;
        }
        
        .sidebar a {
            padding: 8px 10px;
            font-size: 0.85rem;
        }
        
        .sidebar .nav-content a {
            padding: 7px 10px 7px 35px;
            font-size: 0.8rem;
        }
        
        .sidebar-brand {
            font-size: 1.3rem;
        }
        
        .sidebar-brand i {
            font-size: 1.7rem;
        }
    }

    /* Animation for sidebar */
    @keyframes slideIn {
        from { transform: translateX(-100%); }
        to { transform: translateX(0); }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Touch device optimizations */
    @media (hover: none) {
        .sidebar a {
            padding: 14px 15px;
        }
        
        .sidebar .nav-content a {
            padding: 12px 15px 12px 50px;
        }
    }
    /* Add this new style to hide/show the toggle button */
    .mobile-nav-toggle.sidebar-open {
        display: none;
    }

    .theme-toggle {
        background-color: var(--primary);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .theme-toggle:hover {
        transform: scale(1.1);
        background-color: var(--primary-light);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
    }

    /* Dark theme variables */
    [data-theme="dark"] {
        --primary: #6366f1;
        --primary-light: #818cf8;
        --secondary: #1e293b;
        --accent: #0ea5e9;
        --dark: rgb(36, 49, 63);
        --light: rgb(255, 255, 255);
        --gray: #94a3b8;
        --sidebar-bg: #0f172a;
        --sidebar-text: #e2e8f0;
        --sidebar-hover: #1e293b;
        --sidebar-active: #6366f1;
        --content-bg: #0f172a;
        --text-color: #e2e8f0;
        --card-bg: #1e293b;
        --card-border: #334155;
        --table-text: #e2e8f0;
        --heading-color: #ffffff;
        --link-color: #60a5fa;
        --text-muted: #94a3b8;
    }

    /* Add these new styles for dark mode text */
    [data-theme="dark"] {
        /* Text colors */
        color: var(--text-color);
    }

    [data-theme="dark"] h1,
    [data-theme="dark"] h2,
    [data-theme="dark"] h3,
    [data-theme="dark"] h4,
    [data-theme="dark"] h5,
    [data-theme="dark"] h6 {
        color: var(--heading-color);
    }

    [data-theme="dark"] .table {
        color: var(--table-text);
    }

    [data-theme="dark"] .text-muted {
        color: var(--text-muted) !important;
    }

    [data-theme="dark"] .card {
        color: var(--text-color);
    }

    [data-theme="dark"] a:not(.btn) {
        color: var(--link-color);
    }

    [data-theme="dark"] .alert {
        background-color: var(--card-bg);
        border-color: var(--card-border);
    }

    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: var(--dark);
        border-color: var(--card-border);
        color: var(--text-color);
    }

    [data-theme="dark"] .form-control:focus,
    [data-theme="dark"] .form-select:focus {
        background-color: var(--dark);
        color: var(--text-color);
        border-color: var(--primary);
    }

    [data-theme="dark"] .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    [data-theme="dark"] .dropdown-menu {
        background-color: var(--card-bg);
        border-color: var(--card-border);
    }

    [data-theme="dark"] .dropdown-item {
        color: var(--text-color);
    }

    [data-theme="dark"] .dropdown-item:hover {
        background-color: var(--sidebar-hover);
        color: var(--primary);
    }

    /* Light theme variables (default) */
    [data-theme="light"] {
        --primary: #4361ee;
        --primary-light: #8290f7;
        --secondary: #f7fafc;
        --accent: #11cdef;
        --dark: #2d3748;
        --light: #f8f9fa;
        --gray: #a0aec0;
        --sidebar-bg: #ffffff;
        --sidebar-text: #2d3748;
        --sidebar-hover: #f1f5f9;
        --sidebar-active: #4361ee;
        --content-bg: #f6f9ff;
        --text-color: #212529;
        --card-bg: #ffffff;
        --card-border: rgba(0, 0, 0, 0.125);
    }
  </style>
</head>
<body>
    <aside class="sidebar">
    <?php if ($level == 'admin'): ?>
        <!-- Dashboard -->
        <a href="?page=dashboard_admin" class="<?= $page == 'dashboard_admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Manajemen Kamar -->
        <div class="nav-heading">Manajemen Kamar</div>
        <a href="?page=kamar" class="<?= $page == 'kamar' ? 'active' : '' ?>">
            <i class="bi bi-door-closed"></i> Data Kamar
        </a>
        <a href="?page=status_kmr" class="<?= $page == 'status_kmr' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Status Kamar
        </a>
        <a href="?page=tambah_kamar" class="<?= $page == 'tambah_kamar' ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> Tambah Kamar
        </a>

        <!-- Manajemen Tamu -->
        <div class="nav-heading">Manajemen Tamu</div>
        <a href="?page=tamu" class="<?= $page == 'tamu' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Data Tamu
        </a>
        <a href="?page=tambah_tamu" class="<?= $page == 'tambah_tamu' ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i> Tambah Tamu
        </a>

        <!-- Verifikasi -->
        <div class="nav-heading">Verifikasi</div>
        <a href="?page=verifikasi" class="<?= $page == 'verifikasi' ? 'active' : '' ?>">
            <i class="bi bi-check-circle"></i> Verifikasi Pesanan
            <?php
            $pending = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status = 'pending'")->fetchColumn();
            if ($pending > 0):
            ?>
            <span class="badge bg-danger rounded-pill ms-2"><?= $pending ?></span>
            <?php endif; ?>        </a>        <!-- Laporan -->
        <div class="nav-heading">Laporan</div>
        <a href="?page=riwayat" class="<?= $page == 'riwayat' ? 'active' : '' ?>">
            <i class="bi bi-clipboard-data"></i> Riwayat
        </a>
        
        <!-- Feedback Management -->
        <a href="?page=admin_feedback" class="<?= $page == 'admin_feedback' ? 'active' : '' ?>">
            <i class="bi bi-star-fill"></i> Manajemen Feedback
            <?php
            // Count new feedback that needs response
            try {
                $newFeedback = $conn->query("SELECT COUNT(*) FROM transaksi WHERE kategori_feedback IS NOT NULL AND status_feedback = 'baru'")->fetchColumn();
                if ($newFeedback > 0):
            ?>
            <span class="badge bg-danger rounded-pill ms-2"><?= $newFeedback ?></span>
            <?php 
                endif;
            } catch (PDOException $e) {
                // Silently fail if query fails
            }
            ?>
        </a>


    <?php elseif ($level == 'pegawai'): ?>
        <a href="?page=dashboard_pegawai" class="<?= $page == 'dashboard_pegawai' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard Pegawai
        </a>
        
        <div class="nav-heading">Operasional</div>
        <a href="?page=kelola_kamar" class="<?= $page == 'kelola_kamar' ? 'active' : '' ?>">
            <i class="bi bi-building-gear"></i> Kelola Status Kamar
        </a>

    <?php elseif ($level == 'user'): ?>
        <a href="?page=dashboard_user" class="<?= $page == 'dashboard_user' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard Tamu
        </a>
        
        <div class="nav-heading">Booking</div>
        <a href="?page=user_pemesanan" class="<?= $page == 'user_pemesanan' ? 'active' : '' ?>">
            <i class="bi bi-cart"></i> Pesan Kamar
        </a>
    <?php endif; ?>

    <!-- Common menu items for all users -->
    <div class="nav-heading">Akun</div>
    <a href="?page=profile" class="<?= $page == 'profile' ? 'active' : '' ?>">
        <i class="bi bi-person-circle"></i> Profil
    </a>
    <a href="?page=chat" class="<?= $page == 'chat' ? 'active' : '' ?>">
        <i class="bi bi-chat-dots-fill"></i> Chat
        <?php if ($unreadMessages > 0): ?>
            <span class="badge bg-danger rounded-pill ms-2"><?= $unreadMessages ?></span>
        <?php endif; ?>
    </a>
    <a href="logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</aside>

<div class="content">
  <div class="topbar">
    <h4>Selamat Datang, <?= $nama_user ?></h4>
    <div class="badge bg-primary"><?= strtoupper($level) ?></div>
  </div>

  <div class="main-content">
    <?php if ($page === 'status_kmr'): ?>
      <?php
      $stmt = $conn->query("
          SELECT 
              k.*,
              t.nik,
              tamu.nama AS nama_pemesan,
              t.tgl_checkin,
              t.tgl_checkout
          FROM kamar k
          LEFT JOIN transaksi t ON k.id_kamar = t.id_kamar
          LEFT JOIN tamu ON t.nik = tamu.nik
          ORDER BY k.status DESC
      ");
      $kamarList = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      
      <div class="container py-4">
        <h2 class="mb-4">🛠️ Manajemen Status Kamar</h2>
        
        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <table class="table table-bordered table-hover">
          <thead class="table-primary">
            <tr>
              <th>#</th>
              <th>Nama Kamar</th>
              <th>Status</th>
              <th>Pemesan</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kamarList as $index => $kamar): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($kamar['nama']) ?></td>
              <td>
                <span class="badge bg-<?= match($kamar['status']) {
                    'tersedia' => 'success',
                    'tidak tersedia' => 'danger',
                    'maintenance' => 'warning',
                    'sedang dibersihkan' => 'info',
                    default => 'secondary'
                } ?>">
                  <?= ucwords($kamar['status']) ?>
                </span>
              </td>
              <td>
                <?= $kamar['nama_pemesan'] ? htmlspecialchars($kamar['nama_pemesan']) : '-' ?>
                <?php if ($kamar['nik']): ?>
                  <br><small class="text-muted">NIK: <?= $kamar['nik'] ?></small>
                <?php endif; ?>
              </td>
              <td><?= $kamar['tgl_checkin'] ?? '-' ?></td>
              <td><?= $kamar['tgl_checkout'] ?? '-' ?></td>
              <td>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="id_kamar" value="<?= $kamar['id_kamar'] ?>">
                  <select name="status_kamar" class="form-select form-select-sm">
                    <option value="tersedia" <?= $kamar['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="tidak tersedia" <?= $kamar['status'] === 'tidak tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                    <option value="maintenance" <?= $kamar['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    <option value="sedang dibersihkan" <?= $kamar['status'] === 'sedang dibersihkan' ? 'selected' : '' ?>>Sedang Dibersihkan</option>
                  </select>
                  <button type="submit" name="ubah_status_kamar" class="btn btn-warning btn-sm">
                    <i class="bi bi-arrow-repeat"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($page === 'kelola_kamar'): ?>
    <?php
    // Get rooms that need attention
    $stmt = $conn->query("
        SELECT 
            k.*,
            t.id_transaksi,
            t.tgl_checkin,
            t.tgl_checkout,
            t.status as status_transaksi,
            tm.nama as nama_tamu
        FROM kamar k
        LEFT JOIN transaksi t ON k.id_kamar = t.id_kamar 
        LEFT JOIN tamu tm ON t.nik = tm.nik
        WHERE 
            k.status IN ('perlu dibersihkan', 'sedang dibersihkan')
            OR (t.tgl_checkout <= CURDATE() AND t.status = 'checkout')
        ORDER BY 
            CASE 
                WHEN k.status = 'perlu dibersihkan' THEN 1
                WHEN k.status = 'sedang dibersihkan' THEN 2
                ELSE 3
            END,
            t.tgl_checkout DESC
    ");
    $kamarList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="container py-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building-gear"></i> Manajemen Status Kamar</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($_GET['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Kamar</th>
                                <th>Status</th>
                                <th>Tamu Terakhir</th>
                                <th>Check-out</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kamarList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                        Semua kamar sudah bersih dan siap digunakan
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kamarList as $index => $kamar): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($kamar['nama']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= match($kamar['status']) {
                                            'tersedia' => 'success',
                                            'perlu dibersihkan' => 'warning',
                                            'sedang dibersihkan' => 'info',
                                            default => 'secondary'
                                        } ?>">
                                            <?= ucwords($kamar['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($kamar['nama_tamu'] ?? '-') ?></td>
                                    <td><?= $kamar['tgl_checkout'] ? date('d/m/Y', strtotime($kamar['tgl_checkout'])) : '-' ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="id_kamar" value="<?= $kamar['id_kamar'] ?>">
                                            <input type="hidden" name="id_transaksi" value="<?= $kamar['id_transaksi'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $kamar['status'] ?>">
                                            
                                            <?php if ($kamar['status'] === 'perlu dibersihkan'): ?>
                                                <input type="hidden" name="status" value="sedang dibersihkan">
                                                <button type="submit" name="update_status" class="btn btn-warning btn-sm w-100">
                                                    <i class="bi bi-brush"></i> Mulai Bersihkan
                                                </button>
                                            <?php elseif ($kamar['status'] === 'sedang dibersihkan'): ?>
                                                <input type="hidden" name="status" value="tersedia">
                                                <button type="submit" name="update_status" 
                                                        class="btn btn-success btn-sm w-100"
                                                        onclick="return confirm('Apakah pembersihan kamar sudah selesai?')">
                                                    <i class="bi bi-check-lg"></i> Selesai Dibersihkan
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
      <!-- Tetap pertahankan logic untuk halaman lain -->
      <?php
      $allowed = [
          'admin'   => $allowedPages['admin'],
          'pegawai' => $allowedPages['pegawai'],
          'user'    => $allowedPages['user']
      ];      if (in_array($page, $allowed[$level] ?? [])) {
          $file = "$page.php";
          if (file_exists($file)) {              // For admin_chat we need to use require_once to avoid session_start conflicts
              if ($page === 'admin_chat') {
                  // Instead of include, redirect to the page directly
                  $type = $_GET['type'] ?? '';
                  $redirect = "admin_chat.php" . ($type ? "?type=$type" : "");
                  echo "<script>window.location.href = '$redirect';</script>";
                  exit;
              } else if ($page === 'admin_feedback') {
                  // For admin_feedback, define that we're including it in index.php
                  define('INCLUDED_IN_INDEX', true);
                  include $file;
              } else {
                  include $file;
              }
          } else {
              echo "<div class='alert alert-danger'>Halaman tidak ditemukan!</div>";
          }
      } else {
          echo "<div class='alert alert-warning'>Akses ditolak!</div>";
      }
      ?>
    <?php endif; ?>
  </div>
</div>

<div class="theme-selector">
    <button class="theme-btn theme-toggle" title="Toggle Light/Dark Mode">
        <i class="bi bi-moon-fill"></i>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scroll for sidebar links
document.querySelectorAll('.sidebar a[href^="?page="]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        // Reset scroll position when changing pages
        window.scrollTo(0, 0);
    });
});

// Save scroll position for sidebar
const sidebar = document.querySelector('.sidebar');
sidebar.addEventListener('scroll', function() {
    localStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
});

// Restore scroll position
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = localStorage.getItem('sidebarScrollPos');
    if (scrollPos) {
        sidebar.scrollTop = parseInt(scrollPos);
    }
});

// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) {
        console.error('Theme toggle button not found');
        return;
    }
    
    const icon = themeToggle.querySelector('i');
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme === 'dark');
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme === 'dark');
        console.log('Theme changed to:', newTheme);
    });
    
    function updateIcon(isDark) {
        if (icon) {
            icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }
});
});
</script>
</body>
</html>
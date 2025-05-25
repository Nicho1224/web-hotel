<?php
require 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['id_user']);

// If user is logged in, get their data and booking stats
if ($isLoggedIn) {
    $userId = $_SESSION['id_user'];
    
    // Get user details
    $userStmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    // Get user's bookings stats
    $activeBookings = $conn->prepare("
        SELECT COUNT(*) FROM transaksi 
        WHERE id_user = ? AND status = 'siap digunakan'
    ");
    $activeBookings->execute([$userId]);
    $activeCount = $activeBookings->fetchColumn();

    // Get pending payments
    $pendingPayments = $conn->prepare("
        SELECT COUNT(*) FROM transaksi 
        WHERE id_user = ? AND status = 'pending'
    ");
    $pendingPayments->execute([$userId]);
    $pendingCount = $pendingPayments->fetchColumn();

    // Get completed stays
    $completedStays = $conn->prepare("
        SELECT COUNT(*) FROM transaksi 
        WHERE id_user = ? AND status = 'berhasil'
    ");
    $completedStays->execute([$userId]);
    $completedCount = $completedStays->fetchColumn();

    // Get upcoming bookings
    $upcomingBookings = $conn->prepare("
        SELECT t.*, k.nama as nama_kamar
        FROM transaksi t
        JOIN kamar k ON t.id_kamar = k.id_kamar
        WHERE t.id_user = ? 
        AND t.tgl_checkin > CURRENT_DATE
        ORDER BY t.tgl_checkin ASC
        LIMIT 5
    ");
    $upcomingBookings->execute([$userId]);
    $upcomingList = $upcomingBookings->fetchAll();
}

// Get all available rooms for the room selection section
$roomsStmt = $conn->prepare("SELECT * FROM kamar WHERE status = 'tersedia' ORDER BY harga ASC");
$roomsStmt->execute();
$availableRooms = $roomsStmt->fetchAll();

// Get the requested section from URL parameter
$currentSection = isset($_GET['section']) ? $_GET['section'] : 'home';

// Set default booking dates based on URL parameter
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$maxDate = date('Y-m-d', strtotime('+30 days')); // Allow booking up to 30 days in advance

if (isset($_GET['booking_date'])) {
  $bookingDate = $_GET['booking_date'];
  
  if ($bookingDate === 'today') {
    // Set the check-in date to today
    $defaultCheckin = $today;
    $defaultCheckout = $tomorrow;
    $minCheckout = $tomorrow; // Minimum checkout is tomorrow when checking in today
  } elseif ($bookingDate === 'tomorrow') {
    // Set the check-in date to tomorrow
    $defaultCheckin = $tomorrow;
    $defaultCheckout = date('Y-m-d', strtotime('+2 days'));
    $minCheckout = date('Y-m-d', strtotime('+2 days')); // Minimum checkout is day after tomorrow
  } else {
    // Default dates
    $defaultCheckin = $tomorrow;
    $defaultCheckout = date('Y-m-d', strtotime('+2 days'));
    $minCheckout = date('Y-m-d', strtotime('+1 day', strtotime($defaultCheckin))); // Day after check-in
  }
} else {
  // No booking_date parameter, use defaults
  $defaultCheckin = $tomorrow;
  $defaultCheckout = date('Y-m-d', strtotime('+2 days'));
  $minCheckout = date('Y-m-d', strtotime('+1 day', strtotime($defaultCheckin))); // Day after check-in
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Serenity Haven Hotel - Your Luxury Retreat</title>
  <meta name="description" content="Luxury hotel with premium facilities in the heart of the city">
  <meta name="keywords" content="luxury hotel, boutique hotel, premium accommodation">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700,1,800&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    /* Theme variables */
    :root {
        --bg-color: #ffffff;
        --text-color: #2d3748;
        --heading-color: #1a365d;
        --card-bg: #ffffff;
        --card-shadow: rgba(0,0,0,0.1);
        --section-bg: #f8f9fa;
        --footer-bg: #1a365d;
        --footer-text: #ffffff;  /* Keep footer text white in light mode */
        --form-bg: #ffffff;
        --form-border: #ced4da;
        --nav-bg: rgba(255, 255, 255, 0.98);
        --nav-text: #4a5568;
        --btn-text: #ffffff;
        --price-color: #2b82c3;
        --link-color: #2b82c3;
        --link-hover: #1a6298;
        --nav-active: #2b82c3;
        --icon-color: #2b82c3;
        --border-color: #e2e8f0;
    }

    [data-theme="dark"] {
        --bg-color: #0f172a;
        --text-color: #e2e8f0;
        --heading-color: #38bdf8; /* Changed to a more elegant light blue */
        --card-bg: #1e293b;
        --card-shadow: rgba(0,0,0,0.3);
        --section-bg: #111827;
        --footer-bg: #0f172a;
        --footer-text: #ffffff;  /* Keep footer text white in dark mode */
        --form-bg: #1e293b;
        --form-border: #334155;
        --nav-bg: rgba(15, 23, 42, 0.98);
        --nav-text: #cbd5e1;
        --btn-text: #ffffff;
        --price-color: #38bdf8;
        --link-color: #38bdf8;
        --link-hover: #0ea5e9;
        --nav-active: #38bdf8;
        --icon-color: #38bdf8;
        --border-color: #334155;
    }

    /* Enhanced dark mode text styles for better visibility */
    [data-theme="dark"] .text-primary {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .service-item h4 {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .room-item h4 {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .section-title h2 {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .contact-info h4 {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .price {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .nav-link {
        color: #cbd5e1;
    }

    [data-theme="dark"] .nav-link:hover,
    [data-theme="dark"] .nav-link.active {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .bi-check2-circle {
        color: #38bdf8 !important;
    }

    [data-theme="dark"] .text-muted {
        color: #94a3b8 !important;
    }
    
    /* Additional text visibility improvements for dark mode */
    [data-theme="dark"] h1, 
    [data-theme="dark"] h2:not(#testimonials h2), 
    [data-theme="dark"] h3, 
    [data-theme="dark"] h4, 
    [data-theme="dark"] h5, 
    [data-theme="dark"] h6 {
        color: #f8fafc !important;
    }
    
    /* Ensure "Apa Kata Tamu Kami" heading is black in dark mode */
    [data-theme="dark"] #testimonials h2 {
        color: #000000 !important;
    }
    
    [data-theme="dark"] p, 
    [data-theme="dark"] span:not(.badge), 
    [data-theme="dark"] label, 
    [data-theme="dark"] a:not(.btn) {
        color: #e2e8f0 !important;
    }
    
    [data-theme="dark"] a:not(.btn):hover {
        color: #38bdf8 !important;
    }
    
    [data-theme="dark"] .card {
        background-color: #1e293b;
        border-color: #334155;
    }
    
    [data-theme="dark"] .section-title h2 {
        color: #38bdf8 !important;
    }
    
    [data-theme="dark"] .table {
        color: #e2e8f0;
    }

    /* Fix button colors in dark mode */
    [data-theme="dark"] .btn-primary {
        background-color: #38bdf8;
        border-color: #38bdf8;
    }

    [data-theme="dark"] .btn-outline-primary {
        color: #38bdf8;
        border-color: #38bdf8;
    }

    [data-theme="dark"] .btn-outline-primary:hover {
        background-color: #38bdf8;
        color: #ffffff;
    }

    /* Global Styles */
    body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Playfair Display', serif;
        color: var(--heading-color);
    }

    /* Header Styles */
    .header {
        background: var(--nav-bg) !important;
        backdrop-filter: blur(10px) saturate(180%);
        -webkit-backdrop-filter: blur(10px) saturate(180%);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 0;
        transition: all 0.3s ease;
    }

    .sitename {
        font-weight: 700;
        font-size: 1.75rem;
        letter-spacing: -0.5px;
        color: var(--heading-color);
    }

    /* Navigation Menu */
    .navmenu ul {
        display: flex;
        gap: 1.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .navmenu a {
        position: relative;
        font-weight: 500;
        padding: 0.5rem 0;
        color: var(--nav-text);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .navmenu a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--nav-active);
        transition: width 0.4s cubic-bezier(0.22, 0.61, 0.36, 1);
        opacity: 1;
        transform-origin: left center;
    }

    .navmenu a:hover::after,
    .navmenu a.active::after {
        width: 100%;
    }

    /* Ensure underline disappears elegantly when not hovering */
    .navmenu a:not(:hover):not(.active)::after {
        width: 0;
        transition: width 0.3s cubic-bezier(0.55, 0.055, 0.675, 0.19);
    }

    /* Special handling for dropdown toggle - keep its underline */
    .navmenu .dropdown-toggle::after {
      display: none !important; /* Hide Bootstrap's default caret */
    }

    /* Custom caret for dropdown */
    .navmenu .dropdown-toggle .bi-chevron-down {
      transition: transform 0.3s ease;
    }

    .navmenu .dropdown-toggle:hover .bi-chevron-down {
      transform: rotate(180deg);
    }

    /* Add a decorative underline specifically for the dropdown toggle */
    .navmenu .dropdown-toggle::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--nav-active);
      transition: width 0.4s cubic-bezier(0.22, 0.61, 0.36, 1);
    }

    .navmenu .dropdown-toggle:hover::before,
    .navmenu .dropdown-toggle.active::before {
      width: 100%;
    }

    .navmenu .dropdown-toggle:not(:hover):not(.active)::before {
      width: 0;
      transition: width 0.3s cubic-bezier(0.55, 0.055, 0.675, 0.19);
    }

    /* Ensure dropdown items don't have the same underline effect */
    .navmenu .dropdown-item::after {
      display: none;
    }

    .navmenu .dropdown-item {
      position: relative;
      overflow: hidden;
    }

    /* Add a subtle highlight effect for dropdown items instead */
    .navmenu .dropdown-item::before {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 4px;
      height: 0;
      background: var(--nav-active);
      transition: height 0.3s ease;
    }

    .navmenu .dropdown-item:hover::before {
      height: 100%;
    }

    /* Add these styles for the dropdown menu */
    .navmenu .dropdown {
      position: relative;
    }

    .navmenu .dropdown-toggle {
      display: flex;
      align-items: center;
    }

    .navmenu .dropdown-menu {
      position: absolute;
      background-color: var(--card-bg);
      border-radius: 8px;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
      border: 1px solid var(--border-color);
      padding: 0.5rem 0;
      min-width: 200px;
      z-index: 1000;
      display: none;
      margin-top: 0.5rem;
    }

    .navmenu .dropdown-menu.show {
      display: block;
    }

    .navmenu .dropdown-item {
      display: block;
      width: 100%;
      padding: 0.5rem 1.5rem;
      clear: both;
      font-weight: 500;
      text-align: inherit;
      white-space: nowrap;
      background-color: transparent;
      border: 0;
      color: var(--text-color);
      transition: all 0.2s ease;
    }

    .navmenu .dropdown-item:hover, 
    .navmenu .dropdown-item:focus {
      color: var(--nav-active);
      background-color: var(--section-bg);
    }

    .navmenu .dropdown-item i {
      width: 20px;
      text-align: center;
    }

    /* Auth Buttons */
    .auth-buttons .btn {
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    /* Theme Toggle */
    .theme-toggle {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--price-color);
        border: 2px solid var(--price-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 9999;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        opacity: 1;
    }

    .theme-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 0 15px var(--price-color);
    }

    /* Fix carousel caption positioning */
    .carousel-caption {
        bottom: 25%;
        transform: translateY(-50%);
        padding: 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .carousel-caption h1 {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
    }

    .carousel-caption p {
        font-size: 1.25rem;
        margin-bottom: 2rem;
    }

    /* Make sure text is readable on all backgrounds */
    .carousel-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5));
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .carousel-caption {
            bottom: 20%;
        }
        
        .carousel-caption h1 {
            font-size: 2.5rem;
        }
        
        .carousel-caption p {
            font-size: 1.1rem;
        }
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .navmenu ul {
            gap: 1rem;
        }
        
        .auth-buttons .btn {
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
        }
        
        .sitename {
            font-size: 1.5rem;
        }
    }

    /* Add these specific footer overrides */
    footer h4 {
        color: var(--footer-text) !important;
    }

    footer .text-light,
    footer a.text-light {
        color: var(--footer-text) !important;
        opacity: 0.9;
    }

    footer a.text-light:hover {
        opacity: 1;
        text-decoration: none;
    }

    /* Horizontal room carousel styles */
    .room-carousel-container {
        position: relative;
        padding: 2rem 0;
    }
    
    .room-carousel {
        display: flex;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
        gap: 20px;
        padding: 1.5rem 0;
    }
    
    .room-carousel::-webkit-scrollbar {
        display: none; /* Chrome/Safari/Opera */
    }
    
    .room-card {
        min-width: 300px;
        background-color: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 4px 15px var(--card-shadow);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        transform: translateY(0) scale(1);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid var(--border-color);
        flex: 0 0 auto;
    }
    
    .room-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    
    .room-card-img {
        height: 200px;
        overflow: hidden;
    }
    
    .room-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
        transform: scale(1);
    }
    
    .room-card:hover .room-card-img img {
        transform: scale(1.08);
    }
    
    .room-card-body {
        padding: 1.5rem;
    }
    
    .room-type-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: var(--price-color);
        color: white;
    }
    
    .carousel-controls {
        position: absolute;
        top: 50%;
        width: 100%;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        pointer-events: none;
        padding: 0 1rem;
        z-index: 10;
    }
    
    .carousel-control {
        width: 45px;
        height: 45px;
        background-color: var(--card-bg);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px var(--card-shadow);
        cursor: pointer;
        pointer-events: auto;
        border: 1px solid var(--border-color);
        color: var(--text-color);
        transition: all 0.3s ease;
    }
    
    .carousel-control:hover {
        background-color: var(--price-color);
        color: white;
    }
    
    /* Chat button and modal styles */
    .chat-button {
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--price-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                    box-shadow 0.4s ease;
        z-index: 1000;
        box-shadow: 0 4px 15px var(--card-shadow);
        border: none;
    }
    
    .chat-button:hover {
        transform: scale(1.15) rotate(10deg);
    }
    
    .chat-button:not(:hover) {
        transform: scale(1) rotate(0);
        transition: all 0.2s ease;
    }
    
    .chat-button .badge {
        position: absolute;
        top: 0;
        right: 0;
        padding: 0.25rem 0.5rem;
    }
    
    .chat-modal {
        position: fixed;
        bottom: 100px;
        left: 30px;
        width: 350px;
        height: 450px;
        background: var(--card-bg);
        border-radius: 15px;
        box-shadow: 0 10px 25px var(--card-shadow);
        z-index: 1001;
        overflow: hidden;
        display: none;
        border: 1px solid var(--border-color);
    }
    
    .chat-header {
        background: var(--price-color);
        color: white;
        padding: 15px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chat-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
    }
    
    .chat-messages {
        height: 320px;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .chat-input-container {
        display: flex;
        border-top: 1px solid var(--border-color);
        padding: 10px;
    }
    
    .chat-input {
        flex: 1;
        border: 1px solid var (--border-color);
        border-radius: 20px;
        padding: 8px 15px;
        outline: none;
        background-color: var(--bg-color);
        color: var(--text-color);
    }
    
    .chat-send {
        background-color: var(--price-color);
        color: white;
        border: none;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        margin-left: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .chat-message {
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 15px;
    }
    
    .chat-message.user {
        align-self: flex-end;
        background-color: var(--price-color);
        color: white;
        border-bottom-right-radius: 5px;
    }
    
    .chat-message.admin {
        align-self: flex-start;
        background-color: var(--section-bg);
        color: var(--text-color);
        border-bottom-left-radius: 5px;
    }
    
    /* User dropdown menu */
    .user-menu-container {
        position: relative;
    }
    
    .user-menu {
        position: absolute;
        top: 100%;
        right: 0;
        width: 220px;
        background-color: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 5px 15px var (--card-shadow);
        border: 1px solid var(--border-color);
        padding: 0.5rem 0;
        z-index: 1000;
        margin-top: 0.5rem;
        display: none;
    }
    
    .user-menu.show {
        display: block;
    }
    
    .user-menu-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .user-menu-item {
        display: block;
        padding: 0.6rem 1rem;
        color: var(--text-color);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .user-menu-item:hover {
        background-color: var(--section-bg);
    }
    
    .user-menu-item i {
        width: 20px;
        text-align: center;
        margin-right: 8px;
        color: var(--price-color);
    }
    
    .user-menu-divider {
        height: 1px;
        background-color: var(--border-color);
        margin: 0.5rem 0;
    }
    
    /* Enhanced animation behaviors with cleaner transitions */
    .room-card {
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      transform: translateY(0) scale(1);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .room-card:hover {
      transform: translateY(-12px) scale(1.02);
      box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    
    .room-card-img img {
      transition: transform 0.5s ease;
      transform: scale(1);
    }
    
    .room-card:hover .room-card-img img {
      transform: scale(1.08);
    }
    
    /* Reset transitions immediately when mouse leaves */
    .room-card:not(:hover) {
      transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .room-card:not(:hover) .room-card-img img {
      transition: transform 0.3s ease;
    }
    
    /* Enhanced button animations */
    .btn {
      transition: all 0.25s ease !important;
      overflow: hidden;
      position: relative;
    }
    
    .btn:after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      opacity: 0;
      transition: width 0.5s ease, height 0.5s ease, opacity 0.5s ease;
    }
    
    .btn:hover:after {
      width: 200%;
      height: 200%;
      opacity: 1;
    }
    
    .btn:not(:hover):after {
      opacity: 0;
      width: 0;
      height: 0;
      transition: all 0.2s ease;
    }
    
    /* Pulse animation fix */
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    .pulse-animation {
      animation: pulse 1.5s ease infinite;
    }

    .btn:not(:hover) {
      animation: none !important;
    }

    /* Fix the carousel control animations */
    .carousel-control {
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
                  background-color 0.3s ease, 
                  color 0.3s ease;
      transform: scale(1);
    }

    .carousel-control:hover {
      transform: scale(1.15);
    }

    .carousel-control:not(:hover) {
      transform: scale(1);
      transition: all 0.2s ease;
    }

    /* Theme toggle animation fix */
    .theme-toggle {
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                  box-shadow 0.4s ease;
    }

    .theme-toggle:hover {
      transform: scale(1.1);
      box-shadow: 0 0 15px var(--price-color);
    }

    .theme-toggle:not(:hover) {
      transform: scale(1);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      transition: all 0.2s ease;
    }

    /* Fix user menu animations */
    .user-menu-item {
      transition: background-color 0.2s ease, padding-left 0.2s ease;
    }

    .user-menu-item:not(:hover) {
      background-color: transparent;
      padding-left: 1rem;
      transition: all 0.1s ease;
    }

    .user-menu-item i {
      transition: transform 0.2s ease;
    }

    .user-menu-item:not(:hover) i {
      transform: translateX(0);
      transition: transform 0.1s ease;
    }

    /* Dropdown menu animation fixes */
    .navmenu .dropdown-menu {
      transition: opacity 0.3s ease, transform 0.3s ease !important;
    }

    .navmenu .dropdown-menu:not(.show) {
      opacity: 0 !important;
      transform: translateY(-10px) !important;
      pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s ease !important;
    }

    .navmenu .dropdown-item::after {
      transition: width 0.3s ease;
      width: 0;
    }

    .navmenu .dropdown-item:not(:hover)::after {
      width: 0 !important;
      transition: width 0.2s ease;
    }

    /* Chat button animation fix */
    .chat-button {
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                  box-shadow 0.4s ease;
    }

    .chat-button:hover {
      transform: scale(1.15) rotate(10deg);
    }

    .chat-button:not(:hover) {
      transform: scale(1) rotate(0);
      transition: all 0.2s ease;
    }

    /* Add this to your CSS to highlight active dropdown item */
    .navmenu .dropdown-item.active {
      background-color: var(--price-color);
      color: white;
    }

    .navmenu .dropdown-item.active i {
      color: white;
    }

    /* Style for readonly input */
    input[readonly].bg-light {
      cursor: not-allowed;
      border-color: #ced4da;
      background-color: #f8f9fa !important;
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <h1 class="sitename">Serenity Haven</h1>
      </a>

      <!-- Replace the existing Rooms nav item with this dropdown version -->
      <nav id="navmenu" class="navmenu mx-auto">
        <ul>
          <li><a href="landing_page.php?section=home" class="<?= !isset($_GET['section']) || $_GET['section'] == 'home' ? 'active' : '' ?>">Home</a></li>
          
          <!-- Rooms dropdown - Add active state for the dropdown items -->
          <li class="dropdown">
            <a href="#" class="dropdown-toggle <?= isset($_GET['section']) && $_GET['section'] == 'rooms' ? 'active' : '' ?>" 
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Rooms <i class="bi bi-chevron-down small ms-1"></i>
            </a>
            <ul class="dropdown-menu">
              <li>
                <a class="dropdown-item <?= isset($_GET['section']) && $_GET['section'] == 'today_booking' ? 'active' : '' ?>" 
                   href="landing_page.php?section=today_booking">
                  <i class="bi bi-lightning-fill me-2"></i>Pesan Kamar Hari Ini
                </a>
              </li>
              <li>

              <li>
                <a class="dropdown-item <?= isset($_GET['booking_date']) && $_GET['booking_date'] == 'tomorrow' ? 'active' : '' ?>" 
                   href="landing_page.php?section=rooms&booking_date=tomorrow">
                  <i class="bi bi-calendar-plus me-2"></i>Book Tomorrow
                </a>
              </li>
            </ul>
          </li>
          
          <?php if($isLoggedIn): ?>
          <li><a href="landing_page.php?section=riwayat" class="<?= isset($_GET['section']) && $_GET['section'] == 'riwayat' ? 'active' : '' ?>">Riwayat</a></li>
          <?php endif; ?>
          <li><a href="landing_page.php?section=facilities" class="<?= isset($_GET['section']) && $_GET['section'] == 'facilities' ? 'active' : '' ?>">Facilities</a></li>
          <li><a href="landing_page.php?section=about" class="<?= isset($_GET['section']) && $_GET['section'] == 'about' ? 'active' : '' ?>">About</a></li>
          <li><a href="landing_page.php?section=contact" class="<?= isset($_GET['section']) && $_GET['section'] == 'contact' ? 'active' : '' ?>">Contact</a></li>
        </ul>
      </nav>

      <div class="auth-buttons d-flex align-items-center gap-3">
        <?php if($isLoggedIn): ?>
        <!-- User menu container with dropdown -->
        <div class="user-menu-container">
          <button type="button" class="btn btn-outline-primary" id="userMenuButton">
            <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($user['nama']) ?>
            <i class="bi bi-chevron-down ms-1 small"></i>
          </button>
          
          <!-- User dropdown menu -->
          <div class="user-menu" id="userMenu">
            <div class="user-menu-header">
              <div class="fw-bold"><?= htmlspecialchars($user['nama']) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <a href="landing_page.php?section=profile" class="user-menu-item<?= (isset($_GET['section']) && $_GET['section'] == 'profile') ? ' active' : '' ?>">
              <i class="bi bi-person"></i> My Profile
            </a>
            <a href="landing_page.php?section=riwayat" class="user-menu-item<?= (isset($_GET['section']) && $_GET['section'] == 'riwayat') ? ' active' : '' ?>">
              <i class="bi bi-clock-history"></i> Booking History
            </a>
            <div class="user-menu-divider"></div>
            <a href="logout.php" class="user-menu-item text-danger">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </div>
        </div>
        <?php else: ?>
        <a href="login.php" class="btn btn-outline-primary">
          <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </a>
        <a href="register.php" class="btn btn-primary">
          <i class="bi bi-person-plus me-2"></i>Register
        </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="main">
    <?php
    // Define a constant to let sections know they're included from here
    define('INCLUDED_CONFIG', true);
    
    // Get the requested section from URL parameter
    $section = isset($_GET['section']) ? $_GET['section'] : 'home';
    
    // Only show hero slider on home page
    if ($section == 'home' || !isset($_GET['section'])):
    ?>
    <!-- Hero Slider -->
    <section id="hero" class="hero-carousel">
      <div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="0" class="active"></button>
          <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="1"></button>
          <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner">
          <!-- Slide 1 -->
          <div class="carousel-item active">
            <img src="hotel1.jpg" class="d-block w-100" alt="Luxury Room">
            <div class="carousel-caption text-center">
              <h1 class="display-4 fw-bold mb-4" data-aos="fade-down">Experience Luxury Redefined</h1>
              <p class="lead mb-5" data-aos="fade-up" data-aos-delay="200">Discover unparalleled comfort in the heart of the city</p>
              <a href="http://localhost/project3/NiceAdmin/landing_page.php?section=rooms" class="btn btn-primary btn-lg" data-aos="zoom-in" data-aos-delay="400">Book Your Stay Now</a>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item">
            <img src="hotel2.jpg" class="d-block w-100" alt="Swimming Pool">
            <div class="carousel-caption text-center">
              <h1 class="display-4 fw-bold mb-4" data-aos="fade-down">Premium Facilities</h1>
              <p class="lead mb-5" data-aos="fade-up" data-aos-delay="200">Enjoy our world-class amenities</p>
              <a href="#facilities" class="btn-book btn-lg" data-aos="zoom-in" data-aos-delay="400">Explore Facilities</a>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item">
            <img src="hotel3.jpg" class="d-block w-100" alt="Spa">
            <div class="carousel-caption text-center">
              <h1 class="display-4 fw-bold mb-4" data-aos="fade-down">Luxury Spa Experience</h1>
              <p class="lead mb-5" data-aos="fade-up" data-aos-delay="200">Rejuvenate your senses</p>
              <a href="#about" class="btn-book btn-lg" data-aos="zoom-in" data-aos-delay="400">Learn More</a>
            </div>
          </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </section>
    
    <?php if($isLoggedIn): ?>
    <!-- User Dashboard Section (Only visible when logged in) -->
    <section id="userDashboard" class="section">
      <div class="container">
        <div class="section-title text-center mb-5" data-aos="fade-up">
          <h2 class="mb-3" style="color: #000000 !important;">Welcome, <?= htmlspecialchars($user['nama']) ?></h2>
          <p class="text-muted">Manage your bookings and reservations</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-5" data-aos="fade-up">
          <!-- Active Bookings -->
          <div class="col-lg-4 col-md-6">
            <div class="card border-start border-primary border-4 h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="text-muted mb-2">Active Bookings</h6>
                    <h4 class="mb-0"><?= $activeCount ?></h4>
                  </div>
                  <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-calendar-check fs-4 text-primary"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Payments -->
          <div class="col-lg-4 col-md-6">
            <div class="card border-start border-warning border-4 h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="text-muted mb-2">Pending Payments</h6>
                    <h4 class="mb-0"><?= $pendingCount ?></h4>
                  </div>
                  <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-wallet2 fs-4 text-warning"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Completed Stays -->
          <div class="col-lg-4 col-md-6">
            <div class="card border-start border-success border-4 h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="text-muted mb-2">Completed Stays</h6>
                    <h4 class="mb-0"><?= $completedCount ?></h4>
                  </div>
                  <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="mt-5" data-aos="fade-up" data-aos-delay="100">
          <div class="card">
            <div class="card-header bg-primary bg-opacity-10 py-3">
              <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Upcoming Bookings</h5>
            </div>
            <div class="card-body">
              <?php if (empty($upcomingList)): ?>
                <div class="text-center py-4">
                  <i class="bi bi-calendar-x text-muted fs-1"></i>
                  <p class="mt-2">No upcoming bookings</p>
                  <a href="#rooms" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Book a Room
                  </a>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($upcomingList as $booking): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($booking['nama_kamar']) ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($booking['tgl_checkin'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($booking['tgl_checkout'])) ?></td>
                        <td>
                          <span class="badge bg-<?= match($booking['status']) {
                              'pending' => 'warning',
                              'siap digunakan' => 'success',
                              'belum siap' => 'info',
                              'berhasil' => 'primary',
                              'dibatalkan' => 'danger',
                              default => 'secondary'
                          } ?>">
                            <?= ucwords(str_replace('_', ' ', $booking['status'])) ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- User Quick Actions -->
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="riwayat_user.php" class="btn btn-outline-primary">
              <i class="bi bi-clock-history me-2"></i>View Booking History
            </a>
            <a href="landing_page.php?section=profile" class="btn btn-outline-primary">
              <i class="bi bi-person-gear me-2"></i>Update Profile
            </a>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Facilities Section -->
    <section id="facilities" class="section bg-light">
      <div class="container">
        <div class="section-title text-center mb-5" data-aos="fade-up">
          <h2 class="mb-3" style="color: #000000 !important;">Our Facilities</h2>
          <p class="text-muted">Premium amenities for your comfort</p>
        </div>

        <div class="row g-4">
          <div class="col-md-4" data-aos="fade-up">
            <div class="service-item text-center p-4">
              <img src="hotel2.jpg" class="img-fluid rounded mb-3" alt="Infinity Pool">
              <div class="icon mb-3"><i class="bi bi-water fs-1 text-primary"></i></div>
              <h4 class="mb-3">Infinity Pool</h4>
              <p class="text-muted">Stunning rooftop pool with panoramic city views</p>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item text-center p-4">
              <img src="hotel1.jpg" class="img-fluid rounded mb-3" alt="Fine Dining">
              <div class="icon mb-3"><i class="bi bi-cup-hot fs-1 text-primary"></i></div>
              <h4 class="mb-3">Gourmet Dining</h4>
              <p class="text-muted">Award-winning restaurants with international cuisine</p>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item text-center p-4">
              <img src="spa.jpg" class="img-fluid rounded mb-3" alt="Luxury Spa">
              <div class="icon mb-3"><i class="bi bi-emoji-smile fs-1 text-primary"></i></div>
              <h4 class="mb-3">Wellness Spa</h4>
              <p class="text-muted">Rejuvenate with our signature spa treatments</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Rooms Carousel Section (Enhanced Version) -->
    <section id="rooms-carousel" class="section">
      <div class="container">
        <div class="section-title text-center mb-4" data-aos="fade-up">
          <h2 class="mb-3">Explore Our Rooms</h2>
          <p class="text-muted">Swipe right to discover more options</p>
        </div>
        
        <div class="room-carousel-container" data-aos="fade-up">
          <!-- Carousel Navigation Controls -->
          <div class="carousel-controls">
            <button class="carousel-control prev-btn" id="prevRoom">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="carousel-control next-btn" id="nextRoom">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
          
          <!-- Scrollable Room Carousel -->
          <div class="room-carousel" id="roomsCarousel">
            <?php foreach($availableRooms as $index => $room): ?>
            <div class="room-card">
              <div class="room-card-img">
                <img src="<?= ($index % 3 == 0) ? 'hotel1.jpg' : (($index % 3 == 1) ? 'hotel2.jpg' : 'hotel3.jpg') ?>" alt="<?= htmlspecialchars($room['nama']) ?>">
                <div class="room-type-badge"><?= htmlspecialchars($room['jenis']) ?></div>
              </div>
              <div class="room-card-body">
                <h4 class="mb-2"><?= htmlspecialchars($room['nama']) ?></h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($room['bed']) ?> bed</p>
                <div class="d-flex align-items-center mb-3">
                  <div class="me-auto">
                    <span class="price h5">Rp<?= number_format($room['harga'], 0, ',', '.') ?></span>
                    <span class="text-muted">/night</span>
                  </div>
                  <div class="room-rating">
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-half text-warning"></i>
                  </div>
                </div>
                <div class="room-features mb-3">
                  <div class="d-flex justify-content-between">
                    <span><i class="bi bi-people me-1"></i> 2 Guests</span>
                    <span><i class="bi bi-wifi me-1"></i> Free WiFi</span>
                  </div>
                </div>
                <button class="btn btn-primary w-100" onclick="checkLoginAndBook(<?= $room['id_kamar'] ?>)">
                  <i class="bi bi-calendar-check me-2"></i>Book Now
                </button>
              </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(count($availableRooms) == 0): ?>
            <div class="text-center py-5 w-100">
              <div class="mb-3">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
              </div>
              <h5>No rooms available</h5>
              <p class="text-muted">Please check back later</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
        

      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section bg-light">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6" data-aos="fade-right">
            <img src="hotel3.jpg" class="img-fluid rounded" alt="About Our Hotel">
          </div>
          <div class="col-lg-6" data-aos="fade-left">
            <div class="ps-lg-5">
<h2 class="text-center mb-5" style="color: #000000 !important;">Apa Kata Tamu Kami</h2>
              <p class="lead">Experience luxury redefined at our 5-star boutique hotel located in the heart of the city.</p>
              <ul class="list-unstyled">
                <li class="mb-3"><i class="bi bi-check2-circle text-primary me-2"></i>200+ Luxury Rooms & Suites</li>
                <li class="mb-3"><i class="bi bi-check2-circle text-primary me-2"></i>24/7 Concierge Service</li>
                <li class="mb-3"><i class="bi bi-check2-circle text-primary me-2"></i>Award Winning Spa Facilities</li>
              </ul>
              <div class="mt-4">
                <a href="http://localhost/project3/NiceAdmin/landing_page.php?section=contact" class="btn btn-primary">
                  <i class="bi bi-chat-dots me-2"></i>Contact Us
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php 
    // End of the if statement for home section
    endif;
    
    // Include the appropriate section file based on the section parameter
    $sectionFile = 'sections/' . $section . '_section.php';
    if (file_exists($sectionFile)) {
        include $sectionFile;
    } else if ($section === 'today_booking') {
        include 'sections/today_booking.php';
    } else if ($section != 'home') {
        echo '<div class="container py-5 text-center">';
        echo '<div class="alert alert-warning" role="alert">';
        echo 'Section not found. <a href="landing_page.php" class="alert-link">Return to home</a>';
        echo '</div>';
        echo '</div>';
    }
    ?>

  </main>

  <!-- To ensure dropdown functionality across all sections -->
  <div style="display:none">
    <script>
      // Ensure the user menu is correctly initialized for the home page
      (function() {
        function initMenu() {
          const userMenuButton = document.getElementById('userMenuButton');
          const userMenu = document.getElementById('userMenu');
          
          if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              userMenu.classList.toggle('show');
            });
            
            document.addEventListener('click', function(e) {
              if (userMenu.classList.contains('show') && 
                  !userMenu.contains(e.target) && 
                  !userMenuButton.contains(e.target)) {
                userMenu.classList.remove('show');
              }
            });
          }
        }
        
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
          setTimeout(initMenu, 1);
        } else {
          document.addEventListener('DOMContentLoaded', initMenu);
        }
        window.addEventListener('load', initMenu);
      })();
    </script>
  </div>

  <footer class="bg-dark text-light pt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <h4 class="mb-4">Serenity Haven</h4>
          <p>Redefining luxury hospitality since 2010</p>
        </div>
        <div class="col-lg-4">
          <h4 class="mb-4">Quick Links</h4>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="#rooms" class="text-light">Rooms & Suites</a></li>
            <li class="mb-2"><a href="#facilities" class="text-light">Facilities</a></li>
            <li class="mb-2"><a href="landing_page.php?section=contact" class="text-light">Contact Us</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <h4 class="mb-4">Connect With Us</h4>
          <div class="social-links">
            <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-twitter-x"></i></a>
          </div>
        </div>
      </div>
      <div class="text-center py-4 mt-4 border-top">
        <p>&copy; 2024 Serenity Haven. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Theme toggle button positioned at bottom right corner -->
  <button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode">
    <i class="bi bi-moon-fill"></i>
  </button>

  <!-- Chat Button to toggle modal - only show on home page -->
  <?php if($isLoggedIn && ($currentSection == 'home' || !isset($_GET['section']))): ?>
  <button class="chat-button" id="chatButton" data-bs-toggle="modal" data-bs-target="#chatModal">
    <i class="bi bi-chat-dots-fill fs-4"></i>
    <?php if(isset($user['unread_user']) && $user['unread_user'] > 0): ?>
    <span class="badge bg-danger"><?= $user['unread_user'] ?></span>
    <?php endif; ?>
  </button>
  
  <!-- Chat Modal -->
  <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="chatModalLabel"><i class="bi bi-chat-dots"></i> Chat with Admin</h5>
          <div class="ms-auto">
            <div class="dropdown d-inline-block me-2">
              <button class="btn btn-sm btn-light" type="button" id="chatMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chatMenuButton">
                <li>
                  <button class="dropdown-item text-danger" type="button" id="clearChatBtn">
                    <i class="bi bi-trash"></i> Clear All Messages
                  </button>
                </li>
                <li>
                  <button class="dropdown-item" type="button" id="toggleDeleteModeBtn">
                    <i class="bi bi-check-square"></i> Delete Selected Messages
                  </button>
                </li>
              </ul>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>
        <div class="modal-body p-0">
          <div class="row g-0">
            <!-- Admin list (only show the admin) -->
            <div class="col-md-4 border-end">
              <div class="p-3 bg-light border-bottom">
                <h6 class="mb-0">Contacts</h6>
              </div>
              <div class="list-group list-group-flush">
                <?php
                // Get admin to chat with
                $adminStmt = $conn->prepare("SELECT id_user, username, lv, last_online FROM user WHERE lv = 'admin' LIMIT 1");
                $adminStmt->execute();
                $admin = $adminStmt->fetch();
                
                if ($admin):
                ?>
                <a href="#" class="list-group-item list-group-item-action active admin-contact" data-admin-id="<?= $admin['id_user'] ?>">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <i class="bi bi-person-circle fs-4"></i>
                    </div>
                    <div>
                      <div class="fw-bold"><?= htmlspecialchars($admin['username']) ?></div>
                      <small class="text-truncate d-block" style="max-width: 140px;">
                        <?php
                        $lastOnline = new DateTime($admin['last_online']);
                        $now = new DateTime();
                        $diff = $now->diff($lastOnline);
                        
                        if ($diff->days > 0) {
                            echo "Last seen " . $diff->days . " day" . ($diff->days > 1 ? "s" : "") . " ago";
                        } elseif ($diff->h > 0) {
                            echo "Last seen " . $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
                        } elseif ($diff->i > 0) {
                            echo "Last seen " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
                        } else {
                            echo "Online";
                        }
                        ?>
                      </small>
                    </div>
                  </div>
                </a>
                <?php else: ?>
                <div class="p-3">
                  <div class="alert alert-info mb-0">No admin available</div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Chat area -->
            <div class="col-md-8">
              <?php if ($admin): ?>
              <?php
              // Get chat history
              $chatStmt = $conn->prepare("SELECT chat_history FROM user WHERE id_user = ?");
              $chatStmt->execute([$userId]);
              $chatData = $chatStmt->fetch();
              $chatHistory = json_decode($chatData['chat_history'] ?? '[]', true);
              
              // Filter to get only messages between current user and admin
              $messages = array_filter($chatHistory, function($msg) use ($admin, $userId) {
                  return ($msg['from'] == $userId && $msg['to'] == $admin['id_user']) ||
                         ($msg['from'] == $admin['id_user'] && $msg['to'] == $userId);
              });
              
              // Reset unread counter
              $resetUnread = $conn->prepare("UPDATE user SET unread_user = 0 WHERE id_user = ?");
              $resetUnread->execute([$userId]);
              ?>
              
              <!-- Chat messages -->
              <div class="chat-messages p-3" id="chatMessagesContainer" style="height: 300px; overflow-y: auto;">
                <?php if (!empty($messages)): ?>
                  <?php foreach ($messages as $msg): ?>
                    <?php 
                    $isSender = $msg['from'] == $userId;
                    $messageText = $msg['message'] ?? '';
                    $messageTime = $msg['time'] ?? '';
                    $attachments = is_array($msg['lampiran'] ?? false) ? $msg['lampiran'] : (!empty($msg['lampiran']) ? [$msg['lampiran']] : []);
                    ?>
                    <div class="chat-message <?= $isSender ? 'user' : 'admin' ?>" data-message-id="<?= $msg['id'] ?>">
                      <div class="message-checkbox d-none">
                        <input type="checkbox" class="message-select" data-message-id="<?= $msg['id'] ?>">
                      </div>
                      <?php if (!empty($attachments)): ?>
                      <div class="mb-2 d-flex gap-2 flex-wrap">
                        <?php foreach ($attachments as $image): ?>
                        <a href="uploads/chat/<?= htmlspecialchars($image) ?>" target="_blank" class="chat-image-link">
                          <img src="uploads/chat/<?= htmlspecialchars($image) ?>" alt="Image" class="chat-image">
                        </a>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                      <?php if (!empty($messageText)): ?>
                      <div class="message-text">
                        <?= htmlspecialchars($messageText) ?>
                      </div>
                      <?php endif; ?>
                      <div class="message-time">
                        <?= date('H:i', strtotime($messageTime)) ?>
                        <?php if ($isSender): ?>
                          <i class="bi bi-check2-all <?= ($msg['status'] == 'read_by_admin') ? 'text-primary' : '' ?>"></i>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center text-muted my-5">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mt-2">No messages yet. Start a conversation!</p>
                  </div>
                <?php endif; ?>
              </div>
              
              <!-- Image previews -->
              <div id="image-previews" class="d-flex gap-2 flex-wrap p-2"></div>
              
              <!-- Chat input -->
              <div class="chat-input-area border-top p-3">
                <form id="chatForm" action="chat-process.php" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="receiver_id" value="<?= $admin['id_user'] ?>">
                  <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Type a message..." id="chatMessageInput">
                    <label class="btn btn-outline-secondary position-relative" for="chatImageInput">
                      <i class="bi bi-image"></i>
                      <input type="file" name="lampiran[]" multiple accept="image/*" class="d-none" id="chatImageInput">
                      <span id="image-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                        0
                      </span>
                    </label>
                    <button type="submit" name="send_message" class="btn btn-primary">
                      <i class="bi bi-send"></i>
                    </button>
                  </div>
                </form>
              </div>
              <?php else: ?>
              <div class="p-5 text-center">
                <div class="alert alert-warning">
                  No admin available for chat. Please try again later.
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Delete Messages Form (Hidden) -->
  <form id="deleteMessagesForm" action="chat-process.php" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete_messages">
    <input type="hidden" name="receiver_id" value="<?= $admin['id_user'] ?? '' ?>">
    <input type="hidden" name="message_ids" id="selectedMessageIds" value="">
  </form>
  
  <!-- Clear Chat Form (Hidden) -->
  <form id="clearChatForm" action="chat-process.php" method="post" style="display: none;">
    <input type="hidden" name="action" value="clear_chat">
    <input type="hidden" name="receiver_id" value="<?= $admin['id_user'] ?? '' ?>">
  </form>
  <?php endif; ?>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- jQuery - Required for AJAX -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    // Initialize AOS
    AOS.init({
      duration: 1000,
      once: true
    });

    // Initialize Hero Slider
    const heroSlider = new bootstrap.Carousel('#heroSlider', {
      interval: 5000,
      wrap: true,
      pause: false
    });

    // Refresh AOS on slide change
    document.getElementById('heroSlider').addEventListener('slid.bs.carousel', () => {
      AOS.refresh();
    });

    // Login check function
    function checkLoginAndBook(roomId) {
      <?php if(isset($_SESSION['id_user'])): ?>
        // Get the current booking_date parameter if present
        const urlParams = new URLSearchParams(window.location.search);
        const bookingDate = urlParams.get('booking_date');
        
        // Redirect to the rooms section with the selected room ID and preserve booking_date
        const bookingDateParam = bookingDate ? `&booking_date=${bookingDate}` : '';
        window.location.href = `landing_page.php?section=rooms${bookingDateParam}&selected_room=${roomId}`;
      <?php else: ?>
        Swal.fire({
          title: 'Login Required',
          text: 'Please login to continue booking',
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Login',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            const urlParams = new URLSearchParams(window.location.search);
            const bookingDate = urlParams.get('booking_date');
            const bookingDateParam = bookingDate ? `&booking_date=${bookingDate}` : '';
            
            sessionStorage.setItem('selectedRoom', roomId);
            sessionStorage.setItem('bookingDate', bookingDate || '');
            window.location.href = `login.php?redirect=landing_page.php?section=rooms${bookingDateParam}`;
          }
        });
      <?php endif; ?>
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            
            // Apply the current theme as soon as possible
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Update the icon based on current theme
            if (icon) {
                icon.className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
            
            // Add click event listener to the theme toggle button
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Set the new theme
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Update the icon
                if (icon) {
                    icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
                }
                
                console.log('Theme switched to:', newTheme);
            });
        } else {
            console.error('Theme toggle button not found!');
        }
        
        // Horizontal Room Carousel functionality
        const carousel = document.getElementById('roomsCarousel');
        const prevBtn = document.getElementById('prevRoom');
        const nextBtn = document.getElementById('nextRoom');
        
        if (carousel && prevBtn && nextBtn) {
            // Previous button click handler
            prevBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: -330, behavior: 'smooth' });
            });
            
            // Next button click handler
            nextBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: 330, behavior: 'smooth' });
            });
        }
        
        // Enhanced User Menu Dropdown with better event handling
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        
        function initUserMenu() {
            if (userMenuButton && userMenu) {
                // Clean up any existing listeners to prevent duplicates
                const newButton = userMenuButton.cloneNode(true);
                userMenuButton.parentNode.replaceChild(newButton, userMenuButton);
                
                // Get the new reference after replacement
                const updatedButton = document.getElementById('userMenuButton');
                
                // Add click event
                updatedButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                    
                    // Update aria attributes for accessibility
                    const isExpanded = userMenu.classList.contains('show');
                    updatedButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                });
                
                // Ensure the button is properly initialized
                updatedButton.setAttribute('aria-expanded', 'false');
                updatedButton.setAttribute('aria-haspopup', 'true');
                
                // Make sure the z-index is high enough to stay on top of other elements
                if (userMenu) {
                    userMenu.style.zIndex = '1050';
                }
            }
        }
        
        // Initial setup for user menu
        initUserMenu();
        
        // Add click handler to document to close menu when clicking outside
        document.addEventListener('click', (e) => {
            const userMenu = document.getElementById('userMenu');
            const userMenuButton = document.getElementById('userMenuButton');
            
            if (userMenu && userMenuButton && 
                userMenu.classList.contains('show') && 
                !userMenu.contains(e.target) && 
                !userMenuButton.contains(e.target)) {
                userMenu.classList.remove('show');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Chat Modal functionality
        const chatModal = document.getElementById('chatModal');
        if (chatModal) {
            chatModal.addEventListener('shown.bs.modal', function() {
                const chatMessages = document.getElementById('chatMessagesContainer');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            });
            
            // Image upload handling
            const imageInput = document.getElementById('chatImageInput');
            const imagePreviews = document.getElementById('image-previews');
            const imageCount = document.getElementById('image-count');
            let selectedFiles = [];
            
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    
                    files.forEach(file => {
                        if (file.type.startsWith('image/')) {
                            selectedFiles.push(file);
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const container = document.createElement('div');
                                container.className = 'preview-container';
                                container.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                    <div class="remove-image" style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer; font-size: 12px;">&times;</div>
                                `;
                                
                                container.querySelector('.remove-image').onclick = function() {
                                    selectedFiles = selectedFiles.filter(f => f !== file);
                                    container.remove();
                                    updateImageCount();
                                    if (selectedFiles.length === 0) {
                                        imagePreviews.classList.remove('has-images');
                                    }
                                };
                                
                                imagePreviews.appendChild(container);
                                imagePreviews.classList.add('has-images');
                            };
                            
                            reader.readAsDataURL(file);
                        }
                    });
                    
                    updateImageCount();
                });
                
                function updateImageCount() {
                    if (selectedFiles.length > 0) {
                        imageCount.textContent = selectedFiles.length;
                        imageCount.classList.remove('d-none');
                    } else {
                        imageCount.classList.add('d-none');
                    }
                }
            }
            
            // Handle message sending with AJAX
            const chatForm = document.getElementById('chatForm');
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('send_message', '1');  // Add the send_message parameter
                    
                    // Check if we have a message or attachments
                    const messageInput = document.getElementById('chatMessageInput');
                    const hasText = messageInput.value.trim() !== '';
                    const hasFiles = document.getElementById('chatImageInput').files.length > 0;
                    
                    
                    if (!hasText && !hasFiles) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Please enter a message or attach an image',
                            icon: 'error'
                        });
                        return;
                    }
                    
                    $.ajax({
                        url: 'chat-process.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        beforeSend: function() {
                            // Show loading or disable submit button
                            $('#chatForm button[type="submit"]').prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                // Clear the message input
                                document.getElementById('chatMessageInput').value = '';
                                
                                // Clear image previews
                                document.getElementById('image-previews').innerHTML = '';
                                document.getElementById('image-count').classList.add('d-none');
                                document.getElementById('chatImageInput').value = '';
                                selectedFiles = [];
                                
                                // Add the new message to the chat
                                addMessageToChat(response.message);
                                
                                // Scroll to the bottom of the chat
                                const chatContainer = document.getElementById('chatMessagesContainer');
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            } else {
                                // Show error notification
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Failed to send message',
                                    icon: 'error'
                                });
                                console.error("Message error:", response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            // Show detailed error for debugging
                            console.error("AJAX Error:", xhr.responseText);
                            
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to send message. Please try again.',
                                icon: 'error'
                            });
                        },
                        complete: function() {
                            // Re-enable submit button
                            $('#chatForm button[type="submit"]').prop('disabled', false);
                        }
                    });
                });
                
                // Function to add message to chat
                function addMessageToChat(message) {
                    const chatMessages = document.getElementById('chatMessagesContainer');
                    const msgElement = document.createElement('div');
                    msgElement.className = 'chat-message user';
                    msgElement.dataset.messageId = message.id;
                    
                    let content = '';
                    
                    // Add message checkbox for delete functionality
                    content += '<div class="message-checkbox d-none">';
                    content += `<input type="checkbox" class="message-select" data-message-id="${message.id}">`;
                    content += '</div>';
                    
                    // Add image attachments if any
                    if (message.lampiran && message.lampiran.length > 0) {
                        content += '<div class="mb-2 d-flex gap-2 flex-wrap">';
                        message.lampiran.forEach(image => {
                            content += `<a href="uploads/chat/${image}" target="_blank" class="chat-image-link">`;
                            content += `<img src="uploads/chat/${image}" alt="Image" class="chat-image">`;
                            content += '</a>';
                        });
                        content += '</div>';
                    }
                    
                    // Add message text if any
                    if (message.message && message.message.trim() !== '') {
                        content += `<div class="message-text">${escapeHtml(message.message)}</div>`;
                    }
                    
                    // Add timestamp
                    content += `
                        <div class="message-time">
                            ${formatTime(message.time)}
                            <i class="bi bi-check2-all"></i>
                        </div>
                    `;
                    
                    msgElement.innerHTML = content;
                    chatMessages.appendChild(msgElement);
                    scrollChatToBottom();
                    
                    // Add click handler for images
                    msgElement.querySelectorAll('.chat-image').forEach(img => {
                        img.addEventListener('click', function(e) {
                            e.preventDefault();
                            showImageLightbox(this.src);
                        });
                    });
                }
                
                // Helper functions
                function scrollChatToBottom() {
                    const chatMessages = document.getElementById('chatMessagesContainer');
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
                
                function formatTime(timestamp) {
                    const date = new Date(timestamp);
                    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
                
                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
                
                function showImageLightbox(imageUrl) {
                    Swal.fire({
                        imageUrl: imageUrl,
                        imageAlt: 'Chat Image',
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            image: 'img-fluid'
                        }
                    });
                }
            }
            
            // Refresh chat automatically
            let chatRefreshInterval;
            function startChatRefresh() {
                const receiverId = document.querySelector('input[name="receiver_id"]').value;
                chatRefreshInterval = setInterval(() => {
                    fetchNewMessages(receiverId);
                }, 5000); // Check for new messages every 5 seconds
            }
            
            function stopChatRefresh() {
                clearInterval(chatRefreshInterval);
            }
            
            // Start and stop refresh on modal show/hide
            chatModal.addEventListener('shown.bs.modal', startChatRefresh);
            chatModal.addEventListener('hidden.bs.modal', stopChatRefresh);
            
            // Fetch new messages
            function fetchNewMessages(receiverId) {
                fetch(`load_chat.php?receiver_id=${receiverId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.messages) {
                            updateChatMessages(data.messages, receiverId);
                        }
                    })
                    .catch(error => console.error('Error fetching messages:', error));
            }
            
            // Update chat messages
            function updateChatMessages(messages, receiverId) {
                const chatMessages = document.getElementById('chatMessagesContainer');
                const currentUserId = <?= $userId ?? 0 ?>;
                
                // Clear chat container if empty result but we had messages
                if (messages.length === 0 && chatMessages.children.length > 0) {
                    chatMessages.innerHTML = `
                        <div class="text-center text-muted my-5">
                            <i class="bi bi-chat-dots fs-1"></i>
                            <p class="mt-2">No messages yet. Start a conversation!</p>
                        </div>
                    `;
                    return;
                }
                
                // Get existing message IDs
                const existingMsgIds = new Set();
                document.querySelectorAll('.chat-message').forEach(el => {
                    existingMsgIds.add(el.dataset.messageId);
                });
                
                // Filter messages between current user and receiver
                const relevantMessages = messages.filter(msg => 
                    (msg.from == currentUserId && msg.to == receiverId) || 
                    (msg.from == receiverId && msg.to == currentUserId)
                );
                
                // Add new messages that don't exist yet
                let hasNewMessages = false;
                relevantMessages.forEach(msg => {
                    if (!existingMsgIds.has(msg.id)) {
                        const isFromCurrentUser = msg.from == currentUserId;
                        
                        // Create message element
                        const msgElement = document.createElement('div');
                        msgElement.className = `chat-message ${isFromCurrentUser ? 'user' : 'admin'}`;
                        msgElement.dataset.messageId = msg.id
                        
                        let content = '';
                        
                        // Add message checkbox for delete functionality
                        content += '<div class="message-checkbox d-none">';
                        content += `<input type="checkbox" class="message-select" data-message-id="${msg.id}">`;
                        content += '</div>';
                        
                        // Add image attachments if any
                        if (msg.lampiran && msg.lampiran.length > 0) {
                            content += '<div class="mb-2 d-flex gap-2 flex-wrap">';
                            
                            // Handle different data structures for attachments
                            const attachments = Array.isArray(msg.lampiran) 
                                ? msg.lampiran 
                                : (typeof msg.lampiran === 'string' && msg.lampiran ? [msg.lampiran] : []);
                                
                            attachments.forEach(image => {
                                content += `<a href="uploads/chat/${image}" target="_blank" class="chat-image-link">`;
                                content += `<img src="uploads/chat/${image}" alt="Image" class="chat-image">`;
                                content += '</a>';
                            });
                            content += '</div>';
                        }
                        
                        // Add message text if any
                        if (msg.message && msg.message.trim() !== '') {
                            content += `<div class="message-text">${escapeHtml(msg.message)}</div>`;
                        }
                        
                        // Add timestamp
                        content += `
                            <div class="message-time">
                                ${formatTime(msg.time)}
                                ${isFromCurrentUser ? `<i class="bi bi-check2-all ${msg.status === 'read_by_admin' ? 'text-primary' : ''}"></i>` : ''}
                            </div>
                        `;
                        
                        msgElement.innerHTML = content;
                        chatMessages.appendChild(msgElement);
                        hasNewMessages = true;
                        
                        // Add click handler for images
                        msgElement.querySelectorAll('.chat-image').forEach(img => {
                            img.addEventListener('click', function(e) {
                                e.preventDefault();
                                showImageLightbox(this.src);
                            });
                        });
                    }
                });
                
                // Scroll to bottom if new messages were added
                if (hasNewMessages) {
                    scrollChatToBottom();
                }
            }
            
            // Delete Mode Toggle
            const toggleDeleteModeBtn = document.getElementById('toggleDeleteModeBtn');
            if (toggleDeleteModeBtn) {
                let deleteMode = false;
                
                toggleDeleteModeBtn.addEventListener('click', function() {
                    deleteMode = !deleteMode;
                    
                    // Toggle visibility of checkboxes
                    document.querySelectorAll('.message-checkbox').forEach(checkbox => {
                        checkbox.classList.toggle('d-none');
                    });
                    
                    // Update button text
                    this.innerHTML = deleteMode ? 
                        '<i class="bi bi-x-lg"></i> Cancel Delete' : 
                        '<i class="bi bi-check-square"></i> Delete Selected Messages';
                    
                    // Show/hide delete selected button
                    if (deleteMode) {
                        // If there isn't already a delete button
                        if (!document.getElementById('deleteSelectedBtn')) {
                            const deleteBtn = document.createElement('button');
                            deleteBtn.id = 'deleteSelectedBtn';
                            deleteBtn.className = 'btn btn-danger position-fixed';
                            deleteBtn.style.bottom = '20px';
                            deleteBtn.style.right = '20px';
                            deleteBtn.style.zIndex = '1060';
                            deleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
                            
                            deleteBtn.addEventListener('click', function() {
                                const selectedMessages = Array.from(document.querySelectorAll('.message-select:checked'))
                                    .map(cb => cb.dataset.messageId);
                                
                                if (selectedMessages.length > 0) {
                                    Swal.fire({
                                        title: 'Delete Selected Messages?',
                                        text: `Are you sure you want to delete ${selectedMessages.length} message(s)?`,
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Delete',
                                        cancelButtonText: 'Cancel'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            const form = document.getElementById('deleteMessagesForm');
                                            document.getElementById('selectedMessageIds').value = JSON.stringify(selectedMessages);
                                            form.submit();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'No Messages Selected',
                                        text: 'Please select at least one message to delete.',
                                        icon: 'info'
                                    });
                                }
                            });
                            
                            document.body.appendChild(deleteBtn);
                        }
                    } else {
                        // Remove the delete button if it exists
                        const deleteBtn = document.getElementById('deleteSelectedBtn');
                        if (deleteBtn) {
                            deleteBtn.remove();
                        }
                    }
                });
            }
            
            // Clear Chat Button
            const clearChatBtn = document.getElementById('clearChatBtn');
            if (clearChatBtn) {
                clearChatBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Clear All Messages?',
                        text: 'This will permanently delete all messages in this conversation. This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Clear All',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('clearChatForm');
                            form.submit();
                        }
                    });
                });
            }
            
            // Enable image lightbox for chat images
            const chatImages = document.querySelectorAll('.chat-image');
            chatImages.forEach(img => {
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    const imageUrl = this.src;
                    
                    Swal.fire({
                        imageUrl: imageUrl,
                        imageAlt: 'Chat Image',
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            image: 'img-fluid'
                        }
                    });
                });
            });
        }
    });
  </script>

  <style>
    /* Modal chat styles */
    .chat-message {
        margin-bottom: 15px;
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 15px;
        position: relative;
        clear: both;
    }
    
    .chat-message.user {
        float: right;
        background-color: var(--price-color);
        color: white;
        margin-left: 20%;
        border-bottom-right-radius: 5px;
    }
    
    .chat-message.admin {
        float: left;
        background-color: var(--section-bg);
        color: var(--text-color);
        margin-right: 20%;
        border-bottom-left-radius: 5px;
    }
    
    .message-time {
        display: block;
        font-size: 0.7rem;
        margin-top: 5px;
        opacity: 0.8;
        text-align: right;
    }
    
    .chat-input-area {
        background-color: var(--card-bg);
    }
    
    #chatMessagesContainer::after {
        content: "";
        clear: both;
        display: table;
    }
    
    /* Image style */
    .chat-image {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
        object-fit: cover;
    }
    
    .chat-image:hover {
        transform: scale(1.05);
    }
    
    /* Image previews */
    #image-previews {
        min-height: 0;
        transition: min-height 0.3s;
    }
    
    #image-previews.has-images {
        min-height: 100px;
    }
    
    .preview-container {
        position: relative;
        display: inline-block;
        margin: 5px;
    }
    
    /* Checkbox for message selection */
    .message-checkbox {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 5;
    }
    
    .chat-message.user .message-checkbox {
        left: 5px;
        right: auto;
    }
    
    /* Make sure message content is clear of the checkbox */
    .chat-message.user {
        padding-left: 25px;
    }
    
    .chat-message.admin {
        padding-right: 25px;
    }
  </style>

  <!-- Simple direct DOM access for the user menu button (fallback) -->
  <script>
    // Fallback direct click handler for home page
    window.addEventListener('load', function() {
      const userMenuButton = document.getElementById('userMenuButton');
      const userMenu = document.getElementById('userMenu');
      
      if (userMenuButton && userMenu) {
        userMenuButton.onclick = function(e) {
          e.stopPropagation();
          userMenu.classList.toggle('show');
          console.log('Direct click handler executed');
        };
        
        document.addEventListener('click', function(e) {
          if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.remove('show');
          }
        });
      }
    });
  </script>
</body>
</html>
<?php
require 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['id_user']);

// If user is logged in, get their data
if ($isLoggedIn) {
    $userId = $_SESSION['id_user'];
    
    // Get user details
    $userStmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Contact Us - Serenity Haven Hotel</title>
  <meta name="description" content="Get in touch with Serenity Haven Hotel. We're here to assist you with any inquiries.">
  <meta name="keywords" content="hotel contact, luxury hotel, contact us, hotel support">

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
        --footer-text: #ffffff;
        --form-bg: #ffffff;
        --form-border: #ced4da;
        --nav-bg: rgba(255, 255, 255, 0.98);        --nav-text: #4a5568;
        --btn-text: #ffffff;
        --price-color: #2b82c3;
        --price-color-rgb: 43, 130, 195;
        --link-color: #2b82c3;
        --link-hover: #1a6298;
        --nav-active: #2b82c3;
        --icon-color: #2b82c3;
        --border-color: #e2e8f0;
    }

    [data-theme="dark"] {
        --bg-color: #0f172a;
        --text-color: #e2e8f0;
        --heading-color: #38bdf8;
        --card-bg: #1e293b;
        --card-shadow: rgba(0,0,0,0.3);
        --section-bg: #111827;
        --footer-bg: #0f172a;
        --footer-text: #ffffff;
        --form-bg: #1e293b;
        --form-border: #334155;
        --nav-bg: rgba(15, 23, 42, 0.98);        --nav-text: #cbd5e1;
        --btn-text: #ffffff;
        --price-color: #38bdf8;
        --price-color-rgb: 56, 189, 248;
        --link-color: #38bdf8;
        --link-hover: #0ea5e9;
        --nav-active: #38bdf8;
        --icon-color: #38bdf8;
        --border-color: #334155;
    }

    /* Dark mode overrides */
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

    [data-theme="dark"] .contact-info h4,
    [data-theme="dark"] .contact-info h5 {
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
        transition: width 0.3s ease;
    }

    .navmenu a:hover::after,
    .navmenu a.active::after {
        width: 100%;
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
        background: var(--card-bg);
        border: 2px solid var(--price-color);
        color: var(--text-color);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 4px 15px var(--card-shadow);
    }

    .theme-toggle:hover {
        transform: scale(1.1);
        background: var(--price-color);
        color: var(--btn-text);
    }    /* User dropdown menu */
    .user-menu-container {
        position: relative;
    }
    
    #userMenuButton {
        transition: all 0.3s ease;
        border-width: 1px;
    }
    
    #userMenuButton:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    
    .user-menu {
        position: absolute;
        top: 100%;
        right: 0;
        width: 250px;
        background-color: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 5px 15px var(--card-shadow);
        border: 1px solid var(--border-color);
        padding: 0.5rem 0;
        z-index: 1000;
        margin-top: 0.5rem;
        display: none;
        animation: fadeIn 0.2s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
        font-weight: 500;
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
    
    .user-menu-item.active {
        background-color: rgba(var(--price-color-rgb), 0.1);
        color: var(--price-color);
    }

    /* Page header */
    .page-header {
        background-size: cover;
        background-position: center;
        position: relative;
        min-height: 350px;
        display: flex;
        align-items: center;
        padding: 3rem 0;
        z-index: 1;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6));
        z-index: -1;
    }

    .page-title {
        color: #fff;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
    }

    .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
    }

    .breadcrumb-item.active {
        color: #ffffff;
    }

    .contact-info-icon {
        width: 60px;
        height: 60px;
        background: rgba(43, 130, 195, 0.1);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.5rem;
        color: var(--price-color);
        margin-bottom: 1rem;
    }

    .contact-block {
        padding: 2.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }

    .contact-block:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .message-box {
        background: var(--card-bg);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 0 30px rgba(0,0,0,0.05);
    }

    .message-header {
        background-color: var(--price-color);
        color: white;
        padding: 1.5rem;
    }

    .message-body {
        padding: 2rem;
    }

    .contact-form .form-control {
        border: 1px solid var(--form-border);
        border-radius: 0.5rem;
        padding: 0.8rem 1.2rem;
        background-color: var(--form-bg);
        color: var(--text-color);
    }

    .contact-form .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(43, 130, 195, 0.25);
        border-color: var(--price-color);
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .social-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        background-color: rgba(43, 130, 195, 0.1);
        color: var(--price-color);
        transition: all 0.3s ease;
    }

    .social-icon:hover {
        background-color: var(--price-color);
        color: white;
    }

    .map-container {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .faq-item {
        background: var(--card-bg);
        border-radius: 0.5rem;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .faq-question {
        padding: 1.25rem;
        cursor: pointer;
        position: relative;
        font-weight: 500;
        color: var(--heading-color);
        transition: all 0.3s ease;
    }

    .faq-answer {
        padding: 0 1.25rem 1.25rem;
    }

    /* Chat box */
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
    
    #chatMessagesContainer::after {
        content: "";
        clear: both;
        display: table;
    }
  </style>
</head>

<body>

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <h1 class="sitename">Serenity Haven</h1>
      </a>

      <nav id="navmenu" class="navmenu mx-auto">
        <ul>
          <li><a href="landing_page.php">Home</a></li>
          <li><a href="landing_page.php#rooms">Rooms</a></li>
          <li><a href="landing_page.php#facilities">Facilities</a></li>
          <li><a href="landing_page.php#about">About</a></li>
          <li><a href="contact.php" class="active">Contact</a></li>
        </ul>
      </nav>

      <div class="auth-buttons d-flex align-items-center gap-3">
        <?php if($isLoggedIn): ?>
        <!-- User menu container with dropdown -->        <div class="user-menu-container">
          <button type="button" class="btn btn-outline-primary d-flex align-items-center" id="userMenuButton" aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <span class="me-1 text-truncate" style="max-width: 150px;"><?= htmlspecialchars($user['nama']) ?></span>
            <i class="bi bi-chevron-down ms-1 small"></i>
          </button>
          
          <!-- User dropdown menu -->
          <div class="user-menu" id="userMenu">
            <div class="user-menu-header">
              <div class="fw-bold"><?= htmlspecialchars($user['nama']) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <a href="profile.php" class="user-menu-item">
              <i class="bi bi-person"></i> My Profile
            </a>
            <a href="landing_page.php?section=riwayat" class="user-menu-item<?= (isset($_GET['section']) && $_GET['section'] == 'riwayat') ? ' active' : '' ?>">
              <i class="bi bi-clock-history"></i> Booking History            </a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="admin/" class="user-menu-item">
              <i class="bi bi-speedometer2"></i> Admin Dashboard
            </a>
            <?php endif; ?>
            <div class="user-menu-divider"></div>
            <div class="px-3 py-2">
              <p class="mb-2 small text-muted fw-bold">Booking Status</p>
              <div class="d-flex flex-column gap-2 mb-2">
                <div class="d-flex justify-content-between">
                  <span class="small">Active Bookings</span>
                  <span class="badge bg-success rounded-pill"><?= $activeCount ?></span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="small">Pending Payments</span>
                  <span class="badge bg-warning text-dark rounded-pill"><?= $pendingCount ?></span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="small">Completed Stays</span>
                  <span class="badge bg-info rounded-pill"><?= $completedCount ?></span>
                </div>
              </div>
            </div>
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

  <main id="main">
    <!-- Page Header -->
    <div class="page-header" style="background-image: url('hotel1.jpg');">
      <div class="container">
        <div class="row">
          <div class="col-lg-8">
            <h1 class="page-title" data-aos="fade-up">Contact Us</h1>
            <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="landing_page.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
              </ol>
            </nav>
            <p class="text-white lead mt-3" data-aos="fade-up" data-aos-delay="200">Reach out to us for any inquiries or assistance. We're here to make your stay exceptional.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Info Cards Section -->
    <section class="py-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-md-4" data-aos="fade-up">
            <div class="card h-100 border-0 shadow-sm contact-block">
              <div class="card-body text-center">
                <div class="contact-info-icon mx-auto">
                  <i class="bi bi-geo-alt"></i>
                </div>
                <h4 class="mb-3">Our Location</h4>
                <p class="mb-0">Midpoint Place<br>Jl. H. Fachrudin No.26, RT.9/RW.5, Kp. Bali<br>Kecamatan Tanah Abang, Jakarta Pusat, 10250</p>
              </div>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card h-100 border-0 shadow-sm contact-block">
              <div class="card-body text-center">
                <div class="contact-info-icon mx-auto">
                  <i class="bi bi-telephone"></i>
                </div>
                <h4 class="mb-3">Contact Details</h4>
                <p class="mb-1"><strong>Phone:</strong> +62 21 1234 5678</p>
                <p class="mb-1"><strong>Mobile:</strong> +62853-1111-0010</p>
                <p class="mb-0"><strong>Email:</strong> info@serenityhaven.com</p>
              </div>
            </div>
          </div>

          <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card h-100 border-0 shadow-sm contact-block">
              <div class="card-body text-center">
                <div class="contact-info-icon mx-auto">
                  <i class="bi bi-clock"></i>
                </div>
                <h4 class="mb-3">Open Hours</h4>
                <p class="mb-1"><strong>Reception:</strong> 24/7</p>
                <p class="mb-1"><strong>Restaurant:</strong> 6:30 AM - 10:30 PM</p>
                <p class="mb-0"><strong>Spa & Wellness:</strong> 9:00 AM - 8:00 PM</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Contact Section -->
    <section class="py-5 bg-light">
      <div class="container">
        <div class="row g-5">
          <!-- Google Maps -->
          <div class="col-lg-6" data-aos="fade-right">
            <div class="map-container">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.6664163781287!2d106.81742081019425!3d-6.1823876610262985!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5d47f196479%3A0xfa32d89e11246639!2sJl.%20H.%20Fachrudin%20No.26%2C%20RT.9%2FRW.5%2C%20Kp.%20Bali%2C%20Kecamatan%20Tanah%20Abang%2C%20Kota%20Jakarta%20Pusat%2C%20Daerah%20Khusus%20Ibukota%20Jakarta%2010250!5e0!3m2!1sen!2sid!4v1683520109040!5m2!1sen!2sid" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            
            <div class="mt-4">
              <h4 class="mb-3">Connect With Us</h4>
              <div class="d-flex flex-wrap">
                <a href="#" class="social-icon">
                  <i class="bi bi-facebook"></i>
                </a>
                <a href="#" class="social-icon">
                  <i class="bi bi-instagram"></i>
                </a>
                <a href="#" class="social-icon">
                  <i class="bi bi-twitter-x"></i>
                </a>
                <a href="#" class="social-icon">
                  <i class="bi bi-linkedin"></i>
                </a>
                <a href="#" class="social-icon">
                  <i class="bi bi-whatsapp"></i>
                </a>
              </div>
              
              <div class="mt-4">
                <h5>Hotel Facilities</h5>
                <div class="row g-2 mt-2">
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-wifi text-primary me-2"></i>
                      <span>Free WiFi</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-p-circle text-primary me-2"></i>
                      <span>Free Parking</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-water text-primary me-2"></i>
                      <span>Swimming Pool</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-cup-hot text-primary me-2"></i>
                      <span>Restaurant</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-person-raised-hand text-primary me-2"></i>
                      <span>Room Service</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-emoji-smile text-primary me-2"></i>
                      <span>Spa & Wellness</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Contact Form & My Chat Area -->
          <div class="col-lg-6" data-aos="fade-left">
            <div class="message-box">
              <div class="message-header">
                <h3 class="m-0"><i class="bi bi-chat-dots me-2"></i>My Chat</h3>
              </div>
              <div class="message-body">
                <?php if($isLoggedIn): ?>
                <form class="contact-form" id="chatForm">
                  <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject" required>
                      <option value="" selected disabled>Select Subject</option>
                      <option value="reservation">Reservation Inquiry</option>
                      <option value="feedback">Feedback</option>
                      <option value="services">Special Services</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="message" class="form-label">Your Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" placeholder="How can we help you?" required></textarea>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="attachments" class="form-label">Attachments (optional)</label>
                        <input class="form-control" type="file" id="attachments" name="attachments[]" multiple>
                      </div>
                    </div>
                    <div class="col-md-6 align-self-end">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="priority" name="priority">
                        <label class="form-check-label" for="priority">Mark as Priority</label>
                      </div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-2"></i>Send Message
                    </button>
                    <a href="chat_user.php" class="btn btn-outline-primary">
                      <i class="bi bi-chat-history me-2"></i>View Chat History
                    </a>
                  </div>
                </form>
                <?php else: ?>
                <div class="text-center py-4">
                  <div class="mb-4">
                    <i class="bi bi-chat-left-dots text-primary" style="font-size: 4rem;"></i>
                  </div>
                  <h4 class="mb-3">Login to Chat With Us</h4>
                  <p class="text-muted mb-4">Please login to your account to access our chat support and manage your communications with us.</p>
                  <div class="d-grid gap-2 col-lg-8 mx-auto">
                    <a href="login.php" class="btn btn-primary">
                      <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                    <a href="register.php" class="btn btn-outline-primary">
                      <i class="bi bi-person-plus me-2"></i>Create Account
                    </a>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- FAQ Section -->
            <div class="mt-5">
              <h4 class="mb-4">Frequently Asked Questions</h4>
              
              <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                      What are the check-in and check-out times?
                    </button>
                  </h2>
                  <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      Check-in time is from 14:00 (2:00 PM), and check-out time is until 12:00 (noon). Early check-in or late check-out may be arranged upon request, subject to availability and additional charges.
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                      How can I modify or cancel my reservation?
                    </button>
                  </h2>
                  <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      You can modify or cancel your reservation through your account dashboard. Cancellations made at least 48 hours before check-in are eligible for a full refund. Late cancellations may be subject to a fee according to our policy.
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                      Is airport transfer available?
                    </button>
                  </h2>
                  <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      Yes, we offer airport transfer services for an additional fee. Please contact our concierge at least 24 hours before your arrival to arrange transportation. You can reach us by email at concierge@serenityhaven.com or by phone at +62 21 1234 5678.
                    </div>
                  </div>
                </div>
                
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                      Are pets allowed in the hotel?
                    </button>
                  </h2>
                  <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      We have designated pet-friendly rooms available with certain restrictions. Please inform us in advance if you'll be bringing a pet, as additional cleaning fees may apply. Service animals are always welcome throughout the hotel.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8 text-center" data-aos="fade-up">
            <h3 class="mb-3">Subscribe to Our Newsletter</h3>
            <p class="text-muted mb-4">Stay updated with our latest offers, promotions and news.</p>
            <div class="row justify-content-center">
              <div class="col-md-8">
                <form class="newsletter-form">
                  <div class="input-group">
                    <input type="email" class="form-control" placeholder="Your email address" required>
                    <button class="btn btn-primary" type="submit">Subscribe</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="bg-dark text-light pt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <h4 class="mb-4">Serenity Haven</h4>
          <p>Redefining luxury hospitality since 2010</p>
          <div class="mt-3">
            <p class="mb-1"><i class="bi bi-geo-alt me-2"></i> Jl. H. Fachrudin No.26, Jakarta Pusat</p>
            <p class="mb-1"><i class="bi bi-telephone me-2"></i> +62 21 1234 5678</p>
            <p class="mb-1"><i class="bi bi-envelope me-2"></i> info@serenityhaven.com</p>
          </div>
        </div>
        <div class="col-lg-4">
          <h4 class="mb-4">Quick Links</h4>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="landing_page.php" class="text-light">Home</a></li>
            <li class="mb-2"><a href="landing_page.php#rooms" class="text-light">Rooms & Suites</a></li>
            <li class="mb-2"><a href="landing_page.php#facilities" class="text-light">Facilities</a></li>
            <li class="mb-2"><a href="landing_page.php#about" class="text-light">About Us</a></li>
            <li class="mb-2"><a href="contact.php" class="text-light">Contact Us</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <h4 class="mb-4">Connect With Us</h4>
          <div class="social-links mb-4">
            <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-linkedin"></i></a>
          </div>
          <p>Download our mobile app for exclusive offers</p>
          <div class="d-flex gap-2 mt-3">
            <a href="#" class="btn btn-outline-light"><i class="bi bi-apple me-2"></i>App Store</a>
            <a href="#" class="btn btn-outline-light"><i class="bi bi-google-play me-2"></i>Google Play</a>
          </div>
        </div>
      </div>
      <div class="text-center py-4 mt-4 border-top border-secondary">
        <p class="mb-0">&copy; 2024 Serenity Haven Hotel. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Theme Toggle Button -->
  <button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode">
    <i class="bi bi-moon-fill"></i>
  </button>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Template Main JS File -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 1000,
        once: true
      });
      
      // Theme toggle functionality
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
        // Enhanced User Menu Dropdown
      const userMenuButton = document.getElementById('userMenuButton');
      const userMenu = document.getElementById('userMenu');
      
      if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', (e) => {
          e.stopPropagation();
          userMenu.classList.toggle('show');
          
          // Toggle aria-expanded attribute for accessibility
          const isExpanded = userMenu.classList.contains('show');
          userMenuButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
          
          // Focus the first item in the menu when opened
          if (isExpanded) {
            const firstMenuItem = userMenu.querySelector('.user-menu-item');
            if (firstMenuItem) {
              setTimeout(() => {
                // Small delay to allow animation to complete
                firstMenuItem.focus();
              }, 100);
            }
          }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
          if (userMenu.classList.contains('show') && !userMenu.contains(e.target) && !userMenuButton.contains(e.target)) {
            userMenu.classList.remove('show');
            userMenuButton.setAttribute('aria-expanded', 'false');
          }
        });
        
        // Close menu on Escape key
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && userMenu.classList.contains('show')) {
            userMenu.classList.remove('show');
            userMenuButton.setAttribute('aria-expanded', 'false');
            userMenuButton.focus(); // Return focus to button
          }
        });
      }
      
      // Form submission (prevent default for demo)
      const contactForm = document.getElementById('chatForm');
      if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Show success message (replace with your actual form submission logic)
          alert('Thank you for your message! Our team will get back to you shortly.');
          this.reset();
        });
      }
      
      // Newsletter subscription (prevent default for demo)
      const newsletterForm = document.querySelector('.newsletter-form');
      if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Show success message
          alert('Thank you for subscribing to our newsletter!');
          this.reset();
        });
      }      
      // Function to reinitialize user menu when content changes
      function reinitializeUserMenu() {
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuButton && userMenu) {
          // Make sure the menu is properly initialized
          userMenuButton.setAttribute('aria-expanded', userMenu.classList.contains('show') ? 'true' : 'false');
          userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('show');
            
            const isExpanded = userMenu.classList.contains('show');
            userMenuButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
          });
        }
      }
      
      // Call reinitialize on any dynamic content changes
      document.addEventListener('DOMContentLoaded', reinitializeUserMenu);
    });
  </script>
</body>

</html>
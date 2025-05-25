<?php
// Contact Section for landing_page.php
// This file is included in landing_page.php
// It contains the contact section content

// Check if config file is included
if (!defined('INCLUDED_CONFIG')) {
    require_once 'config.php';
}

// Check login status (assuming your login system sets this variable)
$isLoggedIn = (isset($_SESSION['id_user']) && !empty($_SESSION['id_user']));
$userId = $isLoggedIn ? $_SESSION['id_user'] : 0;

$userData = null;
if ($isLoggedIn) {
    $userStmt = $conn->prepare("SELECT username, email, nama FROM user WHERE id_user = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission (works for both logged in and guest users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $kategori = $_POST['kategori'];
    $judul = $_POST['judul'];
    $pesan = $_POST['pesan'];
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : NULL;
    
    // For guest users
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $nama = isset($_POST['nama']) ? $_POST['nama'] : '';
    
    if ($isLoggedIn) {
        // Use logged in user data
        $nama = $userData['nama'] ?? $userData['username'];
        $email = $userData['email'];
        
        // Get user information to link to transaction
        $stmt = $conn->prepare("SELECT nik FROM user WHERE id_user = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();
        
        if ($userRow) {
            $nik = $userRow['nik'];
        } else {
            $nik = '0000000000000000'; // Default NIK for system if user doesn't have one
        }
    } else {
        // Guest user with no account
        $nik = '0000000000000000'; // Default NIK for guest
    }
    
    // Find an available room to attach this feedback to
    // Changed from mysqli_query to PDO query
    $stmt = $conn->query("SELECT id_kamar FROM kamar WHERE status = 'tersedia' LIMIT 1");
    $roomRow = $stmt->fetch();
    
    if ($roomRow) {
        $kamarId = $roomRow['id_kamar'];
    } else {
        // Use default room ID if none available
        $kamarId = 1; // Assuming you have at least one room with ID 1
    }
    
    // Process file upload if present
    $lampiran = NULL;
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $upload_dir = 'uploads/feedback/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['lampiran']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
            $lampiran = $target_file;
        }
    }
    
    // Modified Insert query - removed guest_name and guest_email fields that don't exist
    $insertStmt = $conn->prepare("INSERT INTO transaksi (nik, id_kamar, id_user, tgl_booking, tgl_checkin, tgl_checkout, 
              totalharga, status, jenis_booking, feedback_kategori, feedback_judul, feedback_pesan, 
              feedback_rating, feedback_status, feedback_lampiran, feedback_tanggal) 
              VALUES (?, ?, ?, CURDATE(), CURDATE(), CURDATE(), 
              0.00, 'pending', 'feedback', ?, ?, ?, 
              ?, 'baru', ?, NOW())");
              
    $insertStmt->execute([
        $nik,
        $kamarId,
        $isLoggedIn ? $userId : null,
        $kategori,
        $judul,
        $pesan,
        $rating,
        $lampiran
        // Removed the guest_name and guest_email parameters
    ]);
    
    if ($insertStmt->rowCount() > 0) {
        $successMessage = "Thank you for your feedback! We appreciate your input.";
    } else {
        $errorMessage = "Failed to send feedback: " . $conn->errorInfo()[2];
    }
}
?>

<section class="page-header" style="background-image: url('hotel1.jpg');">
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
</section>

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
      
      <!-- Contact Form & Feedback Area -->
      <div class="col-lg-6" data-aos="fade-left">
        <div class="message-box shadow-sm">
          <div class="message-header">
            <h3 class="m-0"><i class="bi bi-chat-dots me-2"></i>Give Feedback</h3>
          </div>
          <div class="message-body">
            <!-- Success/error message container -->
            <div id="feedbackMessages"></div>
            
            <?php if(!$isLoggedIn): ?>
            <div class="text-center py-4">
              <div class="mb-4">
                <i class="bi bi-chat-left-dots text-primary" style="font-size: 4rem;"></i>
              </div>
              <h4 class="mb-3">Login to Send Feedback</h4>
              <p class="text-muted mb-4">Please login to your account to share your feedback with us. Your input helps us improve our services.</p>
              <div class="d-grid gap-2 col-lg-8 mx-auto">
                <a href="login.php" class="btn btn-primary">
                  <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </a>
                <a href="register.php" class="btn btn-outline-primary">
                  <i class="bi bi-person-plus me-2"></i>Create Account
                </a>
              </div>
            </div>
            <?php else: ?>
            <form id="contactForm" class="contact-form" method="POST" action="" enctype="multipart/form-data">
              <!-- Show user info -->
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Your Name</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($userData['nama'] ?? $userData['username']) ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Your Email</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
                  <option value="" selected disabled>Select Category</option>
                  <option value="Service">Service</option>
                  <option value="Facility">Facility</option>
                  <option value="Staff">Staff</option>
                  <option value="Cleanliness">Cleanliness</option>
                  <option value="Food">Food & Beverage</option>
                  <option value="Umum">General</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label for="judul" class="form-label">Subject</label>
                <input type="text" class="form-control" id="judul" name="judul" placeholder="Enter subject" required>
              </div>
              
              <div class="mb-3">
                <label for="pesan" class="form-label">Your Message</label>
                <textarea class="form-control" id="pesan" name="pesan" rows="4" placeholder="How was your experience?" required></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="rating-input">
                  <?php for($i = 1; $i <= 5; $i++): ?>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>">
                    <label class="form-check-label" for="rating<?php echo $i; ?>">
                      <?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i>
                    </label>
                  </div>
                  <?php endfor; ?>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="lampiran" class="form-label">Attachment (optional)</label>
                <input class="form-control" type="file" id="lampiran" name="lampiran">
                <div class="form-text">Upload images, documents, or other files related to your feedback.</div>
              </div>
              
              <div class="d-flex justify-content-between align-items-center">
                <button type="submit" name="submit_feedback" id="submitFeedback" class="btn btn-primary">
                  <i class="bi bi-send me-2"></i>Send Feedback
                </button>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="mt-5">
          <h4 class="mb-4">Frequently Asked Questions</h4>
          
          <div class="accordion" id="contactFaq">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  How can I make a reservation?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#contactFaq">
                <div class="accordion-body">
                  You can make reservations through our website by selecting the "Rooms" section and choosing your desired room type and dates. Alternatively, you can contact us by phone or email.
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  What is the check-in and check-out time?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#contactFaq">
                <div class="accordion-body">
                  Standard check-in time is 2:00 PM and check-out time is 12:00 PM. Early check-in or late check-out can be arranged subject to availability.
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  Do you offer airport transportation?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#contactFaq">
                <div class="accordion-body">
                  Yes, we offer airport transfers for our guests. Please contact our concierge at least 24 hours before your arrival to arrange transportation.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: rgba(var(--price-color-rgb), 0.1);
    border-radius: 50%;
    color: var(--price-color);
    margin-right: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
  }
  
  .social-icon:hover {
    background-color: var(--price-color);
    color: #ffffff;
    transform: translateY(-3px);
  }
  
  .message-header {
    background-color: var(--price-color);
    color: white;
    padding: 1rem;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }
  
  .message-body {
    padding: 2rem;
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
  
  .rating-input {
    display: flex;
    gap: 10px;
  }
  
  .rating-input .form-check-input:checked + .form-check-label {
    font-weight: bold;
    color: var(--price-color);
  }
  
  .rating-input .form-check-label {
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .rating-input .form-check-label:hover {
    color: var(--price-color);
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('contactForm');
  
  if (form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      
      const submitButton = document.getElementById('submitFeedback');
      const originalButtonText = submitButton.innerHTML;
      submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...';
      submitButton.disabled = true;
      
      const formData = new FormData(form);
      formData.append('submit_feedback', 'true');
      
      fetch('process_feedback.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        const messageContainer = document.getElementById('feedbackMessages');
        
        if (data.success) {
          messageContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                          ${data.message}
                                          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>`;
          form.reset();
        } else {
          messageContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                          ${data.message}
                                          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>`;
          
          // If redirection is needed
          if (data.redirect) {
            setTimeout(function() {
              window.location.href = data.redirect;
            }, 2000);
          }
        }
        
        messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
      })
      .catch(error => {
        console.error('Error:', error);
        const messageContainer = document.getElementById('feedbackMessages');
        messageContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Network error. Please check your connection and try again.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                      </div>`;
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
      });
    });
  }
});
</script>

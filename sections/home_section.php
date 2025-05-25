<?php
// This file is included in landing_page.php
// It contains the home section content

// Check if config file is included
if (!defined('INCLUDED_CONFIG')) {
    require_once 'config.php';
}

// Get featured rooms for homepage
$featured_rooms = $conn->prepare("
    SELECT * FROM kamar 
    WHERE status = 'tersedia' 
    ORDER BY harga DESC
    LIMIT 3
");
$featured_rooms->execute();
$featuredRooms = $featured_rooms->fetchAll();
?>

<!-- Testimonials Section -->
<section id="testimonials" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5" style="color: #000000 !important;">Apa Kata Tamu Kami</h2>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <p class="card-text">"Pengalaman menginap yang luar biasa. Kamar bersih, staf ramah, dan fasilitas lengkap. Kami pasti akan kembali lagi."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-3">
                                <h6 class="fw-bold mb-0">Budi Santoso</h6>
                                <small class="text-muted">Jakarta</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <p class="card-text">"Lokasi strategis dan kamar yang nyaman. Sarapan pagi sangat enak dengan banyak pilihan menu. Sangat direkomendasikan!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-3">
                                <h6 class="fw-bold mb-0">Siti Rahayu</h6>
                                <small class="text-muted">Surabaya</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                            </div>
                        </div>
                        <p class="card-text">"Pelayanan sangat bagus dan profesional. Kolam renang bersih dan nyaman. Pemandangan dari kamar sangat indah."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-3">
                                <h6 class="fw-bold mb-0">Ahmad Rizki</h6>
                                <small class="text-muted">Bandung</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Home page specific script to fix user menu dropdown -->
<script>
    // This ensures the user menu works specifically on the home page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Home page section loaded - initializing user menu');
        
        // Wait a moment for all elements to be fully loaded and processed
        setTimeout(function() {
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuButton && userMenu) {
                console.log('User menu elements found on home page');
                
                // Direct click handler that takes precedence over other handlers
                userMenuButton.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                    console.log('User menu toggled on home page');
                };
                
                // Make sure menu is initialized in correct state
                userMenu.classList.remove('show');
                
                // Document click handler for closing the menu
                document.addEventListener('click', function(e) {
                    if (userMenu.classList.contains('show') && 
                        !userMenu.contains(e.target) && 
                        !userMenuButton.contains(e.target)) {
                        userMenu.classList.remove('show');
                    }
                });
            } else {
                console.log('User menu elements not found on home page');
            }
        }, 500); // Half-second delay to ensure DOM is ready
    });
</script>



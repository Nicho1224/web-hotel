<?php
// This file is included in landing_page.php
// It contains the facilities section content

// Check if config file is included
if (!defined('INCLUDED_CONFIG')) {
    require_once 'config.php';
}
?>

<section class="facilities-hero py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-4 mb-4">Fasilitas Hotel Kami</h2>
                <p class="lead mb-4">
                    Nikmati berbagai fasilitas premium yang kami sediakan untuk membuat pengalaman menginap Anda lebih menyenangkan dan berkesan.
                </p>
                <p class="mb-4">
                    Hotel kami menawarkan berbagai fasilitas untuk memenuhi kebutuhan Anda selama menginap, mulai dari kolam renang, spa, pusat kebugaran, hingga restoran dan bar.
                </p>
                <a href="#main-facilities" class="btn btn-primary">Lihat Semua Fasilitas</a>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="hotel2.jpg" alt="Hotel Facilities" class="img-fluid rounded shadow">
                    <img src="spa.jpg" alt="Spa" class="img-fluid rounded shadow position-absolute" style="width: 60%; right: -10%; bottom: -20%;">
                </div>
            </div>
        </div>
    </div>
</section>

<section id="main-facilities" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Fasilitas Utama</h2>
        
        <div class="row">
            <!-- Swimming Pool -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="hotel1.jpg" class="card-img-top" alt="Swimming Pool">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-water me-2"></i>Kolam Renang</h4>
                        <p class="card-text">
                            Nikmati segar dan keindahan kolam renang kami yang terletak di lantai dasar hotel. Kolam renang ini dibuka setiap hari dari pukul 07:00 hingga 21:00.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Kolam renang dewasa dan anak-anak</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Kursi berjemur dan payung</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Handuk gratis</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Bar kolam renang</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Restaurant -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="hotel3.jpg" class="card-img-top" alt="Restaurant">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-cup-hot me-2"></i>Restoran</h4>
                        <p class="card-text">
                            Restoran kami menyajikan berbagai menu lokal dan internasional dengan bahan-bahan segar dan berkualitas tinggi.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Sarapan pagi 06:00 - 10:00</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Makan siang 12:00 - 15:00</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Makan malam 18:00 - 22:00</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Room service 24 jam</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Spa -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="spa.jpg" class="card-img-top" alt="Spa">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-brightness-high me-2"></i>Spa</h4>
                        <p class="card-text">
                            Nikmati relaksasi dan perawatan tubuh di spa kami yang menawarkan berbagai treatment untuk menyegarkan tubuh dan pikiran Anda.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Pijat tradisional dan modern</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Perawatan wajah</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Body scrub dan masker</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Jacuzzi dan sauna</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Fitness Center -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="deluxe.jpg" class="card-img-top" alt="Fitness Center">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-bicycle me-2"></i>Pusat Kebugaran</h4>
                        <p class="card-text">
                            Jaga kebugaran Anda selama menginap di hotel kami dengan menggunakan pusat kebugaran yang dilengkapi dengan peralatan modern.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Peralatan kardio dan beban</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Personal trainer (dengan biaya tambahan)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Kelas yoga (Senin, Rabu, Jumat)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Buka 24 jam untuk tamu hotel</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Business Center -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="hotel1.jpg" class="card-img-top" alt="Business Center">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-laptop me-2"></i>Pusat Bisnis</h4>
                        <p class="card-text">
                            Pusat bisnis kami menyediakan berbagai fasilitas untuk mendukung kebutuhan bisnis Anda selama menginap di hotel kami.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Komputer dan printer</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Layanan fotokopi dan fax</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Ruang rapat (kapasitas hingga 20 orang)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> WiFi kecepatan tinggi</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Lounge & Bar -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <img src="hotel2.jpg" class="card-img-top" alt="Lounge & Bar">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bi bi-cup-straw me-2"></i>Lounge & Bar</h4>
                        <p class="card-text">
                            Nikmati suasana santai di lounge dan bar kami yang menawarkan berbagai minuman dan makanan ringan.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Koktail dan minuman premium</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Live music (Jumat & Sabtu)</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Happy hour 17:00 - 19:00</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Buka dari 11:00 - 24:00</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Fasilitas Dalam Kamar</h2>
        
        <div class="row text-center">
            <!-- WiFi -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-wifi"></i>
                    </div>
                    <h4>WiFi Gratis</h4>
                    <p>Koneksi internet cepat di seluruh area hotel</p>
                </div>
            </div>
            
            <!-- TV -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-tv"></i>
                    </div>
                    <h4>TV Layar Datar</h4>
                    <p>Smart TV dengan berbagai channel lokal dan internasional</p>
                </div>
            </div>
            
            <!-- AC -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-snow"></i>
                    </div>
                    <h4>AC</h4>
                    <p>Pengatur suhu ruangan untuk kenyamanan Anda</p>
                </div>
            </div>
            
            <!-- Mini Bar -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-minecart"></i>
                    </div>
                    <h4>Minibar</h4>
                    <p>Minuman dan cemilan tersedia di dalam kamar</p>
                </div>
            </div>
            
            <!-- Safe Deposit Box -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-archive"></i>
                    </div>
                    <h4>Brankas</h4>
                    <p>Simpan barang berharga Anda dengan aman</p>
                </div>
            </div>
            
            <!-- Coffee/Tea Maker -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-cup-hot"></i>
                    </div>
                    <h4>Pembuat Kopi/Teh</h4>
                    <p>Nikmati kopi atau teh kapan saja di kamar Anda</p>
                </div>
            </div>
            
            <!-- Bathroom Amenities -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-droplet"></i>
                    </div>
                    <h4>Perlengkapan Kamar Mandi</h4>
                    <p>Peralatan mandi lengkap untuk kenyamanan Anda</p>
                </div>
            </div>
            
            <!-- Telephone -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <h4>Telepon</h4>
                    <p>Telepon untuk layanan kamar dan panggilan lokal</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Peraturan dan Kebijakan Hotel</h2>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Waktu Check-in dan Check-out</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-clock me-2"></i> Check-in</span>
                                <span class="badge bg-primary">14:00 - 22:00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-clock-history me-2"></i> Check-out</span>
                                <span class="badge bg-primary">Sebelum 12:00</span>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-info-circle me-2"></i> Early check-in dan late check-out tersedia dengan biaya tambahan, tergantung ketersediaan kamar.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Kebijakan Pembatalan</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-check-circle me-2"></i> Pembatalan gratis hingga 24 jam sebelum waktu check-in.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-exclamation-triangle me-2"></i> Pembatalan kurang dari 24 jam sebelum check-in dikenakan biaya satu malam.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-x-circle me-2"></i> No-show (tidak datang tanpa pemberitahuan) dikenakan biaya penuh sesuai pemesanan.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Peraturan Hotel</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-slash-circle me-2"></i> Dilarang merokok di dalam kamar (tersedia area khusus untuk merokok).
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-slash-circle me-2"></i> Dilarang membawa hewan peliharaan (kecuali anjing pemandu).
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-volume-down me-2"></i> Harap menjaga ketenangan setelah pukul 22:00.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Informasi Tambahan</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-credit-card me-2"></i> Deposit kartu kredit atau tunai diperlukan saat check-in.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-people me-2"></i> Tempat tidur tambahan tersedia dengan biaya tambahan.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-car-front me-2"></i> Parkir gratis tersedia untuk tamu hotel.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Booking Call to Action -->
<section class="py-5 text-white text-center" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('hotel3.jpg'); background-size: cover; background-position: center;">
    <div class="container py-5">
        <h2 class="display-4 mb-4">Pesan Kamar Sekarang</h2>
        <p class="lead mb-4">Nikmati semua fasilitas premium kami dengan harga terbaik</p>
        <a href="landing_page.php?section=rooms" class="btn btn-primary btn-lg px-5 py-3">Pesan Sekarang</a>
    </div>
</section>

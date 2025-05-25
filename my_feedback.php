<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Get user's feedback history
$feedbacks = $conn->prepare("
    SELECT t.*, k.nama as nama_kamar, k.jenis as jenis_kamar
    FROM transaksi t
    LEFT JOIN kamar k ON t.id_kamar = k.id_kamar
    WHERE t.id_user = ? AND t.kategori_feedback IS NOT NULL
    ORDER BY t.created_at DESC
");
$feedbacks->execute([$id_user]);
$feedbackList = $feedbacks->fetchAll();

// Include header
include_once 'header_user.php';
?>

<div class="container mt-5 pt-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-star me-2"></i>Riwayat Feedback Saya</h2>
                <a href="contact.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Buat Feedback Baru
                </a>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        Feedback berhasil dikirim. Terima kasih atas masukan Anda!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <?php if (empty($feedbackList)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-chat-left-dots" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">Belum Ada Riwayat Feedback</h4>
                <p class="text-muted">Anda belum memberikan feedback terkait pengalaman menginap di hotel kami.</p>
                <a href="contact.php" class="btn btn-outline-primary mt-2">Berikan Feedback Sekarang</a>
            </div>
        <?php else: ?>
            <?php foreach ($feedbackList as $feedback): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($feedback['judul_feedback']) ?></h5>
                            <span class="badge badge-<?= $feedback['status_feedback'] ?>">
                                <?= $feedback['status_feedback'] ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="star-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= ($i <= $feedback['rating_feedback']) ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-2"><?= $feedback['rating_feedback'] ?>/5</span>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-primary"><?= ucfirst($feedback['kategori_feedback']) ?></span>
                                </div>
                            </div>
                            
                            <p class="card-text text-truncate">
                                <?= htmlspecialchars($feedback['pesan_feedback']) ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= date('d M Y', strtotime($feedback['created_at'])) ?>
                                </small>
                                
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal<?= $feedback['id_transaksi'] ?>">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feedback Detail Modal -->
                <div class="modal fade" id="feedbackModal<?= $feedback['id_transaksi'] ?>" tabindex="-1" aria-labelledby="feedbackModalLabel<?= $feedback['id_transaksi'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="feedbackModalLabel<?= $feedback['id_transaksi'] ?>">
                                    Detail Feedback: <?= htmlspecialchars($feedback['judul_feedback']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Informasi Feedback</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th>Kategori</th>
                                                <td><?= ucfirst($feedback['kategori_feedback']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Rating</th>
                                                <td>
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?= ($i <= $feedback['rating_feedback']) ? '-fill' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                    (<?= $feedback['rating_feedback'] ?>/5)
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Kirim</th>
                                                <td><?= date('d M Y, H:i', strtotime($feedback['created_at'])) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td><span class="badge badge-<?= $feedback['status_feedback'] ?>"><?= $feedback['status_feedback'] ?></span></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Informasi Kamar</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th>Kamar</th>
                                                <td><?= htmlspecialchars($feedback['nama_kamar']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jenis</th>
                                                <td><?= htmlspecialchars($feedback['jenis_kamar']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Check-in</th>
                                                <td><?= date('d M Y', strtotime($feedback['tgl_checkin'])) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Check-out</th>
                                                <td><?= date('d M Y', strtotime($feedback['tgl_checkout'])) ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Isi Feedback</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($feedback['pesan_feedback'])) ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($feedback['lampiran_feedback'])): ?>
                                <div class="mb-4">
                                    <h6>Lampiran</h6>
                                    <div class="text-center">
                                        <img src="uploads/<?= htmlspecialchars($feedback['lampiran_feedback']) ?>" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($feedback['balasan_feedback'])): ?>
                                <div>
                                    <h6 class="d-flex align-items-center">
                                        <i class="bi bi-reply-fill me-2 text-primary"></i> Balasan dari Admin
                                    </h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <?= nl2br(htmlspecialchars($feedback['balasan_feedback'])) ?>
                                            
                                            <div class="text-muted small mt-3">
                                                <i class="bi bi-clock me-1"></i> Dibalas pada: <?= date('d M Y, H:i', strtotime($feedback['waktu_balasan_feedback'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .badge-baru {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-dibaca {
        background-color: #0d6efd;
        color: white;
    }
    
    .badge-diproses {
        background-color: #ffc107;
        color: #000;
    }
    
    .badge-selesai {
        background-color: #198754;
        color: white;
    }
    
    .star-rating {
        color: #ffc107;
    }
</style>

<?php include_once 'footer.php'; ?>

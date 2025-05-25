<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['id_user']) || $_SESSION['lv'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

// Ambil daftar feedback dari tabel transaksi
try {
    $feedbacks = $conn->query("
        SELECT t.*, u.username, u.profile_image
        FROM transaksi t
        JOIN user u ON t.id_user = u.id_user
        WHERE t.feedback_kategori IS NOT NULL AND t.is_deleted = 0
        ORDER BY t.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $feedbacks = [];
}

// Handle feedback responses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['balasan_feedback'])) {
    $feedbackId = $_POST['feedback_id'];
    $balasan = $_POST['balasan_feedback'];
    
    $stmt = $conn->prepare("
        UPDATE transaksi 
        SET feedback_status = 'diproses', 
            balasan = ?,
            waktu_balasan = NOW(),
            id_admin = ?
        WHERE id_transaksi = ?
    ");
    $stmt->execute([$balasan, $_SESSION['id_user'], $feedbackId]);
    
    header("Location: admin_feedback.php?feedback=$feedbackId");
    exit;
}

// Mark feedback as complete
if (isset($_GET['feedback']) && isset($_GET['complete']) && $_GET['complete'] === 'true') {
    $feedbackId = $_GET['feedback'];
    $conn->prepare("UPDATE transaksi SET feedback_status = 'selesai' WHERE id_transaksi = ?")->execute([$feedbackId]);
    header("Location: admin_feedback.php?feedback=$feedbackId");
    exit;
}

// Get selected feedback details
$selectedFeedback = null;
if (isset($_GET['feedback'])) {
    $feedbackId = $_GET['feedback'];
    $stmt = $conn->prepare("
        SELECT t.*, u.username, u.profile_image, a.username as admin_name
        FROM transaksi t
        JOIN user u ON t.id_user = u.id_user
        LEFT JOIN user a ON t.id_admin = a.id_user
        WHERE t.id_transaksi = ?
    ");
    $stmt->execute([$feedbackId]);
    $feedback = $stmt->fetch();
    
    if ($feedback && $feedback['feedback_status'] === 'baru') {
        $conn->prepare("UPDATE transaksi SET feedback_status = 'dibaca' WHERE id_transaksi = ?")->execute([$feedbackId]);
        $feedback['feedback_status'] = 'dibaca';
    }
}
?>

<style>
    /* ... (tetap sama dengan style yang Anda berikan sebelumnya) ... */
</style>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="bi bi-star-fill me-2"></i>Manajemen Feedback</h2>
            <p class="text-muted">Kelola dan tanggapi feedback dari pengguna.</p>
        </div>
    </div>
        
    <div class="row">
        <!-- Feedback List -->
        <div class="col-md-4 session-list p-0">
            <div class="p-3 bg-light border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Daftar Feedback</h5>
                    <span class="badge bg-primary"><?= count($feedbacks) ?></span>
                </div>
            </div>
            
            <?php if (empty($feedbacks)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-star" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="mt-2">Belum ada feedback</p>
                </div>
            <?php else: ?>
                <?php foreach ($feedbacks as $item): ?>
                    <a href="?feedback=<?= $item['id_transaksi'] ?>" 
                       class="d-block p-3 border-bottom session-item <?= isset($_GET['feedback']) && $_GET['feedback'] == $item['id_transaksi'] ? 'active' : '' ?>">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?= $item['profile_image'] ?>" 
                                 class="rounded-circle me-2" width="40" alt="<?= $item['username'] ?>">
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($item['username']) ?></h6>
                                <div class="star-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= ($i <= $item['feedback_rating']) ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars($item['feedback_kategori']) ?> - <?= htmlspecialchars($item['feedback_judul']) ?>
                                </small>
                            </div>
                            <span class="ms-auto badge badge-<?= $item['feedback_status'] ?>">
                                <?= $item['feedback_status'] ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Feedback Detail -->
        <div class="col-md-8">
            <?php if (isset($_GET['feedback']) && $feedback): ?>
                <div class="p-4">
                    <div class="card feedback-details">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0"><?= htmlspecialchars($feedback['feedback_judul']) ?></h4>
                                <span class="badge badge-<?= $feedback['feedback_status'] ?>"><?= $feedback['feedback_status'] ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <div class="star-rating mb-1">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= ($i <= $feedback['feedback_rating']) ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-2 text-dark"><?= $feedback['feedback_rating'] ?>/5</span>
                                </div>
                                <span class="badge bg-primary"><?= ucfirst($feedback['feedback_kategori']) ?></span>
                            </div>
                            
                            <div class="card-text mb-3">
                                <h5>Pesan Feedback:</h5>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($feedback['feedback_pesan'])) ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($feedback['feedback_lampiran'])): ?>
                            <div class="mb-3">
                                <h5>Lampiran:</h5>
                                <div class="mt-2">
                                    <img src="uploads/<?= htmlspecialchars($feedback['feedback_lampiran']) ?>" class="img-fluid rounded" style="max-height: 200px;" alt="Lampiran Feedback">
                                    <div class="mt-2">
                                        <a href="uploads/<?= htmlspecialchars($feedback['feedback_lampiran']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-download"></i> Lihat Full Size
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-muted small mt-3">
                                <i class="bi bi-calendar-event me-1"></i> Transaksi: #<?= $feedback['id_transaksi'] ?><br>
                                <i class="bi bi-clock me-1"></i> Dikirim pada: <?= date('d M Y, H:i', strtotime($feedback['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($feedback['balasan'])): ?>
                    <!-- Balasan Admin -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-reply me-2"></i>Balasan Admin</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <?= nl2br(htmlspecialchars($feedback['balasan'])) ?>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-clock me-1"></i> Dibalas pada: <?= date('d M Y, H:i', strtotime($feedback['waktu_balasan'])) ?><br>
                                <i class="bi bi-person me-1"></i> Oleh: <?= $feedback['admin_name'] ?? 'Admin' ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($feedback['feedback_status'] != 'selesai' && empty($feedback['balasan'])): ?>
                    <!-- Form Balas Feedback -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Balas Feedback</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="feedback_id" value="<?= $feedback['id_transaksi'] ?>">
                                <div class="mb-3">
                                    <textarea name="balasan_feedback" class="form-control" rows="5" placeholder="Tulis balasan untuk feedback ini..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i> Kirim Balasan
                                    </button>
                                    <a href="?feedback=<?= $feedback['id_transaksi'] ?>&complete=true" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i> Tandai Selesai
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($feedback['feedback_status'] != 'selesai'): ?>
                    <div class="mt-4 text-end">
                        <a href="?feedback=<?= $feedback['id_transaksi'] ?>&complete=true" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i> Tandai Selesai
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="p-5 text-center">
                    <i class="bi bi-star" style="font-size: 4rem; color: #dee2e6;"></i>
                    <h3 class="mt-3">Pilih Feedback</h3>
                    <p class="text-muted">Silakan pilih feedback dari daftar di sebelah kiri untuk melihat detail dan memberikan tanggapan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!defined('INCLUDED_IN_INDEX')): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
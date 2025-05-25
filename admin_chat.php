<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['lv'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if required tables exist and create them if they don't
function createRequiredTables($conn) {
    try {
        // Check if chat_session table exists
        $result = $conn->query("SHOW TABLES LIKE 'chat_session'");
        if ($result->rowCount() === 0) {
            // Create chat_session table
            $conn->exec("
                CREATE TABLE `chat_session` (
                    `id_session` int(11) NOT NULL AUTO_INCREMENT,
                    `id_user` int(11) NOT NULL,
                    `id_admin` int(11) DEFAULT NULL,
                    `status` enum('open','diproses','selesai') NOT NULL DEFAULT 'open',
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id_session`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }
        
        // Check if chat table exists
        $result = $conn->query("SHOW TABLES LIKE 'chat'");
        if ($result->rowCount() === 0) {
            // Create chat table
            $conn->exec("
                CREATE TABLE `chat` (
                    `id_chat` int(11) NOT NULL AUTO_INCREMENT,
                    `id_session` int(11) NOT NULL,
                    `id_user` int(11) NOT NULL,
                    `pengirim` enum('user','admin') NOT NULL,
                    `pesan` text NOT NULL,
                    `status` enum('terkirim','dibaca') NOT NULL DEFAULT 'terkirim',
                    `waktu_kirim` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id_chat`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }
        
        return true;
    } catch (PDOException $e) {
        // Display error message for admin debugging
        echo "<div class='alert alert-danger'>Error creating tables: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Create tables if needed
createRequiredTables($conn);

// Ambil daftar chat yang belum direspon
try {
    $sessions = $conn->query("
        SELECT s.id_session, u.username, u.profile_image, 
               MAX(c.waktu_kirim) AS last_message,
               COUNT(c.id_chat) AS total_unread
        FROM chat_session s
        JOIN user u ON s.id_user = u.id_user
        LEFT JOIN chat c ON s.id_session = c.id_session AND c.status = 'terkirim'
        WHERE s.status = 'open'
        GROUP BY s.id_session
        ORDER BY last_message DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $sessions = []; // Empty array if error occurs
}

// Ambil daftar feedback dari tabel transaksi
try {
    $feedbacks = $conn->query("
        SELECT t.*, u.username, u.profile_image
        FROM transaksi t
        JOIN user u ON t.id_user = u.id_user
        WHERE t.kategori_feedback IS NOT NULL AND t.is_deleted = 0
        ORDER BY t.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $feedbacks = []; // Empty array if error occurs
}

// Handle pilih sesi
if (isset($_GET['session'])) {
    // Assign admin ke sesi
    $conn->prepare("
        UPDATE chat_session 
        SET id_admin = ?, status = 'diproses'
        WHERE id_session = ?
    ")->execute([$_SESSION['id_user'], $_GET['session']]);
}

// Handle kirim pesan admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle chat responses
    if (isset($_POST['id_session']) && isset($_POST['pesan'])) {
        $id_session = $_POST['id_session'];
        $pesan = $_POST['pesan'];
        
        $conn->prepare("
            INSERT INTO chat 
            (id_session, id_user, pengirim, pesan, status)
            VALUES (?, ?, 'staff', ?, 'terkirim')
        ")->execute([$id_session, $_SESSION['id_user'], $pesan]);
        
        // Update status pesan user jadi dibaca
        $conn->prepare("
            UPDATE chat 
            SET status = 'dibaca' 
            WHERE id_session = ? AND pengirim = 'user'
        ")->execute([$id_session]);
    }
    
    // Handle feedback responses
    if (isset($_POST['balasan_feedback'])) {
        $feedbackId = $_POST['feedback_id'];
        $balasan = $_POST['balasan_feedback'];
        
        $conn->prepare("
            UPDATE transaksi 
            SET status_feedback = 'diproses', 
                balasan_feedback = ?,
                waktu_balasan_feedback = NOW(),
                id_admin_feedback = ?
            WHERE id_transaksi = ?
        ")->execute([$balasan, $_SESSION['id_user'], $feedbackId]);
        
        // Redirect to refresh
        header("Location: admin_chat.php?feedback=$feedbackId&type=feedback");
        exit;
    }
}

// Tampilan admin
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat & Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        .session-list {
            max-width: 300px;
            border-right: 1px solid #ddd;
            height: 90vh;
            overflow-y: auto;
        }
        
        .session-item {
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .session-item:hover {
            background: #f8f9fa;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.8rem;
        }
        
        .feedback-badge {
            background: #198754;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.8rem;
        }
        
        .badge-baru {
            background-color: #dc3545;
        }
        
        .badge-dibaca {
            background-color: #0d6efd;
        }
        
        .badge-diproses {
            background-color: #ffc107;
            color: #000;
        }
        
        .badge-selesai {
            background-color: #198754;
        }
        
        .nav-tabs .nav-link {
            font-weight: 500;
        }
        
        .chat-container {
            height: 65vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .chat-message {
            max-width: 80%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
        }
        
        .user-message {
            background-color: #e9ecef;
            align-self: flex-start;
        }
        
        .admin-message {
            background-color: #0d6efd;
            color: white;
            align-self: flex-end;
        }
        
        .feedback-details {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .active-tab {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 session-list p-0">
                <nav>
                    <div class="nav nav-tabs w-100" id="nav-tab" role="tablist">
                        <button class="nav-link <?= !isset($_GET['type']) || $_GET['type'] == 'chat' ? 'active' : '' ?> flex-fill text-center" id="nav-chat-tab" data-bs-toggle="tab" 
                            data-bs-target="#nav-chat" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-chat-dots me-1"></i> Chat
                        </button>
                        <button class="nav-link <?= isset($_GET['type']) && $_GET['type'] == 'feedback' ? 'active' : '' ?> flex-fill text-center" id="nav-feedback-tab" data-bs-toggle="tab" 
                            data-bs-target="#nav-feedback" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-star me-1"></i> Feedback
                        </button>
                    </div>
                </nav>
                
                <div class="tab-content" id="nav-tabContent">
                    <!-- Chat Sessions List -->
                    <div class="tab-pane fade <?= !isset($_GET['type']) || $_GET['type'] == 'chat' ? 'show active' : '' ?>" id="nav-chat" role="tabpanel">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="m-0">Chat Aktif</h5>
                                <span class="badge bg-primary"><?= count($sessions) ?></span>
                            </div>
                        </div>
                        <?php if (empty($sessions)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-chat-square-text" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2">Tidak ada chat aktif</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                                <a href="?session=<?= $session['id_session'] ?>&type=chat" 
                                   class="d-block p-3 border-bottom session-item">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?= $session['profile_image'] ?>" 
                                             class="rounded-circle me-2" width="40" alt="<?= $session['username'] ?>">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($session['username']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('d M Y, H:i', strtotime($session['last_message'])) ?>
                                            </small>
                                        </div>
                                        <?php if ($session['total_unread'] > 0): ?>
                                            <span class="ms-auto unread-badge">
                                                <?= $session['total_unread'] ?> baru
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Feedback List -->
                    <div class="tab-pane fade <?= isset($_GET['type']) && $_GET['type'] == 'feedback' ? 'show active' : '' ?>" id="nav-feedback" role="tabpanel">
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="m-0">Feedback</h5>
                                <span class="badge bg-primary"><?= count($feedbacks) ?></span>
                            </div>
                        </div>
                        
                        <?php if (empty($feedbacks)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-star" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2">Belum ada feedback</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <a href="?feedback=<?= $feedback['id_transaksi'] ?>&type=feedback" 
                                   class="d-block p-3 border-bottom session-item">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?= $feedback['profile_image'] ?>" 
                                             class="rounded-circle me-2" width="40" alt="<?= $feedback['username'] ?>">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($feedback['username']) ?></h6>
                                            <div class="star-rating">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= ($i <= $feedback['rating_feedback']) ? '-fill' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted d-block">
                                                <?= htmlspecialchars($feedback['kategori_feedback']) ?> - <?= htmlspecialchars($feedback['judul_feedback']) ?>
                                            </small>
                                        </div>
                                        <span class="ms-auto badge badge-<?= $feedback['status_feedback'] ?>">
                                            <?= $feedback['status_feedback'] ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
              <!-- Area Chat & Feedback Detail -->
            <div class="col-md-9">
                <?php if (isset($_GET['session']) && $_GET['type'] == 'chat'): ?>
                    <!-- Tampilan chat mirip user, tapi dengan kemampuan admin -->
                    <?php
                    // Get session details
                    $sessionId = $_GET['session'];
                    $sessionDetail = $conn->query("
                        SELECT s.*, u.username, u.profile_image 
                        FROM chat_session s
                        JOIN user u ON s.id_user = u.id_user
                        WHERE s.id_session = $sessionId
                    ")->fetch();
                    
                    // Get chat messages
                    $messages = $conn->query("
                        SELECT c.*, u.username, u.profile_image
                        FROM chat c
                        JOIN user u ON c.id_user = u.id_user
                        WHERE c.id_session = $sessionId
                        ORDER BY c.waktu_kirim ASC
                    ")->fetchAll();
                    ?>
                    <!-- Chat Header -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?= $sessionDetail['profile_image'] ?>" class="rounded-circle me-2" width="45" alt="User Profile">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($sessionDetail['username']) ?></h5>
                                <small class="text-muted"><?= $sessionDetail['status'] ?></small>
                            </div>
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-outline-secondary me-2">
                                    <i class="bi bi-info-circle"></i> Info Pelanggan
                                </button>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle"></i> Tutup Chat
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Messages -->
                    <div class="chat-container p-3">
                        <?php foreach ($messages as $message): ?>
                            <div class="chat-message <?= $message['pengirim'] == 'user' ? 'user-message' : 'admin-message' ?>">
                                <div class="small <?= $message['pengirim'] == 'user' ? 'text-muted' : 'text-light' ?> mb-1">
                                    <?= $message['pengirim'] == 'user' ? htmlspecialchars($sessionDetail['username']) : 'Admin' ?> - 
                                    <?= date('H:i', strtotime($message['waktu_kirim'])) ?>
                                </div>
                                <div><?= nl2br(htmlspecialchars($message['pesan'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Chat Input -->
                    <div class="p-3 border-top">
                        <form method="POST" action="">
                            <input type="hidden" name="id_session" value="<?= $sessionId ?>">
                            <div class="input-group">
                                <input type="text" name="pesan" class="form-control" placeholder="Ketik pesan..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                
                <?php elseif (isset($_GET['feedback']) && $_GET['type'] == 'feedback'): ?>
                    <?php
                    // Get feedback details
                    $feedbackId = $_GET['feedback'];
                    $feedback = $conn->query("
                        SELECT t.*, u.username, u.profile_image 
                        FROM transaksi t
                        JOIN user u ON t.id_user = u.id_user
                        WHERE t.id_transaksi = $feedbackId
                    ")->fetch();
                    
                    // Update status to dibaca jika belum
                    if ($feedback['status_feedback'] == 'baru') {
                        $conn->prepare("
                            UPDATE transaksi 
                            SET status_feedback = 'dibaca'
                            WHERE id_transaksi = ?
                        ")->execute([$feedbackId]);
                        $feedback['status_feedback'] = 'dibaca';
                    }
                    
                    // Handle balasan feedback
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['balasan_feedback'])) {
                        $balasan = $_POST['balasan_feedback'];
                        $conn->prepare("
                            UPDATE transaksi 
                            SET status_feedback = 'diproses', 
                                balasan_feedback = ?,
                                waktu_balasan_feedback = NOW(),
                                id_admin_feedback = ?
                            WHERE id_transaksi = ?
                        ")->execute([$balasan, $_SESSION['id_user'], $feedbackId]);
                        
                        // Redirect to refresh
                        header("Location: admin_chat.php?feedback=$feedbackId&type=feedback");
                        exit;
                    }
                    
                    // Handle marking as complete
                    if (isset($_GET['complete']) && $_GET['complete'] == 'true') {
                        $conn->prepare("
                            UPDATE transaksi 
                            SET status_feedback = 'selesai'
                            WHERE id_transaksi = ?
                        ")->execute([$feedbackId]);
                        
                        // Redirect to refresh
                        header("Location: admin_chat.php?feedback=$feedbackId&type=feedback");
                        exit;
                    }
                    ?>
                    
                    <!-- Feedback Header -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="uploads/<?= $feedback['profile_image'] ?>" class="rounded-circle me-2" width="45" alt="User Profile">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($feedback['username']) ?></h5>
                                <small class="text-muted">Feedback #<?= $feedbackId ?></small>
                            </div>
                            <div class="ms-auto">
                                <a href="dashboard_admin.php" class="btn btn-sm btn-outline-secondary me-2">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                                </a>
                                <?php if ($feedback['status_feedback'] != 'selesai'): ?>
                                    <a href="?feedback=<?= $feedbackId ?>&type=feedback&complete=true" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Tandai Selesai
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feedback Detail -->
                    <div class="p-4">
                        <div class="card feedback-details">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0"><?= htmlspecialchars($feedback['judul_feedback']) ?></h4>
                                    <span class="badge badge-<?= $feedback['status_feedback'] ?>"><?= $feedback['status_feedback'] ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="star-rating mb-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= ($i <= $feedback['rating_feedback']) ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2 text-dark"><?= $feedback['rating_feedback'] ?>/5</span>
                                    </div>
                                    <span class="badge bg-primary"><?= ucfirst($feedback['kategori_feedback']) ?></span>
                                </div>
                                
                                <div class="card-text mb-3">
                                    <h5>Pesan Feedback:</h5>
                                    <div class="p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($feedback['pesan_feedback'])) ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($feedback['lampiran_feedback'])): ?>
                                <div class="mb-3">
                                    <h5>Lampiran:</h5>
                                    <div class="mt-2">
                                        <img src="uploads/<?= htmlspecialchars($feedback['lampiran_feedback']) ?>" class="img-fluid rounded" style="max-height: 200px;" alt="Lampiran Feedback">
                                        <div class="mt-2">
                                            <a href="uploads/<?= htmlspecialchars($feedback['lampiran_feedback']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
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
                        
                        <?php if (!empty($feedback['balasan_feedback'])): ?>
                        <!-- Balasan Admin -->
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-reply me-2"></i>Balasan Admin</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <?= nl2br(htmlspecialchars($feedback['balasan_feedback'])) ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-clock me-1"></i> Dibalas pada: <?= date('d M Y, H:i', strtotime($feedback['waktu_balasan_feedback'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                          <?php if ($feedback['status_feedback'] != 'selesai' && empty($feedback['balasan_feedback'])): ?>
                        <!-- Form Balas Feedback -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Balas Feedback</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="feedback_id" value="<?= $feedbackId ?>">
                                    <div class="mb-3">
                                        <textarea name="balasan_feedback" class="form-control" rows="5" placeholder="Tulis balasan untuk feedback ini..." required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send me-2"></i> Kirim Balasan
                                        </button>
                                        <a href="?feedback=<?= $feedbackId ?>&type=feedback&complete=true" class="btn btn-success">
                                            <i class="bi bi-check-circle me-2"></i> Tandai Selesai
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Default/Welcome Screen -->
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center p-5">
                            <i class="bi bi-chat-square-text" style="font-size: 5rem; color: #ccc;"></i>
                            <h2 class="mt-4">Selamat Datang di Panel Admin</h2>
                            <p class="lead text-muted">Pilih sesi chat atau feedback untuk mulai berkomunikasi dengan pengguna</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto scroll to bottom of chat container
            const chatContainer = document.querySelector('.chat-container');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            // Set active tab based on URL parameter
            if (window.location.search.includes('type=feedback')) {
                const feedbackTab = document.querySelector('#nav-feedback-tab');
                if (feedbackTab) {
                    feedbackTab.classList.add('active');
                    document.querySelector('#nav-chat-tab').classList.remove('active');
                    document.querySelector('#nav-feedback').classList.add('show', 'active');
                    document.querySelector('#nav-chat').classList.remove('show', 'active');
                }
            }
        });
    </script>
</body>
</html>
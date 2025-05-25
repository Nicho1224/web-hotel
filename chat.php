<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require 'config.php';

$error = '';
if (isset($_GET['error'])) {
    $error = "Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.";
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isAdmin = in_array($_SESSION['lv'], ['admin', 'pegawai']);
$currentUserId = $_SESSION['id_user'];
$selectedUserId = $_GET['user'] ?? null;
$kontakList = [];
$riwayatChat = [];
$infoTransaksi = [];

try {
    // Update last online
    $conn->prepare("UPDATE user SET last_online = NOW() WHERE id_user = ?")
         ->execute([$currentUserId]);

    if ($isAdmin) {
        // Ambil daftar tamu untuk admin dengan data transaksi terakhir
        $stmt = $conn->prepare("
            SELECT 
                u.id_user, 
                u.nama, 
                u.profile_image, 
                u.last_online, 
                u.unread_admin AS unread, 
                t.status AS status_transaksi,
                t.tgl_checkin,
                t.tgl_checkout
            FROM user u
            LEFT JOIN (
                SELECT 
                    id_user, 
                    MAX(created_at) as latest_transaksi 
                FROM transaksi 
                GROUP BY id_user
            ) latest_t ON u.id_user = latest_t.id_user
            LEFT JOIN transaksi t ON t.id_user = latest_t.id_user 
                AND t.created_at = latest_t.latest_transaksi
            WHERE u.lv = 'user'
            ORDER BY u.last_online DESC
        ");
        $stmt->execute();
        $kontakList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($selectedUserId && is_numeric($selectedUserId)) {
            // Handle chat session
            $conn->prepare("UPDATE user SET unread_admin = 0 WHERE id_user = ?")
                 ->execute([$selectedUserId]);

            // Ambil riwayat chat
            $stmt = $conn->prepare("SELECT chat_history FROM user WHERE id_user = ?");
            $stmt->execute([$selectedUserId]);
            $chatData = $stmt->fetch();
            $riwayatChat = json_decode($chatData['chat_history'] ?? '[]', true);

            // Ambil info transaksi terakhir
            $stmt = $conn->prepare("
                SELECT t.*, k.nama AS nama_kamar, k.harga, k.status AS status_kamar
                FROM transaksi t
                JOIN kamar k ON t.id_kamar = k.id_kamar
                WHERE t.id_user = ?
                ORDER BY t.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$selectedUserId]);
            $infoTransaksi = $stmt->fetch() ?: [];

            // Handle kirim pesan
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $uploadedFiles = [];
                $messageText = trim($_POST['pesan'] ?? '');
                
                // Handle multiple file uploads
                if (!empty($_FILES['lampiran']['name'][0])) {
                    if (!file_exists('uploads/chat')) {
                        mkdir('uploads/chat', 0777, true);
                    }
                    
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    // Loop through each uploaded file
                    foreach ($_FILES['lampiran']['tmp_name'] as $key => $tmp_name) {
                        $file_name = $_FILES['lampiran']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        if (in_array($file_ext, $allowed)) {
                            $new_filename = uniqid() . '.' . $file_ext;
                            if (move_uploaded_file($tmp_name, 'uploads/chat/' . $new_filename)) {
                                $uploadedFiles[] = $new_filename;
                            }
                        }
                    }
                }

                $pesan = [
                    'id' => uniqid(),
                    'from' => $currentUserId,
                    'to' => $selectedUserId,
                    'message' => $messageText,
                    'time' => date('Y-m-d H:i:s'),
                    'status' => 'terkirim',
                    'lampiran' => $uploadedFiles // Sekarang berupa array
                ];

                $riwayatChat[] = $pesan;
                $chatHistory = json_encode($riwayatChat);

                $conn->prepare("
                    UPDATE user 
                    SET chat_history = ?, unread_user = unread_user + 1 
                    WHERE id_user = ?
                ")->execute([$chatHistory, $selectedUserId]);

                header("Location: ?page=chat&user=" . $selectedUserId);
                exit();
            }
        }
    } else {
        // Handle untuk user biasa
        $conn->prepare("UPDATE user SET unread_user = 0 WHERE id_user = ?")
             ->execute([$currentUserId]);

        // Ambil riwayat chat
        $stmt = $conn->prepare("SELECT chat_history FROM user WHERE id_user = ?");
        $stmt->execute([$currentUserId]);
        $chatData = $stmt->fetch();
        $riwayatChat = json_decode($chatData['chat_history'] ?? '[]', true);

        // Handle kirim pesan
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
            $pesan = [
                'id' => uniqid(),
                'from' => $currentUserId,
                'to' => $_POST['receiver_id'],
                'message' => trim($_POST['message']), // Ubah dari 'pesan' ke 'message'
                'time' => date('Y-m-d H:i:s'),
                'status' => 'terkirim',
                'lampiran' => ''
            ];

            // Handle upload gambar
            if (!empty($_FILES['lampiran']['name'][0])) { // Tambah [0] untuk array files
                if (!file_exists('uploads/chat')) {
                    mkdir('uploads/chat', 0777, true);
                }
                
                $filename = '';
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['lampiran']['name'][0], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $filename = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['lampiran']['tmp_name'][0], 'uploads/chat/' . $filename);
                    $pesan['lampiran'] = $filename;
                } else {
                    $error = "Hanya file gambar (JPG, JPEG, PNG, GIF) yang diizinkan!";
                }
            }

            try {
                // Get both users' chat histories
                $stmt = $conn->prepare("SELECT id_user, chat_history FROM user WHERE id_user IN (?, ?)");
                $stmt->execute([$currentUserId, $_POST['receiver_id']]);
                $chatHistories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                // Update both users' chat histories
                foreach ($chatHistories as $userId => $history) {
                    $messages = json_decode($history ?? '[]', true);
                    $messages[] = $pesan;

                    $stmt = $conn->prepare("
                        UPDATE user 
                        SET 
                            chat_history = ?,
                            unread_" . ($userId == $_POST['receiver_id'] ? 'admin' : 'user') . " = unread_" . ($userId == $_POST['receiver_id'] ? 'admin' : 'user') . " + 1
                        WHERE id_user = ?
                    ");
                    $stmt->execute([json_encode($messages), $userId]);
                }

                header("Location: ?page=chat&with=" . $_POST['receiver_id']);
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

$user_id = $_SESSION['id_user'];
$level = $_SESSION['lv'];

// Get chat participants based on user level
$participants = [];
if ($level === 'admin') {
    // Admin can see users and pegawai
    $stmt = $conn->prepare("SELECT id_user, username, lv FROM user WHERE id_user != ? AND lv IN ('user', 'pegawai')");
    $stmt->execute([$user_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($level === 'pegawai') {
    // Pegawai can only see admin
    $stmt = $conn->prepare("SELECT id_user, username, lv FROM user WHERE lv = 'admin'");
    $stmt->execute();
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Regular users can only see admin
    $stmt = $conn->prepare("SELECT id_user, username, lv FROM user WHERE lv = 'admin'");
    $stmt->execute();
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $currentUserId = $_SESSION['id_user'];
    $uploadedFiles = [];
    $messageText = trim($_POST['message'] ?? '');
    $receiver_id = $_POST['receiver_id'];
    
    // Handle file uploads
    if (!empty($_FILES['lampiran']['name'][0])) {
        if (!file_exists('uploads/chat')) {
            mkdir('uploads/chat', 0777, true);
        }
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($_FILES['lampiran']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['lampiran']['name'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed)) {
                $new_filename = uniqid() . '.' . $file_ext;
                if (move_uploaded_file($tmp_name, 'uploads/chat/' . $new_filename)) {
                    $uploadedFiles[] = $new_filename;
                }
            }
        }
    }

    $new_message = [
        'id' => uniqid(),
        'from' => $currentUserId,
        'to' => $receiver_id,
        'message' => $messageText,
        'time' => date('Y-m-d H:i:s'),
        'status' => 'terkirim',
        'lampiran' => $uploadedFiles
    ];

    try {
        // Get both users' chat histories
        $stmt = $conn->prepare("SELECT id_user, chat_history FROM user WHERE id_user IN (?, ?)");
        $stmt->execute([$currentUserId, $receiver_id]);
        $chatHistories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Update both users' chat histories
        foreach ($chatHistories as $userId => $history) {
            $messages = json_decode($history ?? '[]', true);
            $messages[] = $new_message;

            $stmt = $conn->prepare("
                UPDATE user 
                SET chat_history = ?,
                    unread_" . ($userId == $receiver_id ? 'user' : 'admin') . " = unread_" . ($userId == $receiver_id ? 'user' : 'admin') . " + 1
                WHERE id_user = ?
            ");
            $stmt->execute([json_encode($messages), $userId]);
        }

        header("Location: index.php?page=chat&with=" . $receiver_id);
        exit;
    } catch (Exception $e) {
        error_log("Chat Error: " . $e->getMessage());
        header("Location: index.php?page=chat&with=" . $receiver_id . "&error=1");
        exit;
    }
}

// Add this after other POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'clear_chat':
                try {
                    $receiver_id = $_POST['receiver_id'];
                    
                    // Get chat histories from both users
                    $stmt = $conn->prepare("
                        SELECT id_user, chat_history 
                        FROM user 
                        WHERE id_user IN (?, ?)
                    ");
                    $stmt->execute([$currentUserId, $receiver_id]);
                    $histories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    // Update chat histories for both users
                    foreach ($histories as $userId => $history) {
                        $messages = json_decode($history ?? '[]', true);
                        // Remove all messages between these two users
                        $filtered_messages = array_filter($messages, function($msg) use ($currentUserId, $receiver_id) {
                            return !($msg['from'] == $currentUserId && $msg['to'] == $receiver_id) &&
                                   !($msg['from'] == $receiver_id && $msg['to'] == $currentUserId);
                        });
                        
                        // Update database with filtered messages
                        $stmt = $conn->prepare("UPDATE user SET chat_history = ? WHERE id_user = ?");
                        $stmt->execute([json_encode(array_values($filtered_messages)), $userId]);
                    }

                    header("Location: ?page=chat&with=" . $receiver_id);
                    exit;
                } catch (Exception $e) {
                    error_log("Clear chat error: " . $e->getMessage());
                    $error = $e->getMessage();
                }
                break;

            case 'delete_messages':
                if (!empty($_POST['message_ids'])) {
                    $messageIds = json_decode($_POST['message_ids'], true);
                    $receiver_id = $_POST['receiver_id'];
                    
                    try {
                        // Get chat histories from both users
                        $stmt = $conn->prepare("
                            SELECT id_user, chat_history 
                            FROM user 
                            WHERE id_user IN (?, ?)
                        ");
                        $stmt->execute([$currentUserId, $receiver_id]);
                        $histories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                        // Update chat histories for both users
                        foreach ($histories as $userId => $history) {
                            $messages = json_decode($history ?? '[]', true);
                            // Filter out deleted messages
                            $filtered_messages = array_filter($messages, function($msg) use ($messageIds) {
                                return !in_array($msg['id'], $messageIds);
                            });
                            
                            // Update database
                            $stmt = $conn->prepare("UPDATE user SET chat_history = ? WHERE id_user = ?");
                            $stmt->execute([json_encode(array_values($filtered_messages)), $userId]);
                        }

                        header("Location: ?page=chat&with=" . $receiver_id);
                        exit;
                    } catch (Exception $e) {
                        error_log("Delete messages error: " . $e->getMessage());
                        $error = $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Reset unread counter when opening chat page
if ($level === 'admin') {
    // For admin, reset all unread_admin counters
    $stmt = $conn->prepare("UPDATE user SET unread_admin = 0 WHERE unread_admin > 0");
    $stmt->execute();
} else {
    // For users, only reset their own unread_user counter
    $stmt = $conn->prepare("UPDATE user SET unread_user = 0 WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
}

// Get selected chat and messages
$selected_user = null;
$chat_messages = [];
if (isset($_GET['with'])) {
    $stmt = $conn->prepare("SELECT id_user, username, lv FROM user WHERE id_user = ?");
    $stmt->execute([$_GET['with']]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_user) {
        // Get chat history
        $stmt = $conn->prepare("SELECT chat_history FROM user WHERE id_user = ?");
        $stmt->execute([$user_id]);
        $chat_history = json_decode($stmt->fetchColumn() ?? '[]', true);
        
        // Filter messages for this conversation
        $chat_messages = array_filter($chat_history, function($msg) use ($selected_user, $user_id) {
            return ($msg['from'] == $user_id && $msg['to'] == $selected_user['id_user']) ||
                   ($msg['from'] == $selected_user['id_user'] && $msg['to'] == $user_id);
        });
        
        // Reset unread counter when opening chat
        if ($level === 'admin') {
            // If admin, reset admin's unread counter for this user
            $stmt = $conn->prepare("UPDATE user SET unread_admin = 0 WHERE id_user = ?");
            $stmt->execute([$selected_user['id_user']]);
        } else {
            // If user, reset user's unread counter
            $stmt = $conn->prepare("UPDATE user SET unread_user = 0 WHERE id_user = ?");
            $stmt->execute([$user_id]);
        }
    }
}
?>

<!-- Chat Interface -->
<div class="container-fluid py-4">
    <div class="row">
        <!-- Chat List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-chat-dots"></i> 
                        Daftar Chat
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($participants as $p): 
                        // Get unread count - modified query
                        $stmt = $conn->prepare("
                            SELECT CASE 
                                WHEN ? = 'admin' THEN unread_admin 
                                ELSE unread_user 
                            END as unread_count
                            FROM user 
                            WHERE id_user = ?
                        ");
                        $stmt->execute([$level, ($level === 'admin' ? $p['id_user'] : $user_id)]);
                        $unread = $stmt->fetchColumn();
                    ?>
                        <a href="?page=chat&with=<?= $p['id_user'] ?>" 
                           class="list-group-item list-group-item-action <?= isset($_GET['with']) && $_GET['with'] == $p['id_user'] ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-person-circle me-2"></i>
                                    <?= htmlspecialchars($p['username']) ?>
                                    <small class="text-muted">(<?= ucfirst($p['lv']) ?>)</small>
                                </div>
                                <?php if ($unread > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?= $unread ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="col-md-8">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($selected_user): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-chat"></i>
                                Chat dengan <?= htmlspecialchars($selected_user['username']) ?>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-white" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#clearChatModal">
                                            <i class="bi bi-trash"></i> Clear Chat
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="toggleDeleteMode">
                                            <i class="bi bi-check-square"></i> Hapus Pesan Terpilih
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Updated chat message display section -->
                    <div class="card-body chat-messages" style="height: 400px; overflow-y: auto;">
                        <?php if (!empty($chat_messages)): ?>
                            <?php foreach ($chat_messages as $msg): ?>
                                <?php
                                $isSender = ($msg['from'] ?? $msg['pengirim']) == $user_id;
                                $messageText = $msg['message'] ?? $msg['pesan'] ?? '';
                                $messageTime = $msg['time'] ?? $msg['waktu'] ?? date('Y-m-d H:i:s');
                                ?>
                                <div class="chat-message <?= $isSender ? 'sent' : 'received' ?>">
                                    <div class="message-content">
                                        <div class="message-checkbox d-none">
                                            <input type="checkbox" class="message-select" data-message-id="<?= htmlspecialchars($msg['id']) ?>">
                                        </div>
                                        <?php if (!empty($msg['lampiran'])): ?>
                                            <div class="mb-2 d-flex gap-2 flex-wrap">
                                                <?php 
                                                $attachments = is_array($msg['lampiran']) ? $msg['lampiran'] : [$msg['lampiran']];
                                                foreach ($attachments as $image): 
                                                ?>
                                                    <a href="uploads/chat/<?= htmlspecialchars($image) ?>" target="_blank">
                                                        <img src="uploads/chat/<?= htmlspecialchars($image) ?>" 
                                                             alt="Image" class="chat-image">
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($messageText)): ?>
                                            <div class="message-text">
                                                <?= htmlspecialchars($messageText) ?>
                                            </div>
                                        <?php endif; ?>
                                        <small class="message-time">
                                            <?= date('H:i', strtotime($messageTime)) ?>
                                            <?php 
                                            // Add read status indicator
                                            if ($isSender): 
                                                $isRead = false;
                                                if ($level === 'user' && $msg['status'] === 'read_by_admin') {
                                                    $isRead = true;
                                                } elseif ($level === 'admin' && $msg['status'] === 'read_by_user') {
                                                    $isRead = true;
                                                }
                                            ?>
                                                <i class="bi bi-check2-all <?= $isRead ? 'text-primary' : 'text-light' ?>"></i>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info m-3">Belum ada pesan.</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <form action="chat-process.php" method="post" enctype="multipart/form-data" class="chat-form">
                            <input type="hidden" name="receiver_id" value="<?= $selected_user['id_user'] ?>">
                            <div id="image-previews" class="d-flex gap-2 flex-wrap mb-2"></div>
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Ketik pesan...">
                                <label class="btn btn-outline-secondary position-relative">
                                    <i class="bi bi-image"></i>
                                    <input type="file" name="lampiran[]" multiple accept="image/*" class="d-none" id="image-input">
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
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Pilih pengguna untuk memulai chat
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Clear Chat Modal -->
<div class="modal fade" id="clearChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus semua pesan dalam chat ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="chat-process.php" method="post" class="d-inline">
                    <input type="hidden" name="action" value="clear_chat">
                    <input type="hidden" name="receiver_id" value="<?= $selected_user['id_user'] ?>">
                    <button type="submit" class="btn btn-danger">Hapus Semua</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.chat-message {
    margin: 10px 0;
    display: flex;
}

.chat-message.sent {
    justify-content: flex-end;
}

.message-content {
    max-width: 70%;
    padding: 8px 12px;
    border-radius: 15px;
    position: relative;
}

.chat-message.sent .message-content {
    background-color: #007bff;
    color: white;
    border-bottom-right-radius: 5px;
}

.chat-message.received .message-content {
    background-color: #e9ecef;
    color: black;
    border-bottom-left-radius: 5px;
}

.message-time {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 4px;
}

.chat-message.sent .message-time {
    text-align: right;
}

.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
}

.chat-image {
    max-width: 150px;
    max-height: 150px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s;
    object-fit: cover;
}

.chat-message .message-content {
    max-width: 80%;
}

.chat-message .d-flex.gap-2 {
    flex-wrap: wrap;
}

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
}

.preview-container img {
    max-width: 100px;
    max-height: 100px;
    border-radius: 8px;
}

.remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    text-align: center;
    line-height: 20px;
    cursor: pointer;
    font-size: 12px;
}

.message-checkbox {
    position: absolute;
    right: -30px;
    top: 50%;
    transform: translateY(-50%);
}

.chat-message.sent .message-checkbox {
    left: -30px;
    right: auto;
}

.message-content {
    position: relative;
}

/* Light theme (default) */
:root {
    --text-color: #212529;
    --bg-color: #ffffff;
    --chat-sent-bg: #007bff;
    --chat-received-bg: #e9ecef;
    --chat-sent-text: #ffffff;
    --chat-received-text: #212529;
    --card-bg: #ffffff;
    --card-border: #dee2e6;
    --input-bg: #ffffff;
    --input-border: #ced4da;
    --dropdown-bg: #ffffff;
    --dropdown-border: rgba(0,0,0,.15);
    --dropdown-link-hover: #f8f9fa;
}

/* Dark theme */
[data-theme="dark"] {
    --text-color: #e2e8f0;
    --bg-color: #0f172a;
    --chat-sent-bg: #3b82f6;
    --chat-received-bg: #1e293b;
    --chat-sent-text: #ffffff;
    --chat-received-text: #e2e8f0;
    --card-bg: #1e293b;
    --card-border: #334155;
    --input-bg: #1e293b;
    --input-border: #334155;
    --dropdown-bg: #1e293b;
    --dropdown-border: #334155;
    --dropdown-link-hover: #2d3748;
}

/* Apply theme colors */
body {
    background-color: var(--bg-color);
    color: var(--text-color);
}

.card {
    background-color: var(--card-bg);
    border-color: var(--card-border);
}

.card-header:not(.bg-primary) {
    background-color: var(--card-bg);
    border-color: var(--card-border);
}

.list-group-item {
    background-color: var(--card-bg);
    border-color: var(--card-border);
    color: var(--text-color);
}

.list-group-item:hover {
    background-color: var(--dropdown-link-hover);
}

.list-group-item.active {
    background-color: var(--chat-sent-bg);
    border-color: var(--chat-sent-bg);
}

.chat-message.sent .message-content {
    background-color: var(--chat-sent-bg);
    color: var(--chat-sent-text);
}

.chat-message.received .message-content {
    background-color: var(--chat-received-bg);
    color: var(--chat-received-text);
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

.dropdown-menu {
    background-color: var(--dropdown-bg);
    border-color: var(--dropdown-border);
}

.dropdown-item {
    color: var(--text-color);
}

.dropdown-item:hover {
    background-color: var(--dropdown-link-hover);
    color: var(--text-color);
}

.text-muted {
    color: var(--text-muted) !important;
}

.alert-info {
    background-color: var(--chat-received-bg);
    border-color: var(--card-border);
    color: var(--text-color);
}

/* Fix modal in dark mode */
.modal-content {
    background-color: var(--card-bg);
    border-color: var(--card-border);
    color: var(--text-color);
}

.modal-header {
    border-color: var(--card-border);
}

.modal-footer {
    border-color: var(--card-border);
}

/* Keep checkmarks visible */
.chat-message.sent .message-time i.bi-check2-all {
    color: #ffffff !important;
    opacity: 0.8;
}

.chat-message.sent .message-time i.bi-check2-all.text-primary {
    color: #3b82f6 !important;
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('image-input');
    const imagePreviews = document.getElementById('image-previews');
    const imageCount = document.getElementById('image-count');
    let selectedFiles = [];

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
                        <img src="${e.target.result}" alt="Preview">
                        <div class="remove-image">&times;</div>
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

    // Scroll to bottom on load
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleDeleteMode = document.getElementById('toggleDeleteMode');
    const chatForm = document.querySelector('.chat-form');
    let deleteMode = false;

    if (toggleDeleteMode) {
        toggleDeleteMode.addEventListener('click', function() {
            deleteMode = !deleteMode;
            document.querySelectorAll('.message-checkbox').forEach(checkbox => {
                checkbox.classList.toggle('d-none');
            });

            if (deleteMode) {
                // Show delete selected button
                const deleteButton = document.createElement('button');
                deleteButton.id = 'deleteSelected';
                deleteButton.className = 'btn btn-danger position-fixed bottom-0 end-0 m-3';
                deleteButton.innerHTML = '<i class="bi bi-trash"></i> Hapus Terpilih';
                document.body.appendChild(deleteButton);

                deleteButton.addEventListener('click', function() {
                    const selectedMessages = Array.from(document.querySelectorAll('.message-select:checked'))
                        .map(cb => cb.dataset.messageId);
                    
                    if (selectedMessages.length > 0) {
                        if (confirm('Hapus ' + selectedMessages.length + ' pesan terpilih?')) {
                            // Buat form untuk submit
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.style.display = 'none';
                            form.action = 'chat-process.php';  // Add this line in the toggleDeleteMode click handler
                            
                            // Tambahkan input fields yang diperlukan
                            form.innerHTML = `
                                <input type="hidden" name="action" value="delete_messages">
                                <input type="hidden" name="receiver_id" value="${chatForm.querySelector('[name="receiver_id"]').value}">
                                <input type="hidden" name="message_ids" value='${JSON.stringify(selectedMessages)}'>
                            `;

                            // Tambahkan form ke document dan submit
                            document.body.appendChild(form);
                            form.submit();
                        }
                    } else {
                        alert('Pilih pesan yang akan dihapus terlebih dahulu');
                    }
                });
            } else {
                // Remove delete selected button
                const deleteButton = document.getElementById('deleteSelected');
                if (deleteButton) {
                    deleteButton.remove();
                }
            }

            this.innerHTML = deleteMode ? 
                '<i class="bi bi-x-lg"></i> Batal Hapus' : 
                '<i class="bi bi-check-square"></i> Hapus Pesan Terpilih';
        });
    }
});
</script>
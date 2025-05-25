<?php
require 'config.php';

if (!isset($_SESSION['id_user'], $_SESSION['lv']) || $_SESSION['lv'] !== 'user') {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// Cek session user
$stmt = $conn->prepare("SELECT * FROM chat_session WHERE id_user = ? AND status = 'open'");
$stmt->execute([$id_user]);
$session = $stmt->fetch();

if (!$session) {
    $stmt = $conn->prepare("INSERT INTO chat_session (id_user, status, last_activity) VALUES (?, 'open', NOW())");
    $stmt->execute([$id_user]);
    $session_id = $conn->lastInsertId();
} else {
    $session_id = $session['id_session'];
}

// Handle kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $pesan = trim($_POST['pesan']);
    if ($pesan !== '') {
        $stmt = $conn->prepare("INSERT INTO chat (id_session, id_user, pengirim, pesan) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$session_id, $id_user, $pesan]);
        $conn->prepare("UPDATE chat_session SET last_activity = NOW() WHERE id_session = ?")->execute([$session_id]);
    }
    exit;
}

// Handle ambil pesan
if (($_GET['action'] ?? '') === 'fetch') {
    $conn->prepare("UPDATE chat SET dibaca = '1' WHERE id_session = ? AND pengirim = 'staff'")->execute([$session_id]);
    $stmt = $conn->prepare(
        "SELECT c.pengirim, c.pesan, c.waktu_kirim, c.dibaca 
         FROM chat c 
         WHERE c.id_session = ? 
         ORDER BY c.waktu_kirim ASC"
    );
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll();
    header('Content-Type: application/json');
    echo json_encode($messages);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Chat User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .chat-container { max-width: 600px; margin: 20px auto; border: 1px solid #ddd; height: 80vh; display: flex; flex-direction: column; }
    #messages { flex-grow: 1; overflow-y: auto; padding: 20px; background: #f5f5f5; }
    .message { margin-bottom: 10px; padding: 10px 15px; border-radius: 15px; max-width: 70%; }
    .user { background: #d1e7ff; align-self: flex-end; }
    .staff { background: #e2e2e2; align-self: flex-start; }
    .meta { font-size: 0.7rem; color: #555; margin-top: 5px; text-align: right; }
    form { display: flex; padding: 10px; border-top: 1px solid #ccc; }
    input { flex: 1; margin-right: 10px; }
  </style>
</head>
<body>
<div class="chat-container">
  <div id="messages"></div>
  <form id="chat-form">
    <input type="text" id="pesan" class="form-control" placeholder="Ketik pesan..." autocomplete="off">
    <button class="btn btn-primary">Kirim</button>
  </form>
</div>

<script>
function loadMessages() {
    $.get('?action=fetch', function(data) {
        const container = $('#messages').empty();
        data.forEach(msg => {
            const cls = msg.pengirim === 'user' ? 'user message' : 'staff message';
            const centang = msg.dibaca === '1' ? '✔✔' : '✔';
            container.append(`<div class="${cls}">
                <div>${msg.pesan}</div>
                <div class="meta">${msg.waktu_kirim} ${msg.pengirim === 'user' ? centang : ''}</div>
            </div>`);
        });
        container.scrollTop(container[0].scrollHeight);
    }, 'json');
}

$(function() {
    loadMessages();
    $('#chat-form').submit(function(e) {
        e.preventDefault();
        const pesan = $('#pesan').val().trim();
        if (pesan) {
            $.post('?action=send', { pesan }, function() {
                $('#pesan').val('');
                loadMessages();
            });
        }
    });
    setInterval(loadMessages, 3000);
});
</script>
</body>
</html>

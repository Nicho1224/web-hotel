<?php
require 'config.php';

if (!isset($_SESSION['id_user'], $_SESSION['lv']) || $_SESSION['lv'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Load semua sesi
$stmt = $conn->query(
    "SELECT cs.id_session, cs.last_activity, u.nama 
     FROM chat_session cs 
     JOIN user u ON cs.id_user = u.id_user 
     ORDER BY cs.last_activity DESC"
);
$sessions = $stmt->fetchAll();

// Handle kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $session_id = intval($_POST['session_id']);
    $pesan = trim($_POST['pesan']);
    if ($pesan !== '') {
        $stmt = $conn->prepare("INSERT INTO chat (id_session, id_user, pengirim, pesan) VALUES (?, ?, 'staff', ?)");
        $stmt->execute([$session_id, $_SESSION['id_user'], $pesan]);
        $conn->prepare("UPDATE chat_session SET last_activity = NOW() WHERE id_session = ?")->execute([$session_id]);
    }
    exit;
}

// Handle ambil pesan
if (($_GET['action'] ?? '') === 'fetch' && isset($_GET['session_id'])) {
    $session_id = intval($_GET['session_id']);
    $conn->prepare("UPDATE chat SET dibaca = '1' WHERE id_session = ? AND pengirim = 'user'")->execute([$session_id]);
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
  <title>Chat Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .chat-container { display: flex; height: 90vh; }
    .sidebar { width: 250px; border-right: 1px solid #ddd; overflow-y: auto; }
    .chat-box { flex: 1; display: flex; flex-direction: column; }
    #messages { flex-grow: 1; overflow-y: auto; padding: 20px; background: #f5f5f5; }
    .message { margin-bottom: 10px; padding: 10px 15px; border-radius: 15px; max-width: 70%; }
    .user { background: #e2e2e2; align-self: flex-start; }
    .staff { background: #d1e7ff; align-self: flex-end; }
    .meta { font-size: 0.7rem; color: #555; margin-top: 5px; text-align: right; }
    form { display: flex; padding: 10px; border-top: 1px solid #ccc; }
    input { flex: 1; margin-right: 10px; }
  </style>
</head>
<body>
<div class="chat-container">
  <div class="sidebar">
    <h5 class="p-2">Sessions</h5>
    <div id="session-list">
      <?php foreach ($sessions as $s): ?>
        <div class="list-group-item session-item" data-session-id="<?= $s['id_session'] ?>">
          <?= htmlspecialchars($s['nama']) ?><br>
          <small><?= htmlspecialchars($s['last_activity']) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="chat-box">
    <div id="messages"></div>
    <form id="chat-form">
      <input type="text" id="pesan" class="form-control" placeholder="Ketik pesan..." autocomplete="off">
      <button class="btn btn-primary">Kirim</button>
    </form>
  </div>
</div>

<script>
let currentSession = null;
function loadMessages() {
  if (!currentSession) return;
  $.get('?action=fetch&session_id=' + currentSession, function(data) {
    const container = $('#messages').empty();
    data.forEach(msg => {
      const cls = msg.pengirim === 'user' ? 'user message' : 'staff message';
      const centang = msg.dibaca === '1' ? '✔✔' : '✔';
      container.append(`<div class="${cls}">
        <div>${msg.pesan}</div>
        <div class="meta">${msg.waktu_kirim} ${msg.pengirim === 'staff' ? centang : ''}</div>
      </div>`);
    });
    container.scrollTop(container[0].scrollHeight);
  }, 'json');
}

$(function() {
  $('.session-item').click(function() {
    $('.session-item').removeClass('active');
    $(this).addClass('active');
    currentSession = $(this).data('session-id');
    loadMessages();
  });

  $('#chat-form').submit(function(e) {
    e.preventDefault();
    const pesan = $('#pesan').val().trim();
    if (pesan && currentSession) {
      $.post('', { action: 'send', session_id: currentSession, pesan }, function() {
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

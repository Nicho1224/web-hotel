<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_session = $_POST['id_session'] ?? null;
    $pengirim = $_POST['pengirim'] ?? 'user';
    $pesan = $_POST['pesan'] ?? '';
    $id_user = $_SESSION['id_user'];

    try {
        // Jika session belum ada (untuk user biasa)
        if (!$id_session && $pengirim === 'user') {
            $stmt = $conn->prepare("
                INSERT INTO chat_session (id_user, status) 
                VALUES (?, 'open')
            ");
            $stmt->execute([$id_user]);
            $id_session = $conn->lastInsertId();
        }

        // Simpan pesan ke database
        $stmt = $conn->prepare("
            INSERT INTO chat (id_session, id_user, pengirim, pesan, status) 
            VALUES (?, ?, ?, ?, 'terkirim')
        ");
        $stmt->execute([$id_session, $id_user, $pengirim, $pesan]);

        // Redirect kembali ke halaman chat
        header("Location: chat.php" . ($pengirim === 'staff' ? "?user=$id_session" : ''));
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
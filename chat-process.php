<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    } else {
        // Redirect for regular requests
        header("Location: login.php");
        exit();
    }
}

$currentUserId = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle sending messages (both AJAX and regular form submissions)
    if (isset($_POST['send_message']) || (!isset($_POST['action']) && isset($_POST['receiver_id']))) {
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

        $message_id = uniqid();
        $current_time = date('Y-m-d H:i:s');
        
        $new_message = [
            'id' => $message_id,
            'from' => $currentUserId,
            'to' => $receiver_id,
            'message' => $messageText,
            'time' => $current_time,
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

            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => $new_message
                ]);
                exit;
            } else {
                // Redirect for regular form submissions
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referer, 'landing_page.php') !== false) {
                    header("Location: landing_page.php");
                } else {
                    header("Location: index.php?page=chat&with=" . $receiver_id);
                }
                exit;
            }
        } catch (Exception $e) {
            error_log("Chat Error: " . $e->getMessage());
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
                exit;
            } else {
                // Redirect for regular form submissions
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referer, 'landing_page.php') !== false) {
                    header("Location: landing_page.php?error=1");
                } else {
                    header("Location: index.php?page=chat&with=" . $receiver_id . "&error=1");
                }
                exit;
            }
        }
    }

    // Handle clear chat
    if (isset($_POST['action']) && $_POST['action'] === 'clear_chat') {
        try {
            $receiver_id = $_POST['receiver_id'];
            
            $stmt = $conn->prepare("
                SELECT id_user, chat_history 
                FROM user 
                WHERE id_user IN (?, ?)
            ");
            $stmt->execute([$currentUserId, $receiver_id]);
            $histories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($histories as $userId => $history) {
                $messages = json_decode($history ?? '[]', true);
                $filtered_messages = array_filter($messages, function($msg) use ($currentUserId, $receiver_id) {
                    return !($msg['from'] == $currentUserId && $msg['to'] == $receiver_id) &&
                           !($msg['from'] == $receiver_id && $msg['to'] == $currentUserId);
                });
                
                // Update database with filtered messages
                $stmt = $conn->prepare("UPDATE user SET 
                    chat_history = ?,
                    unread_admin = 0,
                    unread_user = 0 
                    WHERE id_user = ?");
                $stmt->execute([json_encode(array_values($filtered_messages)), $userId]);
            }

            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Chat cleared successfully']);
                exit;
            } else {
                // Redirect for regular form submissions
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referer, 'landing_page.php') !== false) {
                    header("Location: landing_page.php");
                } else {
                    header("Location: index.php?page=chat&with=" . $receiver_id);
                }
                exit;
            }
        } catch (Exception $e) {
            error_log("Clear chat error: " . $e->getMessage());
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to clear chat']);
                exit;
            } else {
                // Redirect for regular form submissions
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referer, 'landing_page.php') !== false) {
                    header("Location: landing_page.php?error=1");
                } else {
                    header("Location: index.php?page=chat&with=" . $receiver_id . "&error=1");
                }
                exit;
            }
        }
    }

    // Handle delete selected messages
    if (isset($_POST['action']) && $_POST['action'] === 'delete_messages') {
        if (!empty($_POST['message_ids'])) {
            $messageIds = json_decode($_POST['message_ids'], true);
            $receiver_id = $_POST['receiver_id'];
            
            try {
                $stmt = $conn->prepare("
                    SELECT id_user, chat_history 
                    FROM user 
                    WHERE id_user IN (?, ?)
                ");
                $stmt->execute([$currentUserId, $receiver_id]);
                $histories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                foreach ($histories as $userId => $history) {
                    $messages = json_decode($history ?? '[]', true);
                    $filtered_messages = array_filter($messages, function($msg) use ($messageIds) {
                        return !in_array($msg['id'], $messageIds);
                    });
                    
                    // Update database with reset counters
                    $stmt = $conn->prepare("UPDATE user SET 
                        chat_history = ?,
                        unread_admin = 0, 
                        unread_user = 0
                        WHERE id_user = ?");
                    $stmt->execute([json_encode(array_values($filtered_messages)), $userId]);
                }

                // Check if this is an AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // Return JSON response for AJAX requests
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Messages deleted successfully']);
                    exit;
                } else {
                    // Redirect for regular form submissions
                    $referer = $_SERVER['HTTP_REFERER'] ?? '';
                    if (strpos($referer, 'landing_page.php') !== false) {
                        header("Location: landing_page.php");
                    } else {
                        header("Location: index.php?page=chat&with=" . $receiver_id);
                    }
                    exit;
                }
            } catch (Exception $e) {
                error_log("Delete messages error: " . $e->getMessage());
                
                // Check if this is an AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // Return JSON response for AJAX requests
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to delete messages']);
                    exit;
                } else {
                    // Redirect for regular form submissions
                    $referer = $_SERVER['HTTP_REFERER'] ?? '';
                    if (strpos($referer, 'landing_page.php') !== false) {
                        header("Location: landing_page.php?error=1");
                    } else {
                        header("Location: index.php?page=chat&with=" . $receiver_id . "&error=1");
                    }
                    exit;
                }
            }
        }
    }
}
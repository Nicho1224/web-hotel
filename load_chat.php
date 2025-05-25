<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

// Make sure the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$currentUserId = $_SESSION['id_user'];
$response = ['success' => false, 'messages' => []];

// Get the receiver ID from query string
if (isset($_GET['receiver_id'])) {
    $receiver_id = $_GET['receiver_id'];
    
    try {
        // Get current user's chat history
        $stmt = $conn->prepare("SELECT chat_history FROM user WHERE id_user = ?");
        $stmt->execute([$currentUserId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['chat_history'])) {
            $messages = json_decode($result['chat_history'], true);
            
            // Filter messages between current user and receiver
            $filteredMessages = array_filter($messages, function($msg) use ($currentUserId, $receiver_id) {
                return ($msg['from'] == $currentUserId && $msg['to'] == $receiver_id) || 
                       ($msg['from'] == $receiver_id && $msg['to'] == $currentUserId);
            });
            
            // Sort messages by time
            usort($filteredMessages, function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
            
            // Update message status to read if from receiver
            $messagesToUpdate = [];
            foreach ($filteredMessages as &$msg) {
                if ($msg['from'] == $receiver_id && $msg['to'] == $currentUserId && $msg['status'] != 'read_by_user') {
                    $msg['status'] = 'read_by_user';
                    $messagesToUpdate[] = $msg;
                }
            }
            
            // Update chat history in database if there are messages to update
            if (!empty($messagesToUpdate)) {
                // Get both users' chat histories to update the messages
                $stmt = $conn->prepare("SELECT id_user, chat_history FROM user WHERE id_user IN (?, ?)");
                $stmt->execute([$currentUserId, $receiver_id]);
                $chatHistories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                foreach ($chatHistories as $userId => $history) {
                    $allMessages = json_decode($history ?? '[]', true);
                    
                    // Update message statuses
                    foreach ($allMessages as &$msg) {
                        foreach ($messagesToUpdate as $updatedMsg) {
                            if ($msg['id'] === $updatedMsg['id']) {
                                $msg['status'] = $updatedMsg['status'];
                            }
                        }
                    }
                    
                    // Reset unread counter for current user
                    $unreadField = $userId == $currentUserId ? 'unread_user' : 'unread_admin';
                    $stmt = $conn->prepare("UPDATE user SET chat_history = ?, $unreadField = 0 WHERE id_user = ?");
                    $stmt->execute([json_encode($allMessages), $userId]);
                }
            }
            
            $response['success'] = true;
            $response['messages'] = array_values($filteredMessages);
        } else {
            $response['success'] = true;
            $response['messages'] = [];
        }
    } catch (Exception $e) {
        error_log("Load chat error: " . $e->getMessage());
        $response['message'] = 'Error loading messages';
    }
} else {
    $response['message'] = 'No receiver ID provided';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

<?php
// Define that we're including this from another file
define('INCLUDED_CONFIG', true);

// Include the configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = ['success' => false, 'message' => ''];

// Check if this is a POST request with feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        // Get form data
        $kategori = $_POST['kategori'] ?? '';
        $judul = $_POST['judul'] ?? '';
        $pesan = $_POST['pesan'] ?? '';
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : NULL;
        
        // Check login status
        $isLoggedIn = (isset($_SESSION['id_user']) && !empty($_SESSION['id_user']));
        $userId = $isLoggedIn ? $_SESSION['id_user'] : null; // Set to null if not logged in
        
        if (!$isLoggedIn) {
            // For guest users, we need to create a temporary user account or direct them to login
            $response['success'] = false;
            $response['message'] = "Please log in to submit feedback.";
            $response['redirect'] = "login.php";
        } else {
            // Process file upload if present
            $lampiran = NULL;
            if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['lampiran']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
                    $lampiran = $target_file;
                }
            }
            
            // Update the user table with feedback information
            $updateStmt = $conn->prepare("UPDATE user SET 
                    kategori_feedback = ?, 
                    judul_feedback = ?, 
                    pesan_feedback = ?, 
                    rating_feedback = ?, 
                    lampiran_feedback = ?, 
                    status_feedback = 'baru', 
                    tgl_submit_feedback = NOW() 
                    WHERE id_user = ?");
                    
            $result = $updateStmt->execute([
                $kategori,
                $judul,
                $pesan,
                $rating,
                $lampiran,
                $userId
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = "Thank you for your feedback! We appreciate your input.";
            } else {
                $errorInfo = $updateStmt->errorInfo();
                $response['message'] = "Failed to send feedback: " . ($errorInfo[2] ?? 'Unknown error');
            }
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
} else {
    $response['message'] = "Invalid request method";
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>

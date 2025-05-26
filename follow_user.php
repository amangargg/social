<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a log file for debugging
file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login_user'])) {
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Not logged in\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['user_id'])) {
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Invalid request\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Database connection with error handling
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get current user ID
    $current_user = $_SESSION['login_user'];
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Current user: $current_user\n", FILE_APPEND);
    
    $user_query = "SELECT id FROM users WHERE username = ?";
    $user_stmt = $conn->prepare($user_query);
    
    if (!$user_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_stmt->bind_param("s", $current_user);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows == 0) {
        throw new Exception("Current user not found in database");
    }
    
    $user_data = $user_result->fetch_assoc();
    $follower_id = $user_data['id'];
    
    $followed_id = intval($_POST['user_id']);
    
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Follower ID: $follower_id, Followed ID: $followed_id\n", FILE_APPEND);
    
    // Check if already following
    $check_query = "SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $follower_id, $followed_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    $is_following = $check_result->num_rows > 0;
    
    if ($is_following) {
        // Unfollow
        $delete_query = "DELETE FROM follows WHERE follower_id = ? AND followed_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $follower_id, $followed_id);
        $delete_stmt->execute();
        
        $following = false;
    } else {
        // Follow
        $insert_query = "INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $follower_id, $followed_id);
        $insert_stmt->execute();
        
        $following = true;
    }
    
    // Get updated follower count
    $count_query = "SELECT COUNT(*) as count FROM follows WHERE followed_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $followed_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $follower_count = $count_data['count'];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'following' => $following,
        'count' => $follower_count
    ]);
    
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Success: following=" . ($following ? "true" : "false") . ", count=$follower_count\n", FILE_APPEND);
    
    $conn->close();
    
} catch (Exception $e) {
    file_put_contents('follow_debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>
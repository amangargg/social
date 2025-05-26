<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login_user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['tweet_id']) || empty($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user ID
$current_user = $_SESSION['login_user'];
$user_query = "SELECT id FROM users WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $current_user);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_id = $user_data['id'];

// Insert comment
$tweet_id = $_POST['tweet_id'];
$content = $_POST['content'];

$insert_query = "INSERT INTO comments (tweet_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iis", $tweet_id, $user_id, $content);

if ($insert_stmt->execute()) {
    // Get updated comment count
    $count_query = "SELECT COUNT(*) as count FROM comments WHERE tweet_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $tweet_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'count' => $count_data['count']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to post comment'
    ]);
}

$conn->close();
?>
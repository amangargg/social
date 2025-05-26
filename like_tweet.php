<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login_user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['tweet_id'])) {
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

$tweet_id = $_POST['tweet_id'];

// Check if user already liked this tweet
$check_query = "SELECT id FROM likes WHERE user_id = ? AND tweet_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $tweet_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // User already liked the tweet, so unlike it
    $unlike_query = "DELETE FROM likes WHERE user_id = ? AND tweet_id = ?";
    $unlike_stmt = $conn->prepare($unlike_query);
    $unlike_stmt->bind_param("ii", $user_id, $tweet_id);
    $unlike_stmt->execute();
    $liked = false;
} else {
    // User hasn't liked the tweet, so like it
    $like_query = "INSERT INTO likes (user_id, tweet_id) VALUES (?, ?)";
    $like_stmt = $conn->prepare($like_query);
    $like_stmt->bind_param("ii", $user_id, $tweet_id);
    $like_stmt->execute();
    $liked = true;
}

// Get updated like count
$count_query = "SELECT COUNT(*) as count FROM likes WHERE tweet_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $tweet_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

$conn->close();

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $count_data['count']
]);
?>
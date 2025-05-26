<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login_user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "GET" || empty($_GET['tweet_id'])) {
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

$tweet_id = $_GET['tweet_id'];

// Get comments for this tweet
$comments_query = "SELECT c.id, c.content, c.created_at, u.username 
                  FROM comments c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.tweet_id = ?
                  ORDER BY c.created_at ASC";
$comments_stmt = $conn->prepare($comments_query);
$comments_stmt->bind_param("i", $tweet_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($comment = $comments_result->fetch_assoc()) {
    $comments[] = $comment;
}

$conn->close();

echo json_encode([
    'success' => true,
    'comments' => $comments
]);
?>
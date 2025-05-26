<?php
session_start();

if (!isset($_SESSION['login_user'])) {
    header("location: index.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['tweet_content'])) {
    header("location: home.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// Insert tweet
$content = $_POST['tweet_content'];
$tweet_query = "INSERT INTO tweets (user_id, tweet_text) VALUES (?, ?)";
$tweet_stmt = $conn->prepare($tweet_query);
$tweet_stmt->bind_param("is", $user_id, $content);
$tweet_stmt->execute();

$conn->close();

// Redirect back to home
header("location: home.php");
exit;
?>
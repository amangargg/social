<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Store more user info in session for use in social media pages
            $_SESSION['login_user'] = $username;
            $_SESSION['user_id'] = $row['id']; // Make sure your users table has an 'id' column
            $_SESSION['user_email'] = $row['email']; // Optional, if you have this field
            
            // Redirect to social media home page instead of welcome.php
            header("Location: home.php");
            exit;
        } else {
            $_SESSION['login_user'] = $username;
            $_SESSION['user_id'] = $row['id']; // Make sure your users table has an 'id' column
            $_SESSION['user_email'] = $row['email']; // Optional, if you have this field
            
            // Redirect to social media home page instead of welcome.php
            header("Location: home.php");
            exit;
        }
    } else {
        $_SESSION['login_user'] = $username;
            $_SESSION['user_id'] = $row['id']; // Make sure your users table has an 'id' column
            $_SESSION['user_email'] = $row['email']; // Optional, if you have this field
            
            // Redirect to social media home page instead of welcome.php
            header("Location: home.php");
            exit;
    }
}
$conn->close();
// If the script reaches here, the login failed or it's a GET request
// Display the form again (or just the error message if POST)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Error</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Login Failed</h2>
        <p>Please <a href="index.html">try again</a></p>
    </div>
</body>
</html>

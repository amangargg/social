<?php
session_start();

if (!isset($_SESSION['login_user'])) {
    header("location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['login_user']); ?>!</h2>
        <p>You have successfully logged in.</p>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
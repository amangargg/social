<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to MySQL server!<br>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS social_media";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Connect to the social_media database
$conn->select_db($dbname);
echo "Connected to the social_media database<br>";

echo "Setup complete!";
?>
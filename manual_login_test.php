<?php
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

// Let's see what users exist in the database
echo "<h2>Users in Database:</h2>";
$result = $conn->query("SELECT id, username, email, password FROM users");

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Hash</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["username"] . "</td>";
        echo "<td>" . $row["email"] . "</td>";
        echo "<td>" . $row["password"] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No users found in database!";
}

// Let's test password verification
echo "<h2>Test Password Verification:</h2>";
$test_username = "testuser"; // Change this to a username that exists in your DB
$test_password = "password"; // Change this to what you think the password should be

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "User found: " . $test_username . "<br>";
    echo "Password hash in DB: " . $row['password'] . "<br>";
    
    // Test the password
    if (password_verify($test_password, $row['password'])) {
        echo "Password verification SUCCESSFUL";
    } else {
        echo "Password verification FAILED";
    }
} else {
    echo "User not found: " . $test_username;
}

// Create a new test user with known credentials
echo "<h2>Create Test User:</h2>";
$new_username = "newtest";
$new_email = "newtest@example.com";
$new_password = "testpass";
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "Creating user with:<br>";
echo "Username: " . $new_username . "<br>";
echo "Email: " . $new_email . "<br>";
echo "Password: " . $new_password . "<br>";
echo "Hashed password: " . $hashed_password . "<br>";

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $new_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "User already exists!";
} else {
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $new_username, $new_email, $hashed_password);
    
    if ($stmt->execute()) {
        echo "New test user created successfully!";
    } else {
        echo "Error creating user: " . $conn->error;
    }
}

$conn->close();
?>
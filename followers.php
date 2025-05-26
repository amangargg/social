<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['login_user'])) {
    header("location: index.html");
    exit;
}

// Check if a username parameter is provided
if (!isset($_GET['username'])) {
    header("location: home.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_media";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current logged-in user's info
$current_user = $_SESSION['login_user'];
$current_query = "SELECT id FROM users WHERE username = ?";
$current_stmt = $conn->prepare($current_query);
$current_stmt->bind_param("s", $current_user);
$current_stmt->execute();
$current_result = $current_stmt->get_result();
$current_data = $current_result->fetch_assoc();
$current_user_id = $current_data['id'];

// Get profile user's info
$profile_username = $_GET['username'];
$profile_query = "SELECT id FROM users WHERE username = ?";
$profile_stmt = $conn->prepare($profile_query);
$profile_stmt->bind_param("s", $profile_username);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

// Check if user exists
if ($profile_result->num_rows == 0) {
    header("location: home.php");
    exit;
}

$profile_data = $profile_result->fetch_assoc();
$profile_id = $profile_data['id'];

// Get followers
$followers_query = "SELECT u.id, u.username, 
                   (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = u.id) as is_following
                   FROM follows f
                   JOIN users u ON f.follower_id = u.id
                   WHERE f.followed_id = ?
                   ORDER BY u.username";
$followers_stmt = $conn->prepare($followers_query);
$followers_stmt->bind_param("ii", $current_user_id, $profile_id);
$followers_stmt->execute();
$followers_result = $followers_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Followers of <?php echo $profile_username; ?> - Social Media</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="logo">
                <h1>TweetApp</h1>
            </div>
            <ul>
                <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php?username=<?php echo $current_user; ?>"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="content">
            <header>
                <h2>Followers of @<?php echo $profile_username; ?></h2>
                <div class="user-info">
                    <span><?php echo $current_user; ?></span>
                </div>
            </header>
            
            <section class="users-list">
                <?php if ($followers_result->num_rows > 0): ?>
                    <?php while($follower = $followers_result->fetch_assoc()): ?>
                        <div class="user-item">
                            <div class="user-avatar">
                                <?php echo substr($follower['username'], 0, 1); ?>
                            </div>
                            <div class="user-details">
                                <a href="profile.php?username=<?php echo $follower['username']; ?>" class="username">
                                    <?php echo $follower['username']; ?>
                                </a>
                            </div>
                            <?php if ($follower['username'] !== $current_user): ?>
                                <!-- <button 
                                    class="follow-btn <?php echo $follower['is_following'] ? 'following' : ''; ?>" 
                                    onclick="followUser(<?php echo $follower['id']; ?>)">
                                    <?php echo $follower['is_following'] ? 'Unfollow' : 'Follow'; ?>
                                </button> -->
                                <button 
                                    class="follow-btn <?php echo $follower['is_following'] ? 'following' : ''; ?>" 
                                    onclick="followUser(<?php echo $follower['id']; ?>, event)">
                                    <?php echo $follower['is_following'] ? 'Unfollow' : 'Follow'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-users">
                        <p>No followers yet</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        
        <aside class="right-sidebar">
            <div class="profile-card">
                <h3><?php echo $current_user; ?></h3>
                <div class="profile-stats">
                    <a href="followers.php?username=<?php echo $current_user; ?>">
                        <span class="count"><?php 
                            $follower_count_query = "SELECT COUNT(*) as count FROM follows WHERE followed_id = ?";
                            $follower_count_stmt = $conn->prepare($follower_count_query);
                            $follower_count_stmt->bind_param("i", $current_user_id);
                            $follower_count_stmt->execute();
                            $follower_count_result = $follower_count_stmt->get_result();
                            $follower_count_data = $follower_count_result->fetch_assoc();
                            echo $follower_count_data['count'];
                        ?></span>
                        <span class="label">Followers</span>
                    </a>
                    <a href="following.php?username=<?php echo $current_user; ?>">
                        <span class="count"><?php 
                            $following_count_query = "SELECT COUNT(*) as count FROM follows WHERE follower_id = ?";
                            $following_count_stmt = $conn->prepare($following_count_query);
                            $following_count_stmt->bind_param("i", $current_user_id);
                            $following_count_stmt->execute();
                            $following_count_result = $following_count_stmt->get_result();
                            $following_count_data = $following_count_result->fetch_assoc();
                            echo $following_count_data['count'];
                        ?></span>
                        <span class="label">Following</span>
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <script src="timeline.js"></script>
</body>
</html>
<?php $conn->close(); ?>
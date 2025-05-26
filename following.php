<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

// Get current logged-in user
$current_user = $_SESSION['login_user'];
$current_user_query = "SELECT id FROM users WHERE username = ?";
$current_user_stmt = $conn->prepare($current_user_query);
$current_user_stmt->bind_param("s", $current_user);
$current_user_stmt->execute();
$current_user_result = $current_user_stmt->get_result();
$current_user_data = $current_user_result->fetch_assoc();
$current_user_id = $current_user_data['id'];

// Get profile username's info
$profile_username = $_GET['username'];
$profile_query = "SELECT id, username FROM users WHERE username = ?";
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

// Get users that profile user is following
$following_query = "SELECT u.id, u.username, 
                    (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
                    (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as follower_count,
                    (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND followed_id = u.id) as is_following
                    FROM users u 
                    JOIN follows f ON u.id = f.followed_id
                    WHERE f.follower_id = ?
                    ORDER BY u.username";
$following_stmt = $conn->prepare($following_query);
$following_stmt->bind_param("ii", $current_user_id, $profile_id);
$following_stmt->execute();
$following_result = $following_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profile_username; ?> Following - TweetApp</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .users-list {
            margin-top: 20px;
        }
        .user-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #e6ecf0;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #1da1f2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }
        .user-details h4 {
            margin: 0 0 5px 0;
        }
        .user-details p {
            margin: 0;
            color: #657786;
            font-size: 14px;
        }
        .user-stats {
            margin-top: 5px;
            font-size: 14px;
            color: #657786;
        }
        .user-stats span {
            margin-right: 10px;
        }
    </style>
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
                <h2><a href="javascript:history.back()"><i class="fas fa-arrow-left"></i></a> People <?php echo $profile_username; ?> follows</h2>
                <div class="user-info">
                    <span><?php echo $current_user; ?></span>
                </div>
            </header>
            
            <section class="users-list">
                <?php if ($following_result->num_rows > 0): ?>
                    <?php while($user = $following_result->fetch_assoc()): ?>
                        <div class="user-card">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo substr($user['username'], 0, 1); ?>
                                </div>
                                <div class="user-details">
                                    <h4><a href="profile.php?username=<?php echo $user['username']; ?>"><?php echo $user['username']; ?></a></h4>
                                    <p>@<?php echo $user['username']; ?></p>
                                    <div class="user-stats">
                                        <span><strong><?php echo $user['following_count']; ?></strong> Following</span>
                                        <span><strong><?php echo $user['follower_count']; ?></strong> Followers</span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($user['username'] !== $current_user): ?>
                                <button 
                                    class="follow-btn <?php echo $user['is_following'] ? 'following' : ''; ?>" 
                                    onclick="followUser(<?php echo $user['id']; ?>)">
                                    <?php echo $user['is_following'] ? 'Unfollow' : 'Follow'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-users">
                        <p><?php echo $profile_username; ?> isn't following anyone yet</p>
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
                            $current_follower_query = "SELECT COUNT(*) as count FROM follows WHERE followed_id = ?";
                            $current_follower_stmt = $conn->prepare($current_follower_query);
                            $current_follower_stmt->bind_param("i", $current_user_id);
                            $current_follower_stmt->execute();
                            $current_follower_result = $current_follower_stmt->get_result();
                            $current_follower_data = $current_follower_result->fetch_assoc();
                            echo $current_follower_data['count']; 
                        ?></span>
                        <span class="label">Followers</span>
                    </a>
                    <a href="following.php?username=<?php echo $current_user; ?>">
                        <span class="count"><?php 
                            $current_following_query = "SELECT COUNT(*) as count FROM follows WHERE follower_id = ?";
                            $current_following_stmt = $conn->prepare($current_following_query);
                            $current_following_stmt->bind_param("i", $current_user_id);
                            $current_following_stmt->execute();
                            $current_following_result = $current_following_stmt->get_result();
                            $current_following_data = $current_following_result->fetch_assoc();
                            echo $current_following_data['count']; 
                        ?></span>
                        <span class="label">Following</span>
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <script>
    function followUser(userId) {
        // Create a new XMLHttpRequest object
        const xhr = new XMLHttpRequest();
        
        // Configure it to make a POST request to follow_action.php
        xhr.open('POST', 'follow_user.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        // Set up the callback for when the request completes
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                
                // Find all buttons for this user and update them
                const buttons = document.querySelectorAll(`button[onclick="followUser(${userId})"]`);
                
                buttons.forEach(button => {
                    if (response.following) {
                        button.classList.add('following');
                        button.textContent = 'Unfollow';
                    } else {
                        button.classList.remove('following');
                        button.textContent = 'Follow';
                    }
                });
                
                // Update follower counts if needed
                const followerCounts = document.querySelectorAll('.follower-count');
                if (followerCounts.length > 0 && response.followerCount !== undefined) {
                    followerCounts.forEach(count => {
                        count.textContent = response.followerCount;
                    });
                }
            }
        };
        
        // Send the request with the user ID
        xhr.send('user_id=' + userId);
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // This will make MySQL errors more verbose
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
$current_user_query = "SELECT id FROM users WHERE username = ?";
$current_user_stmt = $conn->prepare($current_user_query);
$current_user_stmt->bind_param("s", $current_user);
$current_user_stmt->execute();
$current_user_result = $current_user_stmt->get_result();
$current_user_data = $current_user_result->fetch_assoc();
$current_user_id = $current_user_data['id'];

// Get profile user's info
$profile_username = $_GET['username'];
$profile_query = "SELECT id, username, email FROM users WHERE username = ?";
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

// Get follower and following counts
$follower_query = "SELECT COUNT(*) as follower_count FROM follows WHERE followed_id = ?";
$follower_stmt = $conn->prepare($follower_query);
$follower_stmt->bind_param("i", $profile_id);
$follower_stmt->execute();
$follower_result = $follower_stmt->get_result();
$follower_data = $follower_result->fetch_assoc();

$following_query = "SELECT COUNT(*) as following_count FROM follows WHERE follower_id = ?";
$following_stmt = $conn->prepare($following_query);
$following_stmt->bind_param("i", $profile_id);
$following_stmt->execute();
$following_result = $following_stmt->get_result();
$following_data = $following_result->fetch_assoc();

// Check if current user follows profile user
// $follows_query = "SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?";
// $follows_stmt = $conn->prepare($follows_query);
// $stmt = $conn->prepare($your_query);
// if ($stmt === false) {
//     die("Prepare failed: " . $conn->error);
// }
// $stmt->bind_param(...);
// $follows_stmt->bind_param("ii", $current_user_id, $profile_id);
// Check if current user follows profile user
$follows_query = "SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?";
$follows_stmt = $conn->prepare($follows_query);
if ($follows_stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$follows_stmt->bind_param("ii", $current_user_id, $profile_id);
$follows_stmt->execute();
$follows_result = $follows_stmt->get_result();
$is_following = ($follows_result->num_rows > 0);
$follows_stmt->execute();
$follows_result = $follows_stmt->get_result();
$is_following = ($follows_result->num_rows > 0);

// Get profile user's tweets
$tweets_query = "SELECT t.tweet_id as id, t.tweet_text as content, t.created_at, 
                (SELECT COUNT(*) FROM likes WHERE tweet_id = t.tweet_id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE tweet_id = t.tweet_id) as comment_count,
                (SELECT COUNT(*) FROM likes WHERE tweet_id = t.tweet_id AND user_id = ?) as user_liked
                FROM tweets t
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC";
$tweets_stmt = $conn->prepare($tweets_query);
$tweets_stmt->bind_param("ii", $current_user_id, $profile_id);
$tweets_stmt->execute();
$tweets_result = $tweets_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profile_username; ?> - Social Media</title>
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
                <li class="<?php echo ($profile_username === $current_user) ? 'active' : ''; ?>">
                    <a href="profile.php?username=<?php echo $current_user; ?>"><i class="fas fa-user"></i> Profile</a>
                </li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="content">
            <header>
                <h2>Profile</h2>
                <div class="user-info">
                    <span><?php echo $current_user; ?></span>
                </div>
            </header>
            
            <section class="profile-header">
                <div class="profile-cover"></div>
                <div class="profile-avatar">
                    <?php echo substr($profile_username, 0, 1); ?>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?php echo $profile_username; ?></h2>
                    <p class="profile-username">@<?php echo $profile_username; ?></p>
                    
                    <div class="profile-meta">
                        <div class="profile-stat">
                            <a href="following.php?username=<?php echo $profile_username; ?>">
                                <span class="count"><?php echo $following_data['following_count']; ?></span> Following
                            </a>
                        </div>
                        <div class="profile-stat">
                            <a href="followers.php?username=<?php echo $profile_username; ?>">
                                <span class="count follower-count"><?php echo $follower_data['follower_count']; ?></span> Followers
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($current_user !== $profile_username): ?>
                    <div class="profile-actions">
                        <button 
                            class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" 
                            onclick="followUser(<?php echo $profile_id; ?>)">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <section class="timeline">
                <h3 class="section-title">Tweets</h3>
                
                <?php if ($tweets_result->num_rows > 0): ?>
                    <?php while($tweet = $tweets_result->fetch_assoc()): ?>
                        <div class="tweet" data-tweet-id="<?php echo $tweet['id']; ?>">
                            <div class="tweet-header">
                                <a href="profile.php?username=<?php echo $profile_username; ?>" class="username">@<?php echo $profile_username; ?></a>
                                <span class="time"><?php echo date('M d', strtotime($tweet['created_at'])); ?></span>
                            </div>
                            <div class="tweet-content">
                                <?php echo htmlspecialchars($tweet['content']); ?>
                            </div>
                            <div class="tweet-actions">
                                <button class="like-btn <?php echo $tweet['user_liked'] ? 'liked' : ''; ?>" 
                                    onclick="likeTweet(<?php echo $tweet['id']; ?>)">
                                    <i class="<?php echo $tweet['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i> 
                                    <span class="like-count"><?php echo $tweet['like_count']; ?></span>
                                </button>
                                <button class="comment-btn" onclick="showComments(<?php echo $tweet['id']; ?>)">
                                    <i class="far fa-comment"></i> 
                                    <span class="comment-count"><?php echo $tweet['comment_count']; ?></span>
                                </button>
                            </div>
                            <div class="comments-section" id="comments-<?php echo $tweet['id']; ?>"></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tweets">
                        <p>No tweets yet</p>
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
            
            <div class="who-to-follow">
                <h3>Who to follow</h3>
                <?php
                // Get users not currently followed by the user
                $suggest_query = "SELECT id, username FROM users 
                                 WHERE id != ? 
                                 AND id NOT IN (SELECT followed_id FROM follows WHERE follower_id = ?)
                                 LIMIT 3";
                $suggest_stmt = $conn->prepare($suggest_query);
                $suggest_stmt->bind_param("ii", $current_user_id, $current_user_id);
                $suggest_stmt->execute();
                $suggest_result = $suggest_stmt->get_result();
                
                if ($suggest_result->num_rows > 0) {
                    while($suggest = $suggest_result->fetch_assoc()) {
                        echo '<div class="user-suggest">';
                        echo '<a href="profile.php?username=' . $suggest['username'] . '">' . $suggest['username'] . '</a>';
                        echo '<button class="follow-btn" onclick="followUser(' . $suggest['id'] . ')">Follow</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No suggestions available</p>';
                }
                ?>
            </div>
        </aside>
    </div>

    <script src="timeline.js"></script>
</body>
</html>
<?php $conn->close(); ?>
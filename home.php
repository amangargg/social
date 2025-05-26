
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Check if user is logged in
if (!isset($_SESSION['login_user'])) {
    header("location: index.html");
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

// Get current user's info
$current_user = $_SESSION['login_user'];
$user_query = "SELECT id, username FROM users WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $current_user);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_id = $user_data['id'];

// Get follower and following counts
$follower_query = "SELECT COUNT(*) as follower_count FROM follows WHERE followed_id = ?";
$follower_stmt = $conn->prepare($follower_query);
$follower_stmt->bind_param("i", $user_id);
$follower_stmt->execute();
$follower_result = $follower_stmt->get_result();
$follower_data = $follower_result->fetch_assoc();

$following_query = "SELECT COUNT(*) as following_count FROM follows WHERE follower_id = ?";
$following_stmt = $conn->prepare($following_query);
$following_stmt->bind_param("i", $user_id);
$following_stmt->execute();
$following_result = $following_stmt->get_result();
$following_data = $following_result->fetch_assoc();

// Get tweets from the user and people they follow
$timeline_query = "SELECT t.tweet_id as id, t.tweet_text as content, t.created_at, u.username, 
                  (SELECT COUNT(*) FROM likes WHERE tweet_id = t.tweet_id) as like_count,
                  (SELECT COUNT(*) FROM comments WHERE tweet_id = t.tweet_id) as comment_count,
                  (SELECT COUNT(*) FROM likes WHERE tweet_id = t.tweet_id AND user_id = ?) as user_liked
                  FROM tweets t
                  JOIN users u ON t.user_id = u.id
                  WHERE t.user_id = ? 
                  OR t.user_id IN (SELECT followed_id FROM follows WHERE follower_id = ?)
                  ORDER BY t.created_at DESC";

$timeline_stmt = $conn->prepare($timeline_query);
if ($timeline_stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$timeline_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$timeline_stmt->execute();
$timeline_result = $timeline_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Social Media</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="logo">
                <h1>ChatKar</h1>
            </div>
            <ul>
                <li class="active"><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php?username=<?php echo $current_user; ?>"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="content">
            <header>
                <h2>Home</h2>
                <div class="user-info">
                    <span><?php echo $current_user; ?></span>
                </div>
            </header>
            
            <section class="compose-tweet">
                <form action="post_tweet.php" method="post">
                    <textarea name="tweet_content" placeholder="What's happening?" required></textarea>
                    <button type="submit">Tweet</button>
                </form>
            </section>
            
            <section class="timeline">
                <?php if ($timeline_result->num_rows > 0): ?>
                    <?php while($tweet = $timeline_result->fetch_assoc()): ?>
                        <div class="tweet" data-tweet-id="<?php echo $tweet['id']; ?>">
                            <div class="tweet-header">
                                <a href="profile.php?username=<?php echo $tweet['username']; ?>" class="username">@<?php echo $tweet['username']; ?></a>
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
                        <p>No tweets to display. Follow some users or post your first tweet!</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        
        <aside class="right-sidebar">
            <div class="profile-card">
                <h3><?php echo $current_user; ?></h3>
                <div class="profile-stats">
                    <a href="followers.php">
                        <span class="count"><?php echo $follower_data['follower_count']; ?></span>
                        <span class="label">Followers</span>
                    </a>
                    <a href="following.php">
                        <span class="count"><?php echo $following_data['following_count']; ?></span>
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
                $suggest_stmt->bind_param("ii", $user_id, $user_id);
                $suggest_stmt->execute();
                $suggest_result = $suggest_stmt->get_result();
                
                if ($suggest_result->num_rows > 0) {
                    while($suggest = $suggest_result->fetch_assoc()) {
                        echo '<div class="user-suggest">';
                        echo '<a href="profile.php?username=' . $suggest['username'] . '">' . $suggest['username'] . '</a>';
                        echo '<button class="follow-btn" onclick="followUser(' . $suggest['id'] . ', event)">Follow</button>';
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
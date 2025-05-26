<?php
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

// Get follower and following counts for sidebar
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

// Get notifications for the current user
$notifications_query = "SELECT n.id, n.type, n.created_at, n.is_read, 
                        u.username as from_username, 
                        n.content_id,
                        CASE 
                            WHEN n.type = 'follow' THEN ''
                            WHEN n.type = 'like' THEN (SELECT tweet_text FROM tweets WHERE tweet_id = n.content_id)
                            WHEN n.type = 'comment' THEN (SELECT comment_text FROM comments WHERE comment_id = n.content_id)
                        END as content_text
                        FROM notifications n
                        JOIN users u ON n.from_user_id = u.id
                        WHERE n.user_id = ?
                        ORDER BY n.created_at DESC";

$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

// Mark all notifications as read
$mark_read_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$mark_read_stmt = $conn->prepare($mark_read_query);
$mark_read_stmt->bind_param("i", $user_id);
$mark_read_stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - TweetApp</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .notification {
            padding: 15px;
            border-bottom: 1px solid #e1e8ed;
            background-color: #fff;
            transition: background-color 0.2s;
        }
        
        .notification:hover {
            background-color: #f5f8fa;
        }
        
        .notification.unread {
            background-color: #e8f5fd;
        }
        
        .notification-content {
            display: flex;
            align-items: flex-start;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            background-color: #1da1f2;
            color: white;
        }
        
        .notification-details {
            flex: 1;
        }
        
        .notification-message {
            margin-bottom: 5px;
        }
        
        .notification-message a {
            color: #1da1f2;
            text-decoration: none;
            font-weight: bold;
        }
        
        .notification-time {
            color: #657786;
            font-size: 0.85em;
        }
        
        .notification-preview {
            margin-top: 5px;
            padding: 10px;
            background-color: #f5f8fa;
            border-radius: 5px;
            border-left: 3px solid #1da1f2;
            font-style: italic;
            color: #14171a;
        }
        
        .no-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #657786;
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
                <li class="active"><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="content">
            <header>
                <h2>Notifications</h2>
                <div class="user-info">
                    <span><?php echo $current_user; ?></span>
                </div>
            </header>
            
            <section class="notifications-list">
                <?php if ($notifications_result->num_rows > 0): ?>
                    <?php while($notification = $notifications_result->fetch_assoc()): ?>
                        <div class="notification <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-content">
                                <div class="notification-icon">
                                    <?php if ($notification['type'] === 'follow'): ?>
                                        <i class="fas fa-user-plus"></i>
                                    <?php elseif ($notification['type'] === 'like'): ?>
                                        <i class="fas fa-heart"></i>
                                    <?php elseif ($notification['type'] === 'comment'): ?>
                                        <i class="fas fa-comment"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-details">
                                    <div class="notification-message">
                                        <a href="profile.php?username=<?php echo $notification['from_username']; ?>">
                                            @<?php echo $notification['from_username']; ?>
                                        </a>
                                        <?php if ($notification['type'] === 'follow'): ?>
                                            followed you
                                        <?php elseif ($notification['type'] === 'like'): ?>
                                            liked your tweet
                                        <?php elseif ($notification['type'] === 'comment'): ?>
                                            commented on your tweet
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo date('M d, Y · h:i A', strtotime($notification['created_at'])); ?>
                                    </div>
                                    
                                    <?php if (!empty($notification['content_text']) && $notification['type'] !== 'follow'): ?>
                                        <div class="notification-preview">
                                            <?php 
                                                // Truncate long content
                                                $max_length = 100;
                                                $content = htmlspecialchars($notification['content_text']);
                                                if (strlen($content) > $max_length) {
                                                    echo substr($content, 0, $max_length) . '...';
                                                } else {
                                                    echo $content;
                                                }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash" style="font-size: 48px; color: #ccd6dd; margin-bottom: 15px;"></i>
                        <h3>No notifications yet</h3>
                        <p>When you get notifications, they'll show up here.</p>
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

    <script>
    function followUser(userId) {
        fetch('follow_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const followBtn = event.target;
                if (data.action === 'followed') {
                    followBtn.classList.add('following');
                    followBtn.textContent = 'Unfollow';
                } else {
                    followBtn.classList.remove('following');
                    followBtn.textContent = 'Follow';
                }
                
                // Update follower count if on profile page
                const followerCountElement = document.querySelector('.follower-count');
                if (followerCountElement) {
                    followerCountElement.textContent = data.follower_count;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>
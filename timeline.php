<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once('config.php');

// Get current user
$user_id = $_SESSION['user_id'];

// Get tweets from users that the current user follows, plus their own tweets
$sql = "SELECT t.*, u.username, u.profile_image, 
        (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE tweet_id = t.id) AS comment_count,
        (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id AND user_id = ?) AS user_liked
        FROM tweets t
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = ? 
        OR t.user_id IN (SELECT following_id FROM followers WHERE follower_id = ?)
        ORDER BY t.created_at DESC
        LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tweets = [];

while ($row = $result->fetch_assoc()) {
    $tweets[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Timeline</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="timeline.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>MyTwitter</h2>
            <nav>
                <ul>
                    <li class="active"><a href="timeline.php">Home</a></li>
                    <li><a href="profile.php?username=<?php echo $_SESSION['username']; ?>">Profile</a></li>
                    <li><a href="followers.php">Followers/Following</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="tweet-form">
                <form id="tweet-form">
                    <textarea name="tweet_content" placeholder="What's happening?" maxlength="280" required></textarea>
                    <button type="submit">Tweet</button>
                </form>
            </div>
            
            <div class="timeline">
                <?php if (empty($tweets)): ?>
                    <div class="no-tweets">
                        <p>No tweets to display. Follow some users or post your first tweet!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tweets as $tweet): ?>
                        <div class="tweet" data-tweet-id="<?php echo $tweet['id']; ?>">
                            <div class="tweet-header">
                                <img src="<?php echo $tweet['profile_image'] ? $tweet['profile_image'] : 'default_profile.png'; ?>" alt="Profile" class="profile-pic">
                                <a href="profile.php?username=<?php echo $tweet['username']; ?>" class="username">@<?php echo $tweet['username']; ?></a>
                                <span class="time"><?php echo date('M d', strtotime($tweet['created_at'])); ?></span>
                            </div>
                            <div class="tweet-content">
                                <?php echo htmlspecialchars($tweet['content']); ?>
                            </div>
                            <div class="tweet-actions">
                                <button class="like-btn <?php echo $tweet['user_liked'] ? 'liked' : ''; ?>" data-tweet-id="<?php echo $tweet['id']; ?>">
                                    <span class="like-icon">♥</span> <span class="like-count"><?php echo $tweet['like_count']; ?></span>
                                </button>
                                <button class="comment-btn" data-tweet-id="<?php echo $tweet['id']; ?>">
                                    <span class="comment-icon">💬</span> <span class="comment-count"><?php echo $tweet['comment_count']; ?></span>
                                </button>
                            </div>
                            <div class="comments-section" id="comments-<?php echo $tweet['id']; ?>" style="display: none;">
                                <div class="comments-list"></div>
                                <form class="comment-form">
                                    <input type="text" name="comment_content" placeholder="Add a comment..." required>
                                    <button type="submit">Post</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="right-sidebar">
            <div class="who-to-follow">
                <h3>Who to follow</h3>
                <div id="user-suggestions">
                    <!-- User suggestions will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        // Post a new tweet
        $('#tweet-form').submit(function(e) {
            e.preventDefault();
            const content = $(this).find('textarea').val();
            
            $.ajax({
                url: 'post_tweet.php',
                type: 'POST',
                data: { content: content },
                success: function(response) {
                    // Reload the timeline
                    location.reload();
                }
            });
        });
        
        // Like/unlike a tweet
        $('.like-btn').click(function() {
            const tweetId = $(this).data('tweet-id');
            const likeButton = $(this);
            
            $.ajax({
                url: 'like_tweet.php',
                type: 'POST',
                data: { tweet_id: tweetId },
                success: function(response) {
                    const data = JSON.parse(response);
                    likeButton.find('.like-count').text(data.like_count);
                    
                    if (data.liked) {
                        likeButton.addClass('liked');
                    } else {
                        likeButton.removeClass('liked');
                    }
                }
            });
        });
        
        // Show/hide comments
        $('.comment-btn').click(function() {
            const tweetId = $(this).data('tweet-id');
            const commentsSection = $('#comments-' + tweetId);
            
            if (commentsSection.is(':visible')) {
                commentsSection.slideUp();
            } else {
                // Load comments via AJAX
                $.ajax({
                    url: 'get_comments.php',
                    type: 'GET',
                    data: { tweet_id: tweetId },
                    success: function(response) {
                        commentsSection.find('.comments-list').html(response);
                        commentsSection.slideDown();
                    }
                });
            }
        });
        
        // Post a comment
        $('.comment-form').submit(function(e) {
            e.preventDefault();
            const tweetId = $(this).closest('.tweet').data('tweet-id');
            const content = $(this).find('input').val();
            const commentsSection = $('#comments-' + tweetId);
            
            $.ajax({
                url: 'post_comment.php',
                type: 'POST',
                data: { tweet_id: tweetId, content: content },
                success: function(response) {
                    // Reload comments
                    $.ajax({
                        url: 'get_comments.php',
                        type: 'GET',
                        data: { tweet_id: tweetId },
                        success: function(response) {
                            commentsSection.find('.comments-list').html(response);
                            commentsSection.find('input').val('');
                            
                            // Update comment count
                            const commentBtn = $('.comment-btn[data-tweet-id="' + tweetId + '"]');
                            const currentCount = parseInt(commentBtn.find('.comment-count').text());
                            commentBtn.find('.comment-count').text(currentCount + 1);
                        }
                    });
                }
            });
        });
        
        // Load user suggestions
        $.ajax({
            url: 'get_user_suggestions.php',
            type: 'GET',
            success: function(response) {
                $('#user-suggestions').html(response);
            }
        });
    });
    </script>
</body>
</html>
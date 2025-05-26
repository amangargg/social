<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once('config.php');

$user_id = $_SESSION['user_id'];

// Get users that the current user is not following
$sql = "SELECT id, username, profile_image 
        FROM users 
        WHERE id != ? 
        AND id NOT IN (SELECT following_id FROM followers WHERE follower_id = ?)
        ORDER BY RAND()
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        echo '<div class="user-suggestion">';
        echo '<img src="' . ($user['profile_image'] ? $user['profile_image'] : 'default_profile.png') . '" alt="Profile" class="profile-pic-small">';
        echo '<a href="profile.php?username=' . $user['username'] . '" class="username">@' . $user['username'] . '</a>';
        echo '<button class="follow-btn" data-user-id="' . $user['id'] . '">Follow</button>';
        echo '</div>';
    }
} else {
    echo '<p>No user suggestions available.</p>';
}

$stmt->close();
?>

<script>
$(document).ready(function() {
    $('.follow-btn').click(function() {
        const userId = $(this).data('user-id');
        const button = $(this);
        
        $.ajax({
            url: 'follow_user.php',
            type: 'POST',
            data: { user_id: userId },
            success: function(response) {
                button.text('Following');
                button.addClass('following');
                button.prop('disabled', true);
            }
        });
    });
});
</script>
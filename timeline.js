// Function to like/unlike a tweet
function likeTweet(tweetId) {
    fetch('like_tweet.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `tweet_id=${tweetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeBtn = document.querySelector(`.tweet[data-tweet-id="${tweetId}"] .like-btn`);
            const likeCount = document.querySelector(`.tweet[data-tweet-id="${tweetId}"] .like-count`);
            
            if (data.liked) {
                likeBtn.classList.add('liked');
                likeBtn.querySelector('i').classList.remove('far');
                likeBtn.querySelector('i').classList.add('fas');
            } else {
                likeBtn.classList.remove('liked');
                likeBtn.querySelector('i').classList.remove('fas');
                likeBtn.querySelector('i').classList.add('far');
            }
            
            likeCount.textContent = data.count;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Function to follow/unfollow a user
// function followUser(userId) {
//     fetch('follow_user.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/x-www-form-urlencoded'
//         },
//         body: `user_id=${userId}`
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             const followBtn = event.target;
            
//             if (data.following) {
//                 followBtn.textContent = 'Unfollow';
//                 followBtn.classList.add('following');
//             } else {
//                 followBtn.textContent = 'Follow';
//                 followBtn.classList.remove('following');
//             }
            
//             // If on the profile page, update follower count
//             const followerCount = document.querySelector('.follower-count');
//             if (followerCount) {
//                 followerCount.textContent = data.count;
//             }
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//     });
// }

function followUser(userId, event) {
    // Store the button element that was clicked
    
    const followBtn = event ? event.target : document.querySelector(`.follow-btn[data-user-id="${userId}"]`);
    
    fetch('follow_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.following) {
                followBtn.textContent = 'Unfollow';
                followBtn.classList.add('following');
            } else {
                followBtn.textContent = 'Follow';
                followBtn.classList.remove('following');
            }
            
            // If on the profile page, update follower count
            const followerCount = document.querySelector('.follower-count');
            if (followerCount) {
                followerCount.textContent = data.count;
            }
            
            // Update any other follower count displays
            const followerCounts = document.querySelectorAll('.follower-count');
            followerCounts.forEach(element => {
                element.textContent = data.count;
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Function to show/hide comments section
function showComments(tweetId) {
    const commentsSection = document.getElementById(`comments-${tweetId}`);
    
    if (commentsSection.style.display === 'block') {
        commentsSection.style.display = 'none';
        return;
    }
    
    commentsSection.style.display = 'block';
    
    // Load comments for this tweet
    fetch(`get_comments.php?tweet_id=${tweetId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear existing comments
            commentsSection.innerHTML = '';
            
            // Add comment form
            const commentForm = document.createElement('div');
            commentForm.className = 'comment-form';
            commentForm.innerHTML = `
                <input type="text" placeholder="Write a comment..." id="comment-input-${tweetId}">
                <button onclick="postComment(${tweetId})">Post</button>
            `;
            commentsSection.appendChild(commentForm);
            
            // Add comments
            if (data.comments.length > 0) {
                data.comments.forEach(comment => {
                    const commentDiv = document.createElement('div');
                    commentDiv.className = 'comment';
                    commentDiv.innerHTML = `
                        <div class="comment-header">
                            <a href="profile.php?username=${comment.username}" class="username">@${comment.username}</a>
                            <span class="time">${formatDate(comment.created_at)}</span>
                        </div>
                        <div class="comment-content">
                            ${comment.content}
                        </div>
                    `;
                    commentsSection.appendChild(commentDiv);
                });
            } else {
                const noComments = document.createElement('p');
                noComments.textContent = 'No comments yet';
                noComments.style.padding = '10px';
                noComments.style.color = '#657786';
                commentsSection.appendChild(noComments);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Function to post a comment
function postComment(tweetId) {
    const commentInput = document.getElementById(`comment-input-${tweetId}`);
    const content = commentInput.value.trim();
    
    if (!content) return;
    
    fetch('post_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `tweet_id=${tweetId}&content=${encodeURIComponent(content)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            commentInput.value = '';
            
            // Update comment count
            const commentCount = document.querySelector(`.tweet[data-tweet-id="${tweetId}"] .comment-count`);
            commentCount.textContent = data.count;
            
            // Refresh comments
            showComments(tweetId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    
    // If today, show time
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Otherwise show date
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}
// Add this to your timeline.js file or create a new mobile-menu.js file

document.addEventListener('DOMContentLoaded', function() {
    // Create mobile menu toggle button if it doesn't exist
    if (!document.querySelector('.menu-toggle')) {
      const menuToggle = document.createElement('button');
      menuToggle.className = 'menu-toggle';
      menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
      document.body.appendChild(menuToggle);
      
      // Create backdrop
      const backdrop = document.createElement('div');
      backdrop.className = 'menu-backdrop';
      document.body.appendChild(backdrop);
      
      // Toggle sidebar
      menuToggle.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
      });
      
      // Close sidebar when clicking outside
      backdrop.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
      });
    }
  });
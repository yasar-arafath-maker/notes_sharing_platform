<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$story_id = (int)$_GET['id'];

// Get story details
$story_query = "SELECT s.*, u.username, u.profile_picture, u.bio as author_bio,
    (SELECT COUNT(*) FROM likes WHERE story_id = s.story_id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE story_id = s.story_id) as comment_count,
    (SELECT COUNT(*) FROM subscriptions WHERE author_id = s.author_id) as subscriber_count
    FROM stories s 
    JOIN users u ON s.author_id = u.user_id 
    WHERE s.story_id = ? AND s.status = 'published'";
$stmt = $conn->prepare($story_query);
$stmt->bind_param("i", $story_id);
$stmt->execute();
$story = $stmt->get_result()->fetch_assoc();

if (!$story) {
    header('Location: index.php');
    exit();
}

// Get comments
$comments_query = "SELECT c.*, u.username, u.profile_picture 
    FROM comments c 
    JOIN users u ON c.user_id = u.user_id 
    WHERE c.story_id = ? 
    ORDER BY c.created_at DESC";
$stmt = $conn->prepare($comments_query);
$stmt->bind_param("i", $story_id);
$stmt->execute();
$comments = $stmt->get_result();

// Check if current user has liked the story
$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $like_check_query = "SELECT 1 FROM likes WHERE story_id = ? AND user_id = ?";
    $stmt = $conn->prepare($like_check_query);
    $stmt->bind_param("ii", $story_id, $_SESSION['user_id']);
    $stmt->execute();
    $user_liked = $stmt->get_result()->num_rows > 0;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment = sanitize_input($_POST['comment']);
    $stmt = $conn->prepare("INSERT INTO comments (story_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $story_id, $_SESSION['user_id'], $comment);
    if ($stmt->execute()) {
        create_notification($story['author_id'], 'comment', $story_id);
        header("Location: story.php?id=$story_id#comments");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($story['title']); ?> - StoryShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <article class="story-content">
            <h1 class="mb-4"><?php echo htmlspecialchars($story['title']); ?></h1>
            
            <div class="author-info mb-4">
                <div class="d-flex align-items-center">
                    <img src="<?php echo htmlspecialchars($story['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>" 
                         class="rounded-circle me-2" width="40" height="40" alt="Author">
                    <div>
                        <a href="author.php?id=<?php echo $story['author_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($story['username']); ?>
                        </a>
                        <div class="text-muted small">
                            <?php echo $story['subscriber_count']; ?> subscribers
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($story['cover_image']): ?>
                <img src="<?php echo htmlspecialchars($story['cover_image']); ?>" 
                     class="img-fluid rounded mb-4" alt="Story cover">
            <?php endif; ?>

            <div class="story-text mb-4">
                <?php echo nl2br(htmlspecialchars($story['content'])); ?>
            </div>

            <div class="story-actions mb-4">
                <button class="btn btn-outline-primary me-2 like-button <?php echo $user_liked ? 'active' : ''; ?>" 
                        data-story-id="<?php echo $story_id; ?>">
                    <i class="fas fa-heart"></i> 
                    <span class="like-count"><?php echo $story['like_count']; ?></span>
                </button>
                
                <button class="btn btn-outline-primary me-2" data-bs-toggle="collapse" data-bs-target="#comments">
                    <i class="fas fa-comment"></i> 
                    <?php echo $story['comment_count']; ?> Comments
                </button>

                <div class="btn-group">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-share"></i> Share
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="shareOnFacebook()">Facebook</a></li>
                        <li><a class="dropdown-item" href="#" onclick="shareOnTwitter()">Twitter</a></li>
                        <li><a class="dropdown-item" href="#" onclick="copyLink()">Copy Link</a></li>
                    </ul>
                </div>
            </div>
        </article>

        <section id="comments" class="collapse show">
            <h3 class="mb-4">Comments</h3>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Write a comment..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php">login</a> to leave a comment.
                </div>
            <?php endif; ?>

            <div class="comments-list">
                <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="comment mb-3">
                        <div class="d-flex">
                            <img src="<?php echo htmlspecialchars($comment['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>" 
                                 class="rounded-circle me-2" width="32" height="32" alt="User">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($comment['username']); ?></div>
                                <div class="text-muted small">
                                    <?php echo date('F j, Y g:i a', strtotime($comment['created_at'])); ?>
                                </div>
                                <div class="mt-1">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Like functionality
        document.querySelector('.like-button').addEventListener('click', function() {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                window.location.href = 'login.php';
                return;
            }

            const storyId = this.dataset.storyId;
            fetch('ajax/like_story.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ story_id: storyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    document.querySelector('.like-count').textContent = data.like_count;
                }
            });
        });

        // Share functionality
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }

        function shareOnTwitter() {
            const text = encodeURIComponent('<?php echo htmlspecialchars($story['title']); ?>');
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    </script>
</body>
</html> 
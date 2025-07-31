<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$author_id = (int)$_GET['id'];

// Get author details
$author_query = "SELECT u.*, 
    (SELECT COUNT(*) FROM stories WHERE author_id = u.user_id AND status = 'published') as story_count,
    (SELECT COUNT(*) FROM subscriptions WHERE author_id = u.user_id) as subscriber_count
    FROM users u 
    WHERE u.user_id = ?";
$stmt = $conn->prepare($author_query);
$stmt->bind_param("i", $author_id);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();

if (!$author) {
    header('Location: index.php');
    exit();
}

// Get author's stories
$stories_query = "SELECT s.*, 
    (SELECT COUNT(*) FROM likes WHERE story_id = s.story_id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE story_id = s.story_id) as comment_count
    FROM stories s 
    WHERE s.author_id = ? AND s.status = 'published'
    ORDER BY s.created_at DESC";
$stmt = $conn->prepare($stories_query);
$stmt->bind_param("i", $author_id);
$stmt->execute();
$stories = $stmt->get_result();

// Check if current user is subscribed to this author
$is_subscribed = false;
if (isset($_SESSION['user_id'])) {
    $subscription_check_query = "SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND author_id = ?";
    $stmt = $conn->prepare($subscription_check_query);
    $stmt->bind_param("ii", $_SESSION['user_id'], $author_id);
    $stmt->execute();
    $is_subscribed = $stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($author['username']); ?> - StoryShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="author-profile mb-5">
            <div class="row">
                <div class="col-md-3 text-center">
                    <img src="<?php echo htmlspecialchars($author['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>" 
                         class="rounded-circle mb-3" width="150" height="150" alt="Author">
                </div>
                <div class="col-md-9">
                    <h1 class="mb-3"><?php echo htmlspecialchars($author['username']); ?></h1>
                    
                    <div class="author-stats mb-3">
                        <span class="me-4">
                            <strong><?php echo $author['story_count']; ?></strong> Stories
                        </span>
                        <span class="me-4">
                            <strong><?php echo $author['subscriber_count']; ?></strong> Subscribers
                        </span>
                    </div>

                    <?php if ($author['bio']): ?>
                        <div class="author-bio mb-4">
                            <?php echo nl2br(htmlspecialchars($author['bio'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $author_id): ?>
                        <button class="btn <?php echo $is_subscribed ? 'btn-secondary' : 'btn-primary'; ?> subscribe-button" 
                                data-author-id="<?php echo $author_id; ?>">
                            <?php echo $is_subscribed ? 'Unsubscribe' : 'Subscribe'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h2 class="mb-4">Published Stories</h2>
        <div class="row">
            <?php while ($story = $stories->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($story['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($story['cover_image']); ?>" 
                                 class="card-img-top" alt="Story cover">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                            <div class="card-text text-muted small mb-2">
                                Published on <?php echo date('F j, Y', strtotime($story['created_at'])); ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="me-3"><i class="fas fa-heart"></i> <?php echo $story['like_count']; ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo $story['comment_count']; ?></span>
                                </div>
                                <a href="story.php?id=<?php echo $story['story_id']; ?>" class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Subscription functionality
        document.querySelector('.subscribe-button')?.addEventListener('click', function() {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                window.location.href = 'login.php';
                return;
            }

            const authorId = this.dataset.authorId;
            fetch('ajax/subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ author_id: authorId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('btn-primary');
                    this.classList.toggle('btn-secondary');
                    this.textContent = this.textContent === 'Subscribe' ? 'Unsubscribe' : 'Subscribe';
                }
            });
        });
    </script>
</body>
</html> 
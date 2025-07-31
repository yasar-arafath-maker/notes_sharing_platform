<?php
require_once 'includes/config.php';

// Get featured stories
$featured_stories_query = "SELECT s.*, u.username, u.profile_picture, 
    (SELECT COUNT(*) FROM likes WHERE story_id = s.story_id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE story_id = s.story_id) as comment_count
    FROM stories s 
    JOIN users u ON s.author_id = u.user_id 
    WHERE s.status = 'published' 
    ORDER BY like_count DESC 
    LIMIT 6";
$featured_stories = $conn->query($featured_stories_query);

// Get recent stories
$recent_stories_query = "SELECT s.*, u.username, u.profile_picture,
    (SELECT COUNT(*) FROM likes WHERE story_id = s.story_id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE story_id = s.story_id) as comment_count
    FROM stories s 
    JOIN users u ON s.author_id = u.user_id 
    WHERE s.status = 'published' 
    ORDER BY s.created_at DESC 
    LIMIT 12";
$recent_stories = $conn->query($recent_stories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StoryShare - Share Your Stories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Hero Section -->
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Welcome to StoryShare</h1>
            <p class="lead">Share your stories, connect with readers, and discover amazing content.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn btn-primary btn-lg">Get Started</a>
            <?php else: ?>
                <a href="write_story.php" class="btn btn-primary btn-lg">Write a Story</a>
            <?php endif; ?>
        </div>

        <!-- Featured Stories Section -->
        <section class="my-5">
            <h2 class="mb-4">Featured Stories</h2>
            <div class="row">
                <?php while ($story = $featured_stories->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if ($story['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($story['cover_image']); ?>" class="card-img-top" alt="Story cover">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                                <p class="card-text text-muted">
                                    By <a href="author.php?id=<?php echo $story['author_id']; ?>"><?php echo htmlspecialchars($story['username']); ?></a>
                                </p>
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
        </section>

        <!-- Recent Stories Section -->
        <section class="my-5">
            <h2 class="mb-4">Recent Stories</h2>
            <div class="row">
                <?php while ($story = $recent_stories->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if ($story['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($story['cover_image']); ?>" class="card-img-top" alt="Story cover">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                                <p class="card-text text-muted">
                                    By <a href="author.php?id=<?php echo $story['author_id']; ?>"><?php echo htmlspecialchars($story['username']); ?></a>
                                </p>
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
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 
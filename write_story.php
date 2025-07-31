<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$story_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$story = null;
$collaborators = [];

if ($story_id) {
    // Get story details
    $story_query = "SELECT s.*, 
        (SELECT GROUP_CONCAT(user_id) FROM story_collaborators WHERE story_id = s.story_id) as collaborator_ids
        FROM stories s 
        WHERE s.story_id = ? AND (s.author_id = ? OR EXISTS (
            SELECT 1 FROM story_collaborators 
            WHERE story_id = s.story_id AND user_id = ?
        ))";
    $stmt = $conn->prepare($story_query);
    $stmt->bind_param("iii", $story_id, $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $story = $stmt->get_result()->fetch_assoc();

    if ($story) {
        // Get collaborator details
        if ($story['collaborator_ids']) {
            $collaborators_query = "SELECT user_id, username, profile_picture FROM users WHERE user_id IN (" . $story['collaborator_ids'] . ")";
            $collaborators = $conn->query($collaborators_query)->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $status = sanitize_input($_POST['status']);
    $cover_image = null;

    // Handle cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/covers/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                $cover_image = $upload_path;
            }
        }
    }

    if ($story_id) {
        // Update existing story
        $stmt = $conn->prepare("UPDATE stories SET title = ?, content = ?, status = ?, cover_image = COALESCE(?, cover_image) WHERE story_id = ?");
        $stmt->bind_param("ssssi", $title, $content, $status, $cover_image, $story_id);
    } else {
        // Create new story
        $stmt = $conn->prepare("INSERT INTO stories (author_id, title, content, status, cover_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $_SESSION['user_id'], $title, $content, $status, $cover_image);
    }

    if ($stmt->execute()) {
        if (!$story_id) {
            $story_id = $conn->insert_id;
        }
        header("Location: story.php?id=$story_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $story ? 'Edit Story' : 'Write Story'; ?> - StoryShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4"><?php echo $story ? 'Edit Story' : 'Write Story'; ?></h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required
                       value="<?php echo $story ? htmlspecialchars($story['title']) : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php 
                    echo $story ? htmlspecialchars($story['content']) : ''; 
                ?></textarea>
            </div>

            <div class="mb-3">
                <label for="cover_image" class="form-label">Cover Image</label>
                <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                <?php if ($story && $story['cover_image']): ?>
                    <div class="mt-2">
                        <img src="<?php echo htmlspecialchars($story['cover_image']); ?>" 
                             class="img-thumbnail" width="200" alt="Current cover">
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="draft" <?php echo $story && $story['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $story && $story['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>

            <?php if ($story): ?>
                <div class="mb-3">
                    <label class="form-label">Collaborators</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($collaborators as $collaborator): ?>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <img src="<?php echo htmlspecialchars($collaborator['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>" 
                                     class="rounded-circle me-2" width="24" height="24" alt="Collaborator">
                                <span><?php echo htmlspecialchars($collaborator['username']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCollaboratorModal">
                            <i class="fas fa-plus"></i> Add Collaborator
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary"><?php echo $story ? 'Update Story' : 'Publish Story'; ?></button>
                <a href="<?php echo $story ? "story.php?id=$story_id" : 'index.php'; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Add Collaborator Modal -->
    <div class="modal fade" id="addCollaboratorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Collaborator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="collaboratorUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="collaboratorUsername" placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label for="collaboratorRole" class="form-label">Role</label>
                        <select class="form-select" id="collaboratorRole">
                            <option value="editor">Editor</option>
                            <option value="reviewer">Reviewer</option>
                            <option value="contributor">Contributor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addCollaboratorBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 500,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                     alignleft aligncenter alignright alignjustify | \
                     bullist numlist outdent indent | removeformat | help'
        });

        // Add collaborator functionality
        document.getElementById('addCollaboratorBtn')?.addEventListener('click', function() {
            const username = document.getElementById('collaboratorUsername').value;
            const role = document.getElementById('collaboratorRole').value;
            const storyId = <?php echo $story_id ?? 'null'; ?>;

            if (!username || !storyId) return;

            fetch('ajax/add_collaborator.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    story_id: storyId,
                    username: username,
                    role: role
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    </script>
</body>
</html> 
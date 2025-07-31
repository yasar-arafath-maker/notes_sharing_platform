<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes - Online Notes Sharing System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Online Notes Sharing System</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="user/logout.php" class="btn">Logout</a>
            </div>
        </header>

        <div class="notes-container">
            <div class="upload-section">
                <h2>Upload Notes</h2>
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="text" id="noteTitle" placeholder="Note Title" required>
                    </div>
                    <div class="form-group">
                        <select id="noteCategory" required>
                            <option value="">Select Category</option>
                            <option value="academic">Academic</option>
                            <option value="professional">Professional</option>
                            <option value="personal">Personal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="file" id="noteFile" accept=".pdf" required>
                    </div>
                    <button type="submit" class="btn">Upload Note</button>
                </form>
            </div>

            <div class="notes-list">
                <h2>My Notes</h2>
                <div id="notesGrid">
                    <!-- Notes will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/notes.js"></script>
</body>
</html> 
<?php
require 'db.php';
session_start();

$searchTerm = '';
$books = [];

if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    try {
        $stmt = $pdo->prepare('
            SELECT books1.id, books1.title, books1.description, books1.file_name, books1.genre, books1.author_id,
                   users.username AS author_name,
                   (SELECT COUNT(*) FROM likes2 WHERE book_id = books1.id) AS like_count
            FROM books1
            JOIN users ON books1.author_id = users.id
            WHERE books1.title LIKE ? OR books1.description LIKE ? OR books1.genre LIKE ?
        ');
        $searchTermWithWildcards = "%$searchTerm%";
        $stmt->execute([$searchTermWithWildcards, $searchTermWithWildcards, $searchTermWithWildcards]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search query error: " . $e->getMessage());
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    try {
        $stmt = $pdo->query('
            SELECT books1.id, books1.title, books1.description, books1.file_name, books1.genre, books1.author_id,
                   users.username AS author_name,
                   (SELECT COUNT(*) FROM likes2 WHERE book_id = books1.id) AS like_count
            FROM books1
            JOIN users ON books1.author_id = users.id
        ');
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle like action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like'])) {
    $bookId = $_POST['book_id'];
    $userId = $_SESSION['user_id'] ?? null;

    if (empty($bookId) || empty($userId)) {
        $error_message = "Invalid book or user.";
    } else {
        try {
            // Check if the like already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM likes2 WHERE user_id = ? AND book_id = ?');
            $stmt->execute([$userId, $bookId]);
            $likeExists = $stmt->fetchColumn();

            if ($likeExists) {
                // Unlike if already liked
                $stmt = $pdo->prepare('DELETE FROM likes2 WHERE user_id = ? AND book_id = ?');
                $stmt->execute([$userId, $bookId]);
            } else {
                // Like if not liked yet
                $stmt = $pdo->prepare('INSERT INTO likes2 (user_id, book_id) VALUES (?, ?)');
                $stmt->execute([$userId, $bookId]);
            }

            // Redirect to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review'])) {
    $bookId = $_POST['book_id'];
    $userId = $_SESSION['user_id'] ?? null;
    $reviewText = $_POST['review_text'] ?? '';

    if (empty($bookId) || empty($userId) || empty($reviewText)) {
        $error_message = "Invalid book, user, or review.";
    } else {
        try {
            // Insert the review into the database
            $stmt = $pdo->prepare('INSERT INTO reviews1 (user_id, book_id, review_text, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$userId, $bookId, $reviewText]);

            // Redirect to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Attach reviews to corresponding books
foreach ($books as &$book) {
    $bookId = $book['id'];
    try {
        $stmt = $pdo->prepare('SELECT users.username, reviews1.review_text FROM reviews1 JOIN users ON reviews1.user_id = users.id WHERE book_id = ?');
        $stmt->execute([$bookId]);
        $book['reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Review fetch error: " . $e->getMessage());
    }
}
unset($book);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <style>
                /* General Styles */
                body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    background-image: url('images/bg1.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}
header {
            background: #080705; /* Uniform background color for the entire header */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-bottom: 3px solid #1a150f; /* Darker brown bottom border */
            height: 100px;
        }
        
        header .container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #252116; /* Ensure text color is readable */
        }
.header1.container{
            background: #252116;
        }
#branding {
            width: 180px;
            height: 80px;
            background-image: url('images/logo.jpg'); /* Keep the logo */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 28%;
        }
nav {
    flex-grow: 1; /* Allows the nav to take remaining space */
}

nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: row-reverse;
    font-size: 20px;
    justify-content: flex-start;
}




nav ul li {
    margin-left: 15px; /* Space between buttons */
}

nav ul li:first-child {
    margin-left: 0;
}

nav ul li a {
    text-decoration: none;
    color: white;
    font-size: 18px; /* Increased font size */
    padding: 10px 20px; /* Added padding for button-like appearance */
    background-color: #010000; /* Background color for buttons */
    border-radius: 5px; /* Rounded corners */
    transition: background-color 0.3s ease, color 0.3s ease;
    display: inline-block; /* Ensure links are displayed as blocks */
}

nav ul li a:hover {
    background-color: #77aaff; /* Darker background color on hover */
    color: #e0e0e0; /* Light grey text on hover */
}


.search-form {
    text-align: center;
    margin-bottom: 20px;
}

.search-form input[type="text"] {
    width: 300px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

.search-form button {
    padding: 10px 20px;
    background-color: #007BFF;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.search-form button:hover {
    background-color: #0056b3;
}

/* Books Section */
.book-container {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin: 40px auto; /* Adjusted margin to create space around the container */
    max-width: 1200px; /* Ensure it doesn't stretch too wide */
}

.book-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.book-list li {
    background: #f9f9f9;
    margin-bottom: 20px;
    padding: 20px;
    border: #ddd 1px solid;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s, transform 0.3s;
}

.book-list li:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.book-list li h2 {
    margin: 0 0 10px;
    font-size: 22px;
    color: #333;
}

.book-list li p {
    margin: 5px 0;
    font-size: 16px;
    color: #666;
}

/* Buttons */
.button-group {
    margin: 10px 0;
}

.button-group a {
    text-decoration: none;
    color: #fff;
    padding: 10px 15px;
    border-radius: 5px;
    display: inline-block;
    font-size: 14px;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    margin-right: 10px;
}

.button-group a.read-link {
    background-color: #28a745; /* Green background for Read PDF */
}

.button-group a.read-link:hover {
    background-color: #218838;
    box-shadow: 0 2px 5px rgba(40, 167, 69, 0.5);
}

.button-group a.download-link {
    background-color: #007bff; /* Blue background for Download */
}

.button-group a.download-link:hover {
    background-color: #0056b3;
    box-shadow: 0 2px 5px rgba(38, 143, 255, 0.5);
}

.like-button button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.like-button button:hover {
    background-color: #0056b3;
}

.review-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    resize: vertical;
    margin: 10px 0;
}

.review-form button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.review-form button:hover {
    background-color: #0056b3;
}

.reviews {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.reviews h3 {
    margin-top: 0;
    color: #333;
}

.reviews p {
    margin: 0;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    color: #555;
}

.error-message {
    color: #d9534f;
    text-align: center;
    font-size: 16px;
    margin-bottom: 20px;
}
        h1{
            display: block;
    font-size: 2em;
    margin-block-start: 0.67em;
    margin-block-end: 0.67em;
    margin-inline-start: 120px;
    margin-inline-end: 0px;
    font-weight: bold;
    unicode-bidi: isolate;
    color: white
}
.search-form {
    text-align: center;
    margin-bottom: 20px;
    position: relative; 
    margin-block-start: 20px;
}
.search-form h1 {
    font-size: 2em;
    margin: 0;
    margin-left: -20px; /* Adjust this value to move the heading to the left */
    font-weight: bold;
}
@media (max-width: 768px) {
    nav ul {
        flex-direction: column;
        align-items: flex-start;
    }

    nav ul li {
        margin: 10px 0;
    }

    nav ul li a {
        font-size: 16px; /* Adjust font size for smaller screens */
        padding: 8px 16px; /* Adjust padding for smaller screens */
    }
}
/* Share and Copy Link Buttons */
.share-link, .copy-link-button {
    background-color: #00aced;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.3s ease;
    margin-right: 10px;
    display: inline-block;
    cursor: pointer;
}

.share-link.facebook-link {
    background-color: #3b5998; /* Facebook blue */
}

.share-link.twitter-link {
    background-color: #1da1f2; /* Twitter blue */
}

.share-link.instagram-link {
    background-color: #e1306c; /* Instagram pink */
}

.copy-link-button {
    background-color: #ffcc00; /* Yellow for Copy Link */
}

.copy-link-button:hover, .share-link:hover {
    opacity: 0.8;
}
.review-item {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.review-form {
    margin-top: 10px;
}

.review-form textarea {
    width: 100%;
    margin-bottom: 10px;
}

.review-form button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.review-form button:hover {
    background-color: #0056b3;
}
    </style>
</head>
<body>
    <header>
    <div class="container">
            <div id="branding">
                <!-- Logo here -->
            </div>
            <nav>
                <ul>
                <li><a href="profile.php">PROFILE</a></li>
                    
                
                    <li><a href="contact.php">CONTACT US</a></li>
                    <li><a href="about.php">ABOUT US</a></li>
                    <li><a href="upload3.php">UPLOAD</a></li>
                    <li><a href="indexcopy1.php">HOME</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <form class="search-form" method="get" action="">
            <input type="text" name="search" placeholder="Search for books..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit">Search</button>
        </form>
        <h1>BOOK LIST</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if (count($books) > 0): ?>
            <div class="book-container">
                <ul class="book-list">
                    <?php foreach ($books as $book): ?>
                        <li>
                            <h2><?= htmlspecialchars($book['title']) ?></h2>
                            <p><?= htmlspecialchars($book['description']) ?></p>
                            <p>Genre: <?= htmlspecialchars($book['genre']) ?></p>
                            <p>Author: <?= htmlspecialchars($book['author_name']) ?></p>
                            <div class="button-group">
                                <?php
                                $fileUrl = 'http://localhost/book-copy/' . urlencode($book['file_name']);
                                ?>
                                <a href="<?= htmlspecialchars($book['file_name']) ?>" target="_blank" class="read-link">Read PDF</a>
                                <a href="<?= htmlspecialchars($book['file_name']) ?>" download class="download-link">Download</a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($fileUrl) ?>" target="_blank" class="share-link facebook-link">Share on Facebook</a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($fileUrl) ?>" target="_blank" class="share-link twitter-link">Share on Twitter</a>
                                <a href="https://www.instagram.com/?url=<?= urlencode($fileUrl) ?>" target="_blank" class="share-link instagram-link">Share on Instagram</a>
                                <button type="button" class="copy-link-button" data-link="<?= urldecode($fileUrl) ?>">Copy Link</button>
                                <form method="post" class="like-button">
                                    <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                                    <button type="submit" name="like">
                                        <?= $book['like_count'] ?> Like
                                    </button>
                                </form>
                            </div>
                            <form method="post" class="review-form">
                                <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                                <textarea name="review_text" placeholder="Write a review..." required></textarea>
                                <button type="submit" name="review">Submit Review</button>
                            </form>
                            <?php if (!empty($book['reviews'])): ?>
                                <div class="reviews">
                                    <h3>Reviews:</h3>
                                    <?php foreach ($book['reviews'] as $review): ?>
                                        <div class="review-item">
                                            <p><strong><?= htmlspecialchars($review['username']) ?>:</strong> <?= htmlspecialchars($review['review_text']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No books found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<script>
document.querySelectorAll('.copy-link-button').forEach(button => {
    button.addEventListener('click', () => {
        const link = button.getAttribute('data-link');
        navigator.clipboard.writeText(link).then(() => {
            alert('Link copied to clipboard: ' + link);
        }).catch(err => {
            console.error('Error copying link: ', err);
        });
    });
});
</script>
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
            background: #080705;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-bottom: 3px solid #1a150f;
            height: 100px;
        }

        header .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #252116;
        }

        #branding {
            width: 180px;
            height: 80px;
            background-image: url('images/logo.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 28%;
        }

        nav {
            flex-grow: 1;
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
            margin-left: 15px;
        }

        nav ul li:first-child {
            margin-left: 0;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            padding: 10px 20px;
            background-color: #010000;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
            display: inline-block;
        }

        nav ul li a:hover {
            background-color: #77aaff;
            color: #e0e0e0;
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
            margin: 40px auto;
            max-width: 1200px;
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
            transition: box-shadow 0.3s ease;
        }

        .book-list li:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .book-list h2 {
            margin-top: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .book-list p {
            color: #555;
        }

        .button-group {
            margin-top: 20px;
        }

        .read-link, .download-link, .like-button {
            text-decoration: none;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border-radius: 4px;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        .read-link:hover, .download-link:hover, .like-button:hover {
            background-color: #0056b3;
        }

        /* Review Section */
        .review-list {
            margin-top: 20px;
            padding-left: 0;
            list-style: none;
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 8px;
        }

        .review-item {
            margin-bottom: 10px;
        }

        .review-item strong {
            font-weight: bold;
            margin-right: 10px;
        }

        .review-item p {
            margin: 5px 0;
        }

        .review-form {
            margin-top: 20px;
        }

        .review-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            resize: vertical;
            font-size: 16px;
            color: #333;
        }

        .review-form button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .review-form button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding"></div>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Shop</a></li>
                    <li><a href="#">Profile</a></li>
                    <li><a href="#">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="search-form">
        <form method="get" action="">
            <input type="text" name="search" placeholder="Search for books" value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit">Search</button>
        </form>
    </div>
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

                    <ul class="review-list">
                        <?php if (!empty($book['reviews'])): ?>
                            <?php foreach ($book['reviews'] as $review): ?>
                                <li class="review-item">
                                    <strong><?= htmlspecialchars($review['username']) ?>:</strong>
                                    <p><?= htmlspecialchars($review['review_text']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No reviews yet.</li>
                        <?php endif; ?>
                    </ul>

                    <div class="review-form">
                        <form method="post" action="">
                            <textarea name="review_text" rows="3" placeholder="Write your review here..."></textarea>
                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                            <button type="submit" name="review">Submit Review</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>

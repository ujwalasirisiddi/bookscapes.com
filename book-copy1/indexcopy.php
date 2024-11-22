<?php
require 'db.php';
session_start(); // Ensure session is started

// Initialize variables
$searchTerm = '';
$books = [];
$error_message = '';

// Handle search
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    try {
        $stmt = $pdo->prepare('
            SELECT books.id, books.title, books.description, books.file_link, books.file_path, users.username, 
                   (SELECT COUNT(*) FROM likes WHERE book_id = books.id) AS like_count
            FROM books 
            JOIN users ON books.author_id = users.id
            WHERE books.title LIKE ? OR books.description LIKE ? OR books.genre LIKE ?
        ');
        $searchTermWithWildcards = "%$searchTerm%";
        $stmt->execute([$searchTermWithWildcards, $searchTermWithWildcards, $searchTermWithWildcards]);
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Search query error: " . $e->getMessage());
    }
} else {
    // If no search term, fetch all books
    try {
        $stmt = $pdo->query('
            SELECT books.id, books.title, books.description, books.file_link, books.file_path, users.username, 
                   (SELECT COUNT(*) FROM likes WHERE book_id = books.id) AS like_count
            FROM books 
            JOIN users ON books.author_id = users.id
        ');
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fetch all books error: " . $e->getMessage());
    }
}

// Handle like action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like'])) {
    $bookId = $_POST['book_id'];
    $userId = $_SESSION['user_id'] ?? null; // Use null coalescing operator to handle unset session variable

    if (empty($bookId) || empty($userId)) {
        $error_message = "Invalid book or user.";
    } else {
        try {
            // Check if the like already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE user_id = ? AND book_id = ?');
            $stmt->execute([$userId, $bookId]);
            $likeExists = $stmt->fetchColumn();

            if ($likeExists) {
                // Unlike if already liked
                $stmt = $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND book_id = ?');
                $stmt->execute([$userId, $bookId]);
            } else {
                // Like if not liked yet
                $stmt = $pdo->prepare('INSERT INTO likes (user_id, book_id) VALUES (?, ?)');
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
            $stmt = $pdo->prepare('INSERT INTO reviews (author_id, book_id, review_text) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $bookId, $reviewText]);

            // Redirect to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch reviews for each book
$reviews = [];
try {
    $stmt = $pdo->query('
        SELECT reviews.book_id, reviews.review_text, users.username
        FROM reviews
        JOIN users ON reviews.author_id = users.id
    ');
    // Fetch reviews grouped by book_id
    $reviews = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch reviews error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eBook Management System</title>
    <style>
                /* Include your CSS styles here */
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
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #120f0a;
            color: #fff;
            padding: 20px 0;
            border-bottom: #77aaff 3px solid;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }
        header:hover {
            box-shadow: 0 0 20px rgba(119, 170, 255, 0.8);
        }
        header a {
            color: #fff;
            text-decoration:solid;
            text-transform: uppercase;
            font-size: 20px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        header a:hover {
            background-color: #77aaff;
            box-shadow: 0 0 10px rgba(119, 170, 255, 0.8);
        }
        header ul {
            padding: 0;
            list-style: none;
        }
        header li {
            float: left;
            display: inline;
            padding: 0 20px;
        }
        header #branding {
            float: left;
            width: 200px;
    height: 100px;
    background-image: url('images/logo.jpg'); /* Replace with your logo image path */
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    border-radius: 30%;
    margin-right: 10px;
        }
        header #branding h1 {
            margin: 0;
            font-size: 24px;
        }
        header nav {
            float: right;
            margin-top: 10px;
        }
        .book-list {
            margin: 20px 0;
            padding: 0;
        }
        .book-list li {
            list-style: none;
            background: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border: #ccc 1px solid;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .book-list li:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .book-list li h2 {
            margin: 0 0 10px;
            font-size: 22px;
            color: #333;
        }
        .book-list li p {
            margin: 0 0 10px;
            font-size: 16px;
            color: #666;
        }
        .book-list li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            transition: all 0.3s ease;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }
        .book-list li a.read-link {
            background: #28a745;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }
        .book-list li a.read-link:hover {
            background: #218838;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.7);
        }
        .book-list li a.download-link {
            background: #dc3545;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
        }
        .book-list li a.download-link:hover {
            background: #c82333;
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.7);
        }
        .like-button, .share-buttons {
            margin-top: 10px;
        }
        .like-button button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .like-button button:hover {
            background-color: #0056b3;
        }
        .share-buttons a {
            display: inline-block;
            margin-right: 10px;
            padding: 5px 10px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .share-buttons a:hover {
            background-color: #0056b3;
        }
        .review-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
        .review-form button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .review-form button:hover {
            background-color: #0056b3;
        }
        .review {
            margin-top: 10px;
            padding: 10px;
            border: #ddd 1px solid;
            border-radius: 5px;
            background: #fff;
        }
        .review strong {
            display: block;
            font-size: 16px;
            color: #333;
        }
        .review p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .search-bar {
                flex-direction: column;
                align-items: center;
            }
            .search-bar input[type="text"] {
                width: 100%;
                max-width: 100%;
                margin-bottom: 10px;
            }
            .search-bar input[type="submit"] {
                width: 100%;
                max-width: 100%;
            }
            .search-bar {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        .search-bar input[type="text"] {
            width: 100%;
            max-width: 600px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .search-bar input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #173b32;
            color: #fff;
            border-radius: 15px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .search-bar input[type="submit"]:hover {
            background-color: #6ba395;
        }
        h1{
            color: white;
            width:15%;
            border-radius:10%;
            text-align:center;
            
        }
        /* Header styles */
header {
    background-color: #007BFF; /* Blue background */
    color: white;
    padding: 10px 0;
    border-bottom: 3px solid #0056b3; /* Darker blue bottom border */
}

header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

#branding h1 {
    margin: 0;
    font-size: 24px;
}

nav ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    display: flex;
}

nav ul li {
    margin-left: 20px;
}

nav ul li:first-child {
    margin-left: 0;
}

nav ul li a {
    text-decoration: none;
    color: white;
    font-size: 16px;
    transition: color 0.3s ease;
}

nav ul li a:hover {
    color: #e0e0e0; /* Light grey on hover */
}

    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                
            </div>
            <nav>
                <ul>
                    <li><a href="indexcopy.php">Home</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="upload.php">Upload</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <div class="search-bar">
            <form action="indexcopy.php" method="get">
                <input type="text" name="search" placeholder="Search for books..." value="<?= htmlspecialchars($searchTerm) ?>">
                <input type="submit" value="Search">
            </form>
        </div>
        <h1>Book List</h1>
        <ul class="book-list">
            <?php foreach ($books as $book): ?>
                <li>
                    <h2><?= htmlspecialchars($book['title']) ?></h2>
                    <p><?= htmlspecialchars($book['description']) ?></p>
                    <p>Author: <?= htmlspecialchars($book['username']) ?></p>
                   
                    <a href="<?= htmlspecialchars($book['file_link']) ?>" target="_blank" class="read-link">Read</a>
                    <a href="download.php?file=<?= htmlspecialchars($book['id']) ?>" class="download-link">Download</a>
                    <div class="like-button">
                        <form method="POST" action="">
                            <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                            <button type="submit" name="like">Like</button>
                        </form>
                        <span class="like-count"><?= htmlspecialchars($book['like_count']) ?> likes</span>
                    </div>
                    <div class="review-form">
                        <h3>Leave a Review:</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                            <textarea name="review_text" rows="4" placeholder="Write your review here..." required></textarea>
                            <button type="submit" name="review">Submit Review</button>
                        </form>
                    </div>
                    <?php if (isset($reviews[$book['id']])): ?>
                        <div class="reviews">
                            <?php foreach ($reviews[$book['id']] as $review): ?>
                                <div class="review">
                                    <strong><?= htmlspecialchars($review['username']) ?></strong>
                                    <p><?= htmlspecialchars($review['review_text']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($book['file_link']) ?>" target="_blank">Share on Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($book['file_link']) ?>&text=Check%20out%20this%20book!" target="_blank">Share on Twitter</a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($book['file_link']) ?>" target="_blank">Share on LinkedIn</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
<?php
require 'db.php';
session_start(); // Ensure session is started

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$loggedInUserId = $_SESSION['user_id']; // Initialize the logged-in user ID

// Fetch reviews for a specific book
$bookId = $_GET['book_id'] ?? null; // Get the book ID from the query string

if ($bookId) {
    try {
        $stmt = $pdo->prepare('SELECT reviews.review, reviews.rating, users.username 
                                FROM reviews 
                                JOIN users ON reviews.user_id = users.id 
                                WHERE reviews.book_id = ?');
        $stmt->execute([$bookId]);
        $reviews = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $reviewText = trim($_POST['review']);
    $rating = intval($_POST['rating']);

    if (empty($reviewText) || $rating < 1 || $rating > 5) {
        $error_message = "Invalid review or rating.";
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO reviews (book_id, user_id, review, rating) VALUES (?, ?, ?, ?)');
            $stmt->execute([$bookId, $loggedInUserId, $reviewText, $rating]);

            // Redirect to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?book_id=" . urlencode($bookId));
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Reviews</title>
    <style>
         body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
            background-image: url('images/bg.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 28px;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        form label {
            margin-bottom: 5px;
            font-size: 16px;
            color: #333;
        }

        form textarea, 
        form input[type="number"] {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        form textarea:focus, 
        form input[type="number"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
        }

        form button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        form button:hover {
            background-color: #0056b3;
            box-shadow: 0 0 8px rgba(0, 86, 179, 0.5);
        }

        .error-message {
            color: #dc3545;
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            border-radius: 5px;
        }

        .review {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }

        .review p {
            margin: 0;
            padding: 5px 0;
        }

        .review strong {
            display: block;
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <h1>Reviews for Book ID: <?= htmlspecialchars($bookId) ?></h1>
    <form method="post">
        <label for="review">Your Review:</label>
        <textarea name="review" id="review" required></textarea>

        <label for="rating">Rating (1-5):</label>
        <input type="number" name="rating" id="rating" min="1" max="5" required>

        <button type="submit" name="submit_review">Submit Review</button>
    </form>

    <?php if (!empty($reviews)): ?>
        <h2>Reviews:</h2>
        <?php foreach ($reviews as $review): ?>
            <div class="review">
                <p><strong><?= htmlspecialchars($review['username']) ?></strong> rated <?= htmlspecialchars($review['rating']) ?>:</p>
                <p><?= htmlspecialchars($review['review']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No reviews yet.</p>
    <?php endif; ?>
</body>
</html>

<?php
require 'db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Handle user actions
        if (isset($_POST['delete_user'])) {
            $user_id = $_POST['user_id'];

            // Fetch books uploaded by the user
            $stmt = $pdo->prepare('SELECT id, file_name FROM books1 WHERE author_id = ?');
            $stmt->execute([$user_id]);
            $books = $stmt->fetchAll();

            foreach ($books as $book) {
                $book_id = $book['id'];
                $file_path = __DIR__ . '/uploads/' . $book['file_name'];

                // Delete the file from the uploads directory
                if (file_exists($file_path)) {
                    if (!unlink($file_path)) {
                        throw new Exception("Failed to delete the file.");
                    }
                }

                // Delete reviews associated with the book
                $stmt = $pdo->prepare('DELETE FROM reviews1 WHERE book_id = ?');
                if (!$stmt->execute([$book_id])) {
                    throw new Exception("Failed to delete reviews.");
                }

                // Delete the book record from the database
                $stmt = $pdo->prepare('DELETE FROM books1 WHERE id = ?');
                if (!$stmt->execute([$book_id])) {
                    throw new Exception("Failed to delete the book.");
                }
            }

            // Delete the user record from the database
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            if (!$stmt->execute([$user_id])) {
                throw new Exception("Failed to delete the user.");
            }
        }

        // Handle book actions
        if (isset($_POST['delete_book'])) {
            $book_id = $_POST['book_id'];

            // Fetch the file path from the database
            $stmt = $pdo->prepare('SELECT file_name FROM books1 WHERE id = ?');
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();

            if ($book) {
                $file_path = __DIR__ . '/uploads/' . $book['file_name'];

                // Delete the file from the uploads directory
                if (file_exists($file_path)) {
                    if (!unlink($file_path)) {
                        throw new Exception("Failed to delete the file.");
                    }
                }

                // Delete reviews associated with the book
                $stmt = $pdo->prepare('DELETE FROM reviews1 WHERE book_id = ?');
                if (!$stmt->execute([$book_id])) {
                    throw new Exception("Failed to delete reviews.");
                }

                // Delete the book record from the database
                $stmt = $pdo->prepare('DELETE FROM books1 WHERE id = ?');
                if (!$stmt->execute([$book_id])) {
                    throw new Exception("Failed to delete the book.");
                }
            } else {
                throw new Exception("Book not found.");
            }
        }

        // Handle review actions
        if (isset($_POST['delete_review'])) {
            $review_id = $_POST['review_id'];

            // Delete the review
            $stmt = $pdo->prepare('DELETE FROM reviews1 WHERE id = ?');
            if (!$stmt->execute([$review_id])) {
                throw new Exception("Failed to delete review.");
            }
        }

        // Commit transaction
        $pdo->commit();

    } catch (Exception $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        // Optionally log the error or handle it here
    }
}

// Fetch users, books, and reviews
$users = $pdo->query('SELECT * FROM users')->fetchAll();
$books = $pdo->query('SELECT * FROM books1')->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <style>
                body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .section {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: #fff;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        form input[type="text"],
        form input[type="email"],
        form textarea {
            flex: 1 1 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s, box-shadow 0.3s;
            margin-bottom: 10px;
        }

        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form textarea:focus {
            border-color: #9b59b6;
            box-shadow: 0 0 8px rgba(155, 89, 182, 0.5);
        }

        form button {
            padding: 10px 20px;
            background-color: #9b59b6;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
            margin-right: 10px;
        }

        form button:hover {
            background-color: #8e44ad;
            box-shadow: 0 0 8px rgba(142, 68, 173, 0.5);
        }

        a {
            color: #fff;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            text-align: center;
            background-color: #e74c3c;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        a:hover {
            background-color: #c0392b;
            box-shadow: 0 0 8px rgba(192, 57, 43, 0.5);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="section">
        <h2>Users</h2>
        <ul>
            <?php foreach ($users as $user): ?>
                <li>
                    <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" name="delete_user">Delete User</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="section">
        <h2>Books</h2>
        <ul>
            <?php foreach ($books as $book): ?>
                <li>
                    <?= htmlspecialchars($book['title']) ?> by User ID <?= htmlspecialchars($book['author_id']) ?>
                    <form method="post">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="delete_book">Delete Book</button>
                    </form>

                    <!-- Fetch and display reviews for the book -->
                    <ul>
                        <?php
                        $reviews = $pdo->prepare('SELECT * FROM reviews1 WHERE book_id = ?');
                        $reviews->execute([$book['id']]);
                        $reviews = $reviews->fetchAll();

                        foreach ($reviews as $review): ?>
                            <li>
                                Review ID <?= htmlspecialchars($review['id']) ?>: <?= htmlspecialchars($review['review_text']) ?>
                                <form method="post">
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                    <button type="submit" name="delete_review">Delete Review</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div style="text-align:center;">
    <a href="index.php">Logout</a>
</div>
</body>
</html>

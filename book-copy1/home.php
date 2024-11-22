<?php
require 'db.php';

// Start by checking if there's a search term
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $stmt = $pdo->prepare('SELECT books.id, books.title, books.description, books.file_link, books.file_path, users.username 
                           FROM books 
                           JOIN users ON books.author_id = users.id 
                           WHERE books.title LIKE ? OR books.description LIKE ? OR books.genre LIKE ?');
    $searchTermWithWildcards = "%$searchTerm%";
    $stmt->execute([$searchTermWithWildcards, $searchTermWithWildcards, $searchTermWithWildcards]);
} else {
    // If no search term, fetch all books
    $stmt = $pdo->query('SELECT books.id, books.title, books.description, books.file_link, books.file_path, users.username FROM books JOIN users ON books.author_id = users.id');
}

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eBook Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            background-image: url('images/bg.png');
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
            background: #333;
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
            text-decoration: none;
            text-transform: uppercase;
            font-size: 16px;
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
        }
        header #branding h1 {
            margin: 0;
            font-size: 24px;
        }
        header nav {
            float: right;
            margin-top: 10px;
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
            background-color: #77aaff;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .search-bar input[type="submit"]:hover {
            background-color: #55aaff;
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
        .book-list iframe {
            width: 100%;
            height: 300px;
            border: none;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .book-list iframe:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        /* Media Query for Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 0 20px;
            }
            header li {
                float: none;
                display: block;
                text-align: center;
                padding: 10px 0;
            }
            header nav {
                float: none;
                margin-top: 0;
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
            .book-list iframe {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1>Bookscapes</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="admin.php">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <div class="search-bar">
            <form action="index.php" method="get">
                <input type="text" name="search" placeholder="Search for books..." value="<?= htmlspecialchars($searchTerm) ?>">
                <input type="submit" value="Search">
            </form>
        </div>
        <h1>Book List</h1>
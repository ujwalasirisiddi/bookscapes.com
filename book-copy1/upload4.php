<?php
require 'db.php';
require 'C:/xampp/htdocs/book-copy/vendor/autoload.php';

session_start();

$upload_success = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    $author_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Validate inputs
    if (empty($title) || empty($description) || empty($genre) || empty($author_id)) {
        $upload_error = "All fields are required, and you must be logged in.";
    } else {
        // Handle book file upload
        $book_file_name = basename($_FILES["book"]["name"]);
        $uploadDir = 'uploads/';
        $book_file_path = $uploadDir . $book_file_name;
        $uploadOk = 1;
        $book_fileType = strtolower(pathinfo($book_file_name, PATHINFO_EXTENSION));

        if ($_FILES["book"]["error"] !== UPLOAD_ERR_OK) {
            $upload_error = "Book file upload error: " . $_FILES["book"]["error"];
            $uploadOk = 0;
        }

        if ($_FILES["book"]["size"] > 5000000) {
            $upload_error = "Sorry, your book file is too large.";
            $uploadOk = 0;
        }

        if ($book_fileType != "pdf" && $book_fileType != "epub" && $book_fileType != "mobi") {
            $upload_error = "Sorry, only PDF, EPUB, and MOBI files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            $upload_error = "Sorry, your book file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["book"]["tmp_name"], $book_file_path)) {
                // No prefix is added; original file name is used
                $fileToSave = $book_file_name;

                if ($book_fileType == "pdf") {
                    $watermarked_file = $uploadDir . $fileToSave;
                    addBackgroundToPDF($book_file_path, $watermarked_file);
                    $fileToSave = $book_file_name; // Use original file name
                }

                // Handle thumbnail image upload
                $thumbnail_file_name = basename($_FILES["thumbnail"]["name"]);
                $thumbnail_uploadDir = 'uploads/thumbnails/';
                $thumbnail_file_path = $thumbnail_uploadDir . $thumbnail_file_name;
                $thumbnail_fileType = strtolower(pathinfo($thumbnail_file_name, PATHINFO_EXTENSION));
                $thumbnailToSave = '';

                if (!empty($thumbnail_file_name)) {
                    if ($_FILES["thumbnail"]["size"] > 2000000) {
                        $upload_error = "Sorry, your thumbnail image is too large.";
                    } elseif (!in_array($thumbnail_fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $upload_error = "Sorry, only JPG, JPEG, PNG, and GIF files are allowed for thumbnail.";
                    } elseif ($_FILES["thumbnail"]["error"] !== UPLOAD_ERR_OK) {
                        $upload_error = "Thumbnail upload error: " . $_FILES["thumbnail"]["error"];
                    } else {
                        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $thumbnail_file_path)) {
                            // Thumbnail uploaded successfully
                            $thumbnailToSave = $thumbnail_uploadDir . $thumbnail_file_name;
                        } else {
                            $upload_error = "Sorry, there was an error uploading your thumbnail image.";
                        }
                    }
                }

                try {
                    $stmt = $pdo->prepare('INSERT INTO uploaded_books (title, author_id, description, file_name, genre, thumbnail) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $author_id, $description, $fileToSave, $genre, $thumbnailToSave]);

                    $stmt = $pdo->prepare('INSERT INTO books1 (title, author_id, description, file_name, genre, thumbnail) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $author_id, $description, 'uploads/' . $fileToSave, $genre, $thumbnailToSave]);

                    $fileUrl = 'http://localhost/book-copy/uploads/' . urlencode($fileToSave);

                    $upload_success = "Book uploaded successfully.";
                } catch (PDOException $e) {
                    $upload_error = "Database error: " . $e->getMessage();
                }
            } else {
                $upload_error = "Sorry, there was an error uploading your book file.";
            }
        }
    }
}

use setasign\Fpdi\Fpdi;

function addBackgroundToPDF($input_file, $output_file) {
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($input_file);

    // Path to your background image
    $backgroundImage = 'images\bookscapes (2).png';

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $pageWidth = $size['width'];
        $pageHeight = $size['height'];

        // Add a new page with the same dimensions as the original
        $pdf->AddPage($size['orientation'], [$pageWidth, $pageHeight]);

        // Add the background image
        $pdf->Image($backgroundImage, 0, 0, $pageWidth, $pageHeight, '', '', '', true, 300, '', true, false, 0);

        // Use the imported page as a template
        $pdf->useTemplate($templateId);
    }

    // Output the PDF with the image in the background
    $pdf->Output('F', $output_file);
}

// HTML and CSS for the form and book listing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Homepage</title>
    <style>
        /* Your existing CSS styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
            background-image: url('images/bg1.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }
        .container {
            max-width: 600px;
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
            font-size: 14px;
            color: #333;
        }
        form input[type="text"], 
        form textarea {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        form input[type="text"]:focus, 
        form textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
        }
        form textarea {
            resize: vertical;
            min-height: 120px;
        }
        form input[type="file"] {
            margin-bottom: 15px;
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
        .success-message {
            color: #28a745;
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            border-radius: 5px;
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
        .book-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .book-tile {
            background-color: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .thumbnail img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .book-info h2 {
            font-size: 22px;
            color: #007bff;
            margin: 0 0 10px 0;
        }
        .book-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .button-group a, 
        .like-button button {
            display: inline-block;
            margin-right: 10px;
            padding: 8px 12px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .button-group a:hover, 
        .like-button button:hover {
            background-color: #0056b3;
            box-shadow: 0 0 8px rgba(0, 86, 179, 0.5);
        }
        .reviews h3 {
            font-size: 16px;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .reviews p {
            font-size: 14px;
            color: #333;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Your Book</h1>
        <?php if ($upload_success): ?>
            <p class="success-message"><?= $upload_success ?></p>
        <?php elseif ($upload_error): ?>
            <p class="error-message"><?= $upload_error ?></p>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="title">Book Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="description">Book Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="genre">Genre:</label>
            <input type="text" id="genre" name="genre" required>

            <label for="book">Upload Book (PDF, EPUB, MOBI):</label>
            <input type="file" id="book" name="book" accept=".pdf,.epub,.mobi" required>

            <label for="thumbnail">Upload Thumbnail (JPG, PNG, GIF):</label>
            <input type="file" id="thumbnail" name="thumbnail" accept=".jpg,.jpeg,.png,.gif">

            <button type="submit">Upload Book</button>
        </form>
    </div>

        </body>
        </html>
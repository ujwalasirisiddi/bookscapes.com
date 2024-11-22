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
        // Handle file upload
        $file_name = basename($_FILES["book"]["name"]);
        $uploadDir = 'uploads/';
        $file_path = $uploadDir . $file_name;
        $uploadOk = 1;
        $fileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION)); // Define $fileType

        if ($_FILES["book"]["error"] !== UPLOAD_ERR_OK) {
            $upload_error = "File upload error: " . $_FILES["book"]["error"];
            $uploadOk = 0;
        }

        if ($_FILES["book"]["size"] > 5000000) {
            $upload_error = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        if ($fileType != "pdf" && $fileType != "epub" && $fileType != "mobi") {
            $upload_error = "Sorry, only PDF, EPUB, and MOBI files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            $upload_error = "Sorry, your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["book"]["tmp_name"], $file_path)) {
                if ($fileType == "pdf") {
                    // Apply background image directly to the original file
                    addBackgroundToPDF($file_path, $file_path);
                }

                try {
                    $stmt = $pdo->prepare('INSERT INTO uploaded_books (title, author_id, description, file_name, genre) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $author_id, $description, $file_name, $genre]);

                    $stmt = $pdo->prepare('INSERT INTO books1 (title, author_id, description, file_name, genre) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $author_id, $description, 'uploads/' . $file_name, $genre]);

                    $fileUrl = 'http://localhost/book-copy/uploads/' . urlencode($file_name);

                    $upload_success = "Book uploaded successfully.";
                } catch (PDOException $e) {
                    $upload_error = "Database error: " . $e->getMessage();
                }
            } else {
                $upload_error = "Sorry, there was an error uploading your file.";
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
        // Adjust the x, y, width, and height as needed
        $pdf->Image($backgroundImage, 0, 0, $pageWidth, $pageHeight, '', '', '', true, 300, '', true, false, 0);

        // Use the imported page as a template
        $pdf->useTemplate($templateId);
    }

    // Output the PDF with the image in the background
    $pdf->Output('F', $output_file);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Book</title>
    <style>
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
        .home-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .home-button:hover {
            background-color: #0056b3;
            box-shadow: 0 0 8px rgba(0, 86, 179, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($message)): ?>
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
        <?php else: ?>
            <a href="indexcopy1.php" class="home-button">Home</a>
            <h1>Upload a New Book</h1>
            <?php if (!empty($upload_success)): ?>
                <p class="success-message"><?= htmlspecialchars($upload_success) ?></p>
            <?php elseif (!empty($upload_error)): ?>
                <p class="error-message"><?= htmlspecialchars($upload_error) ?></p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" required>

                <label for="description">Description:</label>
                <textarea name="description" id="description" required></textarea>

                <label for="genre">Genre:</label>
                <input type="text" name="genre" id="genre" required>

                <label for="book">Upload Book File:</label>
                <input type="file" name="book" id="book" required>

                <button type="submit">Upload</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

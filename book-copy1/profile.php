<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = false;

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = './uploads/';
            $dest_path = $uploadFileDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
                $stmt->execute([$dest_path, $user_id]);

                $success_message = 'Profile picture updated successfully';
                $redirect = true;
            } else {
                $error_message = 'Error uploading the file, please try again.';
            }
        } else {
            $error_message = 'Upload failed. Allowed file types: jpg, jpeg, png, gif.';
        }
    }

    // Handle username update
    if (isset($_POST['new_username'])) {
        $new_username = trim($_POST['new_username']);

        if (!empty($new_username)) {
            $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
            if ($stmt->execute([$new_username, $user_id])) {
                $success_message = 'Username updated successfully';
                $redirect = true;
            } else {
                $error_message = 'Failed to update username. Please try again.';
            }
        } else {
            $error_message = 'Username cannot be empty.';
        }
    }

    // Handle password change
    if (isset($_POST['current_password']) && isset($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        // Fetch current password from database
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update to the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($stmt->execute([$new_password_hash, $user_id])) {
                $success_message = 'Password changed successfully';
                $redirect = true;
            } else {
                $error_message = 'Failed to change password. Please try again.';
            }
        } else {
            $error_message = 'Current password is incorrect.';
        }
    }

    // Handle book deletion
    if (isset($_POST['delete_book']) && isset($_POST['delete_book_file_name'])) {
        $delete_book_file_name = $_POST['delete_book_file_name'];

        if (!empty($delete_book_file_name)) {
            try {
                // Prepare statement to delete the book from books1
                $stmt = $pdo->prepare('DELETE FROM books1 WHERE file_name = ? AND author_id = ?');
                $stmt->execute([$delete_book_file_name, $user_id]);

                if ($stmt->rowCount() > 0) {
                    // Optionally delete the file from the server
                    $filePath = './uploads/' . $delete_book_file_name;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $success_message = 'Book deleted successfully';
                    $redirect = true;
                } else {
                    $error_message = 'Failed to delete the book. It might not exist or you do not have permission to delete it.';
                }
            } catch (PDOException $e) {
                $error_message = 'Failed to delete the book. Please try again.';
                error_log("Deletion error: " . $e->getMessage());
            }
        } else {
            $error_message = 'Invalid book file name.';
        }
    }

    // Redirect to the same page to avoid resubmission warnings
    if ($redirect) {
        header('Location: profile.php');
        exit();
    }
}

// Fetch user data
$stmt = $pdo->prepare('SELECT username, profile_picture FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found.');
}

// Fetch user uploaded books from the books1 table
$stmt = $pdo->prepare('SELECT title, file_name, upload_date FROM books1 WHERE author_id = ? ORDER BY upload_date DESC');
$stmt->execute([$user_id]);
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - eBook Management System</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(115deg, #56d8e4 10%, #9f01ea 90%);
            background-image: url('images/bg1.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            color: #333;
            margin: 0;
            padding: 0;
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

/* Align the navigation links to the right */
nav {
    margin-left: auto;
}

nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: row-reverse;
    font-size: 20px;
}

nav ul li {
    margin-left: 20px;
}

nav ul li a {
    text-decoration: none;
    color: #fff; /* White text color for navigation links */
    font-weight: 500;
}

        
        .container {
            max-width: 800px;
            background: #fff;
            width: 100%;
            padding: 25px;
            margin: 20px auto;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .container img.profile-picture {
            display: block;
            margin: 0 auto;
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 2px solid #ddd;
        }
        .container .username {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            margin-top: 10px;
        }
        .container form {
            margin-top: 20px;
        }
        .container form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .container form input[type="file"],
        .container form input[type="password"],
        .container form input[type="submit"],
        .container form input[type="text"] {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .container form input[type="submit"] {
            background: #56d8e4;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            width: 200px;
        }
        .container form input[type="submit"]:hover {
            background: #45c2d0;
        }
        .message {
            text-align: center;
            font-size: 16px;
            margin-top: 20px;
        }
        .message p {
            margin: 0;
        }
        .message .success {
            color: #4caf50;
        }
        .message .error {
            color: #f44336;
        }
        .uploads ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .uploads ul li {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .uploads ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }
        .uploads ul li form {
            margin: 0;
        }
        .uploads ul li button {
            background: #ff4d4d;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }
        .uploads ul li button:hover {
            background: #ff1a1a;
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
    </style>
</head>
<body>
    <header>
        <div class="header1.container">
            <div id="branding"></div>
            <nav>
                <ul>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="indexcopy1.php">Home</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <h2>Profile</h2>

        <?php if (isset($success_message)): ?>
            <div class="message">
                <p class="success"><?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message">
                <p class="error"><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Display profile picture -->
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-picture">
        <?php endif; ?>
        <p class="username"><?= htmlspecialchars($user['username']) ?></p>

        <form action="profile.php" method="post" enctype="multipart/form-data">
            <label for="profile_picture">Upload Profile Picture:</label>
            <input type="file" name="profile_picture" id="profile_picture">
            <input type="submit" value="Upload Picture">
        </form>

        <form action="profile.php" method="post">
            <label for="new_username">Change Username:</label>
            <input type="text" name="new_username" id="new_username" placeholder="enter your new username...">
            <input type="submit" value="Update Username">
        </form>

        <form action="profile.php" method="post">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required>
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <input type="submit" value="Change Password">
        </form>

        <div class="uploads">
            <h3>Your Uploaded Books</h3>
            <ul>
                <?php foreach ($uploads as $upload): ?>
                    <li>
                        <a href="<?= htmlspecialchars($upload['file_name']) ?>" read><?= htmlspecialchars($upload['title']) ?></a>
                        <form action="profile.php" method="post" style="display: inline;">
                            <input type="hidden" name="delete_book_file_name" value="<?= htmlspecialchars($upload['file_name']) ?>">
                            <button type="submit" name="delete_book">Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>




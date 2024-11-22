<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Fetch the current password hash from the database
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_password, $user['password'])) {
        // Hash the new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        if ($stmt->execute([$new_password_hash, $user_id])) {
            $success_message = 'Password updated successfully.';
        } else {
            $error_message = 'Failed to update the password. Please try again.';
        }
    } else {
        $error_message = 'Current password is incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - eBook Management System</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(115deg, #56d8e4 10%, #9f01ea 90%);
            background-image: url('images/bg.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            background: #fff;
            width: 100%;
            padding: 25px;
            margin: 100px auto;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            text-align: center;
        }
        .container h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        form {
            margin-top: 20px;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        form input[type="password"],
        form input[type="submit"] {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        form input[type="submit"] {
            background: #56d8e4;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        form input[type="submit"]:hover {
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
        .message p.success {
            color: green;
        }
        .message p.error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        <form action="change_password.php" method="post">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <input type="submit" value="Change Password">
        </form>

        <div class="message">
            <?php if (isset($success_message)): ?>
                <p class="success"><?= htmlspecialchars($success_message) ?></p>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <p class="error"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

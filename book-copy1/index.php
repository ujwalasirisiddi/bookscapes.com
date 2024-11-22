<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookscapes</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100vh;
            background-color: #02111f;
        }
        .background-image {
            flex: 1;
            height: 100%;
            background-image: url('images/indexpage.jpg'); /* Replace with your background image path */
            background-size: cover;
            background-position: left center;
            background-repeat: no-repeat;
            width: 120%;
        }
        .nav-links {
            flex: 1;
            display: flex;
            flex-direction: column; /* Stack buttons vertically */
            justify-content: center;
            align-items: center;
            padding-right: 50px;
        }
        .nav-links a {
            color: #333;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 20px;
            padding: 10px 20px;
            margin-bottom: 20px; /* Space between buttons */
            border-radius: 5px;
            background-color: #77aaff;
            transition: all 0.3s ease;
            width:50%;
            text-align:center;
            font-weight: bolder;
        }
        .nav-links a:hover {
            background-color: #55aaff;
            box-shadow: 0 0 10px rgba(119, 170, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="background-image"></div> <!-- Background Image on the left -->
    <div class="nav-links">
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
        <a href="contact.php">Contact us</a>
        <a href="about.php">about us</a>
        <a href="admin.php">admin</a>
    </div>
</body>
</html>

   
      
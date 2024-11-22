<?php
require 'db.php';
session_start();
use PHPMailer\PHPMailer\PHPMailer;
    
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(50)); // Generate a random token
        $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?');
        $stmt->execute([$token, $email]);

        $resetLink = "http://yourwebsite.com/reset_password.php?token=" . $token;

        // Send the email
        $subject = 'Password Reset Request';
        $message = "Please click on the following link to reset your password: " . $resetLink;
        $headers = 'From: noreply@yourwebsite.com' . "\r\n" .
                   'Reply-To: noreply@yourwebsite.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        mail($email, $subject, $message, $headers);

        $success_message = 'A password recovery link has been sent to your email address.';
    } else {
        $error_message = 'No account found with that email address.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data here

    // After processing, send the email


    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';
        $mail->Password   = 'your-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'Mailer');
        $mail->addAddress('recipient@example.com', 'Recipient');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - eBook Management System</title>
    <style>
    @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
* {
    margin: 0;
    padding: 0;
    outline: none;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 10px;
    background: linear-gradient(115deg, #56d8e4 10%, #9f01ea 90%);
    background-image: url('images/bg1.jpg'); /* Use the same background image as the login page */
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}

.container {
    max-width: 800px;
    background: #fff;
    width: 100%;
    padding: 25px 40px 10px 40px;
    box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
}

.container .text {
    text-align: center;
    font-size: 41px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    background: -webkit-linear-gradient(right, #56d8e4, #9f01ea, #56d8e4, #9f01ea);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.container form {
    padding: 30px 0 0 0;
}

.container form .form-row {
    display: flex;
    margin: 32px 0;
}

form .form-row .input-data {
    width: 100%;
    height: 40px;
    margin: 0 20px;
    position: relative;
}

.input-data input {
    display: block;
    width: 100%;
    height: 100%;
    border: none;
    font-size: 17px;
    border-bottom: 2px solid rgba(0,0,0, 0.12);
}

.input-data input:focus ~ label, 
.input-data input:valid ~ label {
    transform: translateY(-20px);
    font-size: 14px;
    color: #3498db;
}

.input-data label {
    position: absolute;
    pointer-events: none;
    bottom: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.input-data .underline {
    position: absolute;
    bottom: 0;
    height: 2px;
    width: 100%;
}

.input-data .underline:before {
    position: absolute;
    content: "";
    height: 2px;
    width: 100%;
    background: #3498db;
    transform: scaleX(0);
    transform-origin: center;
    transition: transform 0.3s ease;
}

.input-data input:focus ~ .underline:before,
.input-data input:valid ~ .underline:before {
    transform: scale(1);
}

.submit-btn .input-data {
    overflow: hidden;
    height: 45px!important;
    width: 25%!important;
}

.submit-btn .input-data .inner {
    height: 100%;
    width: 300%;
    position: absolute;
    left: -100%;
    background: -webkit-linear-gradient(right, #56d8e4, #9f01ea, #56d8e4, #9f01ea);
    transition: all 0.4s;
}

.submit-btn .input-data:hover .inner {
    left: 0;
}

.submit-btn .input-data input {
    background: none;
    border: none;
    color: #fff;
    font-size: 17px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    position: relative;
    z-index: 2;
}

a {
    text-decoration: none;
    color: #3498db;
}

@media (max-width: 700px) {
    .container .text {
        font-size: 30px;
    }
    .container form {
        padding: 10px 0 0 0;
    }
    .container form .form-row {
        display: block;
    }
    form .form-row .input-data {
        margin: 35px 0!important;
    }
    .submit-btn .input-data {
        width: 40%!important;
    }
}
</style>

</head>
<body>
<div class="container">
    <div class="text">Forgot Password</div>
    <form method="post">
        <div class="form-row">
            <div class="input-data">
                <input type="email" name="email" required>
                <div class="underline"></div>
                <label for="">Enter Your Registered Email</label>
            </div>
        </div>
        <div class="form-row submit-btn">
            <div class="input-data">
                <div class="inner"></div>
                <input type="submit" value="Submit">
            </div>
        </div>
        <?php if (isset($error_message)): ?>
            <p style="color: red; text-align: center;"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif (isset($success_message)): ?>
            <p style="color: green; text-align: center;"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>

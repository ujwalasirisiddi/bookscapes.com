<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f06, #4a90e2);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #fff;
            background-image: url('images/bg1.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        .about-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            text-align: center;
        }

        h1, h2 {
            margin-bottom: 20px;
            color: #333;
        }

        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #333;
        }

        .team {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .team-member {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 250px;
            text-align: center;
            margin: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .team-member img {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .team-member h3 {
            margin: 10px 0;
            color: #333;
        }

        .team-member p {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="about-container">
        <h1>About Us</h1>
        <p>Welcome to Our Company! We are dedicated to providing the best service possible. Our team of professionals is committed to making sure your experience with us is exceptional. We value quality, integrity, and customer satisfaction above all.</p>
        <p>Our mission is to deliver outstanding results and build long-lasting relationships with our clients. We strive to innovate and improve continuously, ensuring that we stay at the forefront of our industry.</p>
        <h2>Our Team</h2>
        <div class="team">
            <div class="team-member">
                <img src="images/1.jpg" alt="Team Member 1">
                <h3>John Doe</h3>
                <p>CEO & Founder</p>
            </div>
            <div class="team-member">
                <img src="images/4.jpg" alt="Team Member 2">
                <h3>Jane Smith</h3>
                <p>Chief Operating Officer</p>
            </div>
            <div class="team-member">
                <img src="images/3.jpg" alt="Team Member 3">
                <h3>Robert Brown</h3>
                <p>Head of Marketing</p>
            </div>
            <div class="team-member">
                <img src="images/2.jpg" alt="Team Member 4">
                <h3>Emily Johnson</h3>
                <p>Lead Developer</p>
            </div>
            <div class="team-member">
                <img src="images/5.jpg" alt="Team Member 5">
                <h3>Michael Williams</h3>
                <p>Customer Support Manager</p>
            </div>
        </div>
    </div>
</body>
</html>

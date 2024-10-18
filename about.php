<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        /* Container styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
            font-family: 'Georgia', serif;
        }

        /* Section styles for alternating images and text */
        .section {
            display: flex;
            align-items: center;
            margin-bottom: 50px;
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #5cb85c;
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .section img {
            width: 45%;
            border-radius: 10px;
            margin-right: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 4px solid #eee;
        }

        .section:nth-child(even) img {
            order: 2; /* Reverse the order for even sections */
            margin-right: 0;
            margin-left: 20px;
        }

        .section img:hover {
            transform: scale(1.05);
            box-shadow: 0px 6px 14px rgba(0, 0, 0, 0.3);
        }

        .section p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            flex: 1;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        /* Borders for each section */
        .section p {
            border-left: 5px solid #5cb85c;
        }

        /* Fade-in animation */
        .fade-in {
            opacity: 0;
            animation: fadeIn ease 2s forwards;
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

    </style>
</head>
<body>

    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="track.php">Track Your Order</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="login.php">Log In</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>About Us</h2>

        <!-- Section 1 -->
        <div class="section fade-in">
            <img src="https://pbs.twimg.com/media/GYAnzClb0AIvdvW?format=jpg&name=large" alt="Image 1">
            <p>Welcome to Mamta Charitable Trust, a Non-Governmental Organization (NGO) that focuses on empowering communities through sustainable development initiatives. Our mission is to uplift marginalized groups and provide opportunities for education, healthcare, and community development, helping them lead dignified lives.</p>
        </div>

        <!-- Section 2 -->
        <div class="section fade-in">
            <img src="https://pbs.twimg.com/media/GYAnzChbUAAtC-i?format=jpg&name=large" alt="Image 2">
            <p>Our work is centered around empowering rural and tribal populations by promoting sustainable agriculture, supporting women's education, and enhancing healthcare access. Through collaboration with local leaders, we aim to create a lasting positive impact on society.</p>
        </div>

        <!-- Section 3 -->
        <div class="section fade-in">
            <img src="https://pbs.twimg.com/media/GYAoO4Db0AIy_O8?format=jpg&name=large" alt="Image 3">
            <p>With over a decade of experience, Mamta Charitable Trust has been instrumental in transforming lives across various villages. Our team works tirelessly to ensure the most vulnerable are not left behind and that everyone gets an opportunity to thrive and grow.</p>
        </div>

        <!-- Section 4 -->
        <div class="section fade-in">
            <img src="https://pbs.twimg.com/media/GYAoO4AaAAACN-_?format=jpg&name=large" alt="Image 4">
            <p>Our key projects focus on women’s empowerment, especially in providing vocational training and education. We have helped numerous women in these regions start small businesses, improving both their income and self-sufficiency.</p>
        </div>

        <!-- Section 5 -->
        <div class="section fade-in">
            <img src="https://pbs.twimg.com/media/GYAo3b6b0AENu6p?format=jpg&name=medium" alt="Image 5">
            <p>At Mamta Charitable Trust, we believe in the potential of education to bring about change. Our education initiatives provide children in remote areas with the tools and resources they need to succeed, laying the foundation for a brighter future.</p>
        </div>

    </div>

        <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
        </footer>
</body>
</html>


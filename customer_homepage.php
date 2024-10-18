<?php
session_start();
require_once 'dbConn.php'; 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve user's name from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT FirstName, LastName FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

//Image URLs
$image_urls = [
    "https://cdn.britannica.com/17/196817-159-9E487F15/vegetables.jpg",
    "https://www.healthyeating.org/images/default-source/home-0.0/nutrition-topics-2.0/general-nutrition-wellness/2-2-2-3foodgroups_fruits_detailfeature_thumb.jpg?sfvrsn=7abe71fe_4",
    "https://www.foodnavigator.com/var/wrbm_gb_food_pharma/storage/images/9/7/8/0/900879-1-eng-GB/Interconnected-diets-Two-thirds-of-crops-we-consume-are-result-of-food-globalisation.jpg",  
    "https://d1g9yur4m4naub.cloudfront.net/images/Article_Images/ImageForArticle_712_16449323718033258.jpg"
]; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Homepage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="customer_homepage.php">Home</a></li>
                <li><a href="products_LoggedIn.php">Products</a></li>
                <li><a href="track_LoggedIn.php">Track Your Order</a></li>
                <li><a href="cart.php">View Cart</a></li> 
                <li><a href="notifications.php">View Notifications</a></li>  
                <li><a href="pre_order.php">Pre-Order</a></li> 
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav> 
    </header>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>!</h1>
        <p>We're glad to see you here.</p>
        
        <main>
        <section class="hero"> 
            <h2>Fresh Produce Delivered to Your Doorstep</h2>
            <p>Buy fresh produce directly from local farmers and support the community.</p> 
            <a href="products_LoggedIn.php" class="btn">Shop Now</a> 
        </section>
        
        <section class="slideshow-container">
            <?php foreach ($image_urls as $index => $url): ?>
                <div class="mySlides fade">
                    <img src="<?php echo htmlspecialchars($url); ?>" style="width: 70%">
                </div>
            <?php endforeach; ?>
        </section>

        <section class="features">
            <div class="feature">
                <h3>Eco-Friendly</h3>
                <p>All our products are sourced sustainably, ensuring minimal impact on the environment.</p>
            </div>
            <div class="feature">
                <h3>Support Farmers</h3>
                <p>50% of our proceeds go back to the farmers, supporting their livelihood.</p>
            </div>
            <div class="feature">
                <h3>Wide Variety</h3>
                <p>We offer a wide variety of fresh produce to meet all your needs.</p>
            </div>
        </section>
        <section class="statistics">
            <h3>Our Impact</h3>
            <p>Join the thousands of customers who have chosen to support local farmers.</p>
            <ul>
                <li>Over 10,000 satisfied customers</li>
                <li>More than 50 local farmers supported</li>
                <li>100% organic and fresh produce</li>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>

    <script>
        let slideIndex = 0;
        showSlides();

        function showSlides() {
            let slides = document.getElementsByClassName("mySlides");
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slideIndex++;
            if (slideIndex > slides.length) {slideIndex = 1}
            slides[slideIndex - 1].style.display = "block";
            setTimeout(showSlides, 3000); // Change image every 3 seconds
        }
    </script>
</body>
</html>

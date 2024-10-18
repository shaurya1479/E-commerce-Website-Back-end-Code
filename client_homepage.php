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
                <li><a href="client_homepage.php">Home</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="manage_order.php">Manage Orders</a></li>
                <li><a href="view_monthly_reports.php">View Monthly Report</a></li> 
                <li><a href="notifications_client.php">View Notifications</a></li>  
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav> 
    </header>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>!</h1>
        <p>We're glad to see you here.</p>
        
    <main>
        <section class="hero"> 
            <h2>Manage Your Products and View Reports</h2>
            <p>Efficiently manage your product listings, view monthly reports, and stay updated with notifications.</p> 
        </section>
        
         <section
            <div class="slideshow-container">
                <?php foreach ($image_urls as $index => $url): ?>
                    <div class="mySlides fade">
                        <img src="<?php echo htmlspecialchars($url); ?>" style="width:70%">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

         <section class="features">
            <div class="feature">
                <h3>Efficient Management</h3>
                <p>Easily add, update, and manage your products.</p>
            </div>
            <div class="feature">
                <h3>Detailed Reports</h3>
                <p>Access monthly reports to monitor your sales and performance.</p>
            </div>
            <div class="feature">
                <h3>Stay Informed</h3>
                <p>Get notifications on important updates and actions required.</p>
            </div>
        </section>
    </div>
    </main> 

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
    
    <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
    </footer>
    
</body>
</html>


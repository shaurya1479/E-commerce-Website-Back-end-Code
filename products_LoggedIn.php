<?php
include 'dbConn.php'; 

// Initialize search and filter variables
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Fetch products from the database
$sql = "SELECT * FROM Products WHERE Name LIKE '%$search%'";
if ($filter != 'all') {
    $sql .= " AND Category='$filter'";
}
$result = $conn->query($sql);

$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $user_name = $_POST['user_name'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $date_posted = date("Y-m-d H:i:s");

    // Check for empty fields
    if (empty($product_id) || empty($user_name) || empty($rating) || empty($comment)) {
        echo "<script>alert('All fields are required.');</script>";
    } else {
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("INSERT INTO Reviews (Name, ProductID, Rating, Comment, DatePosted) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("sisss", $user_name, $product_id, $rating, $comment, $date_posted);

        if ($stmt->execute()) {
            echo "<script>alert('Review added successfully.');</script>";
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    }
}

// Handle loading more reviews
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['load_more'])) {
    $product_id = $_POST['product_id'];
    $loaded_reviews[$product_id] = $_POST['loaded_reviews'];
} else {
    $loaded_reviews = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - MyWebsite</title>
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
    <main>
        <h1>Our Products</h1>

        <form method="GET" action="products_LoggedIn.php" class="search-form">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search for products...">
            <select name="filter">
                <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>All</option>
                <option value="Fruit" <?php if ($filter == 'Fruit') echo 'selected'; ?>>Fruit</option>
                <option value="Vegetable" <?php if ($filter == 'Vegetable') echo 'selected'; ?>>Vegetable</option>
                <option value="Crop" <?php if ($filter == 'Crop') echo 'selected'; ?>>Crop</option>
            </select>
            <button type="submit">Search</button>
        </form>

        <section class="product-list">
            <?php if (empty($products)) : ?>
                <p>No products available.</p>
            <?php else : ?>
                <?php foreach ($products as $product) : ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['ImageURL']); ?>" alt="Product Image">
                        <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                        <p><em><?php echo htmlspecialchars($product['Description']); ?></em></p>
                        <p>Price: $<?php echo htmlspecialchars($product['Price']); ?> per kg</p>
                        <p>Quantity Available: <?php echo htmlspecialchars($product['Quantity']); ?>kg</p>
                        <p>Category: <?php echo htmlspecialchars($product['Category']); ?></p>

                        <!-- Add to Cart Form -->
                        
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                            <label for="quantity_<?php echo $product['ProductID']; ?>">Quantity (kg):</label>
                            <input type="number" step="0.1" name="quantity" id="quantity_<?php echo $product['ProductID']; ?>" min="0.1" max="<?php echo $product['Quantity']; ?>" required>
                            <button type="submit">Add to Cart</button>
                        </form>
                        
                        <!-- Reviews Section -->
                        <div class="reviews-section">
                            <h4>Reviews:</h4>
                            <div id="reviews_<?php echo $product['ProductID']; ?>">
                                <?php
                                $limit = isset($loaded_reviews[$product['ProductID']]) ? $loaded_reviews[$product['ProductID']] + 2 : 1;

                                $review_sql = "SELECT Name, Rating, Comment, DatePosted FROM Reviews WHERE ProductID = ? LIMIT ?";
                                $review_stmt = $conn->prepare($review_sql);
                                $review_stmt->bind_param("ii", $product['ProductID'], $limit);
                                $review_stmt->execute();
                                $review_result = $review_stmt->get_result();

                                if ($review_result->num_rows > 0) {
                                    while ($review_row = $review_result->fetch_assoc()) {
                                        echo "<div class='review'>";
                                        echo "<strong>" . htmlspecialchars($review_row['Name']) . ":</strong> ";
                                        echo "<p>Rating: " . htmlspecialchars($review_row['Rating']) . "/5</p>";
                                        echo "<p>" . htmlspecialchars($review_row['Comment']) . "</p>";
                                        echo "<small>Posted on: " . htmlspecialchars($review_row['DatePosted']) . "</small>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p>No reviews yet. Be the first to review!</p>";
                                }
                                $review_stmt->close();
                                ?>
                            </div>

                            <!-- Load More Reviews Button -->
                            <?php if ($review_result->num_rows == $limit): ?>
                                <form method="POST" action="products_LoggedIn.php">
                                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                    <input type="hidden" name="loaded_reviews" value="<?php echo $limit; ?>">
                                    <button type="submit" name="load_more">Load More Reviews</button>
                                </form>
                            <?php endif; ?>

                            <!-- Review Form -->
                            <form method="POST" action="products_LoggedIn.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                <input type="text" name="user_name" placeholder="Your name" required>
                                <input type="number" name="rating" placeholder="Rating (1-5)" min="1" max="5" required>
                                <textarea name="comment" placeholder="Your review" required></textarea>
                                <button type="submit" name="submit_review">Submit Review</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>
</body>
</html>

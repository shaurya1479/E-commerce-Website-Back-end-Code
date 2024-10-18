<?php
include 'dbConn.php';
session_start();

// Check if the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $quantity = floatval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $category = htmlspecialchars(trim($_POST['category']));
    $imageURL = htmlspecialchars(trim($_POST['imageURL']));

    if (empty($name) || empty($description) || empty($quantity) || empty($price) || empty($category) || empty($imageURL)) {
        $error_message = "All fields are required.";
    } elseif (!in_array($category, ['fruit', 'vegetable', 'crop'])) {
        $error_message = "Category must be either 'fruit', 'vegetable', or 'crop'.";
    } else {
        // Insert the new product into the database
        $stmt = $conn->prepare("INSERT INTO Products (Name, Description, Quantity, Price, Category, ImageURL) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddss", $name, $description, $quantity, $price, $category, $imageURL);

        if ($stmt->execute()) {
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Error adding product: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - MyWebsite</title>
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

    <main>
        <h1>Add New Product</h1>

        <?php if (!empty($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="POST" action="add_product.php">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity Available (kg):</label>
                <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
                <label for="price">Price per kg ($):</label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="fruit">Fruit</option>
                    <option value="vegetable">Vegetable</option>
                    <option value="crop">Crop</option>
                </select>
            </div>

            <div class="form-group">
                <label for="imageURL">Image URL:</label>
                <input type="url" id="imageURL" name="imageURL" required>
            </div>

            <button type="submit">Add Product</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>
</body>
</html>

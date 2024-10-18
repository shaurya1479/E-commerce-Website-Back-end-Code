<?php
include 'dbConn.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Retrieve the logged-in user's ID
$client_id = 5; // Client's User ID

// Initialize variables
$error = "";
$success = "";
$order_id = null; // Variable to hold the Order ID (tracking number)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_product = $_POST['product'];
    $quantity_ordered = (float) $_POST['quantity'];
    $selected_date = $_POST['delivery_date'];

    // Calculate the minimum allowed date (2 weeks from today)
    $current_date = new DateTime();
    $min_date = (clone $current_date)->modify('+14 days'); // Add 14 days to current date

    // Convert the selected date to a DateTime object
    $selected_date_obj = DateTime::createFromFormat('Y-m-d', $selected_date);

    // Check if the selected date is at least 2 weeks from today
    if ($selected_date_obj < $min_date) {
        $error = "The delivery date must be at least two weeks from today.";
    } elseif ($quantity_ordered <= 0) {
        $error = "The quantity must be greater than 0.";
    } else {
        // Fetch product details
        $product_query = $conn->prepare("SELECT ProductID, Quantity, Price FROM Products WHERE Name = ?");
        $product_query->bind_param("s", $selected_product);
        $product_query->execute();
        $product_result = $product_query->get_result();
        $product = $product_result->fetch_assoc();

        if ($product) {
            $product_id = $product['ProductID'];
            $available_quantity = (float) $product['Quantity'];
            $price_per_kg = (float) $product['Price'];

            // Check if the requested quantity is available
            if ($quantity_ordered > $available_quantity) {
                $error = "The quantity requested exceeds the available stock.";
            } else {
                // Calculate the total amount
                $amount = $quantity_ordered * $price_per_kg;

                // Insert the order into the Orders table
                $order_query = $conn->prepare(
                    "INSERT INTO Orders (UserID, ProductID, QuantityOrdered, OrderDate, DeliveryDate, Amount, Status) 
                    VALUES (?, ?, ?, NOW(), ?, ?, 'order placed')"
                );

                $order_query->bind_param(
                    "iidss", // "i" for user_id, product_id; "d" for quantity_ordered and amount; "s" for delivery_date
                    $user_id,
                    $product_id,
                    $quantity_ordered,
                    $selected_date,
                    $amount
                );

                // Execute the order query
                if ($order_query->execute()) {
                    // Get the last inserted Order ID (tracking number)
                    $order_id = $order_query->insert_id;
                    $success = "Your order has been placed successfully!";

                    // Insert a notification for the user into the Notifications table
                    $notification_content_user = "Your pre-order #{$order_id} has been placed successfully.";
                    $notification_date = date("Y-m-d H:i:s");
                    $read_status = 0; // Unread status

                    $notification_query_user = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
                    $notification_query_user->bind_param("isss", $user_id, $notification_content_user, $notification_date, $read_status);
                    $notification_query_user->execute();

                    // Insert a notification for the client (UserID 5)
                    $notification_content_client = "A new pre-order has been placed! Check the 'Manage Orders' tab to review it.";
                    $notification_query_client = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
                    $notification_query_client->bind_param("isss", $client_id, $notification_content_client, $notification_date, $read_status);
                    $notification_query_client->execute();

                    // Update the product's available quantity
                    $new_quantity = $available_quantity - $quantity_ordered;
                    $update_product_query = $conn->prepare("UPDATE Products SET Quantity = ? WHERE ProductID = ?");
                    $update_product_query->bind_param("di", $new_quantity, $product_id);
                    $update_product_query->execute();
                } else {
                    $error = "Failed to place the order. Error: " . $conn->error;
                }
            }
        } else {
            $error = "The selected product does not exist.";
        }
    }
}

// Fetch products for the dropdown
$products_query = "SELECT Name FROM Products";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order Product - MyWebsite</title>
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
        <h1>Pre-Order Product</h1>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif ($success && $order_id): ?>
            <p class="success"><?php echo $success; ?></p>
            <p>Your tracking number is: <strong><?php echo $order_id; ?></strong></p>
        <?php endif; ?>

        <form method="post" action="pre_order.php">
            <label for="product">Select Product:</label>
            <select id="product" name="product" required>
                <?php while($row = $products_result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['Name']); ?>">
                        <?php echo htmlspecialchars($row['Name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="quantity">Quantity (kg):</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" required>

            <label for="delivery_date">Select Delivery Date (minimum 2 weeks from today):</label>
            <input type="date" id="delivery_date" name="delivery_date" required>

            <button type="submit">Place Order</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>

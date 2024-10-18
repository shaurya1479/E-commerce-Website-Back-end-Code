<?php
session_start();
include 'dbConn.php';

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit;
}

$user_id = $_SESSION['user_id'];

// Retrieve the user's cart ID
$cart_query = "SELECT CartID FROM Cart WHERE UserID = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows == 0) {
    echo "Your cart is empty.";
    exit;
}

$cart_row = $cart_result->fetch_assoc();
$cart_id = $cart_row['CartID'];

// Retrieve the products in the user's cart
$cart_items_query = "
    SELECT ci.QuantityAdded, p.ProductID, p.Price, p.Quantity 
    FROM Cart_Items ci
    JOIN Products p ON ci.ProductID = p.ProductID
    WHERE ci.CartID = ?";
$cart_items_stmt = $conn->prepare($cart_items_query);
$cart_items_stmt->bind_param("i", $cart_id);
$cart_items_stmt->execute();
$cart_items_result = $cart_items_stmt->get_result();

$cart_items = [];
if ($cart_items_result->num_rows > 0) {
    while ($row = $cart_items_result->fetch_assoc()) {
        $cart_items[] = $row;
    }
} else {
    echo "Your cart is empty.";
    exit;
}

// Proceed with checkout
$order_ids = [];
foreach ($cart_items as $item) {
    $product_id = $item['ProductID'];
    $quantity_ordered = (float) ($item['QuantityAdded']); 
    $order_date = date("Y-m-d");
    $delivery_date = date("Y-m-d", strtotime("+1 week"));
    $amount = $item['Price'] * $quantity_ordered;
    $status = "Order Placed";

    // Insert a new order into the Orders table
    $order_query = "
        INSERT INTO Orders (UserID, ProductID, QuantityOrdered, OrderDate, DeliveryDate, Amount, Status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("iidssds", $user_id, $product_id, $quantity_ordered, $order_date, $delivery_date, $amount, $status);
    if ($order_stmt->execute()) {
        $order_ids[] = $order_stmt->insert_id; // Store the OrderID (tracking number)

        // Update the product quantity in the Products table
        $new_quantity = $item['Quantity'] - $quantity_ordered;
        $update_quantity_query = "UPDATE Products SET Quantity = ? WHERE ProductID = ?";
        $update_quantity_stmt = $conn->prepare($update_quantity_query);
        $update_quantity_stmt->bind_param("di", $new_quantity, $product_id);
        $update_quantity_stmt->execute(); 
        
        // Fetch the updated product name from the 'Products' table after quantity update
        $product_name_query = "SELECT Name FROM Products WHERE ProductID = ?";
        $product_name_stmt = $conn->prepare($product_name_query);
        $product_name_stmt->bind_param("i", $product_id);
        $product_name_stmt->execute();
        $product_name_result = $product_name_stmt->get_result();
        $product_name_row = $product_name_result->fetch_assoc();
        $product_name = $product_name_row['Name']; // Store the product name

        // Insert notification for order confirmation
        $notification_content = "Your order #{$order_stmt->insert_id} has been confirmed.";
        $notification_date = date("Y-m-d H:i:s");
        $read_status = 0; // Unread

        $notification_query = "INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)";
        $notification_stmt = $conn->prepare($notification_query);
        $notification_stmt->bind_param("isss", $user_id, $notification_content, $notification_date, $read_status);
        $notification_stmt->execute(); 
        
        //Insert notification for client
        $client_notification_content = "A new order has been placed! Check the 'Manage Orders' tab to view it.";
        $client_notification_date = date("Y-m-d H:i:s");
        $client_read_status = 0; // Unread 
        $client_id = 5;

        $client_notification_query = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
        $client_notification_query->bind_param("isss", $client_id, $client_notification_content, $client_notification_date, $client_read_status);
        $client_notification_query->execute();  
        
        // Check if the new quantity is below 2 kg, and notify the client if it is 
        if ($new_quantity < 2) {
            // Use the product name in the notification content
            $low_inventory_content = "The stock for '$product_name' has dropped below 2 kg.";
            $low_inventory_date = date("Y-m-d H:i:s");
            $low_inventory_read_status = 0; // Unread
            $client_notification_query = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
            $client_notification_query->bind_param("isss", $client_id, $low_inventory_content, $low_inventory_date, $low_inventory_read_status);
            $client_notification_query->execute();
        }
    }
}

// Clear the cart after checkout
$clear_cart_query = "DELETE FROM Cart_Items WHERE CartID = ?";
$clear_cart_stmt = $conn->prepare($clear_cart_query);
$clear_cart_stmt->bind_param("i", $cart_id);
$clear_cart_stmt->execute();

// Display order confirmation and tracking numbers
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Confirmation</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Order Placed Successfully!</h2>
    <p>Thank you for your order. Your tracking numbers are:</p>
    <ul>
        <?php foreach ($order_ids as $order_id): ?>
            <li><?php echo htmlspecialchars($order_id); ?></li>
        <?php endforeach; ?>
    </ul>
    <p>You can use these tracking numbers to track your orders.</p>
    <form action="cart.php">
        <button type="submit">Back to Cart</button>
    </form>
</div>
</body>
</html>

<?php
// Clean up
$order_stmt->close();
$cart_items_stmt->close();
$clear_cart_stmt->close();
$cart_stmt->close();
$conn->close();
?>

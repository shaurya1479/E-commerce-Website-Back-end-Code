<?php
session_start();
include 'dbConn.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit;
}

$user_id = $_SESSION['user_id']; // Retrieve the logged-in user's ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_product = $_POST['product'];
    $quantity_ordered = (float) $_POST['quantity'];
    $selected_date = $_POST['delivery_date'];

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
                VALUES (?, ?, ?, NOW(), ?, ?, 'Order Placed')"
            );
            $order_query->bind_param(
                "iissd",
                $user_id,
                $product_id,
                $quantity_ordered,
                $selected_date,
                $amount
            );

            if ($order_query->execute()) {
                $order_id = $order_query->insert_id; // Get the order ID for tracking

                // Insert notification for order confirmation
                $notification_content = "Your pre-order #{$order_id} has been confirmed.";
                $notification_date = date("Y-m-d H:i:s");
                $read_status = 0; // Unread

                $notification_query = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
                $notification_query->bind_param("isss", $user_id, $notification_content, $notification_date, $read_status);
                $notification_query->execute(); 
                
                //Insert notification for client
                $client_notification_content = "A new order has been placed! Check the 'Manage Orders' tab to view it.";
                $client_notification_date = date("Y-m-d H:i:s");
                $client_read_status = 0; // Unread 
                $client_id = 5;

                $client_notification_query = $conn->prepare("INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, ?, ?)");
                $client_notification_query->bind_param("isss", $client_id, $client_notification_content, $client_notification_date, $client_read_status);
                $client_notification_query->execute(); 
                
                // Update the product's available quantity
                $new_quantity = $available_quantity - $quantity_ordered;
                $update_product_query = $conn->prepare("UPDATE Products SET Quantity = ? WHERE ProductID = ?");
                $update_product_query->bind_param("di", $new_quantity, $product_id);
                $update_product_query->execute();

                // Success message
                $success_message = "Your pre-order has been placed successfully! Your tracking number is: #{$order_id}.";
            } else {
                $error = "Failed to place the order. Please try again.";
            }
        }
    } else {
        $error = "The selected product does not exist.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order Checkout - MyWebsite</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Pre-Order Confirmation</h2>

    <?php if (isset($success_message)): ?>
        <p class="success"><?php echo $success_message; ?></p>
    <?php elseif (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="pre_order.php">
        <button type="submit">Back to Pre-Order</button>
    </form>
</div>
</body>
</html>

<?php
// Clean up
$order_query->close();
$product_query->close();
$notification_query->close();
$update_product_query->close();
$conn->close();
?>

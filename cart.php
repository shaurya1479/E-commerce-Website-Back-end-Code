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
    SELECT ci.QuantityAdded, p.ProductID, p.Name, p.Price, p.Quantity AS AvailableQuantity 
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
    <h2>Your Shopping Cart</h2>

    <?php if (!empty($cart_items)): ?>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity (kg)</th>
                    <th>Price (per kg)</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['Name']); ?></td>
                        <td>
                            <div class="quantity-controls">
                                <form method="post" action="update_cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['ProductID']); ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <input type="hidden" name="available_quantity" value="<?php echo htmlspecialchars($item['AvailableQuantity']); ?>">
                                    <button type="submit">-</button>
                                </form>
                                <span><?php echo number_format($item['QuantityAdded'], 1); ?></span>
                                <form method="post" action="update_cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['ProductID']); ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <input type="hidden" name="available_quantity" value="<?php echo htmlspecialchars($item['AvailableQuantity']); ?>">
                                    <button type="submit">+</button>
                                </form>
                            </div>
                        </td>
                        <td><?php echo '$' . number_format($item['Price'], 2); ?></td>
                        <td><?php echo '$' . number_format($item['Price'] * $item['QuantityAdded'], 2); ?></td>
                        <td>
                            <form method="post" action="update_cart.php">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['ProductID']); ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="checkout">
            <form method="post" action="checkout.php">
                <button type="submit">Check Out</button>
            </form>
        </div>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</div> 
    
    <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
    </footer>
    
</body>
</html>
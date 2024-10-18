<?php
include 'dbConn.php';

$order_id = '';
$order_details = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];

    // SQL query to fetch order details along with the product name
    $order_query = "
        SELECT o.OrderID, o.QuantityOrdered, o.OrderDate, o.DeliveryDate, o.Amount, o.Status, p.Name AS ProductName
        FROM Orders o
        JOIN Products p ON o.ProductID = p.ProductID
        WHERE o.OrderID = ?
    ";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();

    if ($order_result->num_rows > 0) {
        $order_details = $order_result->fetch_assoc();
    } else {
        echo "<p>No order found with that number.</p>";
    }

    $order_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order</title>
    <link rel="stylesheet" href="styles.css">
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
    <h2>Track Your Order</h2>
    <form method="post" action="track.php">
        <label for="order_id">Enter Your Order Number:</label>
        <input type="number" name="order_id" id="order_id" required>
        <button type="submit">Track Order</button>
    </form>

    <?php if (!empty($order_details)): ?>
        <h3>Order Details</h3>
        <table>
            <tr>
                <th>Order ID</th>
                <td><?php echo htmlspecialchars($order_details['OrderID']); ?></td>
            </tr>
            <tr>
                <th>Product Name</th>
                <td><?php echo htmlspecialchars($order_details['ProductName']); ?></td>
            </tr>
            <tr>
                <th>Quantity Ordered</th>
                <td><?php echo number_format($order_details['QuantityOrdered'], 1); ?> kg</td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td><?php echo htmlspecialchars($order_details['OrderDate']); ?></td>
            </tr>
            <tr>
                <th>Estimated Delivery Date</th>
                <td><?php echo htmlspecialchars($order_details['DeliveryDate']); ?></td>
            </tr>
            <tr>
                <th>Amount Paid</th>
                <td>$<?php echo htmlspecialchars($order_details['Amount']); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?php echo htmlspecialchars($order_details['Status']); ?></td>
            </tr>
        </table>
    <?php endif; ?>
        
</div> 
    
        <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
        </footer>
    
</body> 
</html>

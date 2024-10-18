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

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = htmlspecialchars(trim($_POST['status']));

    // Update the order status in the database
    $stmt = $conn->prepare("UPDATE Orders SET Status=? WHERE OrderID=?");
    if ($stmt) {
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            $success_message = "Order status updated successfully!";
        } else {
            $error_message = "Error updating order status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Prepare failed: " . $conn->error;
    }
}

// Fetch the UserID for the given order
$user_query = "SELECT UserID FROM Orders WHERE OrderID = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $order_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_row = $user_result->fetch_assoc();
$user_id = $user_row['UserID'];

// Create notification content
$notification_content = "The status of your order #$order_id has been updated!";

// Insert notification into the Notifications table
$insert_notification_query = "INSERT INTO Notifications (UserID, Content, DateSent, ReadStatus) VALUES (?, ?, NOW(), 0)";
$insert_notification_stmt = $conn->prepare($insert_notification_query);
$insert_notification_stmt->bind_param("is", $user_id, $notification_content);
$insert_notification_stmt->execute();

// Check if the notification was inserted successfully
if ($insert_notification_stmt->affected_rows > 0) {
    echo "User has been notified of the status update.";
} else {
    echo "Failed to notify the user.";
}

$insert_notification_stmt->close();
$user_stmt->close();


// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $order_id = $_POST['order_id'];

    // Delete the order from the database
    $stmt = $conn->prepare("DELETE FROM Orders WHERE OrderID=?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $success_message = "Order deleted successfully!";
        } else {
            $error_message = "Error deleting order: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Prepare failed: " . $conn->error;
    }
}

// Fetch all orders from the database, sorted by order date (oldest first)
$orders_query = "
    SELECT 
        o.OrderID, u.FirstName, u.LastName, p.Name AS ProductName, o.OrderDate, o.QuantityOrdered, o.Amount, o.Status
    FROM 
        Orders o
    JOIN 
        Users u ON o.UserID = u.UserID
    JOIN 
        Products p ON o.ProductID = p.ProductID
    ORDER BY 
        o.OrderDate ASC";

$orders_result = $conn->query($orders_query);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - MyWebsite</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Additional styles for the orders management page */
        .order-row {
            cursor: pointer;
        }
        .order-row:hover {
            background-color: #f0f0f0;
        }
        .status-select {
            padding: 5px;
        }
        .edit-button, .delete-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-right: 5px;
        }
        .edit-button:hover, .delete-button:hover {
            background-color: #45a049;
        }
        .confirm-delete {
            background-color: red;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }
        .confirm-delete button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-right: 5px;
        }
    </style>
    <script>
        function confirmDelete(orderId) {
            const confirmDiv = document.getElementById('confirm-delete-' + orderId);
            confirmDiv.style.display = 'block';
        }
    </script>
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
        <h1>Manage Orders</h1>

        <?php if (!empty($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Product</th>
                    <th>Order Date</th>
                    <th>Quantity Ordered (kg)</th>
                    <th>Amount Paid ($)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <tr class="order-row">
                        <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                        <td><?php echo htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']); ?></td>
                        <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                        <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                        <td><?php echo htmlspecialchars($order['QuantityOrdered']); ?></td>
                        <td><?php echo htmlspecialchars($order['Amount']); ?></td>
                        <td>
                            <form method="POST" action="manage_order.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                <select name="status" class="status-select">
                                    <option value="Order Placed" <?php if ($order['Status'] == 'Order Placed') echo 'selected'; ?>>Order Placed</option>
                                    <option value="Processing" <?php if ($order['Status'] == 'Processing') echo 'selected'; ?>>Processing</option>
                                    <option value="Shipped" <?php if ($order['Status'] == 'Shipped') echo 'selected'; ?>>Shipped</option>
                                    <option value="Completed" <?php if ($order['Status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                </select>
                                <button type="submit" name="update_status" class="edit-button">Update Status</button>
                            </form>
                        </td>
                        <td>
                            <button class="delete-button" onclick="confirmDelete(<?php echo $order['OrderID']; ?>)">Delete</button>
                            <div id="confirm-delete-<?php echo $order['OrderID']; ?>" class="confirm-delete" style="display:none;">
                                <p>Are you sure you want to delete this order?</p>
                                <form method="POST" action="manage_order.php">
                                    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                    <button type="submit" name="confirm_delete">Yes, Delete</button>
                                    <button type="button" onclick="this.parentElement.parentElement.style.display='none';">Cancel</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>
</body>
</html>

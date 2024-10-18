<?php
session_start();
include 'dbConn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 1;

    if (!isset($_SESSION['user_id'])) {
        echo "User not logged in";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Check if the product exists and get the product name
    $query = "SELECT Name, Quantity FROM Products WHERE ProductID = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "Product not found";
        exit;
    }

    $product = $result->fetch_assoc();
    $product_name = $product['Name'];
    $available_quantity = (float)$product['Quantity'];

    // Ensure the requested quantity is not more than available
    if ($quantity > $available_quantity) {
        echo "Requested quantity exceeds available stock";
        exit;
    }

    // Check if the user already has a cart
    $cart_query = "SELECT CartID FROM Cart WHERE UserID = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    if ($cart_result->num_rows > 0) {
        // User has a cart, retrieve the CartID
        $cart_row = $cart_result->fetch_assoc();
        $cart_id = $cart_row['CartID'];
    } else {
        // User does not have a cart, create one
        $create_cart_query = "INSERT INTO Cart (UserID) VALUES (?)";
        $create_cart_stmt = $conn->prepare($create_cart_query);
        $create_cart_stmt->bind_param("i", $user_id);
        $create_cart_stmt->execute();
        $cart_id = $create_cart_stmt->insert_id; // Get the ID of the newly created cart
    }

    // Check if the product is already in the cart
    $cart_item_query = "SELECT CartItemID, QuantityAdded FROM Cart_Items WHERE CartID = ? AND ProductID = ?";
    $cart_item_stmt = $conn->prepare($cart_item_query);
    $cart_item_stmt->bind_param("ii", $cart_id, $product_id);
    $cart_item_stmt->execute();
    $cart_item_result = $cart_item_stmt->get_result();

    if ($cart_item_result->num_rows > 0) {
        // Product is already in the cart, update the quantity
        $cart_item_row = $cart_item_result->fetch_assoc();
        $new_quantity = $cart_item_row['QuantityAdded'] + $quantity;
        $update_quantity_query = "UPDATE Cart_Items SET QuantityAdded = ? WHERE CartItemID = ?";
        $update_quantity_stmt = $conn->prepare($update_quantity_query);
        $update_quantity_stmt->bind_param("di", $new_quantity, $cart_item_row['CartItemID']);
        $update_quantity_stmt->execute();
    } else {
        // Product is not in the cart, insert a new record
        $insert_cart_item_query = "INSERT INTO Cart_Items (CartID, ProductID, QuantityAdded) VALUES (?, ?, ?)";
        $insert_cart_item_stmt = $conn->prepare($insert_cart_item_query);
        $insert_cart_item_stmt->bind_param("iid", $cart_id, $product_id, $quantity);
        $insert_cart_item_stmt->execute();
    }

    // Display success message and redirect after 2 seconds
    echo "Product added to cart";
    header("refresh:2; url=products_LoggedIn.php");
    exit;
}
?>

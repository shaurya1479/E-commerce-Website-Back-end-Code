<?php
session_start();
include 'dbConn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];
    $available_quantity = $_POST['available_quantity'];

    if (!isset($_SESSION['user_id'])) {
        echo "User not logged in";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Retrieve the user's cart ID from the Cart table
    $cart_query = "SELECT CartID FROM Cart WHERE UserID = ?";
    $cart_stmt = $conn->prepare($cart_query);
    if ($cart_stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    if ($cart_result->num_rows == 0) {
        die("Cart not found for user: $user_id");
    }

    $cart_row = $cart_result->fetch_assoc();
    $cart_id = $cart_row['CartID'];

    // Check current quantity in the Cart_Items table
    $cart_item_query = "SELECT QuantityAdded FROM Cart_Items WHERE CartID = ? AND ProductID = ?";
    $cart_item_stmt = $conn->prepare($cart_item_query);
    $cart_item_stmt->bind_param("ii", $cart_id, $product_id);
    $cart_item_stmt->execute();
    $cart_item_result = $cart_item_stmt->get_result();
    $cart_item = $cart_item_result->fetch_assoc();
    $current_quantity = $cart_item['QuantityAdded'];

    if ($action == 'increase') {
        $new_quantity = $current_quantity + 0.1;
        if ($new_quantity > $available_quantity) {
            $new_quantity = $available_quantity; // Cap the quantity at the available amount
        }
    } elseif ($action == 'decrease') {
        $new_quantity = max($current_quantity - 0.1, 0.1); // Prevent quantity from going below 0.1
    } elseif ($action == 'remove') {
        // Remove item from Cart_Items table
        $delete_query = "DELETE FROM Cart_Items WHERE CartID = ? AND ProductID = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $cart_id, $product_id);
        $delete_stmt->execute();

        // Remove item from session cart
        unset($_SESSION['cart'][$product_id]);

        // Redirect back to the cart page after removal
        header("Location: cart.php");
        exit;
    }

    // Update the quantity in Cart_Items table
    if ($action == 'increase' || $action == 'decrease') {
        $update_query = "UPDATE Cart_Items SET QuantityAdded = ? WHERE CartID = ? AND ProductID = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("dii", $new_quantity, $cart_id, $product_id);
        $update_stmt->execute();

        // Update session cart
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    }

    // Redirect back to the cart page after update
    header("Location: cart.php");
    exit;
}

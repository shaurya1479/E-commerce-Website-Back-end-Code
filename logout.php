<?php
include 'dbConn.php';
session_start(); 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Update the isLoggedIn field to false (0)
    $logout_query = $conn->prepare("UPDATE Users SET isLoggedIn = 0 WHERE UserID = ?");
    $logout_query->bind_param("i", $user_id);
    $logout_query->execute();
    
    // Destroy the session to log out the user
    session_destroy();

    // Redirect to the homepage (index.php)
    header("Location: index.php");
    exit();
} else {
    // If the user is not logged in, just redirect to the homepage
    header("Location: index.php");
    exit();
}

// Close the database connection
$conn->close();
?>

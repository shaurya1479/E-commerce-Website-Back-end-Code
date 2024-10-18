<?php 
session_start();
ob_start();
include 'dbConn.php'; 

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to sanitize user input
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    if (empty($username) || empty($password)) {
        echo "<script>alert('Username and password cannot be empty.');</script>";
    } else {
        $sql = "SELECT UserID, FirstName, Role, Password FROM Users WHERE Username = ? OR Email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $firstname, $role, $stored_password);
        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            if (password_verify($password, $stored_password) || $password === $stored_password) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['firstname'] = $firstname;
                $_SESSION['role'] = $role;

                $update_sql = "UPDATE Users SET isLoggedIn = 1 WHERE UserID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('i', $user_id);
                $update_stmt->execute();

                if (strtolower($role) == 'customer') {
                    header('Location: customer_homepage.php');
                    exit();
                } elseif (strtolower($role) == 'client') {
                    header('Location: client_homepage.php');
                    exit();
                }
            } else {
                echo "<script>alert('Incorrect password.');</script>";
            }
        } else {
            echo "<script>alert('Username or email not found.');</script>";
        }
        $stmt->close();
    }
}

// Handle signup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $email = sanitize($_POST['email']);
    $firstname = sanitize($_POST['firstname']);
    $lastname = sanitize($_POST['lastname']);
    $username = sanitize($_POST['username']);
    $password = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);

    $check_sql = "SELECT Email FROM Users WHERE Email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Email already exists.');</script>";
    } else {
        $insert_sql = "INSERT INTO Users (Username, Password, FirstName, LastName, Email, Role, isLoggedIn, NotificationPreference) VALUES (?, ?, ?, ?, ?, 'Customer', 0, 1)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('sssss', $username, $password, $firstname, $lastname, $email);
        if ($insert_stmt->execute()) {
            echo "<script>alert('Account created successfully. Please log in.');</script>";
        } else {
            echo "<script>alert('Error creating account.');</script>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Sign Up</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom login page styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            display: flex;
            flex-direction: column;
            height: 100vh;
            align-items: center;
        }

        /* Preserve the original menu look */
        nav {
            width: 100%;
            background-color: #333;
            padding: 10px 0;
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }

        nav ul li a:hover {
            background-color: #575757;
            border-radius: 5px;
        }

        /* Container for the forms */
        .login-container {
            display: flex;
            justify-content: space-around;
            align-items: flex-start; /* Ensure alignment at the top */
            flex-wrap: wrap;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin-top: 50px; /* Space below the menu */
        }

        .form-box {
            width: 45%;
            padding: 20px;
        }

        form h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #4cae4c;
        }

        /* Ensure the form headings are aligned */
        .form-box:nth-child(2) {
            margin-top: -10px; /* Align both headings at the top */
        }

    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="track.php">Track Your Order</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="login.php">Log In</a></li>
        </ul>
    </nav>

    <div class="login-container">
        <div class="form-box">
            <form action="login.php" method="POST">
                <h2>Log In</h2>
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" name="login">Log In</button>
            </form>
        </div>
        <div class="form-box">
            <form action="login.php" method="POST">
                <h2>Create an Account</h2>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required>
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>
    </div> 
    
    <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
    </footer>
    
</body> 
</html>

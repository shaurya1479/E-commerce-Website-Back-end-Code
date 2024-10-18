<?php
session_start();
include 'dbConn.php';

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle toggling notification preference
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_notifications'])) {
    $current_preference = $_POST['current_preference'];
    $new_preference = $current_preference == 1 ? 0 : 1;
    $toggle_query = "UPDATE Users SET NotificationPreference = ? WHERE UserID = ?";
    $toggle_stmt = $conn->prepare($toggle_query);
    $toggle_stmt->bind_param("ii", $new_preference, $user_id);
    $toggle_stmt->execute();
}

// Fetch the user's notification preference
$preference_query = "SELECT NotificationPreference FROM Users WHERE UserID = ?";
$preference_stmt = $conn->prepare($preference_query);
$preference_stmt->bind_param("i", $user_id);
$preference_stmt->execute();
$preference_stmt->bind_result($notification_preference);
$preference_stmt->fetch();
$preference_stmt->close();

// Fetch the user's notifications
$notifications_query = "SELECT * FROM Notifications WHERE UserID = ? ORDER BY DateSent DESC";
$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_selected_as_read'])) {
    if (isset($_POST['selected_notifications'])) {
        $selected_notifications = $_POST['selected_notifications'];
        foreach ($selected_notifications as $notification_id) {
            $mark_read_query = "UPDATE Notifications SET ReadStatus = 1 WHERE NotificationID = ? AND UserID = ?";
            $mark_read_stmt = $conn->prepare($mark_read_query);
            $mark_read_stmt->bind_param("ii", $notification_id, $user_id);
            $mark_read_stmt->execute();
            $mark_read_stmt->close();
        }
    }
}

// Handle deleting notifications
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_selected_notifications'])) {
    if (isset($_POST['selected_notifications'])) {
        $selected_notifications = $_POST['selected_notifications'];
        foreach ($selected_notifications as $notification_id) {
            $delete_query = "DELETE FROM Notifications WHERE NotificationID = ? AND UserID = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("ii", $notification_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - MyWebsite</title>
    <link rel="stylesheet" href="styles.css">
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - MyWebsite</title>
    <style>
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .notifications-list {
            list-style-type: none;
            padding: 0;
        }

        .notification-item {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .select-box {
            margin-right: 10px;
            flex-shrink: 0;
        }

        .notification-message {
            margin: 0 10px;
            flex-grow: 1;
            color: black;
        }

        .notification-date {
            margin-right: 10px;
            color: #333;
        }

        .read-indicator, .new-indicator {
            font-size: 0.9em;
            margin-left: 10px;
        }

        .read-indicator {
            color: green;
        }

        .new-indicator {
            color: red;
        }

        .notification-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .small-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .small-btn:hover {
            background-color: #388E3C;
        }

        .toggle-btn {
            padding: 5px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        .toggle-btn:hover {
            background-color: #388E3C;
        }
    </style>
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
    <h2>Your Notifications</h2>

    <!-- Toggle Notifications -->
    <form method="POST" action="notifications.php">
        <input type="hidden" name="current_preference" value="<?php echo $notification_preference; ?>">
        <button type="submit" name="toggle_notifications" class="toggle-btn">
            Turn Notifications <?php echo $notification_preference == 1 ? 'Off' : 'On'; ?>
        </button>
    </form>

    <!-- Display Notifications -->
    <?php if ($notification_preference == 1): ?>
        <?php if ($notifications_result->num_rows > 0): ?>
            <form method="POST" action="notifications.php">
                <ul class="notifications-list">
                    <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                        <li class="notification-item <?php echo $notification['ReadStatus'] == 0 ? 'unread' : 'read'; ?>">
                            <input type="checkbox" name="selected_notifications[]" value="<?php echo $notification['NotificationID']; ?>" class="select-box">
                            <p class="notification-message"><?php echo htmlspecialchars($notification['Content']); ?></p>
                            <small class="notification-date">Date sent: <?php echo htmlspecialchars($notification['DateSent']); ?></small>
                            <?php if ($notification['ReadStatus'] == 1): ?>
                                <span class="read-indicator">Read</span>
                            <?php else: ?>
                                <span class="new-indicator">New</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div class="notification-actions">
                    <button type="submit" name="mark_selected_as_read" class="small-btn">Mark Selected as Read</button>
                    <button type="submit" name="delete_selected_notifications" class="small-btn">Delete Selected</button>
                </div>
            </form>
        <?php else: ?>
            <p>No notifications available.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Notifications are turned off. Turn them on to view your notifications.</p>
    <?php endif; ?>
</div> 
    
    <footer>
        <p>&copy; 2024 Mamta Charitable Trust. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Clean up
$notifications_stmt->close();
$conn->close();
?>

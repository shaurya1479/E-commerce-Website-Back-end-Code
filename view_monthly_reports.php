<?php
include 'dbConn.php';
session_start();

// Ensure user is logged in as a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header("Location: login.php");
    exit();
}

$selected_month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');
$total_quantity_sold = 0;
$total_revenue = 0;
$product_data = [];
$total_visits = 0;
$busiest_day = null;
$busiest_day_revenue = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($selected_month)) {
    // Get total quantity and revenue
    $orders_query = "
        SELECT p.Name, SUM(o.QuantityOrdered) AS QuantitySold, SUM(o.Amount) AS TotalRevenue 
        FROM Orders o 
        JOIN Products p ON o.ProductID = p.ProductID 
        WHERE DATE_FORMAT(o.OrderDate, '%Y-%m') = ? 
        GROUP BY p.ProductID";
    $orders_stmt = $conn->prepare($orders_query);
    $orders_stmt->bind_param("s", $selected_month);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();

    while ($row = $orders_result->fetch_assoc()) {
        $product_data[] = $row;
        $total_quantity_sold += $row['QuantitySold'];
        $total_revenue += $row['TotalRevenue'];
    }
    $orders_stmt->close();

    // Get total visits for the month
    $visits_query = "
        SELECT SUM(VisitCount) AS TotalVisits 
        FROM Visits 
        WHERE DATE_FORMAT(VisitDate, '%Y-%m') = ?";
    $visits_stmt = $conn->prepare($visits_query);
    $visits_stmt->bind_param("s", $selected_month);
    $visits_stmt->execute();
    $visits_result = $visits_stmt->get_result();
    $visits_row = $visits_result->fetch_assoc();
    $total_visits = $visits_row['TotalVisits'] ?? 0;
    $visits_stmt->close();

    // Get the busiest day based on revenue
    $busiest_day_query = "
        SELECT DATE(o.OrderDate) AS Day, SUM(o.Amount) AS TotalRevenue 
        FROM Orders o 
        WHERE DATE_FORMAT(o.OrderDate, '%Y-%m') = ? 
        GROUP BY Day 
        ORDER BY TotalRevenue DESC 
        LIMIT 1";
    $busiest_day_stmt = $conn->prepare($busiest_day_query);
    $busiest_day_stmt->bind_param("s", $selected_month);
    $busiest_day_stmt->execute();
    $busiest_day_result = $busiest_day_stmt->get_result();
    $busiest_day_row = $busiest_day_result->fetch_assoc();
    $busiest_day = $busiest_day_row ? $busiest_day_row['Day'] : null;
    $busiest_day_revenue = $busiest_day_row ? $busiest_day_row['TotalRevenue'] : 0;
    $busiest_day_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-container {
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 10px;
        }
        .stats {
            margin-bottom: 20px;
        }
        .chart-container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
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
        <div class="report-container">
            <h1>Monthly Report</h1>

            <!-- Form to select month -->
            <form method="POST" action="view_monthly_reports.php">
                <label for="month">Select Month:</label>
                <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                <button type="submit">View Report</button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($selected_month)): ?>
                <div class="stats">
                    <h2>Statistics for <?php echo date('F Y', strtotime($selected_month)); ?></h2>
                    <p>Total Quantity Sold: <?php echo number_format($total_quantity_sold, 2); ?> kg</p>
                    <p>Total Revenue: $<?php echo number_format($total_revenue, 2); ?></p>
                    <p>Total Website Visits: <?php echo number_format($total_visits); ?></p>
                    <?php if ($busiest_day): ?>
                        <p>Busiest Day: <?php echo date('F j, Y', strtotime($busiest_day)); ?> (Revenue: $<?php echo number_format($busiest_day_revenue, 2); ?>)</p>
                    <?php endif; ?>
                </div>

                <!-- Product breakdown as a table -->
                <h3>Product Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity Sold (kg)</th>
                            <th>Revenue Generated ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product_data as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['Name']); ?></td>
                                <td><?php echo number_format($product['QuantitySold'], 2); ?></td>
                                <td><?php echo number_format($product['TotalRevenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pie chart for product sales breakdown -->
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Script to generate pie chart -->
        <script>
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($product_data, 'Name')); ?>,
                    datasets: [{
                        label: 'Quantity Sold',
                        data: <?php echo json_encode(array_column($product_data, 'QuantitySold')); ?>,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4CAF50', '#FF9800'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        </script>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>
</body>
</html>

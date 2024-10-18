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

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_selected'])) {
    $selected_products = $_POST['selected_products'] ?? [];
    if (!empty($selected_products)) {
        $placeholders = implode(',', array_fill(0, count($selected_products), '?'));
        $stmt = $conn->prepare("DELETE FROM Products WHERE ProductID IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($selected_products)), ...$selected_products);
        if ($stmt->execute()) {
            $success_message = count($selected_products) . " product(s) deleted successfully!";
        } else {
            $error_message = "Error deleting products: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "No products selected for deletion.";
    }
}

// Handle product editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $quantity = floatval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $category = htmlspecialchars(trim($_POST['category']));
    $imageURL = htmlspecialchars(trim($_POST['imageURL']));

    if (empty($name) || empty($description) || empty($quantity) || empty($price) || empty($category) || empty($imageURL)) {
        $error_message = "All fields are required.";
    } elseif (!in_array($category, ['fruit', 'vegetable', 'crop'])) {
        $error_message = "Category must be either 'fruit', 'vegetable', or 'crop'.";
    } else {
        // Update the product in the database
        $stmt = $conn->prepare("UPDATE Products SET Name=?, Description=?, Quantity=?, Price=?, Category=?, ImageURL=? WHERE ProductID=?");
        $stmt->bind_param("ssddssi", $name, $description, $quantity, $price, $category, $imageURL, $product_id);

        if ($stmt->execute()) {
            $success_message = "Product updated successfully!";
        } else {
            $error_message = "Error updating product: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Fetch products from the database
$products_query = "SELECT * FROM Products";
$products_result = $conn->query($products_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - MyWebsite</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Additional styles for the popup and product management */
        .product-row {
            cursor: pointer;
        }
        .product-row:hover {
            background-color: #f0f0f0;
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            width: 600px; /* Increased width for better readability */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .popup.active {
            display: block;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 500;
        }
        .overlay.active {
            display: block;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
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
        <h1>Manage Products</h1>

        <?php if (!empty($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php elseif (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="POST" action="manage_products.php">
            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Product Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <tr data-description="<?php echo htmlspecialchars($product['Description']); ?>"
                            data-quantity="<?php echo htmlspecialchars($product['Quantity']); ?>"
                            data-price="<?php echo htmlspecialchars($product['Price']); ?>"
                            data-category="<?php echo htmlspecialchars($product['Category']); ?>"
                            data-image-url="<?php echo htmlspecialchars($product['ImageURL']); ?>">
                            <td><input type="checkbox" name="selected_products[]" value="<?php echo $product['ProductID']; ?>"></td>
                            <td class="product-row" data-product-id="<?php echo $product['ProductID']; ?>"><?php echo htmlspecialchars($product['Name']); ?></td>
                            <td>
                                <button type="button" class="edit-button" data-product-id="<?php echo $product['ProductID']; ?>">Edit</button>
                                <button type="button" class="delete-button" data-product-id="<?php echo $product['ProductID']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <button type="submit" name="delete_selected">Delete Selected</button>
        </form>

        <!-- View Product Details Popup -->
        <div class="popup" id="viewProductPopup">
            <h2>Product Details</h2>
            <div class="form-group">
                <label for="viewName">Product Name:</label>
                <p id="viewName"></p>
            </div>
            <div class="form-group">
                <label for="viewDescription">Description:</label>
                <p id="viewDescription"></p>
            </div>
            <div class="form-group">
                <label for="viewQuantity">Quantity Available (kg):</label>
                <p id="viewQuantity"></p>
            </div>
            <div class="form-group">
                <label for="viewPrice">Price per kg ($):</label>
                <p id="viewPrice"></p>
            </div>
            <div class="form-group">
                <label for="viewCategory">Category:</label>
                <p id="viewCategory"></p>
            </div>
            <div class="form-group">
                <label for="viewImageURL">Image:</label>
                <img id="viewImageURL" src="" alt="Product Image" style="max-width: 100%;">
            </div>
            <button type="button" onclick="closePopup()">Close</button>
        </div>

        <!-- Edit Product Popup -->
        <div class="popup" id="editProductPopup">
            <h2>Edit Product</h2>
            <form method="POST" action="manage_products.php">
                <input type="hidden" name="product_id" id="editProductID">
                <div class="form-group">
                    <label for="editName">Product Name:</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editQuantity">Quantity Available (kg):</label>
                    <input type="number" id="editQuantity" name="quantity" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editPrice">Price per kg ($):</label>
                    <input type="number" id="editPrice" name="price" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editCategory">Category:</label>
                    <select id="editCategory" name="category" required>
                        <option value="fruit">Fruit</option>
                        <option value="vegetable">Vegetable</option>
                        <option value="crop">Crop</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editImageURL">Image URL:</label>
                    <input type="text" id="editImageURL" name="imageURL" required>
                </div>
                <button type="submit" name="edit_product">Save Changes</button>
                <button type="button" onclick="closePopup()">Cancel</button>
            </form>
        </div>

        <div class="overlay" id="overlay"></div>
    </main>

    <footer>
        <p>&copy; 2024 MyWebsite. All rights reserved.</p>
    </footer>

    <script>
        const productRows = document.querySelectorAll('.product-row');
        const editButtons = document.querySelectorAll('.edit-button');
        const viewProductPopup = document.getElementById('viewProductPopup');
        const editProductPopup = document.getElementById('editProductPopup');
        const overlay = document.getElementById('overlay');

        productRows.forEach(row => {
            row.addEventListener('click', () => {
                const name = row.querySelector('.product-row').textContent;
                const description = row.dataset.description;
                const quantity = row.dataset.quantity;
                const price = row.dataset.price;
                const category = row.dataset.category;
                const imageURL = row.dataset.imageUrl;

                document.getElementById('viewName').textContent = name;
                document.getElementById('viewDescription').textContent = description;
                document.getElementById('viewQuantity').textContent = quantity;
                document.getElementById('viewPrice').textContent = price;
                document.getElementById('viewCategory').textContent = category;
                document.getElementById('viewImageURL').src = imageURL;

                viewProductPopup.classList.add('active');
                overlay.classList.add('active');
            });
        });

        editButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent the product row click event from triggering

                const productId = button.dataset.productId;
                const row = button.closest('tr');
                const name = row.querySelector('.product-row').textContent;
                const description = row.dataset.description;
                const quantity = row.dataset.quantity;
                const price = row.dataset.price;
                const category = row.dataset.category;
                const imageURL = row.dataset.imageUrl;

                document.getElementById('editProductID').value = productId;
                document.getElementById('editName').value = name;
                document.getElementById('editDescription').value = description;
                document.getElementById('editQuantity').value = quantity;
                document.getElementById('editPrice').value = price;
                document.getElementById('editCategory').value = category;
                document.getElementById('editImageURL').value = imageURL;

                editProductPopup.classList.add('active');
                overlay.classList.add('active');
            });
        });

        function closePopup() {
            viewProductPopup.classList.remove('active');
            editProductPopup.classList.remove('active');
            overlay.classList.remove('active');
        }

        overlay.addEventListener('click', closePopup);
    </script>
</body>
</html>

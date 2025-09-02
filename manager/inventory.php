<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login/");
    exit();
}

require '../config/database.php';

// Fetch the last Product ID to generate the next one
$last_id_stmt = $db->query("SELECT MAX(Product_ID) as last_id FROM inventory");
$last_id_row = $last_id_stmt->fetch(PDO::FETCH_ASSOC);
$next_product_id = $last_id_row['last_id'] ? $last_id_row['last_id'] + 1 : 20251001;

// --- Filtering, Sorting, and Searching Logic ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

$valid_sort_columns = ['Product_ID', 'Brand', 'Product_Name', 'Quantity', 'Date_Acquired', 'Price'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'Product_Name';
$sort_order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'ASC';

// Build the SQL query
$sql = "SELECT Product_ID, Brand, Product_Name, Type, Quantity, Date_Acquired, Expiration_Date, Price FROM inventory";
$params = [];
$where_clauses = ["status = 'active'"];

if ($type_filter !== 'all') {
    $where_clauses[] = "Type = :type";
    $params[':type'] = $type_filter;
}
if (!empty($search_query)) {
    $where_clauses[] = "(Product_Name LIKE :search OR Brand LIKE :search OR Product_ID LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY " . $sort_column . " " . $sort_order;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inventory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare low stock and out of stock notifications
$low_stock_items = [];
$out_of_stock_items = [];
foreach ($inventory_data as $item) {
    if ($item['Quantity'] == 0) {
        $out_of_stock_items[] = $item['Product_Name'];
    } elseif ($item['Quantity'] < 4) {
        $low_stock_items[] = $item['Product_Name'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - Thrivehut Motorworks</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <style>
        .add-product-modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);
            align-items: center; justify-content: center;
        }
        .add-product-modal-content {
            background-color: #d3d3d3; padding: 30px; border: 2px solid black;
            width: 400px; font-family: 'Impact', sans-serif;
        }
        .add-product-modal-content h2 { text-align: center; margin-bottom: 20px; }
        .form-group { display: flex; align-items: center; margin-bottom: 15px; }
        .form-group label { width: 150px; }
        .form-group input, .form-group select {
            flex: 1; padding: 5px; border: 2px solid black; background-color: white;
        }
        .form-group input[type="checkbox"] { flex: 0; width: auto; margin-left: 10px; }
        .add-product-btn-modal {
            display: block; width: 100px; margin: 20px auto 0; padding: 10px;
            background-color: white; border: 2px solid black; font-family: 'Impact', sans-serif;
            font-size: 16px; cursor: pointer;
        }
        .inventory-actions { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; }
        .inventory-filters { display: flex; gap: 10px; align-items: center; }
        .inventory-filters select, .inventory-filters input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .inventory-filters button {
            padding: 8px 15px;
            border: none;
            background-color: #800000;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .inventory-search input { width: 300px; }
        .action-btn-group { display: flex; gap: 5px; }
        .action-btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .add-stock-btn { background-color: #28a745; color: white; }
        .archive-btn { background-color: #dc3545; color: white; }
        
        /* Loading Screen Styles */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loader {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #c00000; /* Red from your theme */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1.5s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .inventory-banner {
            background: #fffbe6;
            border: 1.5px solid #ffb300;
            color: #7a4f01;
            padding: 18px 30px;
            border-radius: 8px;
            margin: 25px 0 10px 0;
            font-size: 1.1em;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(255, 191, 0, 0.08);
        }
        .inventory-banner .item-list {
            margin: 8px 0 0 0; font-weight: 400; font-size: 0.98em;
        }
        .inventory-banner .out {
            color: #b71c1c; font-weight: 700;
        }
        .inventory-banner .low {
            color: #e65100; font-weight: 700;
        }
        .logout-modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .logout-modal-content {
            background: #fff; padding: 30px 40px; border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); text-align: center;
        }
        .logout-modal-content h2 { margin-bottom: 20px; }
        .logout-modal-content button { margin: 0 10px; padding: 10px 20px; border-radius: 5px; border: none; font-weight: 700; cursor: pointer; }
        .logout-modal-content .confirm-logout { background: #800000; color: #fff; }
        .logout-modal-content .cancel-logout { background: #ccc; color: #333; }
    </style>
</head>
<body>
    <div class="loader-wrapper" id="loader-wrapper">
        <div class="loader"></div>
    </div>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header"><img src="../images/thrivehut logo png.png" alt="Logo" class="logo-sidebar"></div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">TRANSACTION</a>
                <a href="sales.php" class="nav-item">SALES</a>
                <a href="inventory.php" class="nav-item active">INVENTORY</a>
                <a href="returned_item.php" class="nav-item">RETURNED ITEM</a>
            </nav>
            <div class="sidebar-footer"><a href="#" class="nav-item-logout" id="logout-link">EXIT</a></div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>THRIVEHUT MOTORWORKS INVENTORY AND SALES AUDIT SYSTEM</h1>
            </header>
            <?php if (!empty($low_stock_items) || !empty($out_of_stock_items)): ?>
            <div class="inventory-banner">
                <?php if (!empty($out_of_stock_items)): ?>
                    <div class="out">Out of Stock: <?php echo implode(', ', $out_of_stock_items); ?></div>
                <?php endif; ?>
                <?php if (!empty($low_stock_items)): ?>
                    <div class="low">Low Stock (&lt; 4): <?php echo implode(', ', $low_stock_items); ?></div>
                <?php endif; ?>
            </div>
            <div style="margin-bottom: 20px;">
                <button id="notify-owner-btn" style="background-color: #ffb300; color: #7a4f01; font-weight: bold; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Notify the Owner</button>
            </div>
            <?php endif; ?>
            <section class="inventory-actions">
                <form action="inventory.php" method="GET" class="inventory-filters">
                    <div class="inventory-search">
                        <input type="text" name="search" placeholder="SEARCH" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">SEARCH</button>
                    </div>
                    <select name="type" onchange="this.form.submit()">
                        <option value="all" <?php if($type_filter == 'all') echo 'selected'; ?>>All Types</option>
                        <option value="Parts" <?php if($type_filter == 'Parts') echo 'selected'; ?>>Parts</option>
                        <option value="Products" <?php if($type_filter == 'Products') echo 'selected'; ?>>Products</option>
                    </select>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="Product_Name" <?php if($sort_column == 'Product_Name') echo 'selected'; ?>>Sort by Name</option>
                        <option value="Brand" <?php if($sort_column == 'Brand') echo 'selected'; ?>>Sort by Brand</option>
                        <option value="Price" <?php if($sort_column == 'Price') echo 'selected'; ?>>Sort by Price</option>
                        <option value="Quantity" <?php if($sort_column == 'Quantity') echo 'selected'; ?>>Sort by Quantity</option>
                    </select>
                    <select name="order" onchange="this.form.submit()">
                        <option value="ASC" <?php if($sort_order == 'ASC') echo 'selected'; ?>>Ascending</option>
                        <option value="DESC" <?php if($sort_order == 'DESC') echo 'selected'; ?>>Descending</option>
                    </select>
                </form>
            </section>
            <section class="item-table-container">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>PRODUCT ID</th>
                            <th>BRAND</th>
                            <th>PRODUCT NAME</th>
                            <th>TYPE</th>
                            <th>QUANTITY</th>
                            <th>DATE ACQUIRED</th>
                            <th>EXPIRATION DATE</th>
                            <th>PRICE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_data)): ?>
                            <tr><td colspan="8" style="text-align: center;">No inventory data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($inventory_data as $item): ?>
                            <tr id="product-row-<?php echo htmlspecialchars($item['Product_ID']); ?>">
                                <td><?php echo htmlspecialchars($item['Product_ID']); ?></td>
                                <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                                <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Type']); ?></td>
                                <td><?php echo htmlspecialchars($item['Quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['Date_Acquired']); ?></td>
                                <td><?php echo htmlspecialchars($item['Expiration_Date'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($item['Price'], 2); ?></td>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <div class="logout-modal" id="logout-modal">
        <div class="logout-modal-content">
            <h2>Confirm Exit</h2>
            <p>Are you sure you want to exit the system?</p>
            <button class="confirm-logout">Yes, Exit</button>
            <button class="cancel-logout">Cancel</button>
        </div>
    </div>
    <script src="js/inventory.js"></script>
    <script>
    // Logout modal logic
    document.getElementById('logout-link').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logout-modal').style.display = 'flex';
    });
    document.querySelector('.confirm-logout').onclick = function() {
        window.location.href = '../logout.php';
    };
    document.querySelector('.cancel-logout').onclick = function() {
        document.getElementById('logout-modal').style.display = 'none';
    };
    // Notify Owner button logic
    var notifyBtn = document.getElementById('notify-owner-btn');
    if (notifyBtn) {
        notifyBtn.addEventListener('click', function() {
            var lowStock = <?php echo json_encode($low_stock_items); ?>;
            var outOfStock = <?php echo json_encode($out_of_stock_items); ?>;
            var message = '';
            if (outOfStock.length > 0) {
                message += 'Out of Stock Items:\n' + outOfStock.join(', ') + '\n\n';
            }
            if (lowStock.length > 0) {
                message += 'Low Stock Items (< 4):\n' + lowStock.join(', ');
            }
            // Send AJAX notification to owner
            fetch('../owner/ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=notify_owner&message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notification sent to owner!');
                } else {
                    alert('Failed to notify owner: ' + data.message);
                }
            })
            .catch(() => {
                alert('Failed to notify owner.');
            });
        });
    }
    </script>
</body>
</html> 
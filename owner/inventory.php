<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
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
        /* Notification Sidebar Styles */
        .notification-sidebar {
            position: fixed;
            right: -350px;
            top: 0;
            width: 350px;
            height: 100%;
            background: #fffbe6;
            box-shadow: -2px 0 8px rgba(255,191,0,0.15);
            z-index: 3000;
            transition: right 0.4s cubic-bezier(0.77,0,0.175,1);
            border-left: 2px solid #ffb300;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .notification-sidebar.open {
            right: 0;
        }
        .notification-header {
            background: #ffb300;
            color: #7a4f01;
            font-weight: bold;
            padding: 18px 20px;
            font-size: 1.2em;
            border-bottom: 1.5px solid #e0a800;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-list {
            flex: 1;
            overflow-y: auto;
            padding: 15px 20px;
        }
        .notification-item {
            background: #fff;
            border: 1px solid #ffe082;
            border-radius: 6px;
            margin-bottom: 15px;
            padding: 12px 15px;
            color: #7a4f01;
            font-size: 1em;
            box-shadow: 0 2px 6px rgba(255,191,0,0.07);
        }
        .notification-unread {
            font-weight: bold;
            background: #fffde7;
        }
        .notification-close {
            background: none;
            border: none;
            font-size: 1.5em;
            color: #7a4f01;
            cursor: pointer;
        }
        .notification-bell {
            position: fixed;
            top: 30px;
            right: 30px;
            background: #ffb300;
            color: #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            box-shadow: 0 2px 8px rgba(255,191,0,0.15);
            cursor: pointer;
            z-index: 3100;
        }
        .notification-bell .notif-dot {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 12px;
            height: 12px;
            background: #b71c1c;
            border-radius: 50%;
            border: 2px solid #fffbe6;
            display: none;
        }
        .notification-bell.has-unread .notif-dot {
            display: block;
        }
    </style>
</head>
<body>
    <div class="loader-wrapper" id="loader-wrapper">
        <div class="loader"></div>
    </div>
    <!-- Notification Bell (always visible, outside dashboard-container) -->
    <div class="notification-bell" id="notification-bell">
        <span>&#128276;</span>
        <span class="notif-dot" id="notif-dot"></span>
        <span class="notif-count-bubble" id="notif-count-bubble" style="position:absolute;top:8px;right:8px;background:#b71c1c;color:#fff;font-size:0.95em;font-weight:bold;border-radius:50%;padding:2px 7px;min-width:22px;text-align:center;display:none;z-index:3200;"></span>
    </div>
    <!-- Notification Sidebar (always overlays, outside dashboard-container) -->
    <div class="notification-sidebar" id="notification-sidebar">
        <div class="notification-header">
            Notifications
            <button class="notification-close" id="close-notification-sidebar">&times;</button>
        </div>
        <div class="notification-list" id="notification-list">
            <!-- Notifications will be loaded here -->
        </div>
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
                <a href="settings.php" class="nav-item">SETTINGS</a>
            </nav>
            <div class="sidebar-footer"><a href="#" class="nav-item-logout" id="logout-link">EXIT</a></div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>THRIVEHUT MOTORWORKS INVENTORY AND SALES AUDIT SYSTEM</h1>
            </header>

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
                <button id="add-product-btn" class="action-btn" style="background: #800000; color: white; border: none; padding: 10px 20px; cursor: pointer;">ADD PRODUCT</button>
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
                            <th>ACTIONS</th>
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
                                <td>
                                    <div class="action-btn-group">
                                        <button class="action-btn-sm add-stock-btn" data-id="<?php echo htmlspecialchars($item['Product_ID']); ?>">Add Stock</button>
                                        <button class="action-btn-sm archive-btn" data-id="<?php echo htmlspecialchars($item['Product_ID']); ?>">Archive</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div id="add-product-modal" class="add-product-modal">
        <div class="add-product-modal-content">
            <span class="close-modal-btn" style="float:right; cursor:pointer;">&times;</span>
            <h2>ADD PRODUCT</h2>
            <form id="add-product-form">
                <div class="form-group">
                    <label for="product_id">PRODUCT ID :</label>
                    <input type="text" id="product_id" name="product_id" value="<?php echo $next_product_id; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="brand">BRAND :</label>
                    <input type="text" id="brand" name="brand" required>
                </div>
                <div class="form-group">
                    <label for="product_name">PRODUCT NAME :</label>
                    <input type="text" id="product_name" name="product_name" required>
                </div>
                <div class="form-group">
                    <label for="type">TYPE :</label>
                    <select id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="Parts">Parts</option>
                        <option value="Products">Products</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">QUANTITY :</label>
                    <input type="number" id="quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="date_acquired">DATE ACQUIRED :</label>
                    <input type="date" id="date_acquired" name="date_acquired" required>
                </div>
                <div class="form-group">
                    <label for="expiration_date">EXPIRATION DATE :</label>
                    <input type="date" id="expiration_date" name="expiration_date">
                    <input type="checkbox" id="non-expiring"> Non-Expiring
                </div>
                <div class="form-group">
                    <label for="price">PRICE :</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <button type="submit" class="add-product-btn-modal">ADD</button>
            </form>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div id="add-stock-modal" class="add-product-modal">
        <div class="add-product-modal-content">
            <span class="close-modal-btn" style="float:right; cursor:pointer;">&times;</span>
            <h2>ADD STOCK</h2>
            <form id="add-stock-form">
                <input type="hidden" id="add-stock-product-id" name="product_id">
                <div class="form-group">
                    <label for="add-stock-quantity">QUANTITY TO ADD:</label>
                    <input type="number" id="add-stock-quantity" name="quantity" min="1" required>
                </div>
                <button type="submit" class="add-product-btn-modal">SUBMIT</button>
            </form>
        </div>
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
    // Notification Sidebar Logic
    const notifBell = document.getElementById('notification-bell');
    const notifSidebar = document.getElementById('notification-sidebar');
    const notifList = document.getElementById('notification-list');
    const notifDot = document.getElementById('notif-dot');
    const notifCountBubble = document.getElementById('notif-count-bubble');
    const closeNotifSidebar = document.getElementById('close-notification-sidebar');

    function fetchNotifications() {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_notifications'
        })
        .then(response => response.json())
        .then(data => {
            notifList.innerHTML = '';
            let hasUnread = false;
            let unreadCount = 0;
            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(n => {
                    const div = document.createElement('div');
                    div.className = 'notification-item' + (n.is_read == 0 ? ' notification-unread' : '');
                    div.textContent = n.message + ' (' + n.created_at + ')';
                    notifList.appendChild(div);
                    if (n.is_read == 0) {
                        hasUnread = true;
                        unreadCount++;
                    }
                });
            } else {
                notifList.innerHTML = '<div style="color:#888;">No notifications.</div>';
            }
            notifBell.classList.toggle('has-unread', hasUnread);
            if (unreadCount > 0) {
                notifCountBubble.style.display = 'block';
                notifCountBubble.textContent = unreadCount;
            } else {
                notifCountBubble.style.display = 'none';
            }
        });
    }

    notifBell.addEventListener('click', function() {
        notifSidebar.classList.add('open');
        // Mark all as read when opening
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_notifications_read'
        }).then(() => fetchNotifications());
    });
    closeNotifSidebar.addEventListener('click', function() {
        notifSidebar.classList.remove('open');
    });
    // Initial fetch
    fetchNotifications();
    // Poll for new notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
    </script>
</body>
</html>
<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone for correct time

// Ensure the user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login/");
    exit();
}

require '../config/database.php';

// --- Logic for Filtering and Sorting ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'inventory'; // inventory or services
$type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, parts, products
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$valid_sort_columns = ['Product_Name', 'Brand', 'Price', 'Quantity'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'Product_Name';
$sort_order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'ASC';
$inventory_items = [];
$services = isset($_SESSION['services']) ? $_SESSION['services'] : [];

if ($filter === 'inventory') {
    $sql = "SELECT Product_ID, Brand, Product_Name, Type, Quantity, Price FROM inventory";
    $params = [];
    $where_clauses = ["status = 'active'"]; // Always filter for active items
    if ($type === 'parts' || $type === 'products') {
        $where_clauses[] = "Type = :type";
        $params[':type'] = ucfirst($type);
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
    $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($filter === 'services') {
    // Handle add/delete actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_service'])) {
            $service_name = trim($_POST['service_name']);
            $service_price = floatval($_POST['service_price']);
            if ($service_name && $service_price > 0) {
                $services[] = ['name' => $service_name, 'price' => $service_price];
                $_SESSION['services'] = $services;
            }
        } elseif (isset($_POST['delete_service'])) {
            $index = intval($_POST['delete_service']);
            if (isset($services[$index])) {
                array_splice($services, $index, 1);
                $_SESSION['services'] = $services;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Thrivehut</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .hidden {
            display: none !important;
        }
        @media print {
            body > *:not(.printable-receipt) {
                display: none;
            }
            .printable-receipt {
                display: block !important;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
        }
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
        .services-section {
            background: #fff;
            border: 2px solid #000;
            border-radius: 0 0 8px 8px;
            padding: 0 0 20px 0;
            margin-bottom: 30px;
        }
        .services-add-form {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 18px 20px 10px 20px;
            border-bottom: 1.5px solid #d3d3d3;
            background: #f9f9f9;
        }
        .services-add-form input[type="text"],
        .services-add-form input[type="number"] {
            padding: 10px;
            border: 2px solid #000;
            border-radius: 4px;
            font-size: 1em;
            font-family: 'Montserrat', sans-serif;
            flex: 1;
        }
        .services-add-form button {
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .services-add-form button:hover {
            background-color: #218838;
        }
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        .services-table th, .services-table td {
            border: 1px solid #000;
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }
        .services-table thead {
            background-color: #d3d3d3;
        }
        .services-table th {
            font-weight: 700;
        }
        .services-actions {
            display: flex;
            gap: 8px;
        }
        .services-actions .delete-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .services-actions .delete-btn:hover {
            background: #b52a37;
        }
        .services-actions .add-to-list-btn {
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 6px 14px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .services-actions .add-to-list-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="loader-wrapper" id="loader-wrapper">
        <div class="loader"></div>
    </div>
    <div class="dashboard-container">
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/thrivehut logo png.png" alt="TMI Logo" class="logo-sidebar">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">TRANSACTION</a>
                <a href="sales.php" class="nav-item">SALES</a>
                <a href="inventory.php" class="nav-item">INVENTORY</a>
                <a href="returned_item.php" class="nav-item">RETURNED ITEM</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="nav-item-logout" id="logout-link">EXIT</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>THRIVEHUT MOTORWORKS INVENTORY AND SALES AUDIT SYSTEM</h1>
            </header>
            <section class="filters-and-search">
                 <div class="filter-controls">
                    <form action="index.php" method="GET" id="filter-sort-form">
                        <a href="?filter=inventory&type=all" class="filter-btn-simple <?php echo ($type == 'all') ? 'active' : ''; ?>">ALL</a>
                        <a href="?filter=inventory&type=parts" class="filter-btn-simple <?php echo ($type == 'parts') ? 'active' : ''; ?>">PARTS</a>
                        <a href="?filter=inventory&type=products" class="filter-btn-simple <?php echo ($type == 'products') ? 'active' : ''; ?>">PRODUCTS</a>
                        <a href="?filter=services" class="filter-btn-simple <?php echo ($filter === 'services') ? 'active' : ''; ?>">SERVICES</a>
                    </form>
                </div>
                <div class="search-bar">
                    <form action="index.php" method="GET" style="display: flex;">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        <input type="text" name="search" placeholder="SEARCH..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">SEARCH</button>
                    </form>
                </div>
            </section>
            <?php if ($filter !== 'services'): ?>
            <section class="item-table-container">
                <table class="item-table">
                     <thead>
                         <tr>
                             <th>PRODUCT ID</th>
                             <th>BRAND</th>
                             <th>PRODUCT NAME</th>
                             <th>QUANTITY</th>
                             <th>PRICE</th>
                             <th>ACTIONS</th>
                         </tr>
                     </thead>
                     <tbody>
                         <?php foreach ($inventory_items as $item): ?>
                             <tr>
                                 <td><?php echo htmlspecialchars($item['Product_ID']); ?></td>
                                 <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                                 <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                 <td>
                                    <?php if ($item['Quantity'] > 0): ?>
                                        <?php echo htmlspecialchars($item['Quantity']); ?>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">Out of Stock</span>
                                    <?php endif; ?>
                                 </td>
                                 <td>₱<?php echo number_format($item['Price'], 2); ?></td>
                                 <td>
                                     <button class="action-btn add-to-list" 
                                             data-id="<?php echo htmlspecialchars($item['Product_ID']); ?>"
                                             <?php if ($item['Quantity'] <= 0) echo 'disabled'; ?>>
                                         Add to List
                                     </button>
                                 </td>
                             </tr>
                         <?php endforeach; ?>
                     </tbody>
                </table>
            </section>
            <?php endif; ?>
            <?php if ($filter === 'services'): ?>
            <section class="services-section">
                <form method="POST" class="services-add-form">
                    <input type="text" name="service_name" placeholder="Service Name" required>
                    <input type="number" name="service_price" placeholder="Price" min="0" step="0.01" required>
                    <button type="submit" name="add_service" title="Add Service">+</button>
                </form>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Services</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr><td colspan="3" style="text-align:center;">No services found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($services as $i => $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td>₱<?php echo number_format($service['price'], 2); ?></td>
                                <td class="services-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_service" value="<?php echo $i; ?>">
                                        <button type="submit" class="delete-btn" title="Delete Service">&#128465;</button>
                                    </form>
                                    <button class="add-to-list-btn add-service-to-list" data-index="<?php echo $i; ?>">Add to List</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.add-service-to-list').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const index = this.getAttribute('data-index');
                        fetch('ajax_handler.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'action=add_service_to_cart&index=' + index
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Failed to add service to list.');
                            }
                        });
                    });
                });
            });
            </script>
            <?php endif; ?>
        </main>

        <!-- Right Sidebar for Item List -->
        <aside class="item-list-sidebar">
            <div class="item-list-header">
                <h2>ITEM LIST</h2>
                <div class="transaction-info">
                    <p>DATE: <span id="current-date"><?php echo date('Y-m-d'); ?></span></p>
                    <p>TRANSACTION NUMBER: <span id="transaction-number">--</span></p>
                </div>
            </div>
            <div class="selected-items-container">
                <table class="selected-items-table">
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>QTY</th>
                            <th>PRICE</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-body">
                        <!-- Cart items will be injected here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="item-list-footer">
                <div class="discount-section">
                    <label for="discount-type">DISCOUNT:</label>
                    <select id="discount-type">
                        <option value="none">None</option>
                        <option value="pwd_senior">PWD/Senior Citizen (20%)</option>
                    </select>
                </div>
                <p style="font-size: 0.95em; color: #555; margin: 0 0 10px 0; text-align: right;">
                    <em>Note: PWD/Senior Citizen discount (20%) is applied to the total bill.</em>
                </p>
                <div class="total-section">
                    <label>TOTAL:</label>
                    <span class="total-amount" id="total-amount">₱0.00</span>
                </div>
                <button class="confirm-btn" id="confirm-transaction-btn">CONFIRM TRANSACTION</button>
            </div>
        </aside>
    </div>

    <!-- Payment Method Modal -->
    <div id="payment-modal" class="payment-modal-overlay">
        <div class="payment-modal-content">
            <div class="payment-modal-header">
                <img src="../images/back-button-icon.png" alt="Back" class="back-button-modal" id="modal-back-btn">
                PAYMENT METHOD
            </div>
            <div class="payment-options">
                <div class="payment-option" data-method="cash">
                    <img src="../images/cash-icon.png" alt="Cash">
                    <h3>CASH</h3>
                </div>
                <div class="payment-option" data-method="cashless">
                    <img src="../images/cashless2-icon.png" alt="Cashless">
                    <h3>CASHLESS</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Payment Modal -->
    <div id="cash-payment-modal" class="payment-modal-overlay">
        <div class="payment-modal-content">
            <div class="payment-modal-header">
                <img src="../images/back-button-icon.png" alt="Back" class="back-button-modal" id="cash-back-btn">
                PAYMENT METHOD
            </div>
            <div id="cash-payment-interface" class="payment-interface-container">
                <div class="payment-main-content">
                    <img src="../images/cash-icon.png" alt="Cash" class="payment-icon">
                    <h3>CASH</h3>
                    <div class="calculator">
                        <input type="text" id="cash-amount">
                        <div class="calculator-grid">
                            <button>7</button><button>8</button><button>9</button>
                            <button>4</button><button>5</button><button>6</button>
                            <button>1</button><button>2</button><button>3</button>
                            <button class="span-2">0</button><button>CLEAR</button>
                        </div>
                        <div class="calculator-actions">
                            <button id="pay-cash-btn">PAY</button>
                            <button id="cancel-cash-btn">CANCEL</button>
                        </div>
                    </div>
                </div>
                <div class="receipt-preview" id="cash-receipt-preview">
                    <div class="receipt-header">
                        <img src="../images/thrivehut logo png.png" alt="Logo" class="logo">
                        <p>THRIVEHUT MOTORWORKS</p>
                        <p>Blk 4 Lot 1 Queensville, Bagumbong, North Caloocan City</p>
                    </div>
                    <div class="receipt-info">
                        <p>DATE: <span id="cash-receipt-date"></span></p>
                        <p>TIME: <span id="cash-receipt-time"></span></p>
                        <p>TRANSACTION NUMBER: <span id="cash-receipt-transaction"></span></p>
                    </div>
                    <div class="receipt-items">
                        <table>
                            <thead>
                                <tr>
                                    <th>ITEM</th>
                                    <th>QTY</th>
                                    <th>PRICE</th>
                                </tr>
                            </thead>
                            <tbody id="cash-receipt-items">
                            </tbody>
                        </table>
                    </div>
                    <div class="receipt-summary">
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span id="cash-receipt-subtotal"></span>
                        </div>
                        <div class="summary-line" id="cash-discount-line" style="display: none;">
                            <span>Discount (20%)</span>
                            <span id="cash-receipt-discount"></span>
                        </div>
                        <div class="summary-line">
                            <span>Amount Tendered</span>
                            <span id="cash-receipt-tendered">₱0.00</span>
                        </div>
                        <div class="summary-line">
                            <span>Change</span>
                            <span id="cash-receipt-change">₱0.00</span>
                        </div>
                        <div class="summary-line total">
                            <span>TOTAL</span>
                            <span id="cash-receipt-total"></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Final Success View -->
            <div id="cash-success-view" class="payment-success-view hidden">
                <div class="success-receipt-preview"></div>
                <div class="success-actions">
                    <div class="success-action-item">
                        <button class="print-receipt-btn">Print</button>
                    </div>
                    <div class="success-action-item">
                        <button class="download-pdf-btn">Download PDF</button>
                    </div>
                    <div class="success-action-item">
                        <button class="back-to-dashboard-btn">Back to Dashboard</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cashless Payment Modal -->
    <div id="cashless-payment-modal" class="payment-modal-overlay">
        <div class="payment-modal-content">
            <div class="payment-modal-header">
                <img src="../images/back-button-icon.png" alt="Back" class="back-button-modal" id="cashless-back-btn">
                PAYMENT METHOD
            </div>
            <div id="cashless-payment-interface" class="payment-interface-container">
                <div class="payment-main-content">
                    <img src="../images/cashless2-icon.png" alt="Cashless" class="payment-icon">
                    <h3>CASHLESS</h3>
                    <div class="cashless-form">
                        <div class="form-group">
                            <label>ACCOUNT NUMBER</label>
                            <input type="text" id="account-number" placeholder="Customer's Account Number">
                        </div>
                        <div class="form-group">
                            <label>REFERENCE NUMBER</label>
                            <input type="text" id="reference-number" placeholder="Reference Number">
                        </div>
                        <div class="form-group">
                            <label>ACCOUNT NAME</label>
                            <input type="text" id="account-name" placeholder="Customer's Account Name">
                        </div>
                        <button id="proceed-cashless-btn">PROCEED</button>
                    </div>
                </div>
                <div class="receipt-preview" id="cashless-receipt-preview">
                    <div class="receipt-header">
                        <img src="../images/thrivehut logo png.png" alt="Logo" class="logo">
                        <p>THRIVEHUT MOTORWORKS</p>
                        <p>Blk 4 Lot 1 Queensville, Bagumbong, North Caloocan City</p>
                    </div>
                    <div class="receipt-info">
                        <p>DATE: <span id="cashless-receipt-date"></span></p>
                        <p>TIME: <span id="cashless-receipt-time"></span></p>
                        <p>TRANSACTION NUMBER: <span id="cashless-receipt-transaction"></span></p>
                    </div>
                    <div class="receipt-items">
                        <table>
                            <thead>
                                <tr>
                                    <th>ITEM</th>
                                    <th>QTY</th>
                                    <th>PRICE</th>
                                </tr>
                            </thead>
                            <tbody id="cashless-receipt-items">
                            </tbody>
                        </table>
                    </div>
                    <div class="receipt-summary">
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span id="cashless-receipt-subtotal"></span>
                        </div>
                        <div class="summary-line" id="cashless-discount-line" style="display: none;">
                            <span>Discount (20%)</span>
                            <span id="cashless-receipt-discount"></span>
                        </div>
                        <div class="summary-line total">
                            <span>TOTAL</span>
                            <span id="cashless-receipt-total"></span>
                        </div>
                        <div class="summary-line" style="margin-top: 10px;">
                            <span>Account Name:</span>
                            <span id="cashless-receipt-acc-name"></span>
                        </div>
                         <div class="summary-line">
                            <span>Account No:</span>
                            <span id="cashless-receipt-acc-num"></span>
                        </div>
                         <div class="summary-line">
                            <span>Reference No:</span>
                            <span id="cashless-receipt-ref-num"></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Final Success View -->
            <div id="cashless-success-view" class="payment-success-view hidden">
                <div class="success-receipt-preview"></div>
                <div class="success-actions">
                    <div class="success-action-item">
                        <button class="print-receipt-btn">Print</button>
                    </div>
                    <div class="success-action-item">
                        <button class="download-pdf-btn">Download PDF</button>
                    </div>
                    <div class="success-action-item">
                        <button class="back-to-dashboard-btn">Back to Dashboard</button>
                    </div>
                </div>
            </div>
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

    <script>
    // Hide loader on window load
    window.addEventListener('load', function() {
        const loader = document.getElementById('loader-wrapper');
        loader.style.display = 'none';
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const cartBody = document.getElementById('cart-items-body');
        const totalAmountEl = document.getElementById('total-amount');
        const discountSelect = document.getElementById('discount-type');
        const confirmBtn = document.getElementById('confirm-transaction-btn');
        const paymentModal = document.getElementById('payment-modal');
        const modalBackBtn = document.getElementById('modal-back-btn');

        // Function to update the cart display
        async function updateCart() {
            try {
                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_cart' // Although handled by default, explicit action can be useful
                });
                const data = await response.json();
                renderCart(data);
            } catch (error) {
                console.error('Error fetching cart:', error);
            }
        }

        // Function to render the cart in the sidebar
        function renderCart(data) {
            cartBody.innerHTML = '';
            if (data.cart.length === 0) {
                cartBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No items added.</td></tr>';
            } else {
                data.cart.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.brand} - ${item.name} (${item.type})</td>
                        <td>
                            <div class="quantity-controls">
                                <button class="quantity-btn" data-id="${item.product_id}" data-change="-1">-</button>
                                <span>${item.quantity}</span>
                                <button class="quantity-btn" data-id="${item.product_id}" data-change="1">+</button>
                            </div>
                        </td>
                        <td>₱${(item.price * item.quantity).toFixed(2)}</td>
                        <td><button class="remove-btn" data-id="${item.product_id}">✖</button></td>
                    `;
                    cartBody.appendChild(row);
                });
            }
            totalAmountEl.textContent = `₱${data.total.toFixed(2)}`;
            discountSelect.value = data.discount.type;
        }

        // Event Delegation for action buttons
        document.body.addEventListener('click', async function(e) {
            const target = e.target;
            let action = '';
            let body = '';

            if (target.classList.contains('add-to-list')) {
                action = 'add_to_cart';
                body = `action=${action}&product_id=${target.dataset.id}`;
            } else if (target.classList.contains('quantity-btn')) {
                action = 'update_quantity';
                body = `action=${action}&product_id=${target.dataset.id}&change=${target.dataset.change}`;
            } else if (target.classList.contains('remove-btn')) {
                action = 'remove_from_cart';
                body = `action=${action}&product_id=${target.dataset.id}`;
            }

            if (action) {
                try {
                    const response = await fetch('ajax_handler.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: body
                    });
                    const data = await response.json();
                    renderCart(data);
                } catch (error) {
                    console.error('Error performing action:', error);
                }
            }
        });

        // Event for discount change
        discountSelect.addEventListener('change', async function() {
            try {
                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=apply_discount&discount_type=${this.value}`
                });
                const data = await response.json();
                renderCart(data);
            } catch (error) {
                console.error('Error applying discount:', error);
            }
        });

        // Event for showing the payment modal
        confirmBtn.addEventListener('click', function() {
            // Check if cart is empty
            if (cartBody.querySelector('.remove-btn') === null) {
                alert('Cannot confirm transaction with an empty cart.');
                return;
            }
            paymentModal.style.display = 'flex';
        });

        // Event for hiding the payment modal
        modalBackBtn.addEventListener('click', function() {
            paymentModal.style.display = 'none';
        });

        // Event for selecting a payment method
        document.querySelector('.payment-options').addEventListener('click', async function(e) {
            const paymentOption = e.target.closest('.payment-option');
            if (!paymentOption) return;

            const paymentMethod = paymentOption.dataset.method;
            paymentModal.style.display = 'none';
            
            function updateTime(prefix) {
                const timeEl = document.getElementById(`${prefix}-receipt-time`);
                if (timeEl) {
                    const now = new Date();
                    timeEl.textContent = now.toLocaleTimeString('en-US');
                }
            }
            
            // Function to populate receipt items
            function populateReceipt(modalPrefix) {
                const cartItems = Array.from(cartBody.querySelectorAll('tr')).map(row => {
                    if (!row.querySelector('.quantity-btn')) return null;
                    const itemText = row.cells[0].textContent;
                    const quantity = parseInt(row.cells[1].querySelector('span').textContent);
                    const price = parseFloat(row.cells[2].textContent.replace('₱', ''));
                    const [brand, rest] = itemText.split(' - ');
                    const [name, type] = rest.split(' (');
                    
                    return {
                        brand, name, type: type.replace(')', ''),
                        quantity, price: price / quantity
                    };
                }).filter(item => item !== null);

                const tbody = document.getElementById(`${modalPrefix}-receipt-items`);
                tbody.innerHTML = '';
                let subtotal = 0;
                cartItems.forEach(item => {
                    const row = document.createElement('tr');
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    row.innerHTML = `
                        <td>${item.brand} - ${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>₱${itemTotal.toFixed(2)}</td>
                    `;
                    tbody.appendChild(row);
                });

                const discountType = discountSelect.value;
                const discountLine = document.getElementById(`${modalPrefix}-discount-line`);
                
                document.getElementById(`${modalPrefix}-receipt-subtotal`).textContent = `₱${subtotal.toFixed(2)}`;
                
                if (discountType === 'pwd_senior') {
                    const discountAmount = subtotal * 0.20;
                    document.getElementById(`${modalPrefix}-receipt-discount`).textContent = `- ₱${discountAmount.toFixed(2)}`;
                    discountLine.style.display = 'flex';
                } else {
                    discountLine.style.display = 'none';
                }

                document.getElementById(`${modalPrefix}-receipt-total`).textContent = totalAmountEl.textContent;
                
                // Set Date and Time
                document.getElementById(`${modalPrefix}-receipt-date`).textContent = document.getElementById('current-date').textContent;
                document.getElementById(`${modalPrefix}-receipt-transaction`).textContent = document.getElementById('transaction-number').textContent;
                updateTime(modalPrefix);
            }

            if (paymentMethod === 'cash') {
                document.getElementById('cash-payment-modal').style.display = 'flex';
                populateReceipt('cash');
                document.getElementById('pay-cash-btn').disabled = true; // Disable until sufficient cash is entered
            } else if (paymentMethod === 'cashless') {
                document.getElementById('cashless-payment-modal').style.display = 'flex';
                populateReceipt('cashless');
            }
        });

        // --- Calculator and Cash Modal Logic ---
        const calculatorGrid = document.querySelector('.calculator-grid');
        const cashAmountInput = document.getElementById('cash-amount');

        // Handle calculator button clicks
        calculatorGrid.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON') return;
            const value = e.target.textContent;

            if (value === 'CLEAR') {
                cashAmountInput.value = '';
            } else {
                cashAmountInput.value += value;
            }
            // Manually trigger the 'input' event to update the change calculation
            cashAmountInput.dispatchEvent(new Event('input'));
        });

        // Handle keyboard input for cash amount and calculate change
        cashAmountInput.addEventListener('input', () => {
            const tendered = parseFloat(cashAmountInput.value.replace(/[^0-9.]/g, '')) || 0;
            const totalText = document.getElementById('cash-receipt-total').textContent;
            const total = parseFloat(totalText.replace('₱', '')) || 0;
            
            document.getElementById('cash-receipt-tendered').textContent = `₱${tendered.toFixed(2)}`;
            const change = tendered - total;
            const changeEl = document.getElementById('cash-receipt-change');
            
            if (change >= 0) {
                changeEl.textContent = `₱${change.toFixed(2)}`;
                document.getElementById('pay-cash-btn').disabled = false;
            } else {
                changeEl.textContent = '₱0.00';
                document.getElementById('pay-cash-btn').disabled = true;
            }
        });

        // --- Cashless Payment Logic ---
        document.getElementById('account-name').addEventListener('input', (e) => {
            document.getElementById('cashless-receipt-acc-name').textContent = e.target.value;
        });
        document.getElementById('reference-number').addEventListener('input', (e) => {
            document.getElementById('cashless-receipt-ref-num').textContent = e.target.value;
        });
        document.getElementById('account-number').addEventListener('input', (e) => {
            const num = e.target.value;
            const masked = num.substring(0, 4) + '*'.repeat(Math.max(0, num.length - 4));
            document.getElementById('cashless-receipt-acc-num').textContent = masked;
        });
        
        // --- Modal Navigation ---
        function showPaymentMethodModal() {
            document.getElementById('cash-payment-modal').style.display = 'none';
            document.getElementById('cashless-payment-modal').style.display = 'none';
            document.getElementById('payment-modal').style.display = 'flex';
        }

        document.getElementById('cash-back-btn').addEventListener('click', showPaymentMethodModal);
        document.getElementById('cashless-back-btn').addEventListener('click', showPaymentMethodModal);
        document.getElementById('cancel-cash-btn').addEventListener('click', showPaymentMethodModal);

        // --- Transaction Processing ---
        document.getElementById('pay-cash-btn').addEventListener('click', async function() {
            const amount = cashAmountInput.value;
            if (!amount) {
                alert('Please enter an amount');
                return;
            }

            try {
                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=process_sale&payment_method=cash&amount=${amount}&transaction_number=${document.getElementById('transaction-number').textContent}`
                });
                const result = await response.json();

                if (result.success) {
                    // Update the transaction number in the sidebar and receipt
                    document.getElementById('transaction-number').textContent = result.transaction_number;
                    document.getElementById('cash-receipt-transaction').textContent = result.transaction_number;

                    // Now copy the updated receipt preview
                    const receiptContent = document.getElementById('cash-receipt-preview').innerHTML;
                    const successView = document.getElementById('cash-success-view');
                    successView.querySelector('.success-receipt-preview').innerHTML = receiptContent;

                    document.getElementById('cash-payment-interface').classList.add('hidden');
                    document.querySelector('#cash-payment-modal .payment-modal-header').classList.add('hidden');
                    successView.classList.remove('hidden');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error processing sale:', error);
                alert('A critical error occurred.');
            }
        });

        // Cashless payment modal events
        document.getElementById('proceed-cashless-btn').addEventListener('click', async function() {
            const accountNumber = document.getElementById('account-number').value;
            const referenceNumber = document.getElementById('reference-number').value;
            const accountName = document.getElementById('account-name').value;

            if (!accountNumber || !referenceNumber || !accountName) {
                alert('Please fill in all customer and reference details.');
                return;
            }
            
            const isConfirmed = confirm("Please confirm that the payment has been successfully received from the customer. This action cannot be undone.");
            if (!isConfirmed) return;

            try {
                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=process_sale&payment_method=cashless&account_number=${accountNumber}&transaction_number=${document.getElementById('transaction-number').textContent}&reference_number=${referenceNumber}&account_name=${accountName}`
                });
                const result = await response.json();

                if (result.success) {
                    // Update the transaction number in the sidebar and receipt
                    document.getElementById('transaction-number').textContent = result.transaction_number;
                    document.getElementById('cashless-receipt-transaction').textContent = result.transaction_number;

                    // Now copy the updated receipt preview
                    const receiptContent = document.getElementById('cashless-receipt-preview').innerHTML;
                    const successView = document.getElementById('cashless-success-view');
                    successView.querySelector('.success-receipt-preview').innerHTML = receiptContent;

                    document.getElementById('cashless-payment-interface').classList.add('hidden');
                    document.querySelector('#cashless-payment-modal .payment-modal-header').classList.add('hidden');
                    successView.classList.remove('hidden');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error processing sale:', error);
                alert('A critical error occurred.');
            }
        });
        
        // --- Final Actions Handlers ---
        document.querySelectorAll('.back-to-dashboard-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                window.location.reload();
            });
        });

        document.querySelectorAll('.print-receipt-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const successView = e.target.closest('.payment-success-view');
                const receiptPreview = successView.querySelector('.success-receipt-preview');
                
                // Temporarily make a printable version
                const printable = receiptPreview.cloneNode(true);
                printable.classList.add('printable-receipt');
                document.body.appendChild(printable);
                
                window.print();
                
                document.body.removeChild(printable);
            });
        });

        document.querySelectorAll('.download-pdf-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const successView = e.target.closest('.payment-success-view');
                const receiptPreview = successView.querySelector('.success-receipt-preview');
                const transactionNumber = document.getElementById('transaction-number').textContent;
                const { jsPDF } = window.jspdf;

                try {
                    const canvas = await html2canvas(receiptPreview, { scale: 2 });
                    const imgData = canvas.toDataURL('image/png');
                    
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'pt',
                        format: [canvas.width, canvas.height]
                    });
                    
                    pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                    pdf.save(`Thrivehut_Receipt_${transactionNumber}.pdf`);

                } catch (error) {
                    console.error('Error generating PDF:', error);
                    alert('Could not generate PDF. Please try again.');
                }
            });
        });
        
        // Initial cart load
        updateCart();

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
    });
    </script>
</body>
</html> 
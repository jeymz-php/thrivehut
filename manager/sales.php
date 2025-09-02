<?php
session_start();
// Ensure the user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login/");
    exit();
}

require '../config/database.php';

// --- Filtering Logic ---
$filter = $_GET['filter'] ?? '';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$where_clauses = [];
$params = [];
$sales_data = [];
$total_sales = 0;

if ($filter === 'this_month') {
    $where_clauses[] = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
} elseif ($filter === 'quarterly') {
    $current_quarter = ceil(date('n') / 3);
    $where_clauses[] = "YEAR(created_at) = :year AND QUARTER(created_at) = :quarter";
    $params[':year'] = $year;
    $params[':quarter'] = $current_quarter;
} elseif ($filter === 'annually') {
    $where_clauses[] = "YEAR(created_at) = :year";
    $params[':year'] = $year;
} elseif (isset($_GET['month']) && isset($_GET['year']) && $_GET['month'] && $_GET['year']) {
    $where_clauses[] = "YEAR(created_at) = :year AND MONTH(created_at) = :month";
    $params[':year'] = $year;
    $params[':month'] = $month;
}

// --- Logic for Comparison ---
$is_comparison = ($filter === 'compare');
$sales_data_period1 = [];
$sales_data_period2 = [];
$total_sales_period1 = 0;
$total_sales_period2 = 0;
$period1_label = '';
$period2_label = '';

if ($is_comparison) {
    // Sanitize inputs for period 1
    $month1 = $_GET['month1'] ?? date('m');
    $year1 = $_GET['year1'] ?? date('Y');
    $period1_label = date('F Y', strtotime("$year1-$month1-01"));

    // Fetch data for period 1
    $stmt1 = $db->prepare("SELECT * FROM sales WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month ORDER BY created_at DESC");
    $stmt1->execute([':year' => $year1, ':month' => $month1]);
    $sales_data_period1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $total_sales_period1 = array_sum(array_column($sales_data_period1, 'price'));

    // Sanitize inputs for period 2
    $month2 = $_GET['month2'] ?? date('m');
    $year2 = $_GET['year2'] ?? date('Y');
    $period2_label = date('F Y', strtotime("$year2-$month2-01"));

    // Fetch data for period 2
    $stmt2 = $db->prepare("SELECT * FROM sales WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month ORDER BY created_at DESC");
    $stmt2->execute([':year' => $year2, ':month' => $month2]);
    $sales_data_period2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $total_sales_period2 = array_sum(array_column($sales_data_period2, 'price'));

} else {
    // Fetch sales data from the sales table
    $sql = "SELECT transaction_number, payment_method, price, discount, DATE(created_at) AS date, gcash_account_name FROM sales";
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Calculate total
    $total_sales = array_sum(array_column($sales_data, 'price'));
}

// Fetch service sales data
$services_sales_data = [];
$services_total_sales = 0;
if (!$is_comparison) {
    $sql_services = "SELECT transaction_number, payment_method, total_price, discount, DATE(created_at) AS date, gcash_account_name FROM services_sales ORDER BY created_at DESC";
    $stmt_services = $db->prepare($sql_services);
    $stmt_services->execute();
    $services_sales_data = $stmt_services->fetchAll(PDO::FETCH_ASSOC);
    $services_total_sales = array_sum(array_column($services_sales_data, 'total_price'));
}

// For year/month dropdowns
$years_stmt = $db->query("SELECT DISTINCT YEAR(created_at) as year FROM sales ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', 
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', 
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// --- Get image data as Base64 ---
// $image_path = '../images/thrivehut logo png.png';
// $image_data = base64_encode(file_get_contents($image_path));
// $logo_src = 'data:image/png;base64,' . $image_data;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Thrivehut Motorworks</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <style>
        .receipt-modal, .compare-modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);
            align-items: center; justify-content: center;
        }
        .receipt-modal-content, .compare-modal-content {
            background-color: #fff; margin: auto; padding: 25px 30px; border: none;
            width: 450px; font-family: 'Montserrat', sans-serif;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; border-radius: 8px;
        }
        .receipt-modal-content { font-family: 'Courier New', monospace; }
        .receipt-modal-header { text-align: center; border-bottom: 1px dashed #ccc; padding-bottom: 15px; margin-bottom: 15px; }
        .receipt-modal-header .logo { width: 90px; margin-bottom: 10px; }
        .receipt-modal-header p { margin: 2px 0; font-size: 14px; }
        .receipt-info p { display: flex; justify-content: space-between; margin: 5px 0; font-size: 14px; }
        .receipt-modal-items table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 20px 0; }
        .receipt-modal-items th, .receipt-modal-items td { padding: 8px 5px; text-align: left; }
        .receipt-modal-items thead { border-bottom: 1px solid #000; }
        .receipt-modal-summary { margin-top: 15px; font-size: 14px; border-top: 1px solid #000; padding-top: 10px; }
        .receipt-modal-summary .summary-line { display: flex; justify-content: space-between; padding: 4px 0; }
        .close-modal-btn {
            position: absolute; top: 10px; right: 20px; font-size: 30px;
            font-weight: bold; cursor: pointer; color: #aaa;
        }
        .close-modal-btn:hover { color: #000; }
        
        /* Compare Modal Styles */
        .compare-modal-content h2 { text-align: center; margin-bottom: 20px; }
        .compare-section { padding: 15px; border: 1px solid #eee; border-radius: 5px; margin-bottom: 15px; }
        .compare-section h3 { margin-top: 0; font-size: 1.1em; }
        .comparison-summary {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .comparison-summary h2 { margin-top: 0; }
        .comparison-tables {
            display: flex;
            gap: 20px;
        }
        .comparison-tables .item-table-container {
            width: 50%;
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
    </style>
</head>
<body>
    <div class="loader-wrapper" id="loader-wrapper">
        <div class="loader"></div>
    </div>
    <div class="logout-modal" id="logout-modal">
        <div class="logout-modal-content">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to exit the system?</p>
            <button class="confirm-logout">Yes, Exit</button>
            <button class="cancel-logout">Cancel</button>
        </div>
    </div>
    <div class="dashboard-container">
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/thrivehut logo png.png" alt="TMI Logo" class="logo-sidebar">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">TRANSACTION</a>
                <a href="sales.php" class="nav-item active">SALES</a>
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
            <section class="sales-filters">
                <div class="top-filters">
                    <button id="show-product-sales" class="filter-btn-simple active" type="button">Show Product Sales</button>
                    <button id="show-service-sales" class="filter-btn-simple" type="button">Show Service Sales</button>
                    <a href="?filter=this_month" class="filter-btn-simple <?php if($filter == 'this_month') echo 'active'; ?>">THIS MONTH</a>
                    <a href="?filter=quarterly" class="filter-btn-simple <?php if($filter == 'quarterly') echo 'active'; ?>">QUARTERLY</a>
                    <a href="?filter=annually" class="filter-btn-simple <?php if($filter == 'annually') echo 'active'; ?>">ANNUALLY</a>
                </div>
                <div class="bottom-filters">
                    <form action="sales.php" method="GET" class="date-search-form">
                        <select name="month" id="month-select">
                            <option>SELECT MONTH</option>
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php if ($month == $num) echo 'selected'; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" id="year-select">
                            <option>SELECT YEAR</option>
                             <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['year']; ?>" <?php if ($year == $y['year']) echo 'selected'; ?>>
                                    <?php echo $y['year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="search-btn">SEARCH</button>
                    </form>
                </div>
            </section>
            <section class="item-table-container" id="sales-report-table">
                <?php if ($is_comparison): ?>
                    <section class="comparison-summary">
                        <h2>Sales Comparison</h2>
                        <p>
                            <strong><?php echo $period1_label; ?>:</strong> ₱<?php echo number_format($total_sales_period1, 2); ?>
                            &nbsp;&nbsp;&nbsp;VS&nbsp;&nbsp;&nbsp;
                            <strong><?php echo $period2_label; ?>:</strong> ₱<?php echo number_format($total_sales_period2, 2); ?>
                        </p>
                    </section>
                    <div class="comparison-tables">
                        <section class="item-table-container">
                            <h3><?php echo $period1_label; ?></h3>
                            <table class="item-table">
                                <thead>
                                    <tr><th>TRANS #</th><th>DATE</th><th>PAYMENT</th><th>AMOUNT</th><th>ACTION</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($sales_data_period1)): ?>
                                        <tr><td colspan="5">No sales.</td></tr>
                                    <?php else: foreach ($sales_data_period1 as $sale): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sale['transaction_number']); ?></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($sale['created_at']))); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($sale['payment_method'])); ?></td>
                                            <td>₱<?php echo number_format($sale['price'], 2); ?></td>
                                            <td><button class="view-receipt-btn" data-tid="<?php echo htmlspecialchars($sale['transaction_number']); ?>">View</button></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </section>
                        <section class="item-table-container">
                            <h3><?php echo $period2_label; ?></h3>
                            <table class="item-table">
                               <thead>
                                    <tr><th>TRANS #</th><th>DATE</th><th>PAYMENT</th><th>AMOUNT</th><th>ACTION</th></tr>
                                </thead>
                                 <tbody>
                                    <?php if(empty($sales_data_period2)): ?>
                                        <tr><td colspan="5">No sales.</td></tr>
                                    <?php else: foreach ($sales_data_period2 as $sale): ?>
                                         <tr>
                                            <td><?php echo htmlspecialchars($sale['transaction_number']); ?></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($sale['created_at']))); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($sale['payment_method'])); ?></td>
                                            <td>₱<?php echo number_format($sale['price'], 2); ?></td>
                                            <td><button class="view-receipt-btn" data-tid="<?php echo htmlspecialchars($sale['transaction_number']); ?>">View</button></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </section>
                    </div>
                <?php else: ?>
                    <div id="product-sales-section">
                        <h2>Product Sales</h2>
                        <table class="item-table">
                            <thead>
                                <tr>
                                    <th>TRANSACTION #</th>
                                    <th>DATE</th>
                                    <th>MODE OF PAYMENT</th>
                                    <th>AMOUNT</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales_data)): ?>
                                    <tr><td colspan="5" style="text-align: center;">No sales data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sales_data as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['transaction_number']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['date']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($sale['payment_method'])); ?></td>
                                        <td>₱<?php echo number_format($sale['price'], 2); ?></td>
                                        <td><button class="view-receipt-btn" data-tid="<?php echo htmlspecialchars($sale['transaction_number']); ?>" data-type="product">View Receipt</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="sales-total" style="margin-bottom: 30px;">
                            <span>TOTAL PRODUCT SALES</span>
                            <span class="total-amount" id="product-sales-total">₱<?php echo number_format($total_sales, 2); ?></span>
                        </div>
                    </div>
                    <div id="service-sales-section" style="display:none;">
                        <h2>Service Sales</h2>
                        <table class="item-table">
                            <thead>
                                <tr>
                                    <th>TRANSACTION #</th>
                                    <th>DATE</th>
                                    <th>MODE OF PAYMENT</th>
                                    <th>AMOUNT</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($services_sales_data)): ?>
                                    <tr><td colspan="5" style="text-align: center;">No service sales data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($services_sales_data as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['transaction_number']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['date']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($sale['payment_method'])); ?></td>
                                        <td>₱<?php echo number_format($sale['total_price'], 2); ?></td>
                                        <td><button class="view-receipt-btn" data-tid="<?php echo htmlspecialchars($sale['transaction_number']); ?>" data-type="service">View Receipt</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="sales-total">
                            <span>TOTAL SERVICE SALES</span>
                            <span class="total-amount" id="service-sales-total">₱<?php echo number_format($services_total_sales, 2); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
            <footer class="sales-footer">
                <div class="sales-total">
                    <span>TOTAL</span>
                    <span class="total-amount">₱<?php echo number_format($total_sales + $services_total_sales, 2); ?></span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="receipt-modal">
        <div class="receipt-modal-content">
            <span class="close-modal-btn">&times;</span>
            <div class="receipt-modal-header">
                <img src="../images/thrivehut logo png.png" alt="Logo" class="logo">
                <p>THRIVEHUT MOTORWORKS</p>
                <p>Blk 4 Lot 1 Queensville, Bagumbong, North Caloocan City</p>
            </div>
            <div class="receipt-info">
                <p>Date: <span id="modal-receipt-date"></span></p>
                <p>Transaction #: <span id="modal-receipt-tid"></span></p>
            </div>
            <div class="receipt-modal-items">
                <table>
                    <thead>
                        <tr><th>ITEM</th><th>QTY</th><th>PRICE</th></tr>
                    </thead>
                    <tbody id="modal-receipt-items"></tbody>
                </table>
            </div>
            <div class="receipt-modal-summary">
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span id="modal-receipt-subtotal"></span>
                </div>
                <div class="summary-line">
                    <span>Discount</span>
                    <span id="modal-receipt-discount"></span>
                </div>
                <div class="summary-line">
                    <span>Amount Tendered</span>
                    <span id="modal-receipt-tendered"></span>
                </div>
                <div class="summary-line">
                    <span>Change</span>
                    <span id="modal-receipt-change"></span>
                </div>
                <div class="summary-line" style="font-weight: bold;">
                    <span>TOTAL</span>
                    <span id="modal-receipt-total"></span>
                </div>
            </div>
        </div>
    </div>


    <script>
    // Hide loader on window load
    window.addEventListener('load', function() {
        const loader = document.getElementById('loader-wrapper');
        loader.style.display = 'none';
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('receipt-modal');
        const closeModalBtn = modal ? modal.querySelector('.close-modal-btn') : null;
        const downloadBtn = document.getElementById('download-pdf-btn');
        const monthSelect = document.querySelector('select[name="month"]');
        const yearSelect = document.querySelector('select[name="year"]');
        
        let currentFilterType = 'monthly'; // Default
        
        document.querySelectorAll('.view-receipt-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const transactionId = this.dataset.tid;
                const type = this.dataset.type;
                let url = 'ajax_handler.php';
                let body = '';
                if (type === 'service') {
                    body = `action=get_service_sale_details&transaction_id=${transactionId}`;
                } else {
                    body = `action=get_sale_details&transaction_id=${transactionId}`;
                }
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: body
                    });
                    const data = await response.json();
                    if (data.success) {
                        const { sale, items } = data;
                        // Populate modal
                        document.getElementById('modal-receipt-date').textContent = new Date(sale.created_at).toLocaleDateString();
                        document.getElementById('modal-receipt-tid').textContent = sale.transaction_number;
                        const itemsBody = document.getElementById('modal-receipt-items');
                        itemsBody.innerHTML = '';
                        let subtotal = 0;
                        if (type === 'service') {
                            items.forEach(item => {
                                const itemTotal = item.price * item.quantity;
                                subtotal += itemTotal;
                                const row = `<tr>
                                    <td>${item.service_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>₱${itemTotal.toFixed(2)}</td>
                                </tr>`;
                                itemsBody.innerHTML += row;
                            });
                        } else {
                            items.forEach(item => {
                                const itemTotal = item.Price * item.Quantity;
                                subtotal += itemTotal;
                                const row = `<tr>
                                    <td>${item.Product_Name}</td>
                                    <td>${item.Quantity}</td>
                                    <td>₱${itemTotal.toFixed(2)}</td>
                                </tr>`;
                                itemsBody.innerHTML += row;
                            });
                        }
                        const total = type === 'service' ? parseFloat(sale.total_price) : parseFloat(sale.price);
                        const discount = subtotal - total;
                        document.getElementById('modal-receipt-subtotal').textContent = `₱${subtotal.toFixed(2)}`;
                        document.getElementById('modal-receipt-discount').textContent = `- ₱${discount.toFixed(2)}`;
                        document.getElementById('modal-receipt-total').textContent = `₱${total.toFixed(2)}`;
                        // Show Amount Tendered and Change for cash, else show '-'
                        if (sale.payment_method === 'cash') {
                            document.getElementById('modal-receipt-tendered').textContent = `₱${parseFloat(sale.cash_amount).toFixed(2)}`;
                            document.getElementById('modal-receipt-change').textContent = `₱${parseFloat(sale.change_amount).toFixed(2)}`;
                        } else {
                            document.getElementById('modal-receipt-tendered').textContent = '-';
                            document.getElementById('modal-receipt-change').textContent = '-';
                        }
                        modal.style.display = 'flex';
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error fetching receipt:', error);
                    alert('An error occurred while fetching the receipt.');
                }
            });
        });

        if (closeModalBtn && modal) {
            closeModalBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        // Only add window click event for modal if modal exists
        if (modal) {
            window.addEventListener('click', (event) => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if we're in comparison view
                const urlParams = new URLSearchParams(window.location.search);
                const filter = urlParams.get('filter');
                
                if (filter === 'compare') {
                    alert('PDF download is not available for comparison view.');
                    return;
                }
                
                // If not in comparison view, proceed with the download
                const href = this.href;
                if (href && href !== '#') {
                    window.location.href = href;
                }
            });
        }

        // Toggle Product/Service Sales
        const btnProduct = document.getElementById('show-product-sales');
        const btnService = document.getElementById('show-service-sales');
        const sectionProduct = document.getElementById('product-sales-section');
        const sectionService = document.getElementById('service-sales-section');
        if (btnProduct && btnService && sectionProduct && sectionService) {
            btnProduct.addEventListener('click', function() {
                btnProduct.classList.add('active');
                btnService.classList.remove('active');
                sectionProduct.style.display = '';
                sectionService.style.display = 'none';
            });
            btnService.addEventListener('click', function() {
                btnService.classList.add('active');
                btnProduct.classList.remove('active');
                sectionService.style.display = '';
                sectionProduct.style.display = 'none';
            });
        }

        // Compare Modal Logic
        const compareBtn = document.getElementById('compare-btn');
        const compareModal = document.getElementById('compare-modal');
        const compareCloseBtn = compareModal ? compareModal.querySelector('.close-modal-btn') : null;
        if (compareBtn && compareModal) {
            compareBtn.addEventListener('click', function(e) {
                e.preventDefault();
                compareModal.style.display = 'flex';
            });
        }
        if (compareCloseBtn && compareModal) {
            compareCloseBtn.addEventListener('click', function() {
                compareModal.style.display = 'none';
            });
        }
        // Only add window click event for compareModal if compareModal exists
        if (compareModal) {
            window.addEventListener('click', function(event) {
                if (event.target == compareModal) {
                    compareModal.style.display = 'none';
                }
            });
        }

        // Logout modal logic
        const logoutLink = document.getElementById('logout-link');
        const logoutModal = document.getElementById('logout-modal');
        if (logoutLink && logoutModal) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
            });
            const confirmLogout = document.querySelector('.confirm-logout');
            const cancelLogout = document.querySelector('.cancel-logout');
            if (confirmLogout) confirmLogout.onclick = function() {
                window.location.href = '../logout.php';
            };
            if (cancelLogout) cancelLogout.onclick = function() {
                logoutModal.style.display = 'none';
            };
        }
    });
    </script>
</body>
</html> 
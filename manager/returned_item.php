<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login/");
    exit();
}
require '../config/database.php';

// Fetch all sales transactions
$sales_stmt = $db->query("SELECT transaction_number, DATE(created_at) as date, payment_method, price FROM sales ORDER BY created_at DESC");
$sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all returned items for display
$returned_items_stmt = $db->query("SELECT Product_ID, Product_Name, Brand, Type, Quantity, Price, Returned_At, Reason_of_Return_Item FROM returned_item ORDER BY Returned_At DESC");
$returned_items_data = $returned_items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returned Items - Thrivehut Motorworks</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <style>
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fff; margin: auto; padding: 25px 30px; border: none;
            width: 600px; font-family: 'Montserrat', sans-serif;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; border-radius: 8px;
        }
        .modal-header h2 {
            text-align: center; margin-bottom: 20px; font-size: 1.5em;
        }
        .close-modal-btn {
            position: absolute; top: 10px; right: 20px; font-size: 30px;
            font-weight: bold; cursor: pointer; color: #aaa;
        }
        .close-modal-btn:hover { color: #000; }
        
        .transaction-details {
            margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;
        }
        .transaction-details p { margin: 8px 0; display: flex; justify-content: space-between; }
        .items-header {
            margin-top: 20px; margin-bottom: 15px; text-align: center; font-size: 1.2em;
        }
        #ordered-items-table .return-qty-input {
            width: 50px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px;
        }
        #ordered-items-table .return-btn {
            padding: 5px 10px; cursor: pointer; border: 1px solid #000; border-radius: 3px;
        }
        #ordered-items-table .return-btn:disabled {
            cursor: not-allowed; background-color: #eee; color: #999;
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
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/thrivehut logo png.png" alt="TMI Logo" class="logo-sidebar">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">TRANSACTION</a>
                <a href="sales.php" class="nav-item">SALES</a>
                <a href="inventory.php" class="nav-item">INVENTORY</a>
                <a href="returned_item.php" class="nav-item active">RETURNED ITEM</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="nav-item-logout" id="logout-link">EXIT</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <h1>RETURNED ITEMS</h1>
            </header>
            <section class="item-table-container" id="returned-items-table">
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
                                <td><button class="view-list-btn" data-tid="<?php echo htmlspecialchars($sale['transaction_number']); ?>">View List</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
            
            <header class="main-header" style="margin-top: 40px;">
                <h1>RETURNED ITEMS HISTORY</h1>
            </header>
            <section class="item-table-container" id="returned-items-history-table">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>PRODUCT ID</th>
                            <th>PRODUCT NAME</th>
                            <th>BRAND</th>
                            <th>QTY</th>
                            <th>PRICE</th>
                            <th>DATE RETURNED</th>
                            <th>REASON</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($returned_items_data)): ?>
                            <tr><td colspan="7" style="text-align: center;">No returned items found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($returned_items_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['Product_ID']); ?></td>
                                <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                                <td><?php echo htmlspecialchars($item['Quantity']); ?></td>
                                <td>₱<?php echo number_format($item['Price'], 2); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($item['Returned_At']))); ?></td>
                                <td><?php echo htmlspecialchars($item['Reason_of_Return_Item']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <!-- View List Modal Placeholder -->
    <div id="view-list-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal-btn">&times;</span>
            <div class="modal-header">
                <h2>Transaction Details</h2>
            </div>
            <div class="transaction-details">
                <p><span>Transaction #:</span> <strong id="modal-trans-id"></strong></p>
                <p><span>Date:</span> <strong id="modal-trans-date"></strong></p>
                <p><span>Payment Mode:</span> <strong id="modal-trans-mop"></strong></p>
                <p><span>Total Amount:</span> <strong id="modal-trans-amount"></strong></p>
            </div>
            <h3 class="items-header">Ordered Items</h3>
            <div id="ordered-items-table">
                <!-- Table of ordered items will be loaded here via JS -->
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
    // Modal logic for viewing and returning items
    let currentTransactionId = null;
    document.querySelectorAll('.view-list-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.dataset.tid;
            currentTransactionId = transactionId;
            fetchOrderedItems(transactionId);
        });
    });
    document.querySelector('.close-modal-btn').onclick = function() {
        document.getElementById('view-list-modal').style.display = 'none';
        document.getElementById('ordered-items-table').innerHTML = '';
    };

    function fetchOrderedItems(transactionId) {
        fetch('ajax_handler.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_ordered_items', transaction_id: transactionId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Populate modal with sale details
                const sale = data.sale;
                document.getElementById('modal-trans-id').textContent = sale.transaction_number;
                document.getElementById('modal-trans-date').textContent = new Date(sale.created_at).toLocaleDateString();
                document.getElementById('modal-trans-mop').textContent = sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1);
                document.getElementById('modal-trans-amount').textContent = `₱${parseFloat(sale.price).toFixed(2)}`;
                
                showOrderedItemsTable(data.items, transactionId);
                document.getElementById('view-list-modal').style.display = 'flex';
            } else {
                alert(data.message || 'Failed to fetch items.');
            }
        });
    }

    function showOrderedItemsTable(items, transactionId) {
        let html = `<table style="width:100%;border-collapse:collapse;">
            <thead><tr><th>Item</th><th>Qty</th><th>Returned</th><th>Price</th><th>Action</th></tr></thead><tbody>`;
        for (const item of items) {
            const returnable = item.returnable_quantity > 0;
            html += `<tr>
                <td>${item.Product_Name}</td>
                <td>${item.Quantity}</td>
                <td>${item.returned_quantity}</td>
                <td>₱${parseFloat(item.Price).toFixed(2)}</td>
                <td>
                    <input type="number" class="return-qty-input" value="1" min="1" max="${item.returnable_quantity}" style="width: 50px;" ${!returnable ? 'disabled' : ''}>
                    <button class="return-btn" data-id="${item.Product_ID}" data-transaction="${transactionId}" ${!returnable ? 'disabled' : ''}>
                        ${returnable ? 'Return' : 'Returned'}
                    </button>
                </td>
            </tr>`;
        }
        html += '</tbody></table>';
        document.getElementById('ordered-items-table').innerHTML = html;
        
        document.querySelectorAll('.return-btn').forEach(btn => {
            btn.onclick = function() {
                const qtyInput = this.previousElementSibling;
                const quantity = parseInt(qtyInput.value);
                const maxQty = parseInt(qtyInput.max);
                if (quantity > maxQty) {
                    alert(`You can only return up to ${maxQty} item(s).`);
                    return;
                }
                const productId = this.dataset.id;
                const reason = prompt(`Reason for returning ${quantity} item(s):`);
                if (reason) {
                    returnItem(productId, quantity, reason, currentTransactionId);
                }
            };
        });
    }

    function returnItem(productId, quantity, reason, transactionId) {
        fetch('ajax_handler.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'return_item',
                product_id: productId,
                quantity: quantity,
                reason: reason,
                transaction_id: transactionId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Item returned successfully!');
                fetchOrderedItems(transactionId); // Refresh modal
                // Optionally, refresh the main returned items history list too
                location.reload();
            } else {
                alert(data.message || 'Failed to return item.');
            }
        });
    }

    // Logout Modal
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
    </script>
</body>
</html> 
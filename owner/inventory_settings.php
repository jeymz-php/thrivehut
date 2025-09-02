<?php
session_start();
// Ensure the user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login/");
    exit();
}

require '../config/database.php';

// Fetch archived items from the database
try {
    $stmt = $db->query("SELECT Product_ID, Brand, Product_Name, Type, Quantity, Price, Date_Acquired, Expiration_Date FROM inventory WHERE status = 'archived' ORDER BY Product_Name ASC");
    $archived_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle potential exceptions, e.g., database connection issues
    $archived_items = [];
    $error_message = "Error fetching archived items: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Settings - Archived Items</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <style>
        .settings-tabs {
            display: flex;
            border-bottom: 2px solid #ccc;
            margin-bottom: 20px;
        }
        .tab-link {
            padding: 15px 25px;
            text-decoration: none;
            color: #333;
            font-weight: 700;
            border: 1px solid transparent;
            border-bottom: none;
            background-color: #f0f0f0;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        .tab-link.active {
            background-color: #fff;
            border-color: #ccc;
            border-bottom-color: #fff;
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/thrivehut logo png.png" alt="Logo" class="logo-sidebar">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">TRANSACTION</a>
                <a href="sales.php" class="nav-item">SALES</a>
                <a href="inventory.php" class="nav-item">INVENTORY</a>
                <a href="returned_item.php" class="nav-item">RETURNED ITEM</a>
                <a href="settings.php" class="nav-item active">SETTINGS</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="nav-item-logout" id="logout-link">EXIT</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="settings-tabs">
                <a href="settings.php" class="tab-link">ACCOUNT SETTINGS</a>
                <a href="inventory_settings.php" class="tab-link active">INVENTORY SETTINGS</a>
            </div>
            <header class="main-header">
                <h1>INVENTORY SETTINGS: ARCHIVED ITEMS</h1>
            </header>
            <section class="item-table-container">
                 <?php if (isset($error_message)): ?>
                    <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>PRODUCT ID</th>
                            <th>BRAND</th>
                            <th>PRODUCT NAME</th>
                            <th>TYPE</th>
                            <th>QUANTITY</th>
                            <th>PRICE</th>
                            <th>DATE ACQUIRED</th>
                            <th>EXPIRATION DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archived_items)): ?>
                            <tr><td colspan="9" style="text-align: center;">No archived items found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($archived_items as $item): ?>
                            <tr id="archived-row-<?php echo htmlspecialchars($item['Product_ID']); ?>">
                                <td><?php echo htmlspecialchars($item['Product_ID']); ?></td>
                                <td><?php echo htmlspecialchars($item['Brand']); ?></td>
                                <td><?php echo htmlspecialchars($item['Product_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Type']); ?></td>
                                <td><?php echo htmlspecialchars($item['Quantity']); ?></td>
                                <td>₱<?php echo number_format($item['Price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['Date_Acquired']); ?></td>
                                <td><?php echo htmlspecialchars($item['Expiration_Date'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="action-btn-sm unarchive-btn" data-id="<?php echo htmlspecialchars($item['Product_ID']); ?>">Back to Inventory</button>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.unarchive-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const productId = this.dataset.id;
                if (confirm('Are you sure you want to restore this item to the active inventory?')) {
                    const formData = new FormData();
                    formData.append('action', 'unarchive_stock');
                    formData.append('product_id', productId);

                    try {
                        const response = await fetch('ajax_handler.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            alert('Item restored successfully!');
                            const rowToRemove = document.getElementById('archived-row-' + productId);
                            if (rowToRemove) {
                                rowToRemove.remove();
                            }
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Request failed:', error);
                        alert('An unexpected error occurred. Please try again.');
                    }
                }
            });
        });
    });
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
    </script>
</body>
</html> 
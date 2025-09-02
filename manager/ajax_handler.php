<?php
session_start();
require '../config/database.php';

header('Content-Type: application/json');

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['discount'])) {
    $_SESSION['discount'] = ['type' => 'none', 'percentage' => 0];
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_to_cart':
    case 'update_quantity':
    case 'remove_from_cart':
    case 'apply_discount':
    case 'clear_cart':
        // Handle cart logic and then fall through to send cart state
        switch ($action) {
            case 'add_to_cart':
                $productId = $_POST['product_id'];
                $stock_stmt = $db->prepare("SELECT Quantity FROM inventory WHERE Product_ID = :pid");
                $stock_stmt->execute([':pid' => $productId]);
                $stock_quantity = $stock_stmt->fetchColumn();

                if ($stock_quantity > 0) {
                    if (isset($_SESSION['cart'][$productId])) {
                        if ($_SESSION['cart'][$productId]['quantity'] < $stock_quantity) {
                            $_SESSION['cart'][$productId]['quantity']++;
                        }
                    } else {
                        $stmt = $db->prepare("SELECT * FROM inventory WHERE Product_ID = :product_id");
                        $stmt->execute([':product_id' => $productId]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($product) {
                            $_SESSION['cart'][$productId] = [
                                'product_id' => $product['Product_ID'], 'name' => $product['Product_Name'],
                                'brand' => $product['Brand'], 'type' => $product['Type'],
                                'price' => $product['Price'], 'quantity' => 1,
                                'max_quantity' => $product['Quantity']
                            ];
                        }
                    }
                }
                break;
            case 'update_quantity':
                $productId = $_POST['product_id'];
                $change = (int)$_POST['change'];
                if (isset($_SESSION['cart'][$productId])) {
                    $newQuantity = $_SESSION['cart'][$productId]['quantity'] + $change;
                    if ($newQuantity > 0 && $newQuantity <= $_SESSION['cart'][$productId]['max_quantity']) {
                        $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
                    }
                }
                break;
            case 'remove_from_cart':
                $productId = $_POST['product_id'];
                unset($_SESSION['cart'][$productId]);
                break;
            case 'apply_discount':
                $discountType = $_POST['discount_type'];
                $discounts = ['none' => 0, 'pwd_senior' => 0.20];
                if (array_key_exists($discountType, $discounts)) {
                    $_SESSION['discount'] = ['type' => $discountType, 'percentage' => $discounts[$discountType]];
                }
                break;
            case 'clear_cart':
                $_SESSION['cart'] = [];
                $_SESSION['discount'] = ['type' => 'none', 'percentage' => 0];
                break;
        }

        // Send back the updated cart state
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $discountAmount = $subtotal * $_SESSION['discount']['percentage'];
        $total = $subtotal - $discountAmount;
        echo json_encode([
            'success' => true,
            'cart' => array_values($_SESSION['cart']),
            'discount' => $_SESSION['discount'],
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total
        ]);
        exit;

    case 'add_product':
        $product_id = $_POST['product_id'];
        $brand = $_POST['brand'];
        $product_name = $_POST['product_name'];
        $type = $_POST['type'];
        $quantity = $_POST['quantity'];
        $date_acquired = $_POST['date_acquired'];
        $price = $_POST['price'];
        $expiration_date = (!empty($_POST['expiration_date'])) ? $_POST['expiration_date'] : null;

        if (empty($product_id) || empty($brand) || empty($product_name) || empty($type) || empty($quantity) || empty($date_acquired) || empty($price)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO inventory (Product_ID, Brand, Product_Name, Type, Quantity, Date_Acquired, Price, Expiration_Date) 
                 VALUES (:pid, :brand, :pname, :type, :qty, :acq_date, :price, :exp_date)"
            );
            $stmt->execute([
                ':pid' => $product_id,
                ':brand' => $brand,
                ':pname' => $product_name,
                ':type' => $type,
                ':qty' => $quantity,
                ':acq_date' => $date_acquired,
                ':price' => $price,
                ':exp_date' => $expiration_date
            ]);
            echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'get_sale_details':
        $transactionId = $_POST['transaction_id'] ?? 0;

        try {
            // Fetch main sale info
            $sale_stmt = $db->prepare("SELECT * FROM sales WHERE transaction_number = :tid");
            $sale_stmt->execute([':tid' => $transactionId]);
            $sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch items sold in that transaction
            $items_stmt = $db->prepare("SELECT * FROM item_sales WHERE Transaction_Number = :tid");
            $items_stmt->execute([':tid' => $transactionId]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($sale && $items) {
                echo json_encode(['success' => true, 'sale' => $sale, 'items' => $items]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'process_sale':
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
            exit;
        }

        $payment_method = $_POST['payment_method'] ?? 'cash';
        $gcash_account_name = $_POST['account_name'] ?? null;
        $amount_tendered = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

        // Separate services and products in the cart
        $services = [];
        $products = [];
        foreach ($_SESSION['cart'] as $item) {
            if ($item['type'] === 'Service') {
                $services[] = $item;
            } else {
                $products[] = $item;
            }
        }

        // If cart contains only services, process as a service sale
        if (count($services) > 0 && count($products) === 0) {
            $subtotal = 0;
            foreach ($services as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $discountAmount = $subtotal * $_SESSION['discount']['percentage'];
            $total = $subtotal - $discountAmount;
            $cash_amount = ($payment_method == 'cash') ? $amount_tendered : 0;
            $change_amount = ($payment_method == 'cash') ? ($amount_tendered - $total) : 0;
            try {
                $db->beginTransaction();
                // 1. Insert into services_sales table
                // Generate transaction number: S420201 + padded id
                $sales_stmt = $db->prepare(
                    "INSERT INTO services_sales (total_price, discount, payment_method, cash_amount, change_amount, gcash_amount, gcash_account_name) VALUES (:price, :discount, :pm, :cash, :change, :gcash, :gcash_name)"
                );
                $sales_stmt->execute([
                    ':price' => $total,
                    ':discount' => $discountAmount,
                    ':pm' => $payment_method,
                    ':cash' => $cash_amount,
                    ':change' => $change_amount,
                    ':gcash' => ($payment_method == 'cashless') ? $total : 0,
                    ':gcash_name' => ($payment_method == 'cashless') ? $gcash_account_name : null
                ]);
                $sale_id = $db->lastInsertId();
                $transaction_number = 'S420201' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
                $update_stmt = $db->prepare("UPDATE services_sales SET transaction_number = :tn WHERE id = :id");
                $update_stmt->execute([':tn' => $transaction_number, ':id' => $sale_id]);
                // 2. Insert each service into services_sales_items
                $item_stmt = $db->prepare(
                    "INSERT INTO services_sales_items (services_sales_id, service_name, price, quantity) VALUES (:sid, :name, :price, :qty)"
                );
                foreach ($services as $item) {
                    $item_stmt->execute([
                        ':sid' => $sale_id,
                        ':name' => $item['name'],
                        ':price' => $item['price'],
                        ':qty' => $item['quantity']
                    ]);
                }
                $db->commit();
                // Clear the cart
                $_SESSION['cart'] = [];
                $_SESSION['discount'] = ['type' => 'none', 'percentage' => 0];
                echo json_encode(['success' => true, 'transaction_number' => $transaction_number]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }

        // Recalculate totals on the backend to be safe
        $subtotal = 0;
        foreach ($products as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $discountAmount = $subtotal * $_SESSION['discount']['percentage'];
        $total = $subtotal - $discountAmount;

        // Calculate cash_amount and change_amount for cash payments
        $cash_amount = ($payment_method == 'cash') ? $amount_tendered : 0;
        $change_amount = ($payment_method == 'cash') ? ($amount_tendered - $total) : 0;

        try {
            $db->beginTransaction();

            // 1. Insert into sales table with date
            $now = date('Y-m-d H:i:s');
            $sales_stmt = $db->prepare(
                "INSERT INTO sales (date, payment_method, price, discount, cash_amount, change_amount, gcash_amount, gcash_account_name) 
                 VALUES (:date, :pm, :price, :discount, :cash, :change, :gcash, :gcash_name)"
            );
            $sales_stmt->execute([
                ':date' => $now,
                ':pm' => $payment_method,
                ':price' => $total,
                ':discount' => $discountAmount,
                ':cash' => $cash_amount,
                ':change' => $change_amount,
                ':gcash' => ($payment_method == 'cashless') ? $total : 0,
                ':gcash_name' => ($payment_method == 'cashless') ? $gcash_account_name : null
            ]);

            // 2. Get the new auto-incremented id
            $sale_id = $db->lastInsertId();

            // 3. Use the id as the transaction_number (or format as needed)
            $transaction_number = $sale_id; // or use a formatted version if desired

            // 4. Update the sales row with the transaction_number
            $update_stmt = $db->prepare("UPDATE sales SET transaction_number = :tn WHERE id = :id");
            $update_stmt->execute([':tn' => $transaction_number, ':id' => $sale_id]);

            // 5. Insert into item_sales and update inventory
            $item_sales_stmt = $db->prepare(
                "INSERT INTO item_sales (Transaction_Number, Product_ID, Brand, Product_Name, Quantity, Type, Price)
                 VALUES (:tn, :pid, :brand, :pname, :qty, :type, :price)"
            );
            $inventory_update_stmt = $db->prepare(
                "UPDATE inventory SET Quantity = Quantity - :qty WHERE Product_ID = :pid"
            );

            foreach ($products as $item) {
                // Insert into item_sales
                $item_sales_stmt->execute([
                    ':tn' => $transaction_number,
                    ':pid' => $item['product_id'],
                    ':brand' => $item['brand'],
                    ':pname' => $item['name'],
                    ':qty' => $item['quantity'],
                    ':type' => $item['type'],
                    ':price' => $item['price']
                ]);

                // Update inventory
                $inventory_update_stmt->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['product_id']
                ]);
            }

            $db->commit();
            
            // Clear the cart
            $_SESSION['cart'] = [];
            $_SESSION['discount'] = ['type' => 'none', 'percentage' => 0];

            echo json_encode(['success' => true, 'transaction_number' => $transaction_number]);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit; // Stop script execution after processing

    case 'add_stock':
        if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
            $product_id = $_POST['product_id'];
            $quantity_to_add = $_POST['quantity'];

            $stmt = $db->prepare("UPDATE inventory SET Quantity = Quantity + :quantity WHERE Product_ID = :product_id");
            $stmt->bindParam(':quantity', $quantity_to_add, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update stock.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing product ID or quantity.']);
        }
        exit;

    case 'archive_stock':
        if (isset($_POST['product_id'])) {
            $product_id = $_POST['product_id'];

            try {
                // Add status column if it doesn't exist.
                $db->exec("ALTER TABLE inventory ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
            } catch (PDOException $e) {
                // Suppress error if column already exists (error code 1060)
                if ($e->getCode() !== '42S21') {
                    throw $e;
                }
            }
            
            try {
                $stmt = $db->prepare("UPDATE inventory SET status = 'archived' WHERE Product_ID = :product_id");
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error archiving product: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing product ID.']);
        }
        exit;

    case 'unarchive_stock':
        if (isset($_POST['product_id'])) {
            $product_id = $_POST['product_id'];

            try {
                $stmt = $db->prepare("UPDATE inventory SET status = 'active' WHERE Product_ID = :product_id");
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error restoring item: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing product ID.']);
        }
        exit;

    case 'get_ordered_items':
        $transactionId = $_POST['transaction_id'] ?? 0;
        try {
            // Fetch main sale info
            $sale_stmt = $db->prepare("SELECT * FROM sales WHERE transaction_number = :tid");
            $sale_stmt->execute([':tid' => $transactionId]);
            $sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch items
            $stmt = $db->prepare("SELECT *, (Quantity - returned_quantity) as returnable_quantity FROM item_sales WHERE Transaction_Number = :tid");
            $stmt->execute([':tid' => $transactionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($sale) {
                echo json_encode(['success' => true, 'sale' => $sale, 'items' => $items]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'return_item':
        $product_id = $_POST['product_id'] ?? null;
        $transaction_id = $_POST['transaction_id'] ?? null;
        $quantity = $_POST['quantity'] ?? 0;
        $reason = $_POST['reason'] ?? '';

        if (!$product_id || !$transaction_id || !$reason || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing required data or invalid quantity.']);
            exit;
        }

        try {
            $db->beginTransaction();

            // 1. Get item details from item_sales and lock the row
            $stmt = $db->prepare("SELECT * FROM item_sales WHERE Transaction_Number = :tid AND Product_ID = :pid FOR UPDATE");
            $stmt->execute([':tid' => $transaction_id, ':pid' => $product_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception('Item not found in this transaction.');
            }

            // 2. Check if the return quantity is valid
            $returnable_quantity = $item['Quantity'] - $item['returned_quantity'];
            if ($quantity > $returnable_quantity) {
                throw new Exception('Cannot return more items than were purchased.');
            }

            // 3. Update returned_quantity in item_sales
            $update_item_sales = $db->prepare("UPDATE item_sales SET returned_quantity = returned_quantity + :qty WHERE Transaction_Number = :tid AND Product_ID = :pid");
            $update_item_sales->execute([':qty' => $quantity, ':tid' => $transaction_id, ':pid' => $product_id]);

            // 4. Insert into returned_item table
            $insert_return = $db->prepare(
                "INSERT INTO returned_item (Product_ID, Brand, Product_Name, Type, Quantity, Price, Reason_of_Return_Item) 
                 VALUES (:pid, :brand, :pname, :type, :qty, :price, :reason)"
            );
            $insert_return->execute([
                ':pid' => $item['Product_ID'],
                ':brand' => $item['Brand'],
                ':pname' => $item['Product_Name'],
                ':type' => $item['Type'],
                ':qty' => $quantity,
                ':price' => $item['Price'],
                ':reason' => $reason
            ]);

            // 5. Add stock back to inventory
            $update_inventory = $db->prepare("UPDATE inventory SET Quantity = Quantity + :qty WHERE Product_ID = :pid");
            $update_inventory->execute([':qty' => $quantity, ':pid' => $product_id]);
            
            // 6. Update the total sale price and returned amount in the sales table
            $returned_value = $item['Price'] * $quantity;
            $update_sales = $db->prepare("UPDATE sales SET price = price - :val, returned_amount = returned_amount + :val WHERE transaction_number = :tid");
            $update_sales->execute([':val' => $returned_value, ':tid' => $transaction_id]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Item returned successfully!']);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'get_cart':
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $discountAmount = $subtotal * $_SESSION['discount']['percentage'];
        $total = $subtotal - $discountAmount;
        echo json_encode([
            'success' => true,
            'cart' => array_values($_SESSION['cart']),
            'discount' => $_SESSION['discount'],
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total
        ]);
        exit;

    case 'add_manager':
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            exit;
        }

        // Check if username already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already taken.']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'manager')");
            $stmt->execute([':username' => $username, ':password' => $hashed_password]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'reset_password':
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];

        if (empty($user_id) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'User ID and new password are required.']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id AND role = 'manager'");
            $stmt->execute([':password' => $hashed_password, ':id' => $user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'archive_user':
        $user_id = $_POST['user_id'];

        if (empty($user_id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }
        
        // Prevent owner from archiving themselves
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot archive your own account.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE users SET status = 'archived' WHERE id = :id AND role = 'manager'");
            $stmt->execute([':id' => $user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'unarchive_user':
        $user_id = $_POST['user_id'];

        if (empty($user_id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = :id AND role = 'manager'");
            $stmt->execute([':id' => $user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    case 'add_service_to_cart':
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        if (isset($_SESSION['services'][$index])) {
            $service = $_SESSION['services'][$index];
            // Use a unique key for the cart to avoid collision with product IDs
            $cart_key = 'service_' . $index;
            if (!isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key] = [
                    'product_id' => $cart_key,
                    'name' => $service['name'],
                    'brand' => 'Service',
                    'type' => 'Service',
                    'price' => $service['price'],
                    'quantity' => 1,
                    'max_quantity' => 1
                ];
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Service not found.']);
        }
        exit;

    case 'get_service_sale_details':
        $transactionId = $_POST['transaction_id'] ?? '';
        try {
            // Fetch main service sale info
            $sale_stmt = $db->prepare("SELECT * FROM services_sales WHERE transaction_number = :tn");
            $sale_stmt->execute([':tn' => $transactionId]);
            $sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sale) {
                echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
                exit;
            }
            // Fetch items for this service sale
            $items_stmt = $db->prepare("SELECT * FROM services_sales_items WHERE services_sales_id = :sid");
            $items_stmt->execute([':sid' => $sale['id']]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'sale' => $sale, 'items' => $items]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        exit;
}

?> 
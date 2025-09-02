<?php
require 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($password) || empty($role) || empty($username)) {
        $message = "Please fill in all fields.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Use INSERT ... ON DUPLICATE KEY UPDATE to create or reset the user
        $stmt = $db->prepare(
            "INSERT INTO users (username, password, role) 
             VALUES (:username, :password, :role) 
             ON DUPLICATE KEY UPDATE password = :password"
        );
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            $message = "Successfully created/updated the '<strong>" . htmlspecialchars($username) . "</strong>' user with the role of '<strong>" . htmlspecialchars($role) . "</strong>'.";
        } else {
            $message = "Error: Could not create/update user.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Setup</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f0f0f0; padding: 40px; }
        .container { max-width: 500px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, button { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box; }
        button { background-color: #800000; color: white; font-weight: bold; cursor: pointer; }
        .message { padding: 15px; background: #e0f0e0; border-left: 5px solid #4CAF50; margin-bottom: 20px; }
        .warning { padding: 15px; background: #fff3cd; border-left: 5px solid #ffc107; margin-top: 20px;}
        .login-link { display: block; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Account Setup</h1>
        <p>Use this page to create or reset the password for the owner and manager accounts.</p>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="setup.php" method="post">
            <div>
                <label for="role">Select Role to Create/Update:</label>
                <select name="role" id="role">
                    <option value="owner">Owner</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div>
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="" required>
            </div>
            <div>
                <label for="password">New Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Create / Reset User</button>
        </form>
        
        <script>
            // Set username field based on role selection
            const roleSelect = document.getElementById('role');
            const usernameInput = document.getElementById('username');
            roleSelect.addEventListener('change', function() {
                usernameInput.value = this.value;
            });
            // Set initial value
            usernameInput.value = roleSelect.value;
        </script>
        
        <a href="login/" class="login-link">Go to Login Page</a>

        <div class="warning">
            <strong>Security Warning:</strong> For your security, please delete this file (<code>setup.php</code>) from your server after you have finished setting up the accounts.
        </div>
    </div>
</body>
</html> 
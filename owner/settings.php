<?php
session_start();
require '../config/database.php';

// Ensure the user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login/");
    exit();
}

// Fetch all non-owner users
$stmt = $db->prepare("SELECT id, username, role, status FROM users WHERE role != 'owner'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Thrivehut Motorworks</title>
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
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
         #add-manager-btn {
            background-color: #800000; /* Dark red */
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: 700;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #add-manager-btn:hover {
            background-color: #a00000; /* Lighter red on hover */
        }
        .action-btn-sm {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
            border: 1px solid #000;
            border-radius: 3px;
        }
        .modal { 
            display: none; position: fixed; z-index: 1001; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 25px 30px;
            border: none;
            width: 450px;
            font-family: 'Montserrat', sans-serif;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            border-radius: 8px;
        }
        .modal-content h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.5em;
        }
        .close-modal-btn {
            position: absolute; top: 10px; right: 20px;
            font-size: 30px; font-weight: bold; cursor: pointer;
            color: #aaa;
        }
        .close-modal-btn:hover {
            color: #000;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.9em;
        }
        .form-group input[type="text"], .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
        }
        .toggle-password {
            position: absolute; right: 15px; cursor: pointer;
            user-select: none;
        }
        .modal-content .action-btn {
            width: 100%;
            padding: 12px;
            font-size: 1.1em;
            border: none;
            background: #800000;
            color: white;
            cursor: pointer;
            margin-top: 20px;
            border-radius: 5px;
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
            <div class="sidebar-header"><img src="../images/thrivehut logo png.png" alt="Logo" class="logo-sidebar"></div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">TRANSACTION</a>
                <a href="sales.php" class="nav-item">SALES</a>
                <a href="inventory.php" class="nav-item">INVENTORY</a>
                <a href="returned_item.php" class="nav-item">RETURNED ITEM</a>
                <a href="settings.php" class="nav-item active">SETTINGS</a>
            </nav>
            <div class="sidebar-footer"><a href="#" class="nav-item-logout" id="logout-link">EXIT</a></div>
        </aside>

        <main class="main-content">
            <div class="settings-tabs">
                <a href="settings.php" class="tab-link active">ACCOUNT SETTINGS</a>
                <a href="inventory_settings.php" class="tab-link">INVENTORY SETTINGS</a>
            </div>
            <header class="main-header">
                <h1>ACCOUNT SETTINGS</h1>
                <button class="action-btn" id="add-manager-btn">Add New Manager</button>
            </header>
            <section class="item-table-container">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>USER ID</th>
                            <th>USERNAME</th>
                            <th>ROLE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" style="text-align: center;">No manager accounts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?php echo $user['id']; ?>">
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['status'])); ?></td>
                                <td>
                                    <button class="action-btn-sm reset-pw-btn" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">Reset Password</button>
                                    <?php if ($user['status'] == 'active'): ?>
                                    <button class="action-btn-sm archive-user-btn" data-id="<?php echo $user['id']; ?>">Archive</button>
                                    <?php else: ?>
                                    <button class="action-btn-sm unarchive-user-btn" data-id="<?php echo $user['id']; ?>">Unarchive</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Add Manager Modal -->
    <div id="add-manager-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal-btn">&times;</span>
            <h2>Add New Manager</h2>
            <form id="add-manager-form">
                <div class="form-group">
                    <label for="new-username">Username</label>
                    <input type="text" id="new-username" required>
                </div>
                <div class="form-group">
                    <label for="new-password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new-password" required>
                        <img src="../images/eye-open-icon.png" alt="Show Password" class="toggle-password" id="new-toggle-password">
                    </div>
                </div>
                <button type="submit" class="action-btn">Save Manager</button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="reset-password-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal-btn">&times;</span>
            <h2>Reset Password for <span id="reset-username"></span></h2>
            <form id="reset-password-form">
                <input type="hidden" id="reset-user-id">
                <div class="form-group">
                    <label for="reset-password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="reset-password" required>
                        <img src="../images/eye-open-icon.png" alt="Show Password" class="toggle-password" id="reset-toggle-password">
                    </div>
                </div>
                <button type="submit" class="action-btn">Set New Password</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addModal = document.getElementById('add-manager-modal');
    const resetModal = document.getElementById('reset-password-modal');

    // --- Modal Control ---
    document.getElementById('add-manager-btn').addEventListener('click', () => {
        addModal.style.display = 'flex';
    });

    document.querySelectorAll('.reset-pw-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reset-user-id').value = this.dataset.id;
            document.getElementById('reset-username').textContent = this.dataset.username;
            resetModal.style.display = 'flex';
        });
    });

    document.querySelectorAll('.modal .close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            addModal.style.display = 'none';
            resetModal.style.display = 'none';
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == resetModal) resetModal.style.display = 'none';
    });

    // --- Show/Hide Password ---
    function setupTogglePassword(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleId);
        if (passwordInput && toggleIcon) {
            toggleIcon.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.src = '../images/eye-slash-icon.png';
                    toggleIcon.alt = 'Hide Password';
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.src = '../images/eye-open-icon.png';
                    toggleIcon.alt = 'Show Password';
                }
            });
        }
    }
    setupTogglePassword('new-password', 'new-toggle-password');
    setupTogglePassword('reset-password', 'reset-toggle-password');

    // --- Form Submissions ---
    document.getElementById('add-manager-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const username = document.getElementById('new-username').value;
        const password = document.getElementById('new-password').value;

        const formData = new FormData();
        formData.append('action', 'add_manager');
        formData.append('username', username);
        formData.append('password', password);

        try {
            const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Manager added successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('An unexpected error occurred.');
        }
    });

    document.getElementById('reset-password-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const userId = document.getElementById('reset-user-id').value;
        const password = document.getElementById('reset-password').value;

        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('user_id', userId);
        formData.append('password', password);
        
        try {
            const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Password reset successfully!');
                resetModal.style.display = 'none';
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('An unexpected error occurred.');
        }
    });

    document.querySelectorAll('.archive-user-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const userId = this.dataset.id;
            if (confirm('Are you sure you want to archive this user? They will no longer be able to log in.')) {
                const formData = new FormData();
                formData.append('action', 'archive_user');
                formData.append('user_id', userId);

                try {
                    const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        alert('User archived successfully.');
                        const row = document.getElementById('user-row-' + userId);
                        row.querySelector('td:nth-child(4)').textContent = 'Archived';
                        this.remove(); // Remove the archive button
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('An unexpected error occurred.');
                }
            }
        });
    });

    document.querySelectorAll('.unarchive-user-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const userId = this.dataset.id;
            if (confirm('Are you sure you want to restore this user? They will be able to log in again.')) {
                const formData = new FormData();
                formData.append('action', 'unarchive_user');
                formData.append('user_id', userId);

                try {
                    const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        alert('User restored successfully.');
                        const row = document.getElementById('user-row-' + userId);
                        row.querySelector('td:nth-child(4)').textContent = 'Active';
                        
                        // Replace unarchive button with archive button
                        const newButton = document.createElement('button');
                        newButton.className = 'action-btn-sm archive-user-btn';
                        newButton.dataset.id = userId;
                        newButton.textContent = 'Archive';
                        this.replaceWith(newButton);
                        // Re-add event listener to the new button
                         newButton.addEventListener('click', archiveButtonListener);

                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('An unexpected error occurred.');
                }
            }
        });
    });

    // Need to have this as a named function to re-apply it
    async function archiveButtonListener(event) {
        const userId = event.target.dataset.id;
        if (confirm('Are you sure you want to archive this user? They will no longer be able to log in.')) {
            const formData = new FormData();
            formData.append('action', 'archive_user');
            formData.append('user_id', userId);

            try {
                const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('User archived successfully.');
                    const row = document.getElementById('user-row-' + userId);
                    row.querySelector('td:nth-child(4)').textContent = 'Archived';
                    event.target.remove(); // Remove the archive button
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An unexpected error occurred.');
            }
        }
    }
     document.querySelectorAll('.archive-user-btn').forEach(btn => {
        btn.addEventListener('click', archiveButtonListener);
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
});
</script>
</body>
</html> 
<?php
session_start();
// Redirect to the correct dashboard if the user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'owner') {
        header("Location: ../owner/");
    } elseif ($_SESSION['role'] == 'manager') {
        header("Location: ../manager/");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thrivehut Motorworks - Login</title>
    <link rel="icon" type="image/png" href="../images/thrivehut logo png.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modal_login.css">
</head>
<body>
    <div class="container" id="main-container">
        <header>
            <img src="../images/thrivehut logo png.png" alt="TMI Logo" class="logo">
            <h1>THRIVEHUT MOTORWORKS INVENTORY AND SALES AUDIT SYSTEM</h1>
        </header>
        <main>
            <div class="login-options">
                <a href="#" class="login-option" onclick="showModal('owner')">
                    <img src="../images/owner-icon.png" alt="Owner Icon">
                    <h2>LOG IN AS OWNER</h2>
                </a>
                <a href="#" class="login-option" onclick="showModal('manager')">
                    <img src="../images/manager-icon.png" alt="Manager Icon">
                    <h2>LOG IN AS MANAGER</h2>
                </a>
            </div>
        </main>
    </div>

    <!-- Owner Login Modal -->
    <div id="owner-modal" class="modal-overlay">
        <div class="modal-content">
            <img src="../images/back-button-icon.png" alt="Back" class="back-button-modal" onclick="hideModal('owner')">
            <img src="../images/owner-icon.png" alt="Owner Icon" class="modal-icon">
            <h2>OWNER LOGIN</h2>
            <p class="error" id="owner-error"></p>
            <form action="login_process.php" method="post">
                <input type="hidden" name="role" value="owner">
                <input type="text" name="username" placeholder="USERNAME" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="owner-password" placeholder="PASSWORD" required>
                    <img src="../images/eye-open-icon.png" alt="Show Password" class="toggle-password" id="owner-toggle-password" onclick="togglePassword('owner-password', 'owner-toggle-password')">
                </div>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </div>

    <!-- Manager Login Modal -->
    <div id="manager-modal" class="modal-overlay">
        <div class="modal-content">
            <img src="../images/back-button-icon.png" alt="Back" class="back-button-modal" onclick="hideModal('manager')">
            <img src="../images/manager-icon.png" alt="Manager Icon" class="modal-icon">
            <h2>MANAGER LOGIN</h2>
            <p class="error" id="manager-error"></p>
            <form action="login_process.php" method="post">
                <input type="hidden" name="role" value="manager">
                <input type="text" name="username" placeholder="USERNAME" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="manager-password" placeholder="PASSWORD" required>
                    <img src="../images/eye-open-icon.png" alt="Show Password" class="toggle-password" id="manager-toggle-password" onclick="togglePassword('manager-password', 'manager-toggle-password')">
                </div>
                <button type="submit">LOGIN</button>
                <p class="forgot-password-note">Forgot password? Try to reach out to our owner to reset the password.</p>
            </form>
        </div>
    </div>

    <script>
        const mainContainer = document.getElementById('main-container');

        function showModal(role) {
            // Clear previous errors
            document.getElementById('owner-error').innerText = '';
            document.getElementById('manager-error').innerText = '';
            
            document.getElementById(role + '-modal').style.display = 'flex';
            mainContainer.classList.add('blur-background');
        }

        function hideModal(role) {
            document.getElementById(role + '-modal').style.display = 'none';
            mainContainer.classList.remove('blur-background');
        }

        function togglePassword(inputId, toggleId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(toggleId);
            if (passwordInput.getAttribute('type') === 'password') {
                passwordInput.setAttribute('type', 'text');
                toggleIcon.src = '../images/eye-slash-icon.png';
                toggleIcon.alt = 'Hide Password';
            } else {
                passwordInput.setAttribute('type', 'password');
                toggleIcon.src = '../images/eye-open-icon.png';
                toggleIcon.alt = 'Show Password';
            }
        }

        // Check for errors on page load
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const role = urlParams.get('role');

            if (error && role) {
                showModal(role);
                document.getElementById(role + '-error').innerText = error;
            }
        };
    </script>
</body>
</html> 
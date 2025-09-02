<?php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        header("Location: index.php?role={$role}&error=All fields are required");
        exit();
    }

    // First, find the user by username only
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if account is archived
        if ($user['status'] === 'archived') {
            $error_message = "This account has been archived and cannot be accessed.";
            header("Location: index.php?role={$role}&error=" . urlencode($error_message));
            exit();
        }

        // User found, now check role and password
        if ($user['role'] != $role) {
            // Role mismatch
            $error_message = "This account does not have " . htmlspecialchars($role) . " privileges.";
            header("Location: index.php?role={$role}&error=" . urlencode($error_message));
            exit();
        } elseif (password_verify($password, $user['password'])) {
            // Password matches
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($role == 'owner') {
                header("Location: ../owner/");
            } else {
                header("Location: ../manager/");
            }
            exit();
        } else {
            // Password mismatch
            header("Location: index.php?role={$role}&error=Invalid username or password");
            exit();
        }
    } else {
        // User not found
        header("Location: index.php?role={$role}&error=Invalid username or password");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
} 
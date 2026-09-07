<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Пожалуйста, войдите в систему';
        header('Location: index.php?page=login');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Доступ запрещен. Требуются права администратора';
        header('Location: index.php');
        exit();
    }
}

function loginUser($userId, $username, $fullName, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['user_role'] = $role;
    $_SESSION['logged_in'] = true;
}

function logoutUser() {
    session_destroy();
    header('Location: index.php?page=login');
    exit();
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $user;
}
?>

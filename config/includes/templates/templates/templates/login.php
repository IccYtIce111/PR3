<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Пожалуйста, заполните все поля';
        header('Location: index.php?page=login');
        exit();
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, full_name, role, password_hash FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        loginUser($user['id'], $user['username'], $user['full_name'], $user['role']);
        
        // Обновляем время последнего входа
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        
        $_SESSION['success'] = 'Добро пожаловать, ' . $user['full_name'] . '!';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = 'Неверный логин или пароль';
        header('Location: index.php?page=login');
        exit();
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">
                    <i class="bi bi-box-arrow-in-right"></i> Вход в систему
                </h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Логин</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>
                <div class="text-center mt-3">
                    <small>Нет аккаунта? <a href="index.php?page=register">Зарегистрироваться</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

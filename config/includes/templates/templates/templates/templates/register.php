<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    $errors = [];
    
    if (!validateUsername($username)) {
        $errors[] = 'Логин должен содержать 3-50 символов (латиница, цифры, _)';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'Неверный формат email';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if (strlen($full_name) < 2) {
        $errors[] = 'Введите полное имя';
    }
    
    if ($phone && !validatePhone($phone)) {
        $errors[] = 'Неверный формат телефона';
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: index.php?page=register');
        exit();
    }
    
    $conn = getDBConnection();
    
    // Проверка существования пользователя
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Пользователь с таким логином или email уже существует';
        $stmt->close();
        $conn->close();
        header('Location: index.php?page=register');
        exit();
    }
    $stmt->close();
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $phone);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Регистрация успешна! Теперь вы можете войти';
        $stmt->close();
        $conn->close();
        header('Location: index.php?page=login');
        exit();
    } else {
        $_SESSION['error'] = 'Ошибка регистрации';
        $stmt->close();
        $conn->close();
        header('Location: index.php?page=register');
        exit();
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">
                    <i class="bi bi-person-plus"></i> Регистрация
                </h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Логин</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <small class="text-muted">3-50 символов, только латиница, цифры и _</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Минимум 6 символов</small>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Полное имя</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Телефон</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+7XXXXXXXXXX">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Зарегистрироваться</button>
                </form>
                <div class="text-center mt-3">
                    <small>Уже есть аккаунт? <a href="index.php?page=login">Войти</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

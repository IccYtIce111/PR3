<!-- login.php -->
<?php
require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: admin.php");
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Вход</title></head>
<body>
    <header>
        <a href="index.php">Светлые Сны</a>
        <p>Приезжайте как гости, уезжайте как друзья!</p>
    </header>
    <main>
        <h2>Вход</h2>
        <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST" action="login.php">
            <label>Логин: <input type="text" name="login" required></label><br>
            <label>Пароль: <input type="password" name="password" required></label><br>
            <button type="submit">Войти</button>
        </form>
    </main>
</body>
</html>

<?php
require 'db.php';

$room_id = $_GET['room_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: index.php");
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';

    // Серверная валидация
    $errors = [];
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $first_name)) $errors[] = 'Пожалуйста, введите имя.';
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $last_name)) $errors[] = 'Пожалуйста, введите фамилию.';
    if (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) $errors[] = 'Пожалуйста, введите номер телефона.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Пожалуйста, введите email.';
    
    $date_in = DateTime::createFromFormat('Y-m-d', $check_in);
    $date_out = DateTime::createFromFormat('Y-m-d', $check_out);
    
    if (!$date_in || !$date_out) {
        $errors[] = 'Некорректный формат даты.';
    } else {
        if ($date_out <= $date_in) $errors[] = 'Дата выезда должна быть позже даты заезда.';
        if ($date_in < new DateTime('today')) $errors[] = 'Дата заезда не может быть в прошлом.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO bookings (first_name, last_name, phone, email, check_in, check_out, room_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone, $email, $check_in, $check_out, $room_id]);
        $success = true;
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование</title>
</head>
<body>
    <header>
        <a href="index.php">Светлые Сны</a>
        <p>Приезжайте как гости, уезжайте как друзья!</p>
    </header>

    <main>
        <?php if ($success): ?>
            <h2>Заявка успешно отправлена!</h2>
        <?php else: ?>
            <h2>Бронирование номера (<?= htmlspecialchars($room['category']) ?>)</h2>
            <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
            
            <form method="POST" action="order.php?room_id=<?= $room_id ?>">
                <label>Имя: <input type="text" name="first_name" required></label><br>
                <label>Фамилия: <input type="text" name="last_name" required></label><br>
                <label>Телефон: <input type="text" name="phone" required></label><br>
                <label>Почта: <input type="email" name="email" required></label><br>
                <label>Дата заезда: <input type="date" name="check_in" required></label><br>
                <label>Дата выезда: <input type="date" name="check_out" required></label><br>
                <button type="submit">Отправить заявку</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>

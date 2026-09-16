<?php
require 'db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Обработка действий (Одобрить / Удалить)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$id]);
        } elseif ($action === 'delete') {
            $pdo->prepare("UPDATE bookings SET status = 'deleted' WHERE id = ?")->execute([$id]);
        }
    }
    header("Location: admin.php");
    exit;
}

// Получение заявок
$stmt = $pdo->query("SELECT * FROM bookings WHERE status != 'deleted' ORDER BY created_at DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
</head>
<body>
    <header>
        <a href="index.php">Светлые Сны</a>
        <p>Приезжайте как гости, уезжайте как друзья!</p>
        <a href="logout.php">Выйти</a>
    </header>

    <main>
        <h2>Панель администратора</h2>
        
        <?php foreach ($bookings as $b): ?>
        <div class="booking-card" style="border: 1px solid #ccc; margin: 10px; padding: 10px;">
            <p>Фамилия: <?= htmlspecialchars($b['last_name']) ?></p>
            <p>Имя: <?= htmlspecialchars($b['first_name']) ?></p>
            <p>Телефон: <?= htmlspecialchars($b['phone']) ?></p>
            <p>Дата заезда: <?= htmlspecialchars(date('d.m.Y', strtotime($b['check_in']))) ?></p>
            <p>Дата выезда: <?= htmlspecialchars(date('d.m.Y', strtotime($b['check_out']))) ?></p>
            <p>Статус: <?= htmlspecialchars($b['status']) ?></p>
            
            <?php if ($b['status'] === 'new'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit">Одобрить</button>
            </form>
            <?php endif; ?>
            
            <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit">Удалить</button>
            </form>
        </div>
        <?php endforeach; ?>
    </main>
</body>
</html>

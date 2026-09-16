<?php
require 'db.php';

// Серверная фильтрация
$category = $_GET['category'] ?? '';
$sql = "SELECT * FROM rooms";
$params = [];
if ($category) {
    $sql .= " WHERE category = ?";
    $params[] = $category;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подгружаем характеристики для каждого номера
foreach ($rooms as &$room) {
    $stmtF = $pdo->prepare("SELECT feature FROM room_features WHERE room_id = ?");
    $stmtF->execute([$room['id']]);
    $room['features'] = $stmtF->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Светлые Сны</title>
</head>
<body>
    <header>
        <a href="index.php">Светлые Сны</a>
        <p>Приезжайте как гости, уезжайте как друзья!</p>
        <a href="login.php">Вход для администратора</a>
    </header>

    <main>
        <h2>Каталог номеров</h2>
        <form method="GET" action="index.php">
            <label>Категории:
                <select name="category">
                    <option value="">Все</option>
                    <option value="Стандарт" <?= $category == 'Стандарт' ? 'selected' : '' ?>>Стандартный</option>
                    <option value="Студия" <?= $category == 'Студия' ? 'selected' : '' ?>>Студия</option>
                    <option value="Люкс" <?= $category == 'Люкс' ? 'selected' : '' ?>>Люкс</option>
                </select>
            </label>
            <button type="submit">Применить</button>
            <a href="index.php">Сбросить фильтр</a>
        </form>

        <div class="rooms">
            <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <h3>Категория: <?= htmlspecialchars($room['category']) ?></h3>
                <p>Цена: <?= htmlspecialchars($room['price']) ?> ₽ / чел</p>
                <h4>Характеристики:</h4>
                <ul>
                    <?php foreach ($room['features'] as $f): ?>
                    <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="order.php?room_id=<?= $room['id'] ?>">Забронировать</a>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>ул. г.Москва, ул. Ивовая, 48</p>
        <p>Время работы: Пн-Пт, с 8:00-17:00</p>
        <p>тел. 8 (800) 555 - 35 - 35</p>
        <p>Email: обращения@СветлыеСны.рф</p>
    </footer>
</body>
</html>

<?php
// Подключение к SQLite
$pdo = new PDO('sqlite:' . __DIR__ . '/database.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Инициализация таблиц и тестовых данных
function initDB($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT, login TEXT UNIQUE, password_hash TEXT, role TEXT DEFAULT 'admin'
        );
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT, category TEXT, price REAL
        );
        CREATE TABLE IF NOT EXISTS room_features (
            id INTEGER PRIMARY KEY AUTOINCREMENT, room_id INTEGER, feature TEXT, FOREIGN KEY(room_id) REFERENCES rooms(id)
        );
        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT, last_name TEXT, phone TEXT, email TEXT, 
            check_in TEXT, check_out TEXT, room_id INTEGER, status TEXT DEFAULT 'new', created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Добавляем админа, если его нет
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE login = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (login, password_hash, role) VALUES (?, ?, ?)")->execute(['admin', $hash, 'admin']);
    }

    // Добавляем номера, если таблица пуста
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    if ($stmt->fetchColumn() == 0) {
        $rooms = [['Стандарт', 10000], ['Студия', 8000], ['Люкс', 19000]];
        $features = [
            ['Включен завтрак', 'Душ + Ванна'],
            ['Включен завтрак, обед', 'Душ + Ванна', 'Кондиционер'],
            ['Включен завтрак, обед, ужин', 'Душ + Ванна', 'Кондиционер', 'Телевизор', 'Мини-бар', 'Вид на город']
        ];
        
        foreach ($rooms as $i => $room) {
            $pdo->prepare("INSERT INTO rooms (category, price) VALUES (?, ?)")->execute($room);
            $roomId = $pdo->lastInsertId();
            foreach ($features[$i] as $f) {
                $pdo->prepare("INSERT INTO room_features (room_id, feature) VALUES (?, ?)")->execute([$roomId, $f]);
            }
        }
    }
}
initDB($pdo);
session_start();
?>

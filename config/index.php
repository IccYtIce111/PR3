<?php
session_start();

// Подключение всех необходимых файлов
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';

// Установка базы данных при первом запуске
installDatabase();
seedTestData();

$page = $_GET['page'] ?? 'rooms';

// Защита от XSS
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Маршрутизация
$pageFile = __DIR__ . '/templates/' . $page . '.php';
$adminPageFile = __DIR__ . '/templates/admin/' . $page . '.php';

include __DIR__ . '/templates/header.php';

if ($page === 'logout') {
    logoutUser();
} elseif ($page === 'login') {
    include __DIR__ . '/templates/login.php';
} elseif ($page === 'register') {
    include __DIR__ . '/templates/register.php';
} elseif ($page === 'admin' && isAdmin()) {
    include __DIR__ . '/templates/admin/dashboard.php';
} elseif ($page === 'rooms') {
    include __DIR__ . '/templates/rooms.php';
} elseif ($page === 'booking') {
    include __DIR__ . '/templates/booking.php';
} elseif ($page === 'my-bookings') {
    include __DIR__ . '/templates/my-bookings.php';
} elseif (file_exists($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="alert alert-danger">Страница не найдена</div>';
}

include __DIR__ . '/templates/footer.php';
?>

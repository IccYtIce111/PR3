<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

$bookingId = (int)($_POST['booking_id'] ?? 0);

if (!$bookingId) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID бронирования']);
    exit();
}

$conn = getDBConnection();

// Проверка принадлежности бронирования пользователю
$stmt = $conn->prepare("SELECT id, status, check_in FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Бронирование не найдено']);
    $conn->close();
    exit();
}

if ($booking['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Бронирование уже отменено']);
    $conn->close();
    exit();
}

if (strtotime($booking['check_in']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Нельзя отменить прошедшее бронирование']);
    $conn->close();
    exit();
}

$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $bookingId);
$success = $stmt->execute();
$stmt->close();
$conn->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Бронирование отменено']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при отмене']);
}
?>

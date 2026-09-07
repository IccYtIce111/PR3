<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowedStatuses = ['pending', 'confirmed', 'cancelled', 'checked_in', 'checked_out'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Неверный статус']);
    exit();
}

if (!$bookingId) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID бронирования']);
    exit();
}

$conn = getDBConnection();

$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $bookingId);
$success = $stmt->execute();
$stmt->close();
$conn->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Статус обновлен']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
}
?>

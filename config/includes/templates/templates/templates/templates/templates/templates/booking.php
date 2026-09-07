<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$roomId = (int)($_GET['room_id'] ?? 0);
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = (int)($_GET['guests'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $checkIn = sanitizeInput($_POST['check_in'] ?? '');
    $checkOut = sanitizeInput($_POST['check_out'] ?? '');
    $guests = (int)($_POST['guests_count'] ?? 1);
    $specialRequests = sanitizeInput($_POST['special_requests'] ?? '');
    
    $errors = [];
    
    if (!$roomId) $errors[] = 'Не выбран номер';
    if (!validateBookingDates($checkIn, $checkOut)) $errors[] = 'Неверные даты бронирования';
    if (!validateGuestsCount($guests)) $errors[] = 'Неверное количество гостей';
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: index.php?page=booking&room_id=$roomId&check_in=$checkIn&check_out=$checkOut&guests=$guests");
        exit();
    }
    
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Проверка доступности
        $stmt = $conn->prepare("
            SELECT r.id, rc.base_price 
            FROM rooms r 
            JOIN room_categories rc ON r.category_id = rc.id 
            WHERE r.id = ? AND r.is_available = TRUE AND rc.capacity >= ?
        ");
        $stmt->bind_param("ii", $roomId, $guests);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();
        
        if (!$room) {
            throw new Exception('Номер недоступен');
        }
        
        // Проверка конфликтов
        $stmt = $conn->prepare("
            SELECT id FROM bookings 
            WHERE room_id = ? 
            AND status IN ('pending', 'confirmed', 'checked_in')
            AND NOT (check_out <= ? OR check_in >= ?)
        ");
        $stmt->bind_param("iss", $roomId, $checkIn, $checkOut);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Номер уже забронирован на выбранные даты');
        }
        $stmt->close();
        
        $totalPrice = calculateTotalPrice($roomId, $checkIn, $checkOut);
        $bookingRef = generateBookingReference();
        
        $stmt = $conn->prepare("
            INSERT INTO bookings (user_id, room_id, check_in, check_out, guests_count, total_price, special_requests, booking_reference) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissidss", $_SESSION['user_id'], $roomId, $checkIn, $checkOut, $guests, $totalPrice, $specialRequests, $bookingRef);
        $stmt->execute();
        $bookingId = $stmt->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        $_SESSION['success'] = "Бронирование успешно создано! Код бронирования: $bookingRef";
        header('Location: index.php?page=my-bookings');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: index.php?page=booking&room_id=$roomId&check_in=$checkIn&check_out=$checkOut&guests=$guests");
        exit();
    } finally {
        $conn->close();
    }
}

// Получение информации о номере
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT r.*, rc.name as category_name, rc.base_price, rc.capacity 
    FROM rooms r 
    JOIN room_categories rc ON r.category_id = rc.id 
    WHERE r.id = ?
");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$room) {
    $_SESSION['error'] = 'Номер не найден';
    header('Location: index.php?page=rooms');
    exit();
}

$totalPrice = calculateTotalPrice($roomId, $checkIn, $checkOut);
$days = $checkIn && $checkOut ? ceil((strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24)) : 0;
?>
<div class="booking-container">
    <h1 class="mb-4"><i class="bi bi-calendar-plus"></i> Бронирование</h1>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h5>Номер <?php echo htmlspecialchars($room['room_number']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($room['description'] ?? ''); ?></p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="room_id" value="<?php echo $roomId; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Дата заезда</label>
                                    <input type="date" class="form-control" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Дата выезда</label>
                                    <input type="date" class="form-control" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Количество гостей</label>
                            <input type="number" class="form-control" name="guests_count" value="<?php echo $guests; ?>" min="1" max="<?php echo $room['capacity']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Особые пожелания</label>
                            <textarea class="form-control" name="special_requests" rows="3" placeholder="Дополнительные пожелания..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Подтвердить бронирование
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5><i class="bi bi-receipt"></i> Стоимость</h5>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Цена за ночь:</span>
                        <strong><?php echo number_format($room['base_price'], 0, '', ' '); ?> руб.</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Количество ночей:</span>
                        <strong><?php echo $days; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Итого:</strong></span>
                        <span class="text-success h5"><?php echo number_format($totalPrice, 0, '', ' '); ?> руб.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

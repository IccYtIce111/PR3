<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, rc.name as category_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_categories rc ON r.category_id = rc.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
$conn->close();
?>
<div class="my-bookings">
    <h1 class="mb-4"><i class="bi bi-calendar-check"></i> Мои бронирования</h1>
    
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> У вас пока нет бронирований
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Код брони</th>
                        <th>Номер</th>
                        <th>Даты</th>
                        <th>Гостей</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                            <td>
                                <?php echo date('d.m.Y', strtotime($booking['check_in'])); ?>
                                <br>
                                <small>→ <?php echo date('d.m.Y', strtotime($booking['check_out'])); ?></small>
                            </td>
                            <td><?php echo $booking['guests_count']; ?></td>
                            <td><?php echo number_format($booking['total_price'], 0, '', ' '); ?> руб.</td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($booking['status']); ?>">
                                    <?php echo getStatusText($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                    <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                        <i class="bi bi-x-circle"></i> Отменить
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function cancelBooking(bookingId) {
    if (!confirm('Вы уверены, что хотите отменить бронирование?')) return;
    
    fetch('ajax/cancel-booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'booking_id=' + bookingId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка при отмене бронирования');
    });
}
</script>

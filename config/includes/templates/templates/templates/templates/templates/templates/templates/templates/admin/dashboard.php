<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$conn = getDBConnection();

// Статистика
$stats = [];
$statsResult = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
    FROM bookings
");
$stats = $statsResult->fetch_assoc();

// Фильтры
$statusFilter = $_GET['status'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$sql = "
    SELECT b.*, r.room_number, rc.name as category_name,
           u.full_name as guest_name, u.email as guest_email, u.phone as guest_phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_categories rc ON r.category_id = rc.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($fromDate) {
    $sql .= " AND DATE(b.created_at) >= ?";
    $params[] = $fromDate;
    $types .= "s";
}

if ($toDate) {
    $sql .= " AND DATE(b.created_at) <= ?";
    $params[] = $toDate;
    $types .= "s";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
$conn->close();
?>
<div class="admin-panel">
    <h1 class="mb-4"><i class="bi bi-shield-lock"></i> Панель администратора</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Всего бронирований</h5>
                    <p class="display-6"><?php echo $stats['total']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Ожидают</h5>
                    <p class="display-6"><?php echo $stats['pending']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Подтверждены</h5>
                    <p class="display-6"><?php echo $stats['confirmed']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Отменены</h5>
                    <p class="display-6"><?php echo $stats['cancelled']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="admin">
                <div class="col-md-3">
                    <label class="form-label">Статус</label>
                    <select class="form-select" name="status">
                        <option value="">Все</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Подтверждено</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                        <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>Заселен</option>
                        <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Выселен</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">С даты</label>
                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">По дату</label>
                    <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Применить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Гость</th>
                            <th>Номер</th>
                            <th>Даты</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['guest_name']); ?>
                                    <br><small><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($booking['check_in'])); ?>
                                    <br><small>→ <?php echo date('d.m.Y', strtotime($booking['check_out'])); ?></small>
                                </td>
                                <td><?php echo number_format($booking['total_price'], 0, '', ' '); ?> руб.</td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusColor($booking['status']); ?>">
                                        <?php echo getStatusText($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button class="btn btn-success" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <button class="btn btn-info" onclick="updateStatus(<?php echo $booking['id']; ?>, 'checked_in')">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        <?php elseif ($booking['status'] === 'checked_in'): ?>
                                            <button class="btn btn-secondary" onclick="updateStatus(<?php echo $booking['id']; ?>, 'checked_out')">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Нет бронирований</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(bookingId, status) {
    if (!confirm('Изменить статус бронирования?')) return;
    
    fetch('ajax/update-booking-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'booking_id=' + bookingId + '&status=' + status
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
        alert('Ошибка при обновлении статуса');
    });
}
</script>

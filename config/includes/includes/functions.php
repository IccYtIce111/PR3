<?php
require_once __DIR__ . '/../config/database.php';

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateBookingReference() {
    return 'BS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function calculateTotalPrice($roomId, $checkIn, $checkOut) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT rc.base_price 
        FROM rooms r 
        JOIN room_categories rc ON r.category_id = rc.id 
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$row) return 0;
    
    $days = ceil((strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24));
    return $row['base_price'] * $days;
}

function getRoomCategories() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM room_categories ORDER BY base_price");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $conn->close();
    return $categories;
}

function getAmenities() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM amenities ORDER BY name");
    $amenities = [];
    while ($row = $result->fetch_assoc()) {
        $amenities[] = $row;
    }
    $conn->close();
    return $amenities;
}

function getStatusText($status) {
    $statuses = [
        'pending' => 'Ожидает',
        'confirmed' => 'Подтверждено',
        'cancelled' => 'Отменено',
        'checked_in' => 'Заселен',
        'checked_out' => 'Выселен'
    ];
    return $statuses[$status] ?? $status;
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'checked_in' => 'info',
        'checked_out' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

function getAvailableRooms($checkIn = null, $checkOut = null, $guests = null, $minPrice = null, $maxPrice = null, $category = null) {
    $conn = getDBConnection();
    
    $sql = "
        SELECT r.*, rc.name as category_name, rc.base_price, rc.capacity,
               GROUP_CONCAT(a.name) as amenities_list
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        LEFT JOIN room_amenities ra ON r.id = ra.room_id
        LEFT JOIN amenities a ON ra.amenity_id = a.id
        WHERE r.is_available = TRUE
    ";
    
    $params = [];
    $types = "";
    
    if ($category) {
        $sql .= " AND rc.name = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($guests) {
        $sql .= " AND rc.capacity >= ?";
        $params[] = (int)$guests;
        $types .= "i";
    }
    
    if ($minPrice) {
        $sql .= " AND rc.base_price >= ?";
        $params[] = (float)$minPrice;
        $types .= "d";
    }
    
    if ($maxPrice) {
        $sql .= " AND rc.base_price <= ?";
        $params[] = (float)$maxPrice;
        $types .= "d";
    }
    
    if ($checkIn && $checkOut) {
        $sql .= " AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status IN ('pending', 'confirmed', 'checked_in')
            AND (
                (check_in <= ? AND check_out > ?) OR
                (check_in < ? AND check_out >= ?) OR
                (check_in >= ? AND check_out <= ?)
            )
        )";
        $params[] = $checkOut;
        $params[] = $checkIn;
        $params[] = $checkOut;
        $params[] = $checkIn;
        $params[] = $checkIn;
        $params[] = $checkOut;
        $types .= "ssssss";
    }
    
    $sql .= " GROUP BY r.id ORDER BY rc.base_price ASC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $row['amenities_list'] = $row['amenities_list'] ? explode(',', $row['amenities_list']) : [];
        $rooms[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $rooms;
}
?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = $_GET['guests'] ?? 1;
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$category = $_GET['category'] ?? '';

$categories = getRoomCategories();
$rooms = getAvailableRooms($checkIn, $checkOut, $guests, $minPrice, $maxPrice, $category);
?>
<div class="room-search-container">
    <h1 class="mb-4"><i class="bi bi-search"></i> Поиск номеров</h1>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="rooms">
                <div class="col-md-3">
                    <label class="form-label">Дата заезда</label>
                    <input type="date" class="form-control" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Дата выезда</label>
                    <input type="date" class="form-control" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Гостей</label>
                    <input type="number" class="form-control" name="guests" value="<?php echo htmlspecialchars($guests); ?>" min="1" max="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Мин. цена</label>
                    <input type="number" class="form-control" name="min_price" placeholder="0" value="<?php echo htmlspecialchars($minPrice); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Макс. цена</label>
                    <input type="number" class="form-control" name="max_price" placeholder="10000" value="<?php echo htmlspecialchars($maxPrice); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Категория</label>
                    <select class="form-select" name="category">
                        <option value="">Все</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Найти
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="rooms-grid">
        <?php if (empty($rooms)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Нет доступных номеров по вашему запросу
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm room-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title">
                                        Номер <?php echo htmlspecialchars($room['room_number']); ?>
                                    </h5>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($room['category_name']); ?></span>
                                </div>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($room['description'] ?? ''); ?></p>
                                <div class="room-meta mb-2">
                                    <span class="badge bg-secondary me-1">
                                        <i class="bi bi-people"></i> <?php echo $room['capacity']; ?> чел.
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="bi bi-currency-ruble"></i> <?php echo number_format($room['base_price'], 0, '', ' '); ?> руб./ночь
                                    </span>
                                </div>
                                <?php if (!empty($room['amenities_list'])): ?>
                                    <div class="amenities mb-3">
                                        <?php foreach ($room['amenities_list'] as $amenity): ?>
                                            <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($amenity); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <a href="index.php?page=booking&room_id=<?php echo $room['id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="bi bi-calendar-plus"></i> Забронировать
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

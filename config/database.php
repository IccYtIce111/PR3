<?php
// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_sweet_sleep');

// Создание подключения
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Ошибка подключения: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    }
}

// SQL для создания таблиц
function installDatabase() {
    $conn = getDBConnection();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        role ENUM('guest', 'admin') DEFAULT 'guest',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        INDEX idx_email (email),
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS room_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        base_price DECIMAL(10, 2) NOT NULL,
        capacity INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS rooms (
        id INT PRIMARY KEY AUTO_INCREMENT,
        room_number VARCHAR(10) UNIQUE NOT NULL,
        category_id INT NOT NULL,
        floor INT,
        description TEXT,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES room_categories(id) ON DELETE CASCADE,
        INDEX idx_room_number (room_number),
        INDEX idx_available (is_available)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS amenities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        icon VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS room_amenities (
        room_id INT NOT NULL,
        amenity_id INT NOT NULL,
        PRIMARY KEY (room_id, amenity_id),
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS bookings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        room_id INT NOT NULL,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        guests_count INT NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'checked_in', 'checked_out') DEFAULT 'pending',
        special_requests TEXT,
        booking_reference VARCHAR(20) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_room_id (room_id),
        INDEX idx_status (status),
        INDEX idx_dates (check_in, check_out),
        INDEX idx_booking_ref (booking_reference)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50),
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        transaction_id VARCHAR(100),
        payment_date TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        INDEX idx_booking_id (booking_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT NOT NULL,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        INDEX idx_booking_id (booking_id),
        INDEX idx_rating (rating)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if ($conn->multi_query($sql)) {
        do {
            // Очистка результатов
        } while ($conn->next_result());
    }
    
    $conn->close();
}

// Тестовые данные
function seedTestData() {
    $conn = getDBConnection();
    
    // Проверка, есть ли уже данные
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        return;
    }

    // Администратор
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, email, password_hash, full_name, role) VALUES 
        ('admin', 'admin@sweetsleep.ru', '$password_hash', 'Администратор', 'admin')");

    // Категории номеров
    $conn->query("INSERT INTO room_categories (name, description, base_price, capacity) VALUES
        ('Стандарт', 'Уютный номер со всеми необходимыми удобствами', 3500.00, 2),
        ('Комфорт', 'Просторный номер с улучшенной планировкой', 5000.00, 3),
        ('Люкс', 'Роскошный номер с панорамным видом', 8000.00, 4),
        ('Семейный', 'Идеальный вариант для семейного отдыха', 6500.00, 5)");

    // Удобства
    $conn->query("INSERT INTO amenities (name, icon) VALUES
        ('Wi-Fi', 'wifi'),
        ('Кондиционер', 'snowflake'),
        ('Телевизор', 'tv'),
        ('Мини-бар', 'beer'),
        ('Ванна', 'bath'),
        ('Фен', 'blow-dryer'),
        ('Кухня', 'kitchen-set')");

    // Номера
    $conn->query("INSERT INTO rooms (room_number, category_id, floor, description) VALUES
        ('101', 1, 1, 'Уютный стандартный номер с видом на сад'),
        ('102', 1, 1, 'Стандартный номер с двумя кроватями'),
        ('201', 2, 2, 'Комфортный номер с гостиной зоной'),
        ('202', 2, 2, 'Комфортный номер с балконом'),
        ('301', 3, 3, 'Роскошный люкс с панорамным видом'),
        ('302', 3, 3, 'Люкс с джакузи'),
        ('401', 4, 4, 'Семейный номер с детской зоной')");

    $conn->close();
}
?>

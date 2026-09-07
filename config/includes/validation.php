<?php
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function validatePhone($phone) {
    return preg_match('/^\+?[0-9]{10,15}$/', $phone);
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateBookingDates($checkIn, $checkOut) {
    if (!validateDate($checkIn) || !validateDate($checkOut)) {
        return false;
    }
    
    $today = new DateTime();
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    
    if ($checkInDate < $today) {
        return false;
    }
    
    if ($checkOutDate <= $checkInDate) {
        return false;
    }
    
    $diff = $checkInDate->diff($checkOutDate);
    if ($diff->days > 30) {
        return false;
    }
    
    return true;
}

function validateGuestsCount($guests) {
    return is_numeric($guests) && $guests >= 1 && $guests <= 10;
}

function validatePrice($price) {
    return is_numeric($price) && $price >= 0;
}
?>

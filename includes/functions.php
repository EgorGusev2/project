<?php
// includes/functions.php
require_once __DIR__ . '/db.php';

// Защита от XSS
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Генерация уникального логина
function generateUniqueLogin($db) {
    $prefixes = ['musician', 'rocker', 'jazz', 'singer', 'band'];
    $prefix = $prefixes[array_rand($prefixes)];
    
    do {
        $login = $prefix . rand(100, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM rehearsal_booking WHERE login = ?");
        $stmt->execute([$login]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    
    return $login;
}

// Генерация пароля
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Получение всех студий
function getAllStudios() {
    global $db;
    $stmt = $db->prepare("SELECT * FROM studios WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение бронирований пользователя по логину
function getUserBookings($login) {
    global $db;
    $stmt = $db->prepare("
        SELECT * FROM rehearsal_booking 
        WHERE login = ? 
        ORDER BY booking_date DESC, booking_time DESC
    ");
    $stmt->execute([$login]);
    return $stmt->fetchAll();
}

// Получение всех бронирований для админа
function getAllBookings() {
    global $db;
    $stmt = $db->prepare("
        SELECT * FROM rehearsal_booking 
        ORDER BY booking_date DESC, booking_time DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Проверка доступности времени
function isTimeSlotAvailable($date, $time, $studio, $excludeId = null) {
    global $db;
    $sql = "SELECT COUNT(*) FROM rehearsal_booking WHERE booking_date = ? AND booking_time = ? AND studio_name = ? AND status != 'cancelled'";
    $params = [$date, $time, $studio];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

// Получение бронирования по ID
function getBookingById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Получение пользователя по логину и паролю
function getUserByLoginAndPass($login, $password_hash) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE login = ? AND password_hash = ?");
    $stmt->execute([$login, $password_hash]);
    return $stmt->fetch();
}

// Получение пользователя по логину
function getUserByLogin($login) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE login = ?");
    $stmt->execute([$login]);
    return $stmt->fetch();
}

// Проверка авторизации админа
function checkAdminAuth() {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        return false;
    }
    
    global $db;
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();
    
    return $admin && md5($_SERVER['PHP_AUTH_PW']) == $admin['password_hash'];
}

// Получение статистики по студиям
function getStudioStats() {
    global $db;
    $stmt = $db->prepare("
        SELECT studio_name, COUNT(*) as booking_count
        FROM rehearsal_booking
        WHERE status != 'cancelled'
        GROUP BY studio_name
        ORDER BY booking_count DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
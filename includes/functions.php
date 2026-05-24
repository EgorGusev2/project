<?php
// includes/functions.php
require_once 'db.php';

// Функция для защиты от XSS
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Получение всех студий
function getAllStudios() {
    global $db;
    $stmt = $db->prepare("SELECT * FROM studios ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение бронирований пользователя
function getUserBookings($userId) {
    global $db;
    $stmt = $db->prepare("
        SELECT * FROM rehearsal_booking 
        WHERE user_id = ? 
        ORDER BY booking_date DESC, booking_time DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Получение пользователя по ID
function getUserById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Получение статистики по студиям
function getStudioStats() {
    global $db;
    $stmt = $db->prepare("
        SELECT s.name, COUNT(b.id) as bookings 
        FROM studios s
        LEFT JOIN rehearsal_booking b ON s.name = b.studio_name
        GROUP BY s.id, s.name
        ORDER BY bookings DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Проверка авторизации администратора
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

// Проверка доступности времени
function isTimeSlotAvailable($db, $date, $time, $excludeId = null) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rehearsal_booking 
        WHERE booking_date = ? 
        AND booking_time = ?
        AND status != 'cancelled'
        " . ($excludeId ? "AND id != ?" : "") . "
    ");
    if ($excludeId) {
        $stmt->execute([$date, $time, $excludeId]);
    } else {
        $stmt->execute([$date, $time]);
    }
    return $stmt->fetchColumn() == 0;
}
?>
<?php
// admin_user_bookings.php
require_once 'includes/functions.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!checkAdminAuth()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$userId = $_GET['id'] ?? 0;
$user = getUserById($userId);

if (!$user) {
    header('Location: admin.php');
    exit();
}

$stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE user_id = ? ORDER BY booking_date DESC");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Записи пользователя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>📋 Записи пользователя: <?= h($user['full_name']) ?></h1>
    
    <?php if (empty($bookings)): ?>
        <div class="success">Нет записей</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Дата</th><th>Время</th><th>Студия</th><th>Пожелания</th><th>Статус</th></tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= h($b['booking_date']) ?></td>
                    <td><?= h($b['booking_time']) ?></td>
                    <td><?= h($b['studio_name']) ?></td>
                    <td><?= h($b['special_requests'] ?: '-') ?></td>
                    <td><?= h($b['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <p style="margin-top: 20px;"><a href="admin.php">← Назад</a></p>
</div>
</body>
</html>
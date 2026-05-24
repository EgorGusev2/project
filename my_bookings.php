<?php
// my_bookings.php - просмотр своих записей
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Проверяем авторизацию
if (empty($_SESSION['login']) || empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$bookings = getUserBookings($_SESSION['uid']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои записи на репетицию</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .booking-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .booking-card:hover {
            transform: translateX(5px);
        }
        .booking-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #ffc107; color: #333; }
        .status-confirmed { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }
        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        .cancel-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📋 Мои записи на репетицию</h1>
    
    <div class="user-info">
        <span>👤 Вы вошли как <strong><?= h($_SESSION['login']) ?></strong></span>
        <a href="logout.php" class="logout-link">🚪 Выйти</a>
    </div>
    
    <?php if (empty($bookings)): ?>
        <div class="success">📭 У вас пока нет записей. <a href="index.php">Записаться на репетицию</a></div>
    <?php else: ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <p><strong>📅 Дата:</strong> <?= h($booking['booking_date']) ?></p>
                <p><strong>⏰ Время:</strong> <?= h($booking['booking_time']) ?></p>
                <p><strong>🏢 Студия:</strong> <?= h($booking['studio_name']) ?></p>
                <p><strong>💭 Пожелания:</strong> <?= h($booking['special_requests'] ?: '-') ?></p>
                <p>
                    <strong>Статус:</strong> 
                    <span class="booking-status status-<?= $booking['status'] ?>">
                        <?= $booking['status'] == 'pending' ? 'В ожидании' : ($booking['status'] == 'confirmed' ? 'Подтверждено' : 'Отменено') ?>
                    </span>
                </p>
                <?php if ($booking['status'] != 'cancelled'): ?>
                    <form method="POST" action="cancel_booking.php" onsubmit="return confirm('Отменить запись?')">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <button type="submit" class="cancel-btn">🗑️ Отменить запись</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <p style="margin-top: 20px; text-align: center;">
        <a href="index.php">← Новая запись</a>
    </p>
</div>
</body>
</html>
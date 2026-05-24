<?php
// admin_bookings.php
require_once 'includes/functions.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!checkAdminAuth()) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    exit();
}

// Обновление статуса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $stmt = $db->prepare("UPDATE rehearsal_booking SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['booking_id']]);
    $message = '<div class="success">✅ Статус обновлён</div>';
}

// Получение всех бронирований
$stmt = $db->prepare("
    SELECT b.*, a.full_name, a.phone, a.login 
    FROM rehearsal_booking b
    JOIN application a ON b.user_id = a.id
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->execute();
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление записями</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-select {
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .booking-row-pending { background: #fff3e0; }
        .booking-row-confirmed { background: #e8f5e9; }
        .booking-row-cancelled { background: #ffebee; }
    </style>
</head>
<body>
<div class="container" style="max-width: 1200px;">
    <h1>🎛️ Управление записями на репетицию</h1>
    
    <?php if (isset($message)) echo $message; ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Дата</th><th>Время</th><th>Клиент</th><th>Телефон</th><th>Студия</th><th>Пожелания</th><th>Статус</th><th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr class="booking-row-<?= $b['status'] ?>">
                <td><?= h($b['id']) ?></td>
                <td><?= h($b['booking_date']) ?></td>
                <td><?= h($b['booking_time']) ?></td>
                <td><?= h($b['full_name']) ?></td>
                <td><?= h($b['phone']) ?></td>
                <td><?= h($b['studio_name']) ?></td>
                <td><?= h($b['special_requests'] ?: '-') ?></td>
                <td>
                    <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <select name="status" class="status-select">
                            <option value="pending" <?= $b['status'] == 'pending' ? 'selected' : '' ?>>⏳ В ожидании</option>
                            <option value="confirmed" <?= $b['status'] == 'confirmed' ? 'selected' : '' ?>>✅ Подтверждено</option>
                            <option value="cancelled" <?= $b['status'] == 'cancelled' ? 'selected' : '' ?>>❌ Отменено</option>
                        </select>
                        <button type="submit" style="width: auto; padding: 5px 10px;">Изменить</button>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Удалить запись?')" style="display: inline;">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" style="background: #f56565; width: auto; padding: 5px 10px;">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p style="margin-top: 20px;"><a href="admin.php">← Назад в админку</a></p>
</div>
</body>
</html>
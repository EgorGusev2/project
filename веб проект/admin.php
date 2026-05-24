<?php
// admin.php
require_once 'includes/functions.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);

// HTTP-авторизация
if (!checkAdminAuth()) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    echo '<h1>401 Требуется авторизация</h1>';
    exit();
}

// Обработка удаления
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $stmt = $db->prepare("DELETE FROM rehearsal_booking WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $message = '<div class="success">✅ Запись удалена</div>';
}

// Обработка изменения статуса
if (isset($_POST['change_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    $stmt = $db->prepare("UPDATE rehearsal_booking SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['id']]);
    $message = '<div class="success">✅ Статус обновлен</div>';
}

// Получение всех бронирований
$bookings = getAllBookings();
$studioStats = getStudioStats();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора - Репетиции</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-panel { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .stats-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .stat-box { background: white; padding: 15px; border-radius: 8px; flex: 1; min-width: 200px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .btn-delete { background: #f44336; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .status-select { padding: 4px; border-radius: 4px; font-size: 12px; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .lang-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="container" style="max-width: 1200px;">
    <h1>👑 Панель администратора</h1>
    
    <?php if (isset($message)) echo $message; ?>
    
    <div class="stats-panel">
        <h2>📊 Статистика</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <strong>Всего записей:</strong> <?php echo count($bookings); ?>
            </div>
            <div class="stat-box">
                <strong>Популярность студий:</strong>
                <div>
                    <?php foreach ($studioStats as $stat): ?>
                        <div class="lang-item">
                            <span><?php echo h($stat['studio_name']); ?></span>
                            <span><?php echo h($stat['booking_count']); ?> зап.</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <h2>📋 Список бронирований</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Логин</th>
                <th>Дата</th>
                <th>Время</th>
                <th>Студия</th>
                <th>Статус</th>
                <th>Пожелания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?php echo h($booking['id']); ?></td>
                <td><?php echo h($booking['full_name']); ?></td>
                <td><?php echo h($booking['phone']); ?></td>
                <td><?php echo h($booking['login']); ?></td>
                <td><?php echo h($booking['booking_date']); ?></td>
                <td><?php echo h($booking['booking_time']); ?></td>
                <td><?php echo h($booking['studio_name']); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo h($booking['id']); ?>">
                        <select name="status" class="status-select" onchange="this.form.submit()">
                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?> class="status-pending">⏳ Ожидание</option>
                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?> class="status-confirmed">✅ Подтверждена</option>
                            <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?> class="status-cancelled">❌ Отменена</option>
                        </select>
                        <input type="hidden" name="change_status" value="1">
                    </form>
                </td>
                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo h($booking['special_requests']); ?></td>
                <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить запись?')">
                        <input type="hidden" name="id" value="<?php echo h($booking['id']); ?>">
                        <button type="submit" name="delete" class="btn-delete">🗑️ Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p style="margin-top: 20px;"><a href="index.php">← На главную</a></p>
</div>
</body>
</html>
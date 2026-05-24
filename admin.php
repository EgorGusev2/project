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

// Обработка удаления пользователя
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $stmt = $db->prepare("DELETE FROM application WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $message = '<div class="success">✅ Пользователь удалён</div>';
}

// Получение всех пользователей
$stmt = $db->prepare("
    SELECT a.*, COUNT(b.id) as bookings_count 
    FROM application a
    LEFT JOIN rehearsal_booking b ON a.id = b.user_id
    GROUP BY a.id ORDER BY a.id DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $db->prepare("SELECT COUNT(*) FROM application");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM rehearsal_booking WHERE status = 'pending'");
$stmt->execute();
$pendingBookings = $stmt->fetchColumn();

$studioStats = getStudioStats();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Репетиции</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-panel { background: linear-gradient(135deg, #667eea, #764ba2); padding: 20px; border-radius: 16px; margin-bottom: 20px; color: white; }
        .stats-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .stat-box { background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 15px; border-radius: 12px; flex: 1; min-width: 150px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4a5568; color: white; }
        tr:hover { background: #f7fafc; }
        .btn-edit { background: #4299e1; color: white; padding: 5px 10px; text-decoration: none; border-radius: 6px; font-size: 12px; }
        .btn-delete { background: #f56565; color: white; padding: 5px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-bookings { background: #48bb78; color: white; padding: 5px 10px; text-decoration: none; border-radius: 6px; font-size: 12px; }
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
                <div class="stat-number"><?= h($totalUsers) ?></div>
                <div>👥 Всего пользователей</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= h($pendingBookings) ?></div>
                <div>⏳ Ожидают подтверждения</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($studioStats) ?></div>
                <div>🎸 Студий</div>
            </div>
        </div>
    </div>
    
    <div class="stats-panel" style="background: #2d3748;">
        <h3>🎵 Популярность студий</h3>
        <?php foreach ($studioStats as $stat): ?>
            <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                <span><?= h($stat['name']) ?></span>
                <span><?= h($stat['bookings']) ?> бронирований</span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2>📋 Список пользователей</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Логин</th><th>Записей</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= h($user['id']) ?></td>
                <td><?= h($user['full_name']) ?></td>
                <td><?= h($user['phone']) ?></td>
                <td><?= h($user['login']) ?></td>
                <td><?= h($user['bookings_count']) ?></td>
                <td>
                    <a href="admin_edit.php?id=<?= h($user['id']) ?>" class="btn-edit">✏️ Ред.</a>
                    <a href="admin_user_bookings.php?id=<?= h($user['id']) ?>" class="btn-bookings">📋 Записи</a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить пользователя?')">
                        <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                        <button type="submit" name="delete" class="btn-delete">🗑️ Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; display: flex; gap: 10px;">
        <a href="admin_bookings.php" class="admin-btn" style="background: #48bb78;">🎸 Управление записями</a>
        <a href="index.php" style="color: #667eea;">← На главную</a>
    </div>
</div>
</body>
</html>
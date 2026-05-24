<?php
// edit_booking.php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/functions.php';

// Проверка авторизации
if (empty($_SESSION['login'])) {
    header('Location: login.php');
    exit();
}

$bookingId = $_GET['id'] ?? 0;
$booking = getBookingById($bookingId);

// Проверка, что запись принадлежит пользователю
if (!$booking || $booking['login'] != $_SESSION['login']) {
    header('Location: index.php');
    exit();
}

$studios = getAllStudios();
$availableTimes = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

// Обработка обновления
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $studio_name = $_POST['studio_name'];
    $special_requests = $_POST['special_requests'];
    
    $errors = false;
    
    // Валидация даты
    $bookingDateObj = DateTime::createFromFormat('Y-m-d', $booking_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if (!$bookingDateObj || $bookingDateObj < $today) {
        $errors = true;
        $dateError = '❌ Неверная дата';
    }
    
    // Проверка доступности
    if (!$errors && !isTimeSlotAvailable($booking_date, $booking_time, $studio_name, $bookingId)) {
        $errors = true;
        $timeError = '❌ Это время уже занято';
    }
    
    if (!$errors) {
        try {
            $stmt = $db->prepare("
                UPDATE rehearsal_booking 
                SET booking_date = ?, booking_time = ?, studio_name = ?, special_requests = ?
                WHERE id = ? AND login = ?
            ");
            $stmt->execute([$booking_date, $booking_time, $studio_name, $special_requests, $bookingId, $_SESSION['login']]);
            
            header('Location: index.php?edited=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Ошибка при сохранении';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование записи</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>✏️ Редактирование записи</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>📅 Дата записи:</label>
            <input type="date" name="booking_date" 
                   value="<?php echo h($booking['booking_date']); ?>"
                   min="<?php echo date('Y-m-d'); ?>" required>
            <?php if (isset($dateError)): ?>
                <small class="error"><?php echo $dateError; ?></small>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>⏰ Время записи:</label>
            <select name="booking_time" required>
                <?php foreach ($availableTimes as $time): ?>
                    <option value="<?php echo $time; ?>" <?php echo $booking['booking_time'] == $time ? 'selected' : ''; ?>>
                        <?php echo $time; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($timeError)): ?>
                <small class="error"><?php echo $timeError; ?></small>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>🏢 Студия:</label>
            <select name="studio_name" required>
                <?php foreach ($studios as $studio): ?>
                    <option value="<?php echo h($studio['name']); ?>" 
                        <?php echo $booking['studio_name'] == $studio['name'] ? 'selected' : ''; ?>>
                        <?php echo h($studio['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>📝 Пожелания:</label>
            <textarea name="special_requests" rows="4"><?php echo h($booking['special_requests']); ?></textarea>
        </div>
        
        <button type="submit">💾 Сохранить изменения</button>
        <a href="index.php" style="margin-left: 10px;">Отмена</a>
        
        <?php if ($booking['status'] != 'cancelled'): ?>
            <hr style="margin: 20px 0;">
            <a href="cancel_booking.php?id=<?php echo $bookingId; ?>" 
               class="btn-delete" 
               onclick="return confirm('Вы уверены, что хотите отменить запись?')"
               style="display: inline-block; text-align: center; text-decoration: none;">
                ❌ Отменить запись
            </a>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
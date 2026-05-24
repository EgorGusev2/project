<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

// Проверка авторизации
if (empty($_SESSION['login'])) {
    header('Location: login.php');
    exit();
}

$bookingId = $_GET['id'] ?? 0;

// Получаем данные бронирования
$stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

// Проверка, что запись принадлежит пользователю
if (!$booking || $booking['login'] != $_SESSION['login']) {
    header('Location: index.php');
    exit();
}

// Список студий
$studios = [];
try {
    $stmt = $db->query("SELECT name FROM studios ORDER BY name");
    $studios = $stmt->fetchAll();
} catch (PDOException $e) {
    $studios = [
        ['name' => 'Rock Studio'],
        ['name' => 'Jazz Hall'],
        ['name' => 'Electronic Lab'],
        ['name' => 'Acoustic Room'],
        ['name' => 'Recording Suite']
    ];
}

$availableTimes = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

$success = '';
$error = '';

// Обработка обновления
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $studio_name = $_POST['studio_name'];
    $special_requests = $_POST['special_requests'];
    
    // Валидация даты
    $bookingDateObj = DateTime::createFromFormat('Y-m-d', $booking_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if (!$bookingDateObj || $bookingDateObj < $today) {
        $error = '❌ Неверная дата (нельзя записаться на прошлое число)';
    }
    
    // Проверка доступности времени
    if (empty($error)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM rehearsal_booking WHERE booking_date = ? AND booking_time = ? AND studio_name = ? AND status != 'cancelled' AND id != ?");
        $stmt->execute([$booking_date, $booking_time, $studio_name, $bookingId]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Это время уже занято, выберите другое';
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $db->prepare("
                UPDATE rehearsal_booking 
                SET booking_date = ?, booking_time = ?, studio_name = ?, special_requests = ?
                WHERE id = ? AND login = ?
            ");
            $stmt->execute([$booking_date, $booking_time, $studio_name, $special_requests, $bookingId, $_SESSION['login']]);
            
            $success = "✅ Запись успешно обновлена!";
            
            // Обновляем данные в переменной
            $booking['booking_date'] = $booking_date;
            $booking['booking_time'] = $booking_time;
            $booking['studio_name'] = $studio_name;
            $booking['special_requests'] = $special_requests;
            
        } catch (PDOException $e) {
            $error = 'Ошибка при сохранении: ' . $e->getMessage();
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
    <style>
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-delete:hover {
            background: #d32f2f;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e0e0e0;
        }
        input:disabled, select:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #888;
        }
        .small-error {
            color: #f44336;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>✏️ Редактирование записи</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>👤 Имя:</label>
            <input type="text" value="<?php echo htmlspecialchars($booking['full_name']); ?>" disabled>
            <small>Имя нельзя изменить. Для смены имени обратитесь к администратору.</small>
        </div>
        
        <div class="form-group">
            <label>📞 Телефон:</label>
            <input type="text" value="<?php echo htmlspecialchars($booking['phone']); ?>" disabled>
            <small>Телефон нельзя изменить. Для смены телефона обратитесь к администратору.</small>
        </div>
        
        <div class="form-group">
            <label for="booking_date">📅 Дата записи:</label>
            <input type="date" id="booking_date" name="booking_date" 
                   value="<?php echo htmlspecialchars($booking['booking_date']); ?>"
                   min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="booking_time">⏰ Время записи:</label>
            <select id="booking_time" name="booking_time" required>
                <option value="">Выберите время</option>
                <?php foreach ($availableTimes as $time): ?>
                    <option value="<?php echo $time; ?>" <?php echo $booking['booking_time'] == $time ? 'selected' : ''; ?>>
                        <?php echo $time; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="studio_name">🏢 Студия:</label>
            <select id="studio_name" name="studio_name" required>
                <option value="">Выберите студию</option>
                <?php foreach ($studios as $studio): ?>
                    <option value="<?php echo htmlspecialchars($studio['name']); ?>" 
                        <?php echo $booking['studio_name'] == $studio['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($studio['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="special_requests">📝 Пожелания:</label>
            <textarea id="special_requests" name="special_requests" rows="4"><?php echo htmlspecialchars($booking['special_requests']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>📊 Статус записи:</label>
            <input type="text" value="<?php 
                $statusText = [
                    'pending' => '⏳ Ожидание подтверждения',
                    'confirmed' => '✅ Подтверждена администратором',
                    'cancelled' => '❌ Отменена'
                ];
                echo $statusText[$booking['status']] ?? $booking['status'];
            ?>" disabled>
            <small>Статус может менять только администратор</small>
        </div>
        
        <button type="submit">💾 Сохранить изменения</button>
        <a href="index.php" style="margin-left: 15px;">← Отмена</a>
        
        <?php if ($booking['status'] != 'cancelled'): ?>
            <hr>
            <a href="cancel_booking.php?id=<?php echo $bookingId; ?>" 
               class="btn-delete" 
               onclick="return confirm('Вы уверены, что хотите отменить эту запись?')">
                ❌ Отменить запись
            </a>
        <?php endif; ?>
    </form>
</div>
</body>
</html>

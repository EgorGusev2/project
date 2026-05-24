<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

// Генерация уникального логина
function generateUniqueLogin($db) {
    $prefixes = ['user', 'client', 'musician', 'rock', 'jazz'];
    $prefix = $prefixes[array_rand($prefixes)];
    do {
        $login = $prefix . rand(100, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM rehearsal_booking WHERE login = ?");
        $stmt->execute([$login]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    return $login;
}

function generatePassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, 8);
}

// Значения полей по умолчанию
$full_name_value = '';
$phone_value = '';
$booking_date_value = '';
$booking_time_value = '';
$studio_name_value = '';
$special_requests_value = '';
$agreement_checked = false;

$message = '';
$errors = [];

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Сохраняем введенные значения
    $full_name_value = trim($_POST['full_name'] ?? '');
    $phone_value = trim($_POST['phone'] ?? '');
    $booking_date_value = $_POST['booking_date'] ?? '';
    $booking_time_value = $_POST['booking_time'] ?? '';
    $studio_name_value = $_POST['studio_name'] ?? '';
    $special_requests_value = $_POST['special_requests'] ?? '';
    $agreement_checked = isset($_POST['agreement']);
    
    $full_name = $full_name_value;
    $phone = $phone_value;
    $booking_date = $booking_date_value;
    $booking_time = $booking_time_value;
    $studio_name = $studio_name_value;
    $special_requests = $special_requests_value;
    $agreement = $agreement_checked;
    
    // Валидация
    if (empty($full_name)) $errors[] = 'Введите имя';
    if (empty($phone)) $errors[] = 'Введите телефон';
    if (empty($booking_date)) $errors[] = 'Выберите дату';
    if (empty($booking_time)) $errors[] = 'Выберите время';
    if (empty($studio_name)) $errors[] = 'Выберите студию';
    if (!$agreement) $errors[] = 'Подтвердите согласие с правилами';
    
    // Проверка даты (не прошлое)
    if ($booking_date && strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Нельзя записаться на прошлую дату';
    }
    
    // Проверка доступности времени
    if (empty($errors) && $booking_date && $booking_time && $studio_name) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM rehearsal_booking WHERE booking_date = ? AND booking_time = ? AND studio_name = ? AND status != 'cancelled'");
        $stmt->execute([$booking_date, $booking_time, $studio_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Это время уже занято, выберите другое';
        }
    }
    
    // Сохранение
    if (empty($errors)) {
        try {
            // Удаляем внешний ключ если есть
            $db->exec("SET FOREIGN_KEY_CHECKS=0");
            
            if (isset($_SESSION['login'])) {
                $login = $_SESSION['login'];
                $stmt = $db->prepare("SELECT password_hash FROM rehearsal_booking WHERE login = ? LIMIT 1");
                $stmt->execute([$login]);
                $user = $stmt->fetch();
                $password_hash = $user['password_hash'];
                
                $stmt = $db->prepare("INSERT INTO rehearsal_booking (full_name, phone, booking_date, booking_time, studio_name, special_requests, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $phone, $booking_date, $booking_time, $studio_name, $special_requests, $login, $password_hash]);
                $message = '<div class="success">✅ Запись успешно создана!</div>';
                
                // Очищаем форму после успешной отправки
                $full_name_value = $phone_value = $booking_date_value = $booking_time_value = $studio_name_value = $special_requests_value = '';
                $agreement_checked = false;
            } else {
                $login = generateUniqueLogin($db);
                $password = generatePassword();
                $password_hash = md5($password);
                
                $stmt = $db->prepare("INSERT INTO rehearsal_booking (full_name, phone, booking_date, booking_time, studio_name, special_requests, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $phone, $booking_date, $booking_time, $studio_name, $special_requests, $login, $password_hash]);
                
                $message = '<div class="success">✅ Запись создана!<br>🔐 Ваши данные для входа:<br><strong>Логин: ' . htmlspecialchars($login) . '</strong><br><strong>Пароль: ' . htmlspecialchars($password) . '</strong><br><a href="login.php">Войти</a> для управления записью</div>';
                
                // Очищаем форму после успешной отправки
                $full_name_value = $phone_value = $booking_date_value = $booking_time_value = $studio_name_value = $special_requests_value = '';
                $agreement_checked = false;
            }
            
            $db->exec("SET FOREIGN_KEY_CHECKS=1");
        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
            $db->exec("SET FOREIGN_KEY_CHECKS=1");
        }
    }
}

// Если пользователь авторизован, подгружаем его данные
if (!empty($_SESSION['login']) && empty($full_name_value)) {
    try {
        $stmt = $db->prepare("SELECT full_name, phone FROM rehearsal_booking WHERE login = ? LIMIT 1");
        $stmt->execute([$_SESSION['login']]);
        $user = $stmt->fetch();
        if ($user) {
            $full_name_value = $user['full_name'];
            $phone_value = $user['phone'];
        }
    } catch (PDOException $e) {}
}

// Получение списка студий
$studios = [];
try {
    $stmt = $db->query("SELECT name FROM studios ORDER BY name");
    $studios = $stmt->fetchAll();
    if (empty($studios)) {
        $studios = [
            ['name' => 'Rock Studio'],
            ['name' => 'Jazz Hall'],
            ['name' => 'Electronic Lab'],
            ['name' => 'Acoustic Room'],
            ['name' => 'Recording Suite']
        ];
    }
} catch (PDOException $e) {
    $studios = [
        ['name' => 'Rock Studio'],
        ['name' => 'Jazz Hall'],
        ['name' => 'Electronic Lab'],
        ['name' => 'Acoustic Room'],
        ['name' => 'Recording Suite']
    ];
}

// Получение записей пользователя
$userBookings = [];
if (isset($_SESSION['login'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM rehearsal_booking WHERE login = ? ORDER BY booking_date DESC, booking_time DESC");
        $stmt->execute([$_SESSION['login']]);
        $userBookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        $userBookings = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на репетицию</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>🎸 Запись на репетицию</h1>
    
    <div class="admin-link">
        <a href="admin.php" class="admin-btn">👑 Админ-панель</a>
    </div>
    
    <?php if (!empty($message)) echo $message; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <div>❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['login'])): ?>
        <div class="user-info">
            <span>👤 Вы вошли как <strong><?php echo htmlspecialchars($_SESSION['login']); ?></strong></span>
            <a href="logout.php" class="logout-link">🚪 Выйти</a>
        </div>
    <?php else: ?>
        <div class="user-info">
            <a href="login.php" class="login-link">🔐 Уже есть запись? Войти</a>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div class="form-group">
            <label for="full_name">👤 Ваше имя:</label>
            <input type="text" id="full_name" name="full_name" 
                   value="<?php echo htmlspecialchars($full_name_value); ?>"
                   required placeholder="Иванов Иван Иванович">
        </div>

        <div class="form-group">
            <label for="phone">📞 Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($phone_value); ?>"
                   required placeholder="+7 (123) 456-78-90">
        </div>

        <div class="form-group">
            <label for="booking_date">📅 Дата записи:</label>
            <input type="date" id="booking_date" name="booking_date" 
                   value="<?php echo htmlspecialchars($booking_date_value); ?>"
                   required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label for="booking_time">⏰ Время записи:</label>
            <select id="booking_time" name="booking_time" required>
                <option value="">Выберите время</option>
                <?php
                $times = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
                foreach ($times as $time):
                ?>
                    <option value="<?php echo $time; ?>" <?php echo $booking_time_value == $time ? 'selected' : ''; ?>>
                        <?php echo $time; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="studio_name">🏢 Выберите студию:</label>
            <select id="studio_name" name="studio_name" required>
                <option value="">Выберите студию</option>
                <?php foreach ($studios as $studio): ?>
                    <option value="<?php echo htmlspecialchars($studio['name']); ?>" 
                        <?php echo $studio_name_value == $studio['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($studio['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="special_requests">📝 Пожелания:</label>
            <textarea id="special_requests" name="special_requests" rows="3" 
                      placeholder="Дополнительное оборудование, особые пожелания..."><?php echo htmlspecialchars($special_requests_value); ?></textarea>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="agreement" name="agreement" value="1" 
                    <?php echo $agreement_checked ? 'checked' : ''; ?>>
                <label for="agreement">📄 Я ознакомлен и согласен с правилами</label>
            </div>
        </div>

        <button type="submit">🎵 Записаться</button>
    </form>
    
    <?php if (!empty($userBookings)): ?>
        <div class="my-bookings">
            <h3>📅 Мои записи</h3>
            <?php foreach ($userBookings as $booking): ?>
                <div class="booking-item">
                    <div class="booking-info">
                        <strong><?php echo htmlspecialchars($booking['booking_date']); ?></strong> 
                        <?php echo htmlspecialchars($booking['booking_time']); ?><br>
                        Студия: <?php echo htmlspecialchars($booking['studio_name']); ?>
                        <?php if ($booking['special_requests']): ?>
                            <br><small>📝 <?php echo htmlspecialchars($booking['special_requests']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="booking-status status-<?php echo $booking['status']; ?>">
                            <?php 
                                $statusText = [
                                    'pending' => '⏳ Ожидание',
                                    'confirmed' => '✅ Подтверждена',
                                    'cancelled' => '❌ Отменена'
                                ];
                                echo $statusText[$booking['status']] ?? $booking['status'];
                            ?>
                        </span>
                        <?php if ($booking['status'] != 'cancelled'): ?>
                            <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn-small">✏️</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

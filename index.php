<?php
// index.php - ЗАПИСЬ НА МУЗЫКАЛЬНУЮ РЕПЕТИЦИЮ
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Отключаем вывод ошибок в production
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Подключение к БД
try {
    $db = new PDO("mysql:host=localhost;dbname=u82361;charset=utf8", 'u82361', '9967838');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Ошибка подключения к базе данных');
}

// Доступные студии
$availableStudios = [
    '🎸 Rock Studio' => '🎸 Rock Studio',
    '🎷 Jazz Hall' => '🎷 Jazz Hall',
    '🎹 Electronic Lab' => '🎹 Electronic Lab',
    '🎻 Acoustic Room' => '🎻 Acoustic Room',
    '🎧 Recording Suite' => '🎧 Recording Suite'
];

// Функция для генерации уникального логина
function generateUniqueLogin($db) {
    $prefixes = ['musician', 'rocker', 'drummer', 'singer', 'producer', 'guitarist'];
    $prefix = $prefixes[array_rand($prefixes)];
    
    do {
        $login = $prefix . rand(100, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM application WHERE login = ?");
        $stmt->execute([$login]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    
    return $login;
}

// Функция для генерации пароля
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Функция проверки доступности времени
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

// GET запрос - показываем форму
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array(
        'full_name' => '',
        'phone' => '',
        'booking_date' => '',
        'booking_time' => '',
        'studio_name' => array_key_first($availableStudios),
        'special_requests' => '',
        'contract' => false
    );

    // Проверяем cookie успешного сохранения
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success">✅ Заявка на репетицию успешно отправлена!</div>';
        
        if (!empty($_COOKIE['new_login']) && !empty($_COOKIE['new_pass'])) {
            $messages[] = sprintf(
                '<div class="success">🔐 Вам создан аккаунт!<br>
                 Логин: <strong>%s</strong><br>
                 Пароль: <strong>%s</strong><br>
                 <a href="login.php">Войти</a> для управления записями.</div>',
                htmlspecialchars($_COOKIE['new_login']),
                htmlspecialchars($_COOKIE['new_pass'])
            );
            setcookie('new_login', '', 100000);
            setcookie('new_pass', '', 100000);
        }
    }

    // Проверяем авторизацию через сессию
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        $messages[] = sprintf('<div class="success">👋 Вы вошли как <strong>%s</strong>. Вы можете редактировать свои данные и записи.</div>', 
            htmlspecialchars($_SESSION['login']));
        
        try {
            // Загружаем данные пользователя
            $stmt = $db->prepare("SELECT full_name, phone, agreed FROM application WHERE id = ?");
            $stmt->execute([$_SESSION['uid']]);
            $userData = $stmt->fetch();
            
            if ($userData) {
                $values['full_name'] = htmlspecialchars($userData['full_name']);
                $values['phone'] = htmlspecialchars($userData['phone']);
                $values['contract'] = (bool)$userData['agreed'];
            }
            
            // Загружаем ЕСЛИ ЕСТЬ бронирование пользователя (для редактирования)
            $stmt = $db->prepare("
                SELECT * FROM rehearsal_booking 
                WHERE user_id = ? 
                AND status != 'cancelled'
                ORDER BY booking_date DESC, booking_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['uid']]);
            $lastBooking = $stmt->fetch();
            
            if ($lastBooking) {
                $values['booking_date'] = $lastBooking['booking_date'];
                $values['booking_time'] = $lastBooking['booking_time'];
                $values['studio_name'] = $lastBooking['studio_name'];
                $values['special_requests'] = htmlspecialchars($lastBooking['special_requests'] ?? '');
                $messages[] = '<div class="info">📝 У вас есть активная запись. Вы можете её изменить, отправив форму заново.</div>';
            }
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $messages[] = '<div class="error">❌ Ошибка загрузки данных</div>';
        }
    } else {
        // Загружаем сохранённые значения из cookies (при ошибках)
        $errorFields = ['full_name', 'phone', 'booking_date', 'booking_time', 'studio_name', 'contract'];
        foreach ($errorFields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
            if ($errors[$field]) {
                setcookie($field . '_error', '', 100000);
            }
        }

        if ($errors['full_name']) $messages[] = '<div class="error">❌ Ошибка в поле "ФИО"</div>';
        if ($errors['phone']) $messages[] = '<div class="error">❌ Ошибка в поле "Телефон"</div>';
        if ($errors['booking_date']) $messages[] = '<div class="error">❌ Ошибка в поле "Дата репетиции"</div>';
        if ($errors['booking_time']) $messages[] = '<div class="error">❌ Ошибка в поле "Время репетиции"</div>';
        if ($errors['studio_name']) $messages[] = '<div class="error">❌ Ошибка в поле "Студия"</div>';
        if ($errors['contract']) $messages[] = '<div class="error">❌ Подтвердите ознакомление с правилами</div>';

        // Восстанавливаем значения из cookies
        $values['full_name'] = $_COOKIE['full_name_value'] ?? '';
        $values['phone'] = $_COOKIE['phone_value'] ?? '';
        $values['booking_date'] = $_COOKIE['booking_date_value'] ?? '';
        $values['booking_time'] = $_COOKIE['booking_time_value'] ?? '';
        $values['studio_name'] = $_COOKIE['studio_value'] ?? array_key_first($availableStudios);
        $values['special_requests'] = $_COOKIE['requests_value'] ?? '';
        $values['contract'] = !empty($_COOKIE['contract_value']);
    }

    include('form.php');
    exit();
}

// POST запрос - валидация и сохранение
else {
    $errors = false;

    // 1. ФИО
    if (empty($_POST['full_name'])) {
        setcookie('full_name_error', '1', time() + 86400);
        $errors = true;
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $_POST['full_name']) || strlen($_POST['full_name']) > 150) {
        setcookie('full_name_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('full_name_value', $_POST['full_name'], time() + 365 * 86400);

    // 2. Телефон
    if (empty($_POST['phone'])) {
        setcookie('phone_error', '1', time() + 86400);
        $errors = true;
    } elseif (!preg_match('/^[\d\s\+\(\)\-]{10,20}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('phone_value', $_POST['phone'], time() + 365 * 86400);

    // 3. Дата репетиции
    if (empty($_POST['booking_date'])) {
        setcookie('booking_date_error', '1', time() + 86400);
        $errors = true;
    } else {
        $bookingDate = DateTime::createFromFormat('Y-m-d', $_POST['booking_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        if (!$bookingDate || $bookingDate < $today) {
            setcookie('booking_date_error', '1', time() + 86400);
            $errors = true;
        }
    }
    setcookie('booking_date_value', $_POST['booking_date'], time() + 365 * 86400);

    // 4. Время репетиции
    if (empty($_POST['booking_time'])) {
        setcookie('booking_time_error', '1', time() + 86400);
        $errors = true;
    } elseif (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $_POST['booking_time'])) {
        setcookie('booking_time_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('booking_time_value', $_POST['booking_time'], time() + 365 * 86400);

    // 5. Студия
    if (empty($_POST['studio_name']) || !array_key_exists($_POST['studio_name'], $availableStudios)) {
        setcookie('studio_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('studio_value', $_POST['studio_name'], time() + 365 * 86400);

    // 6. Пожелания (необязательно, максимум 500 символов)
    $specialRequests = $_POST['special_requests'] ?? '';
    if (strlen($specialRequests) > 500) {
        setcookie('requests_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('requests_value', $specialRequests, time() + 365 * 86400);

    // 7. Правила
    if (empty($_POST['contract'])) {
        setcookie('contract_error', '1', time() + 86400);
        $errors = true;
    }
    setcookie('contract_value', $_POST['contract'], time() + 365 * 86400);

    // Проверка доступности времени (исключаем свою запись при редактировании)
    if (!$errors && !empty($_POST['booking_date']) && !empty($_POST['booking_time'])) {
        $existingBookingId = null;
        if (!empty($_SESSION['uid'])) {
            $stmt = $db->prepare("SELECT id FROM rehearsal_booking WHERE user_id = ? AND status != 'cancelled'");
            $stmt->execute([$_SESSION['uid']]);
            $existing = $stmt->fetch();
            if ($existing) {
                $existingBookingId = $existing['id'];
            }
        }
        
        if (!isTimeSlotAvailable($db, $_POST['booking_date'], $_POST['booking_time'], $existingBookingId)) {
            $messages[] = '<div class="error">❌ Это время уже занято! Выберите другое.</div>';
            setcookie('booking_time_error', '1', time() + 86400);
            $errors = true;
        }
    }

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Очищаем cookies ошибок
    $errorFields = ['full_name', 'phone', 'booking_date', 'booking_time', 'studio_name', 'contract'];
    foreach ($errorFields as $field) {
        setcookie($field . '_error', '', 100000);
    }

    try {
        $db->beginTransaction();
        
        if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
            // Обновляем данные пользователя
            $stmt = $db->prepare("UPDATE application SET full_name = ?, phone = ?, agreed = ? WHERE id = ?");
            $stmt->execute([$_POST['full_name'], $_POST['phone'], 1, $_SESSION['uid']]);
            
            // Проверяем, есть ли уже бронирование у пользователя
            $stmt = $db->prepare("SELECT id FROM rehearsal_booking WHERE user_id = ? AND status != 'cancelled'");
            $stmt->execute([$_SESSION['uid']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // ОБНОВЛЯЕМ существующее бронирование (редактирование)
                $stmt = $db->prepare("UPDATE rehearsal_booking SET booking_date = ?, booking_time = ?, studio_name = ?, special_requests = ?, status = 'pending' WHERE id = ?");
                $stmt->execute([$_POST['booking_date'], $_POST['booking_time'], $_POST['studio_name'], $specialRequests, $existing['id']]);
                $messages[] = '<div class="success">✏️ Ваша запись успешно обновлена!</div>';
            } else {
                // СОЗДАЁМ новое бронирование
                $stmt = $db->prepare("INSERT INTO rehearsal_booking (user_id, booking_date, booking_time, studio_name, special_requests, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['uid'], $_POST['booking_date'], $_POST['booking_time'], $_POST['studio_name'], $specialRequests]);
                $messages[] = '<div class="success">✅ Новая запись создана!</div>';
            }
        } else {
            // Создаём нового пользователя
            $login = generateUniqueLogin($db);
            $password = generatePassword();
            $password_hash = md5($password);
            
            $stmt = $db->prepare("INSERT INTO application (full_name, phone, agreed, login, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['full_name'], $_POST['phone'], 1, $login, $password_hash]);
            $userId = $db->lastInsertId();
            
            // Создаём бронирование
            $stmt = $db->prepare("INSERT INTO rehearsal_booking (user_id, booking_date, booking_time, studio_name, special_requests, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $_POST['booking_date'], $_POST['booking_time'], $_POST['studio_name'], $specialRequests]);
            
            setcookie('new_login', $login, time() + 60);
            setcookie('new_pass', $password, time() + 60);
        }
        
        $db->commit();
        
        setcookie('save', '1', time() + 86400);
        
        // Очищаем временные cookies
        $valueFields = ['full_name_value', 'phone_value', 'booking_date_value', 'booking_time_value', 'studio_value', 'requests_value', 'contract_value'];
        foreach ($valueFields as $field) {
            setcookie($field, '', 100000);
        }
        
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log($e->getMessage());
        setcookie('db_error', '1', time() + 86400);
        header('Location: index.php');
        exit();
    }
}
?>
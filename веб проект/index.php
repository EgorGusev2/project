<?php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/functions.php';

$availableTimes = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

// GET запрос - показываем форму
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array(
        'full_name' => '',
        'phone' => '',
        'booking_date' => '',
        'booking_time' => '',
        'studio_name' => '',
        'special_requests' => '',
        'agreement' => false
    );

    // Проверяем cookie успешного сохранения
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success">✅ Запись успешно создана!</div>';
        
        if (!empty($_COOKIE['new_login']) && !empty($_COOKIE['new_pass'])) {
            $messages[] = sprintf(
                '<div class="success">🔐 Ваши учетные данные для входа:<br>
                 Логин: <strong>%s</strong><br>
                 Пароль: <strong>%s</strong><br>
                 <a href="login.php">Войти</a> для просмотра и редактирования записей.</div>',
                htmlspecialchars($_COOKIE['new_login']),
                htmlspecialchars($_COOKIE['new_pass'])
            );
            setcookie('new_login', '', 100000);
            setcookie('new_pass', '', 100000);
        }
    }

    // Если пользователь авторизован, подгружаем его данные и бронирования
    if (!empty($_SESSION['login'])) {
        $user = getUserByLogin($_SESSION['login']);
        if ($user) {
            $values['full_name'] = h($user['full_name']);
            $values['phone'] = h($user['phone']);
            $messages[] = sprintf('<div class="success">👋 Вы вошли как %s</div>', h($_SESSION['login']));
        }
        
        // Показываем существующие бронирования пользователя
        $userBookings = getUserBookings($_SESSION['login']);
        if ($userBookings) {
            $bookingsHtml = '<div class="my-bookings"><h3>📅 Мои записи</h3><table class="bookings-table">';
            $bookingsHtml .= '<thead><tr><th>Дата</th><th>Время</th><th>Студия</th><th>Статус</th><th>Действия</th></tr></thead><tbody>';
            foreach ($userBookings as $booking) {
                $statusClass = $booking['status'] == 'confirmed' ? 'status-confirmed' : ($booking['status'] == 'cancelled' ? 'status-cancelled' : 'status-pending');
                $statusText = $booking['status'] == 'confirmed' ? 'Подтверждена' : ($booking['status'] == 'cancelled' ? 'Отменена' : 'Ожидание');
                $bookingsHtml .= sprintf(
                    '<tr>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td class="%s">%s</td>
                        <td><a href="edit_booking.php?id=%d" class="btn-small">✏️ Редактировать</a></td>
                    </tr>',
                    h($booking['booking_date']),
                    h($booking['booking_time']),
                    h($booking['studio_name']),
                    $statusClass,
                    $statusText,
                    $booking['id']
                );
            }
            $bookingsHtml .= '</tbody></table></div>';
            $messages[] = $bookingsHtml;
        }
    }

    // Проверяем ошибки из cookies
    $errorFields = ['full_name', 'phone', 'booking_date', 'booking_time', 'studio_name', 'agreement'];
    foreach ($errorFields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if ($errors[$field]) {
            setcookie($field . '_error', '', 100000);
        }
    }

    if ($errors['full_name']) $messages[] = '<div class="error">❌ Ошибка в поле "Имя"</div>';
    if ($errors['phone']) $messages[] = '<div class="error">❌ Ошибка в поле "Телефон"</div>';
    if ($errors['booking_date']) $messages[] = '<div class="error">❌ Неверная дата (должна быть сегодня или позже)</div>';
    if ($errors['booking_time']) $messages[] = '<div class="error">❌ Выберите время</div>';
    if ($errors['studio_name']) $messages[] = '<div class="error">❌ Выберите студию</div>';
    if ($errors['agreement']) $messages[] = '<div class="error">❌ Подтвердите ознакомление с правилами</div>';

    // Восстанавливаем значения из cookies
    $values['full_name'] = empty($_COOKIE['full_name_value']) ? $values['full_name'] : h($_COOKIE['full_name_value']);
    $values['phone'] = empty($_COOKIE['phone_value']) ? $values['phone'] : h($_COOKIE['phone_value']);
    $values['booking_date'] = empty($_COOKIE['booking_date_value']) ? '' : $_COOKIE['booking_date_value'];
    $values['booking_time'] = empty($_COOKIE['booking_time_value']) ? '' : $_COOKIE['booking_time_value'];
    $values['studio_name'] = empty($_COOKIE['studio_name_value']) ? '' : $_COOKIE['studio_name_value'];
    $values['special_requests'] = empty($_COOKIE['special_requests_value']) ? '' : h($_COOKIE['special_requests_value']);
    $values['agreement'] = !empty($_COOKIE['agreement_value']);

    $studios = getAllStudios();
    include('form.php');
    exit();
}

// POST запрос - валидация и сохранение
else {
    $errors = false;

    // Валидация полей
    if (empty($_POST['full_name'])) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $_POST['full_name']) || strlen($_POST['full_name']) > 150) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
    setcookie('full_name_value', $_POST['full_name'], time() + 365 * 24 * 60 * 60);

    if (empty($_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } elseif (!preg_match('/^[\d\s\+\(\)\-]{10,20}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
    setcookie('phone_value', $_POST['phone'], time() + 365 * 24 * 60 * 60);

    if (empty($_POST['booking_date'])) {
        setcookie('booking_date_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    } else {
        $bookingDate = DateTime::createFromFormat('Y-m-d', $_POST['booking_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        if (!$bookingDate || $bookingDate < $today) {
            setcookie('booking_date_error', '1', time() + 24 * 60 * 60);
            $errors = true;
        }
    }
    setcookie('booking_date_value', $_POST['booking_date'], time() + 365 * 24 * 60 * 60);

    if (empty($_POST['booking_time'])) {
        setcookie('booking_time_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
    setcookie('booking_time_value', $_POST['booking_time'], time() + 365 * 24 * 60 * 60);

    if (empty($_POST['studio_name'])) {
        setcookie('studio_name_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
    setcookie('studio_name_value', $_POST['studio_name'], time() + 365 * 24 * 60 * 60);

    setcookie('special_requests_value', $_POST['special_requests'] ?? '', time() + 365 * 24 * 60 * 60);

    if (empty($_POST['agreement'])) {
        setcookie('agreement_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
    setcookie('agreement_value', $_POST['agreement'] ?? '', time() + 365 * 24 * 60 * 60);

    // Проверка доступности времени
    if (!$errors && !empty($_POST['booking_date']) && !empty($_POST['booking_time']) && !empty($_POST['studio_name'])) {
        if (!isTimeSlotAvailable($_POST['booking_date'], $_POST['booking_time'], $_POST['studio_name'])) {
            setcookie('booking_time_error', '1', time() + 24 * 60 * 60);
            $errors = true;
            setcookie('booking_time_value', '', time() + 365 * 24 * 60 * 60);
            header('Location: index.php?error=time_taken');
            exit();
        }
    }

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Очищаем ошибки
    $errorFields = ['full_name', 'phone', 'booking_date', 'booking_time', 'studio_name', 'agreement'];
    foreach ($errorFields as $field) {
        setcookie($field . '_error', '', 100000);
    }

    try {
        // Проверяем, авторизован ли пользователь
        if (!empty($_SESSION['login'])) {
            $login = $_SESSION['login'];
            
            // Обновляем данные пользователя
            $stmt = $db->prepare("UPDATE rehearsal_booking SET full_name = ?, phone = ? WHERE login = ?");
            $stmt->execute([$_POST['full_name'], $_POST['phone'], $login]);
        } else {
            // Создаем нового пользователя
            $login = generateUniqueLogin($db);
            $password = generatePassword();
            $password_hash = md5($password);
            
            setcookie('new_login', $login, time() + 60);
            setcookie('new_pass', $password, time() + 60);
        }
        
        // Если пользователь новый, создаем запись с его данными
        if (empty($_SESSION['login'])) {
            $stmt = $db->prepare("
                INSERT INTO rehearsal_booking (full_name, phone, booking_date, booking_time, studio_name, special_requests, login, password_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['full_name'],
                $_POST['phone'],
                $_POST['booking_date'],
                $_POST['booking_time'],
                $_POST['studio_name'],
                $_POST['special_requests'] ?? '',
                $login,
                $password_hash
            ]);
        } else {
            // Для авторизованного пользователя создаем новую запись
            $stmt = $db->prepare("
                INSERT INTO rehearsal_booking (full_name, phone, booking_date, booking_time, studio_name, special_requests, login, password_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT password_hash FROM rehearsal_booking WHERE login = ? LIMIT 1))
            ");
            $stmt->execute([
                $_POST['full_name'],
                $_POST['phone'],
                $_POST['booking_date'],
                $_POST['booking_time'],
                $_POST['studio_name'],
                $_POST['special_requests'] ?? '',
                $login,
                $login
            ]);
        }
        
        setcookie('save', '1', time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        
        if ($e->errorInfo[1] == 1062) { // Duplicate entry
            setcookie('booking_time_error', '1', time() + 24 * 60 * 60);
        }
        
        header('Location: index.php');
        exit();
    }
}
?>
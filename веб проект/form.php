<?php
// form.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на репетицию</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .my-bookings {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        .bookings-table th, .bookings-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .bookings-table th {
            background: #667eea;
            color: white;
        }
        .status-confirmed { color: #4caf50; font-weight: bold; }
        .status-cancelled { color: #f44336; font-weight: bold; }
        .status-pending { color: #ff9800; font-weight: bold; }
        .btn-small {
            display: inline-block;
            padding: 4px 10px;
            background: #ffc107;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .btn-small:hover {
            background: #e0a800;
        }
        .info-note {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🎸 Запись на репетицию</h1>
    
    <div class="admin-link">
        <a href="admin.php" class="admin-btn">👑 Админ-панель</a>
    </div>
    
    <div class="info-note">
        💡 <strong>Время работы студий:</strong> 10:00 - 22:00. Длительность сессии - 1 час.
    </div>
    
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <?php echo $message; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['login'])): ?>
        <div class="user-info">
            <span>👤 Вы вошли как <strong><?php echo htmlspecialchars($_SESSION['login']); ?></strong></span>
            <a href="logout.php" class="logout-link">🚪 Выйти</a>
        </div>
    <?php else: ?>
        <div class="user-info">
            <a href="login.php" class="login-link">🔐 Уже есть запись? Войти для редактирования</a>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div class="form-group">
            <label for="full_name">👤 Ваше имя:</label>
            <input type="text" id="full_name" name="full_name" 
                   value="<?php echo htmlspecialchars($values['full_name']); ?>"
                   class="<?php echo !empty($errors['full_name']) ? 'error' : ''; ?>"
                   placeholder="Иванов Иван Иванович" required>
        </div>

        <div class="form-group">
            <label for="phone">📞 Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($values['phone']); ?>"
                   class="<?php echo !empty($errors['phone']) ? 'error' : ''; ?>"
                   placeholder="+7 (123) 456-78-90" required>
        </div>

        <div class="form-group">
            <label for="booking_date">📅 Дата записи:</label>
            <input type="date" id="booking_date" name="booking_date" 
                   value="<?php echo htmlspecialchars($values['booking_date']); ?>"
                   class="<?php echo !empty($errors['booking_date']) ? 'error' : ''; ?>"
                   min="<?php echo date('Y-m-d'); ?>" required>
            <small>Нельзя записаться на прошлое число</small>
        </div>

        <div class="form-group">
            <label for="booking_time">⏰ Время записи:</label>
            <select id="booking_time" name="booking_time" class="<?php echo !empty($errors['booking_time']) ? 'error' : ''; ?>" required>
                <option value="">Выберите время</option>
                <?php
                $times = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
                foreach ($times as $time):
                ?>
                    <option value="<?php echo $time; ?>" <?php echo $values['booking_time'] == $time ? 'selected' : ''; ?>>
                        <?php echo $time; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Доступные слоты: 10:00 - 21:00</small>
        </div>

        <div class="form-group">
            <label for="studio_name">🏢 Выберите студию:</label>
            <select id="studio_name" name="studio_name" class="<?php echo !empty($errors['studio_name']) ? 'error' : ''; ?>" required>
                <option value="">Выберите студию</option>
                <?php foreach ($studios as $studio): ?>
                    <option value="<?php echo htmlspecialchars($studio['name']); ?>" 
                        <?php echo $values['studio_name'] == $studio['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($studio['name']); ?>
                        <?php if ($studio['description']): ?> - <?php echo htmlspecialchars($studio['description']); ?><?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="special_requests">📝 Пожелания к записи:</label>
            <textarea id="special_requests" name="special_requests" rows="4" 
                      placeholder="Дополнительное оборудование, особые пожелания..."><?php echo htmlspecialchars($values['special_requests']); ?></textarea>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="agreement" name="agreement" value="1" 
                       <?php echo $values['agreement'] ? 'checked' : ''; ?>>
                <label for="agreement">📄 Я ознакомлен и согласен с правилами пользования студией</label>
            </div>
        </div>

        <button type="submit">🎵 Записаться на репетицию</button>
    </form>
</div>
</body>
</html>
<?php
// form.php - шаблон формы записи на репетицию
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎸 Запись на репетицию</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>🎸 Запись на репетицию</h1>
    
    <div class="admin-link">
        <a href="admin.php" class="admin-btn">👑 Админ-панель</a>
    </div>
    
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['login'])): ?>
        <div class="user-info">
            <span>👤 Вы вошли как <strong><?= htmlspecialchars($_SESSION['login']) ?></strong></span>
            <a href="logout.php" class="logout-link">🚪 Выйти</a>
        </div>
    <?php else: ?>
        <div class="user-info">
            <a href="login.php" class="login-link">🔐 Уже есть аккаунт? Войти</a>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <!-- ФИО -->
        <div class="form-group">
            <label for="full_name">📝 ФИО:</label>
            <input type="text" id="full_name" name="full_name" 
                   value="<?= htmlspecialchars($values['full_name']) ?>"
                   class="<?= !empty($errors['full_name']) ? 'error' : '' ?>"
                   placeholder="Иванов Иван Иванович" required>
        </div>

        <!-- Телефон -->
        <div class="form-group">
            <label for="phone">📞 Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   value="<?= htmlspecialchars($values['phone']) ?>"
                   class="<?= !empty($errors['phone']) ? 'error' : '' ?>"
                   placeholder="+7 (123) 456-78-90" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">📧 Email:</label>
            <input type="email" id="email" name="email" 
                   value="<?= htmlspecialchars($values['email']) ?>"
                   class="<?= !empty($errors['email']) ? 'error' : '' ?>"
                   placeholder="example@mail.ru" required>
        </div>

        <!-- Дата рождения -->
        <div class="form-group">
            <label for="birth_date">🎂 Дата рождения:</label>
            <input type="date" id="birth_date" name="birth_date" 
                   value="<?= htmlspecialchars($values['birth_date']) ?>"
                   class="<?= !empty($errors['birth_date']) ? 'error' : '' ?>">
            <small>Должно быть 18 лет и старше</small>
        </div>

        <!-- Пол -->
        <div class="form-group">
            <label>Пол:</label>
            <div class="radio-group">
                <input type="radio" id="male" name="gender" value="male" 
                       <?= ($values['gender'] == 'male') ? 'checked' : '' ?>>
                <label for="male">👨 Мужской</label>
            </div>
            <div class="radio-group">
                <input type="radio" id="female" name="gender" value="female" 
                       <?= ($values['gender'] == 'female') ? 'checked' : '' ?>>
                <label for="female">👩 Женский</label>
            </div>
        </div>

        <!-- Биография -->
        <div class="form-group">
            <label for="biography">📖 Биография (музыкальный опыт):</label>
            <textarea id="biography" name="biography" rows="4" 
                      placeholder="Расскажите о своём музыкальном опыте, инструментах и т.д."><?= htmlspecialchars($values['biography']) ?></textarea>
        </div>

        <hr style="margin: 20px 0; border-color: #e2e8f0;">

        <h3>🎵 Детали репетиции</h3>

        <!-- Дата репетиции -->
        <div class="form-group">
            <label for="booking_date">📅 Дата репетиции:</label>
            <input type="date" id="booking_date" name="booking_date" 
                   value="<?= htmlspecialchars($values['booking_date']) ?>"
                   class="<?= !empty($errors['booking_date']) ? 'error' : '' ?>"
                   required>
            <small>Выберите дату (от сегодняшней)</small>
        </div>

        <!-- Время репетиции -->
        <div class="form-group">
            <label for="booking_time">⏰ Время репетиции:</label>
            <input type="time" id="booking_time" name="booking_time" 
                   value="<?= htmlspecialchars($values['booking_time']) ?>"
                   class="<?= !empty($errors['booking_time']) ? 'error' : '' ?>"
                   required>
            <small>Работаем с 10:00 до 22:00</small>
        </div>

        <!-- Выбор студии -->
        <div class="form-group">
            <label for="studio_name">🏢 Выберите студию:</label>
            <select id="studio_name" name="studio_name" 
                    class="<?= !empty($errors['studio_name']) ? 'error' : '' ?>" required>
                <?php foreach ($availableStudios as $name => $value): ?>
                    <option value="<?= htmlspecialchars($name) ?>" 
                        <?= $values['studio_name'] == $name ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Пожелания к репетиции -->
        <div class="form-group">
            <label for="special_requests">💭 Пожелания к репетиции:</label>
            <textarea id="special_requests" name="special_requests" rows="4" 
                      placeholder="Напишите особые пожелания: нужное оборудование, особые условия и т.д."><?= htmlspecialchars($values['special_requests']) ?></textarea>
            <small>Необязательное поле, до 500 символов</small>
        </div>

        <!-- Согласие с правилами -->
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="contract" name="contract" value="1" 
                       <?= $values['contract'] ? 'checked' : '' ?>
                       class="<?= !empty($errors['contract']) ? 'error' : '' ?>">
                <label for="contract">📄 Ознакомлен с правилами записи и согласен</label>
            </div>
        </div>

        <button type="submit">🎸 Записаться на репетицию</button>
    </form>
    
    <!-- Ссылка на просмотр своих записей -->
    <?php if (!empty($_SESSION['login'])): ?>
    <div style="margin-top: 20px; text-align: center;">
        <a href="my_bookings.php" style="color: #667eea;">📋 Посмотреть мои записи</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
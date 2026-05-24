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
        <div class="edit-info">
            💡 <strong>У вас уже есть запись?</strong> Просто измените данные в форме ниже — запись обновится автоматически.
        </div>
    <?php else: ?>
        <div class="user-info">
            <a href="login.php" class="login-link">🔐 Уже есть аккаунт? Войти</a>
            <span style="margin-left: 15px; color: #666;">|</span>
            <span style="color: #666;">📝 Нет аккаунта? Заполните форму — он создастся автоматически</span>
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
            <textarea id="special_requests" name="special_requests" rows="3" 
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

        <button type="submit">
            <?= !empty($_SESSION['login']) ? '✏️ Обновить запись' : '🎸 Записаться на репетицию' ?>
        </button>
    </form>
    
    <!-- Ссылка на просмотр своих записей (только для авторизованных) -->
    <?php if (!empty($_SESSION['login'])): ?>
    <div style="margin-top: 20px; text-align: center;">
        <a href="my_bookings.php" class="my-bookings-link">📋 Посмотреть мои записи</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
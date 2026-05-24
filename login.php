<?php
// login.php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/functions.php';

// Если уже авторизован - перенаправляем
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// GET - показываем форму входа
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Вход в систему</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <h1>🔐 Вход для управления записями</h1>
            <?php if (!empty($_GET['error'])): ?>
                <div class="error">❌ Неверный логин или пароль</div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" required placeholder="Введите ваш логин">
                </div>
                <div class="form-group">
                    <label for="pass">Пароль:</label>
                    <input type="password" id="pass" name="pass" required placeholder="Введите пароль">
                </div>
                <button type="submit">🚪 Войти</button>
            </form>
            <p style="margin-top: 20px; text-align: center;">
                <a href="index.php">← Вернуться к форме записи</a>
            </p>
        </div>
    </body>
    </html>
    <?php
}
// POST - проверяем логин и пароль
else {
    $login = $_POST['login'];
    $pass = $_POST['pass'];
    $pass_hash = md5($pass);
    
    $user = getUserByLoginAndPass($login, $pass_hash);
    
    if ($user) {
        $_SESSION['login'] = $user['login'];
        header('Location: index.php');
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
}
?>
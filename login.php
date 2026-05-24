<?php
// login.php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $db = new PDO("mysql:host=localhost;dbname=u82361;charset=utf8", 'u82361', '9967838');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Ошибка сервера. Попробуйте позже.');
}

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
                <a href="index.php">← Вернуться к записи</a>
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
    
    $stmt = $db->prepare("SELECT id, login FROM application WHERE login = ? AND password_hash = ?");
    $stmt->execute([$login, $pass_hash]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['login'] = $user['login'];
        $_SESSION['uid'] = $user['id'];
        session_regenerate_id(true);
        header('Location: index.php');
        exit();
    } else {
        usleep(rand(500000, 1500000));
        header('Location: login.php?error=1');
        exit();
    }
}
?>
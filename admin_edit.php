<?php
// admin_edit.php
require_once 'includes/functions.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!checkAdminAuth()) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    exit();
}

$id = $_GET['id'] ?? 0;
$user = getUserById($id);
if (!$user) {
    header('Location: admin.php');
    exit();
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    if (!empty($full_name)) {
        $stmt = $db->prepare("UPDATE application SET full_name=?, phone=? WHERE id=?");
        $stmt->execute([$full_name, $phone, $id]);
        header('Location: admin.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование пользователя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>✏️ Редактирование пользователя</h1>
    
    <form method="POST">
        <div class="form-group">
            <label>ФИО:</label>
            <input type="text" name="full_name" value="<?= h($user['full_name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Телефон:</label>
            <input type="text" name="phone" value="<?= h($user['phone']) ?>">
        </div>
        
        <div class="form-group">
            <label>Логин:</label>
            <input type="text" value="<?= h($user['login']) ?>" disabled>
            <small>Логин нельзя изменить</small>
        </div>
        
        <button type="submit">💾 Сохранить</button>
        <a href="admin.php" style="margin-left: 10px;">Отмена</a>
    </form>
</div>
</body>
</html>
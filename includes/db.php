<?php
// includes/db.php
try {
    $db = new PDO("mysql:host=localhost;dbname=u82361;charset=utf8", 'u82361', '9967838');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Ошибка подключения к базе данных');
}
?>
<?php
// cancel_booking.php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['login']) || empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $stmt = $db->prepare("UPDATE rehearsal_booking SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['booking_id'], $_SESSION['uid']]);
}

header('Location: my_bookings.php');
exit();
?>
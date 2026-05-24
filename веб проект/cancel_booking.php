<?php
// cancel_booking.php
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/functions.php';

if (empty($_SESSION['login'])) {
    header('Location: login.php');
    exit();
}

$bookingId = $_GET['id'] ?? 0;
$booking = getBookingById($bookingId);

if ($booking && $booking['login'] == $_SESSION['login'] && $booking['status'] != 'cancelled') {
    $stmt = $db->prepare("UPDATE rehearsal_booking SET status = 'cancelled' WHERE id = ? AND login = ?");
    $stmt->execute([$bookingId, $_SESSION['login']]);
}

header('Location: index.php');
exit();
?>
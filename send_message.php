<?php
include "db.php"; 
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = $conn->real_escape_string($_POST['room_id']);
    $message = $conn->real_escape_string($_POST['message']);

    if (!empty($message)) {
     
        $sql = "INSERT INTO chat_messages (room_id, user_id, message) VALUES ('$room_id', '$user_id', '$message')";
        $conn->query($sql);
    }
}
?>
<?php
include "db.php";
session_start();
if(isset($_SESSION['user_id']) && isset($_GET['room_id'])){
    $uid = $_SESSION['user_id'];
    $rid = (int)$_GET['room_id'];
    $conn->query("UPDATE room_member SET last_activity = NOW() WHERE room_id = '$rid' AND user_id = '$uid'");
}
?>
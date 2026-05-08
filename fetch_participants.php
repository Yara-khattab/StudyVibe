<?php
include "db.php";
$room_id = (int)$_GET['room_id'];


$conn->query("DELETE FROM room_member WHERE room_id = '$room_id' AND last_activity < DATE_SUB(NOW(), INTERVAL 60 SECOND)");

$res = $conn->query("SELECT users.user_name FROM room_member 
                     JOIN users ON room_member.user_id = users.id 
                     WHERE room_member.room_id = '$room_id'");

$users = [];
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
?>
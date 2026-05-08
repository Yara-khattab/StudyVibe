<?php
include "db.php";
session_start();


header('Content-Type: application/json');

if(isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    
    
    $query = $conn->prepare("UPDATE users SET profile_pic = NULL WHERE email = ?");
    $query->bind_param("s", $email);
    
    if($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
}
exit();
?>
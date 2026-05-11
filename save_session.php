<?php
include "db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
    $today = date('Y-m-d');
    $user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_query->bind_param("s", $email);
    $user_query->execute();
    $res = $user_query->get_result()->fetch_assoc();
    $user_id = $res['id'] ?? null;

    if ($user_id && $duration > 0) {
        $stmt = $conn->prepare("INSERT INTO study_sessions (user_id, room_id, duration_minutes, study_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $room_id, $duration, $today);
        
        if ($stmt->execute()) {
            echo "success"; 
        } else {
            echo "database_error";
        }
    } else {
        echo "invalid_data";
    }
} else {
    echo "unauthorized";
}
?>

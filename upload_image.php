<?php
include "db.php";
session_start();

if (isset($_FILES['profile_pic'])) {
    $email = $_SESSION['user_email'];
    $file = $_FILES['profile_pic'];
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . $email . '.' . $ext;
    $target = "uploads/" . $filename;

    if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE email = ?");
        $stmt->bind_param("ss", $filename, $email);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB Update Failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload Failed']);
    }
}
?>
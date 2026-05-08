<?php
include "db.php"; 
session_start();


date_default_timezone_set('Africa/Cairo');

if (isset($_GET['room_id'])) {
    $room_id = $conn->real_escape_string($_GET['room_id']);

   
    $sql = "SELECT chat_messages.*, users.user_name 
            FROM chat_messages 
            JOIN users ON chat_messages.user_id = users.id 
            WHERE chat_messages.room_id = '$room_id' 
            ORDER BY chat_messages.id ASC";

    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            
            $timestamp = strtotime($row['created_at']);
            $msg_time = date("h:i A", $timestamp);
            
            
            echo "<div class='message-item' style='margin-bottom: 5px; display: flex; align-items: flex-start; gap: 6px;'>
                    <div class='user-info' style='display: flex; flex-direction: column; min-width: 70px;'>
                        <strong class='user-name' style='color: #2ecc71; font-size:20px; line-height: 1;'>
                            " . htmlspecialchars($row['user_name']) . ":
                        </strong>
                        <span class='message-time' style='color: #666; font-size:16px; margin-top: 3px;'>
                            " . $msg_time . "
                        </span>
                    </div>
                   <span class='message-text' style='color: rgb(218, 216, 215); font-size: 18px; word-wrap: break-word;'>
                            " . htmlspecialchars($row['message']) . "
                        </span>
                  </div>";
        }
    } else {
        echo "<p style='color:gray; text-align:center; font-size: 12px;'>No messages yet.</p>";
    }
}
?>
<?php

$conn = new mysqli(
    "sql303.infinityfree.com",  // host
    "if0_41797883",               // username
    "mAya123456",           // password
    "if0_41797883_studyvibe"     // database name
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
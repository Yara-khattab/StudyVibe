<?php
include "db.php";
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}


$user_name = "User"; 
if (isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    
  
    $result = $conn->query("SELECT user_name FROM users WHERE email = '$email'");
    
    if ($result && $row = $result->fetch_assoc()) {
        $user_name = $row['user_name']; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Page - StudyVibe</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
</head>

<body>
    <main class="rooms-page">

        <section class="rooms-sidebar">
            <div class="logo-sidebar">
                 <i class="fa-solid fa-book-open"></i> StudyVibe
            </div>

            <div class="rooms-sidebar-menu">
                <ul>
                    <li>
                        <button class="btn" onclick="location.href='index.php'"><i class="fas fa-home"></i> Home</button>
                    </li>
                    <li>
                        <button class="btn" onclick="location.href='Profile.php'"><i class="fas fa-user"></i> My Profile</button>
                    </li>
                    <li>
                        <button class="btn" onclick="location.href='rooms.php'"><i class="fas fa-users"></i> Rooms</button>
                    </li>
                    <li>
                      
                        <button class="btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Log out</button>
                    </li>
                </ul>
            </div>
        </section>


        <section class="rooms-body">
                 <div class="item">
                   
                    <h1>Hello, <?php echo htmlspecialchars($user_name); ?>! Are you ready?</h1>
                 </div>
                 <div class="item">
                    <button class="btn" onclick="location.href='createRoom.php'"><i class="fas fa-plus"></i> Create Room</button>
                 </div>
                 <div class="item">
                    <button class="btn" onclick="location.href='join.php'"><i class="fas fa-sign-in-alt"></i> Join Room</button>
                 </div>
                 <div class="item">
                    <p>Start your own private study space. Invite friends, share documents, and chat in real-time.</p>
                 </div>
                 <div class="item">
                    <p>Join public study rooms and find your flow with others. Stay motivated and accountable while hitting your goals together.</p>
                 </div>
        </section>
         
        <section class="rooms-footer">
            <div>
                 <i class="fa-solid fa-clock"></i> Focus Timer
            </div>
           <div class="vertical-divider"></div>
            <div >
                <i class="fa-solid fa-users"></i>  Study Rooms
            </div>
            <div class="vertical-divider"></div>
            <div>
                <i class="fa-solid fa-mug-hot"></i> Smart Breaks
            </div>
        </section>

    </main>
</body>
</html>
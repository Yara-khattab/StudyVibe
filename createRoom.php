<?php
include "db.php";
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name   = $_POST['room_name'] ?? '';
    $topic       = $_POST['topic'] ?? ''; 
    $privacy     = $_POST['privacy'] ?? 'public';
    $max_users   = ($privacy === 'private') ? 1 : (int)($_POST['max_users'] ?? 10);
    
    $study_time  = (int)($_POST['study_time'] ?? 25);
    $break_every = (int)($_POST['break_every'] ?? 25);
    $break_time  = (int)($_POST['break_time'] ?? 5);
    
    $room_code = strtoupper(substr(md5(time() . $room_name), 0, 6));

    $email = $_SESSION['user_email'];
    $user_res = $conn->query("SELECT id FROM users WHERE email = '$email'");
    $user_data = $user_res->fetch_assoc();
    $host_id = $user_data['id'];

    // الترتيب الصحيح حسب صورة الـ Database: room_name, room_code, topic
    $sql = "INSERT INTO rooms (room_name, room_code, topic, max_participants, host_id, study_duration, break_every, break_duration, privacy) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssiiiiis", 
            $room_name, 
            $room_code, 
            $topic, 
            $max_users, 
            $host_id, 
            $study_time, 
            $break_every, 
            $break_time, 
            $privacy
        );

        if ($stmt->execute()) {
            $room_id = $stmt->insert_id;
            $conn->query("INSERT INTO room_member (room_id, user_id) VALUES ($room_id, $host_id)");
            
            $success_message = "Room Created Successfully! Redirecting...";
            $target_page = ($privacy === 'private') ? "private_room.php" : "room.php";
            header("refresh:2; url=$target_page?code=$room_code");
        } else {
            $error_message = "Execution failed: " . $stmt->error;
        }
    } else {
        $error_message = "Prepare failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Room - StudyVibe</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/createroom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <main class="createroom-page">
        <div class="bg-glass">
            <h1> <i class="fa-solid fa-book-open"></i> Create New Study Room</h1>
            
            <?php if($success_message): ?>
                <div class="alert success-msg" style="color: #00ff88; text-align: center; margin-bottom: 20px;">
                    <i class="fa-solid fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if($error_message): ?>
                <div class="alert error-msg" style="color: #ff4d4d; text-align: center; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-box">
                    <h4>Room Name</h4>
                    <input type="text" name="room_name" placeholder="Room Name" required>
                </div>

                <div class="input-box">
                    <h4>Topic</h4>
                    <input type="text" name="topic" placeholder="Topic (e.g. OS, Math)" required>
                </div>

                <div class="row-inputs">
                    <div class="input-box">
                        <h4>Study Time</h4>
                        <input type="number" name="study_time" value="25" required>
                    </div>
                     <div class="input-box">
                        <h4>Break Every</h4>
                        <input type="number" name="break_every" value="25" required>
                    </div>
                    <div class="input-box">
                        <h4>Break Time</h4>
                        <input type="number" name="break_time" value="5" required>
                    </div>
                </div>

                <div class="privacy-container">
                    <label class="privacy-card" onclick="toggleView(true)">
                        <input type="radio" name="privacy" value="private" required>
                        <div class="card-header">
                            <i class="fas fa-lock"></i>
                            <span>Private</span>
                        </div>
                        <p>Personal study space</p>
                    </label>

                    <label class="privacy-card" onclick="toggleView(false)">
                        <input type="radio" name="privacy" value="public" required checked>
                        <div class="card-header">
                            <i class="fas fa-globe"></i>
                            <span>Public</span>
                        </div>
                        <p>Appear to everyone</p>
                    </label>
                </div>

                <div class="input-box" id="participants-section">
                    <div class="participants-header">
                        <h4>Max Participants</h4>
                        <h4>2-20</h4>
                    </div>
                    <input type="range" name="max_users" min="2" max="20" value="10" oninput="this.nextElementSibling.value = this.value">
                    <output style="color: white; display: block; text-align: center;">10</output>
                </div>

                <div class="submit-container">
                    <button type="submit" class="create-btn">Create Room</button>
                </div>
            </form>
        </div>
    </main>

    <script>
    function toggleView(isPrivate) {
        const participantsSection = document.getElementById('participants-section');
        if (isPrivate) {
            participantsSection.style.display = 'none';
        } else {
            participantsSection.style.display = 'block';
        }
    }

    window.onload = () => {
        const isPrivateInitial = document.querySelector('input[value="private"]').checked;
        toggleView(isPrivateInitial);
    };
    </script>
</body>
</html>

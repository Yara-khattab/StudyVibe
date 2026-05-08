<?php
include "db.php";
session_start();

// 1. التأكد من وجود البيانات الأساسية
if(!isset($_SESSION['user_id']) || !isset($_GET['code'])){
    header("Location: join.php");
    exit();
}

$room_code = $conn->real_escape_string($_GET['code']);
$user_id = $_SESSION['user_id'];

// 2. تحديث النشاط (عشان الأيقونات تظهر لايف)
$conn->query("UPDATE room_member SET last_activity = NOW() WHERE user_id = '$user_id'");

// 3. جلب بيانات الغرفة
$sql = "SELECT * FROM rooms WHERE room_code = '$room_code'";        
$res = $conn->query($sql);
$room = $res->fetch_assoc();

if(!$room){
    die("Room not found!");
}

// 4. التأكد من العضوية (لو مش موجود يدخله)
$check_me = $conn->query("SELECT * FROM room_member WHERE room_id = '{$room['id']}' AND user_id = '$user_id'");
if($check_me->num_rows == 0){
    $conn->query("INSERT INTO room_member (room_id, user_id, joined_at, last_activity) VALUES ('{$room['id']}', '$user_id', NOW(), NOW())");
}

$current_count = $conn->query("SELECT COUNT(*) as current FROM room_member WHERE room_id = '{$room['id']}'")->fetch_assoc()['current'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudyVibe - <?php echo htmlspecialchars($room['room_name']); ?></title>
<link rel="stylesheet" href="CSS/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="rk-body">
<div class="rk-container">
    <div class="rk-chat-box">
        <div class="rk-chat-header">
            <h2>Live Chat</h2>
            <span class="rk-status" id="active-status-text"><?php echo $current_count; ?> Active</span>
        </div>
        <div class="rk-line"></div>
        <div class="rk-chat-messages" id="chat-box"></div>
        <div class="rk-chat-input-container" style="position: relative; display: flex; align-items: center;">
            <input type="text" id="msg-input" placeholder="Type a message..." class="rk-input" style="width: 100%; padding-right: 45px;">
            <button id="send-btn" style="position: absolute; top:38px; right: 20px; background: none; border: none; color:rgb(218, 216, 215); cursor: pointer; font-size: 20px;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <div class="rk-side-card">
        <div class="rk-card-top">
            <h3>Welcome to <?php echo htmlspecialchars($room['room_name']); ?> room!</h3>
            <img src="images/room.png" class="rk-img" alt="Study Room">
            <div class="rk-members-label">Members <span class="rk-active-now">Active Now</span></div>
            <div class="rk-avatars" id="live-avatars">
                </div>
        </div>
        <div class="rk-card-bottom">
            <div class="rk-session-row">
                <button class="rk-join-btn">STUDYING...</button>
                <span class="rk-break" id="member-limit-text">Limit: <?php echo $current_count."/".$room['max_participants']; ?></span>
            </div>
            <div class="rk-progress-bg">
                <div class="rk-progress-fill" id="progress-bar" style="width: 0%;"></div>
            </div>
            <div class="rk-timer">
                <i class="fa-solid fa-clock"></i>
                <span id="real-timer">00:00:00</span>
            </div>
            <p class="rk-others" id="others-count-text"></p>
        </div>
    </div>
</div>
<div class="rk-bottom-bar">
    <div class="rk-icons-group">
        <i class="fa-solid fa-arrow-up"></i>
        <i class="fa-solid fa-video"></i>
        <i class="fa-solid fa-microphone"></i>
    </div>
    <a href="#" onclick="leaveRoom()" class="rk-leave-btn" style="text-decoration:none; text-align:center;">Leave Room</a>
</div>

<section class="break-body" style="display:none;">
    <div class="break-card">
        <div class="break-header">
            <h2><i class="fas fa-coffee" style="font-size: 25px;"></i> Take a Break!</h2>
        </div>
        <div class="break-content">
            <p class="instruction-text">Enjoy a quick game before you get back to studying!</p>
            <div class="game-container">
                <p>Memory Challenge</p>              
                <a href="game.php?duration=<?php echo $room['break_duration']; ?>&code=<?php echo $room_code; ?>" class="start-game-btn">Start Game</a>
            </div>
            <p class="timer-text" id="break-timer-popup">Time Left: 00:00</p>
        </div>
        <div class="break-footer">
            <a href="#" class="back-study-btn">Back to Study</a>
        </div>
    </div>
</section>

<script>
// --- إعدادات التايمر (منطق البرايفت روم) ---
const roomCode = "<?php echo $room_code; ?>";
const room_id = <?php echo $room['id']; ?>;
const totalDuration = <?php echo ($room['study_duration'] * 60); ?>;
const breakInterval = <?php echo ($room['break_every'] * 60); ?>;
const breakDuration = <?php echo ($room['break_duration'] * 60); ?>;

// جلب الوقت من المتصفح أو البدء من جديد
let rawTime = localStorage.getItem('studyTimeLeft_' + roomCode);
let timeLeft = (rawTime && !isNaN(rawTime)) ? parseInt(rawTime) : totalDuration;

let secondsSinceLastBreak = 0;
let isOnBreak = false;

// 1. العداد الأساسي
const countdown = setInterval(() => {
    if(isOnBreak) return; // تجميد الوقت تماماً في البريك

    if(timeLeft <= 0){
        clearInterval(countdown);
        finishSession();
        return;
    }

    timeLeft--;
    secondsSinceLastBreak++; 
    localStorage.setItem('studyTimeLeft_' + roomCode, timeLeft); // حفظ الوقت كل ثانية

    // تحديث الشكل
    updateTimerDisplay(timeLeft);
    
    // فحص وقت البريك
    if(secondsSinceLastBreak >= breakInterval){
        startBreakSession();
    }
}, 1000);

function updateTimerDisplay(seconds) {
    let hrs = Math.floor(seconds / 3600);
    let mins = Math.floor((seconds % 3600) / 60);
    let secs = seconds % 60;
    document.getElementById('real-timer').textContent = 
        `${hrs.toString().padStart(2,'0')}:${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
    
    let percentage = ((totalDuration - seconds) / totalDuration) * 100;
    document.getElementById('progress-bar').style.width = percentage + "%";
}

// 2. منطق البريك
function startBreakSession(){
    isOnBreak = true;
    document.querySelector('.break-body').style.display = 'flex';
    let bLeft = breakDuration;
    const bCountdown = setInterval(() => {
        if(!isOnBreak) { clearInterval(bCountdown); return; }
        bLeft--;
        let bMins = Math.floor(bLeft / 60);
        let bSecs = bLeft % 60;
        document.getElementById('break-timer-popup').textContent = 
            `Time Left: ${bMins.toString().padStart(2,'0')}:${bSecs.toString().padStart(2,'0')}`;
        if(bLeft <= 0) { clearInterval(bCountdown); closeBreak(); }
    }, 1000);
}

function closeBreak(){
    isOnBreak = false;
    secondsSinceLastBreak = 0;
    document.querySelector('.break-body').style.display = 'none';
}

document.querySelector('.back-study-btn').addEventListener('click', (e) => {
    e.preventDefault();
    closeBreak();
});

// 3. إنهاء السيشن وحفظ الوقت
function finishSession() {
    const minutesSpent = Math.floor((totalDuration - timeLeft) / 60);
    localStorage.removeItem('studyTimeLeft_' + roomCode);
    
    fetch('save_session.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `room_id=${room_id}&duration=${minutesSpent}`
    }).then(() => {
        window.location.href = "join.php?msg=finished";
    });
}

function leaveRoom() {
    localStorage.removeItem('studyTimeLeft_' + roomCode);
    window.location.href = "join.php";
}

// 4. الأيقونات والدردشة (لايف)
function refreshParticipants() {
    fetch(`fetch_participants.php?room_id=${room_id}`)
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('live-avatars');
        document.getElementById('active-status-text').textContent = data.length + " Active";
        document.getElementById('member-limit-text').textContent = `Limit: ${data.length}/<?php echo $room['max_participants']; ?>`;
        document.getElementById('others-count-text').textContent = (data.length > 1) ? (data.length - 1) + "+ others studying" : "You are the first here!";
        
        let html = '';
        data.forEach(user => {
            html += `<div class="rk-avatar" title="${user.user_name}">
                        <i class="fa-solid fa-user"></i>
                        <span class="rk-plus">${user.user_name[0].toUpperCase()}</span>
                     </div>`;
        });
        container.innerHTML = html;
    });
}

function loadMessages(){
    fetch(`fetch_message.php?room_id=${room_id}`)
    .then(res => res.text())
    .then(data => {
        const chatBox = document.getElementById('chat-box');
        chatBox.innerHTML = data;
    });
}

// التحديث الدوري
setInterval(refreshParticipants, 5000);
setInterval(loadMessages, 2000);
setInterval(() => fetch(`update_activity.php?room_id=${room_id}`), 10000);

// تشغيل أولي
refreshParticipants();
loadMessages();

// إرسال الرسائل
document.getElementById('send-btn').addEventListener('click', () => {
    const msgInput = document.getElementById('msg-input');
    const msg = msgInput.value.trim();
    if(msg !== ""){
        fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `room_id=${room_id}&message=${encodeURIComponent(msg)}`
        }).then(() => { msgInput.value = ""; loadMessages(); });
    }
});
</script>
</body>
</html>
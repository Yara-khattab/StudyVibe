<?php
include "db.php";
session_start();

$room_code = $_GET['code'] ?? $_SESSION['current_room_code'] ?? null;

if (!$room_code || !isset($_SESSION['user_email'])) {
    header("Location: join.php");
    exit();
}

$email = $_SESSION['user_email'];

$sql = "SELECT * FROM rooms WHERE room_code = ? AND privacy = 'private' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $room_code);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) { 
    header("Location: join.php?error=room_not_found");
    exit();
}


$user_query = $conn->prepare("SELECT id, user_name FROM users WHERE email = ?");
$user_query->bind_param("s", $email);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();
$current_username = $user_data['user_name'] ?? 'User';

$_SESSION['current_room_code'] = $room_code;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Room - <?php echo htmlspecialchars($room['room_name']); ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
    <link rel="stylesheet" href="CSS/private_room.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

.focus-item {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05); /* شفافية بسيطة */
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: 0.3s;
}

.focus-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.task-text {
    flex-grow: 1;
    color: white;
    font-size: 14px;
    transition: 0.3s;
}

.remove-task {
    color: #ff4d4d;
    cursor: pointer;
    font-size: 14px;
    opacity: 0;
    transition: 0.3s;
}

.remove-task:hover {
    opacity: 1;
    transform: scale(1.1);
}
    </style>
</head>
<body>
    <section class="private-room">
        <div class="study-room">
            <div class="room-content">
                <div class="left-panel">
                    <div class="hello-join">
                        <div>
                            <h2>Hello <span id="username"><?php echo htmlspecialchars($current_username); ?>!</span></h2>
                        </div>
                        <div class="avatar"><i class="fa-solid fa-circle-user"></i></div>
                    </div>
                    <p class="underline"></p>
                    <div class="focus-box">
                        <div class="focus-header">
                            <h3 style="margin-bottom:5px;">Today’s Focus</h3>
                            <button class="add-task-btn" id="add-task-btn">+</button>
                        </div>
                        <div id="tasks-list"></div>
                    </div>
                </div>

                <div class="right-card">
<h2 style="text-align: center; margin-bottom: 10px;">
    Welcome to <span><?php echo htmlspecialchars($room['room_name']); ?></span> Room
</h2>
                    <div class="study-preview"></div>
                    <div class="partner" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="partner-avatar" style="background:#eab308; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h4 id="partner-name" style="margin: 0;"><?php echo htmlspecialchars($current_username); ?></h4>
                                <p style="margin: 0; font-size: 14px; opacity: 0.8;">Keep Going !!!</p>
                            </div>
                        </div>
                        <button class="leave-btn" onclick="clearStorageAndLeave()" style="width: auto; padding: 8px 20px; margin: 0;">Leave Room</button>
                    </div>

                    <div class="meta" style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="text-study">STUDYING..</span>
                        <span>Break every: <span id="break-time-next"><?php echo htmlspecialchars($room['break_every'] ?? '10'); ?></span> mins</span>
                    </div>
                    <div class="progress">
                        <div class="progress-fill" id="progress-bar"></div>
                    </div>
                    <div class="timer" id="session-timer" style="text-align: left;">00:00:00</div>
                </div>
            </div>
        </div>
    </section>

    <section class="break-body" style="display: none;">
    <div class="break-card">
        <div class="break-header">
            <h2><i class="fas fa-coffee"></i> Take a Break!</h2>
        </div>
        
        <div class="break-content">
            <p class="instruction-text">Enjoy a quick game before you get back to studying!</p>
            <div class="game-container">
                <a href="game_private.php?duration=<?php echo $room['break_duration']; ?>&code=<?php echo $room_code; ?>&source=private_room.php" class="start-game-btn">Start Game</a>
            </div>
            <p class="timer-text" id="break-timer-popup">Time Left: 00:00</p>
        </div>

        <div class="break-footer">
            <button class="back-study-btn" onclick="goToRoom()" style="cursor: pointer;">Back to Study</button>
        </div>
    </div>
</section>

<script>
const roomCode = "<?php echo $room_code; ?>";
const breakEveryMins = <?php echo (int)($room['break_every'] ?? 10); ?>;
const breakDurationMins = <?php echo (int)($room['break_duration'] ?? 5); ?>;
const totalStudyMins = <?php echo (int)($room['study_duration'] ?? 30); ?>;
const breakSection = document.querySelector('.break-body');
const breakTimerDisplay = document.getElementById('break-timer-popup');
const timerElement = document.getElementById('session-timer');
let rawTime = localStorage.getItem('studyTimeLeft_' + roomCode);
let timeLeft = (rawTime && !isNaN(rawTime)) ? parseInt(rawTime) : totalStudyMins * 60;
let breakCounter = 0; 
const breakThreshold = breakEveryMins * 60; 
function goToRoom() {

    if (roomCode && roomCode !== "null" && roomCode !== "") {
        window.location.href = `private_room.php?code=${roomCode}`;
    } else {
        window.location.href = `join.php`;
    }
}
function mainLogic() {
    if (document.body.classList.contains('on-break')) return; // لو في بريك وقف كل حاجة
    if (timeLeft <= 0) {
        saveSessionToDatabase();
        return;
    }
    timeLeft--;
    localStorage.setItem('studyTimeLeft_' + roomCode, timeLeft);    
    let hrs = Math.floor(timeLeft / 3600);
    let mins = Math.floor((timeLeft % 3600) / 60);
    let secs = timeLeft % 60;
    timerElement.textContent = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    breakCounter++;
    if (breakCounter >= breakThreshold) {
        showBreakNow();
    }
}

function showBreakNow() {
    document.body.classList.add('on-break');
    breakCounter = 0; 
    breakSection.style.setProperty('display', 'flex', 'important');
    Object.assign(breakSection.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '100vw',
        height: '100vh',
        zIndex: '2147483647', 
        backgroundColor: 'rgba(10, 15, 35, 0.98)'  
    });
    let bLeft = breakDurationMins * 60;
    const bInterval = setInterval(() => {
        bLeft--;
        let m = Math.floor(bLeft / 60);
        let s = bLeft % 60;
        if(breakTimerDisplay) {
            breakTimerDisplay.textContent = `Time Left: ${m}:${s < 10 ? '0'+s : s}`;
        }
        if (bLeft <= 0 || !document.body.classList.contains('on-break')) {
            clearInterval(bInterval);
            closeBreak();
        }
    }, 1000);
}
function closeBreak() {
    document.body.classList.remove('on-break');
      breakSection.style.setProperty('display', 'none', 'important');
}
setInterval(mainLogic, 1000);
function saveSessionToDatabase() {
    localStorage.removeItem('studyTimeLeft_' + roomCode);
    location.href = 'join.php?status=finished';
}

function clearStorageAndLeave() {
    localStorage.removeItem('studyTimeLeft_' + roomCode);
    location.href='join.php';
}
    const tasksList = document.getElementById('tasks-list');
const addTaskBtn = document.getElementById('add-task-btn');

    document.addEventListener('DOMContentLoaded', loadTasks);

    function loadTasks() {
        const savedTasks = JSON.parse(localStorage.getItem('userTasks')) || [];
        tasksList.innerHTML = '';
        savedTasks.forEach(task => createTaskElement(task.text, task.completed));
    }
    addTaskBtn.onclick = function() {
        const taskName = prompt("What is your focus task for today?");
        
        if (taskName !== null && taskName.trim() !== "") {
            createTaskElement(taskName.trim());
            saveTasks();
        }
    };

function createTaskElement(text, completed = false) {
    const label = document.createElement('label');
    label.className = 'focus-item';
    
    
    label.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
            <input type="checkbox" ${completed ? 'checked' : ''} onchange="toggleTask(this)" style="cursor:pointer;">
            <span class="checkmark"></span>
            <span class="task-text">${text}</span>
            <i class="fa-solid fa-trash-can remove-task" onclick="removeTask(this)"></i>
        </div>
    `;

    tasksList.appendChild(label);
}


function toggleTask(checkbox) {
    const taskText = checkbox.parentElement.querySelector('.task-text');
    saveTasks(); 
}


function removeTask(element) {
    element.closest('.focus-item').remove();
    saveTasks();
}

    
    function saveTasks() {
        const tasks = [];
        document.querySelectorAll('.focus-item').forEach(item => {
            const text = item.querySelector('.task-text').innerText;
            const completed = item.querySelector('input[type="checkbox"]').checked;
            tasks.push({ text, completed });
        });
        localStorage.setItem('userTasks', JSON.stringify(tasks));
    }
</script>  
</body>
</html>

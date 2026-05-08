<?php
include "db.php";
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$user_res = $conn->query("SELECT user_name FROM users WHERE email = '$email'");
$user_data = $user_res->fetch_assoc();
$display_name = $user_data['user_name'] ?? 'Student';
 
// جلب الغرف العامة التي لم ينتهِ وقتها بعد
$sql = "SELECT r.*, 
        (SELECT COUNT(DISTINCT user_id) FROM room_member WHERE room_id = r.id) as current_count,
        TIMESTAMPDIFF(SECOND, r.created_at, NOW()) as elapsed_seconds
        FROM rooms r 
        WHERE r.privacy = 'public' 
        AND (TIMESTAMPDIFF(SECOND, r.created_at, NOW()) < (r.study_duration * 60))
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);

$active_topics = [];
$room_cards_data = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $room_cards_data[] = $row;
        if (!empty($row['topic'])) { 
            $active_topics[] = $row['topic']; 
        }
    }
}
// نرسل المواضيع النشطة فقط للـ JS
$active_topics_json = json_encode(array_values(array_unique($active_topics)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Room</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rooms-nav-btn {
            display: inline-flex; align-items: center; background: rgba(255, 255, 255, 0.1);
            color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px;
            font-size: 14px; gap:4px; border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease-in-out; position: absolute; left: 105px; top: 15px;
        }
        .rooms-nav-btn:hover { background: #6a00ff; border-color: #6a00ff; transform: translateY(-2px); }
        .focus-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .add-task-btn { background: none; border: 1px solid #ffffff55; color: white; cursor: pointer; border-radius: 5px; padding: 2px 8px; transition: 0.3s; }
        .add-task-btn:hover { background: #3b82f6; border-color: #3b82f6; }
        .focus-item { display: flex; align-items: center; margin-bottom: 10px; background: rgba(255, 255, 255, 0.05); padding: 8px; border-radius: 8px; }
        .remove-task { color: #ff4d4d; cursor: pointer; font-size: 12px; margin-left: auto; opacity: 0; transition: 0.3s; }
        .focus-item:hover .remove-task { opacity: 1; }
        .topic-tag { background: #eab308; color: #000; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 8px; font-weight: bold; }
    </style>
</head>
<body>
    <section class="join-room">
        <div class="join-container">
            <div class="left-panel">
                <div class="hello-join">
                    <div>
                        <h2>Hello <span id="username"><?php echo htmlspecialchars($display_name); ?>!</span></h2>
                    </div>
                    <div class="avatar"><i class="fa-solid fa-circle-user"></i></div>
                </div>
                <p class="underline"></p>
                
                <div class="focus-box">
                    <div class="focus-header">
                        <h3>Today’s Focus</h3>
                        <button class="add-task-btn" id="add-task">+</button>
                    </div>
                    <div id="tasks-list"></div>
                </div>
            </div>

            <div class="right-panel">
                <h2 class="title">Featured Open Room</h2>
                <div class="room-grid">
                    <?php if (!empty($room_cards_data)): ?>
                        <?php foreach($room_cards_data as $row): 
                            $rem_mins = ceil((($row['study_duration'] * 60) - $row['elapsed_seconds']) / 60);
                            $spots_left = max(0, $row['max_participants'] - $row['current_count']);
                        ?>
                        <div class="room-card">
                            <div style="display: flex; gap:7px;">
                                <h4><?php echo htmlspecialchars($row['room_name']); ?></h4>
                                <div style="display:flex; font-size: 12px; align-items: center; opacity: 0.8;"> 
                                    <i class="fa-solid fa-book" style="margin-right:3px;"></i>
                                    <?php echo htmlspecialchars($row['topic'] ?: 'General'); ?>
                                </div>
                            </div>
                            <div style="margin: 5px 0;"><i class="fa-solid fa-users"></i><span style="font-size: 13px; margin-left: 5px;"><?php echo $spots_left; ?> spots left</span></div>
                            <div style="margin-bottom: 15px;"><i class="fa-solid fa-clock"></i><span style="font-size: 13px; margin-left: 5px;"><?php echo $rem_mins; ?> mins left</span></div>
                            <a href="room.php?code=<?php echo $row['room_code']; ?>"><button style="cursor: pointer;" <?php echo ($spots_left <= 0) ? 'disabled style="background:#555;"' : ''; ?>>Join Now</button></a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: white; text-align: center; grid-column: 1/-1;">No public rooms available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="rooms.php" class="rooms-nav-btn"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
    </section>

<script>
    const tasksList = document.getElementById('tasks-list');
    const addTaskBtn = document.getElementById('add-task');
    // المواضيع القادمة من السيرفر حالياً
    const serverTopics = <?php echo $active_topics_json; ?>; 

    document.addEventListener('DOMContentLoaded', updateFocusUI);

    function updateFocusUI() {
        tasksList.innerHTML = '';
        
        // 1. عرض مواضيع الغرف النشطة (لا تُحفظ في localStorage، تختفي باختفاء الغرفة)
        serverTopics.forEach(topic => {
            renderItem(topic, false, true); 
        });

        // 2. عرض المهام الشخصية المخزنة في المتصفح
        const personalTasks = JSON.parse(localStorage.getItem('public_page_tasks')) || [];
        personalTasks.forEach(task => renderItem(task.text, task.completed, false));
    }

    addTaskBtn.onclick = function() {
        const val = prompt("Enter your personal focus for today:");
        if (val && val.trim() !== "") {
            renderItem(val.trim(), false, false);
            savePersonalTasks();
        }
    };

    function renderItem(text, completed, isServerTopic) {
        const div = document.createElement('div');
        div.className = 'focus-item';
        if(isServerTopic) div.dataset.type = "topic";

        div.innerHTML = `
            <input type="checkbox" ${completed ? 'checked' : ''} onchange="savePersonalTasks()">
            <span class="task-text" style="margin-left:10px; color:white; font-size:14px; flex-grow:1;">
                ${text} ${isServerTopic ? '<span class="topic-tag">Live Room</span>' : ''}
            </span>
            ${!isServerTopic ? '<i class="fa-solid fa-trash-can remove-task" onclick="deleteTask(this)"></i>' : ''}
        `;
        tasksList.appendChild(div);
    }

    function deleteTask(el) {
        el.closest('.focus-item').remove();
        savePersonalTasks();
    }

    function savePersonalTasks() {
        const toSave = [];
        document.querySelectorAll('.focus-item').forEach(item => {
            // نحفظ فقط المهام التي ليست "Server Topic"
            if (item.dataset.type !== "topic") {
                toSave.push({
                    text: item.querySelector('.task-text').innerText.replace('Live Room', '').trim(),
                    completed: item.querySelector('input[type="checkbox"]').checked
                });
            }
        });
        localStorage.setItem('public_page_tasks', JSON.stringify(toSave));
    }
</script>
</body>
</html>
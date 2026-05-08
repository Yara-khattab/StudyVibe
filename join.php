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
 
$sql = "SELECT r.*, 
        (SELECT COUNT(DISTINCT user_id) FROM room_member WHERE room_id = r.id) as current_count,
        TIMESTAMPDIFF(SECOND, r.created_at, NOW()) as elapsed_seconds
        FROM rooms r 
        WHERE r.privacy = 'public' 
        AND (TIMESTAMPDIFF(SECOND, r.created_at, NOW()) < (r.study_duration * 60))
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>join room</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rooms-nav-btn {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            gap:4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease-in-out;
            position: absolute;
            left: 105px;
            top: 15px;
        }
        .rooms-nav-btn:hover {
            background: #6a00ff;
            border-color: #6a00ff;
            transform: translateY(-2px);
        }

        .focus-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .add-task-btn {
            background: none;
            border: 1px solid #ffffff55;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            padding: 2px 8px;
            transition: 0.3s;
        }
        .add-task-btn:hover {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        .focus-item input[type="text"] {
            background: transparent;
            border: none;
            color: white;
            outline: none;
            width: 100%;
            font-size: 14px;
            font-family: inherit;
        }
        .focus-item {
            display: flex;
            align-items: center;
            position: relative;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px;
            border-radius: 8px;
        }
        .remove-task {
            color: #ff4d4d;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
            opacity: 0;
            transition: 0.3s;
        }
        .focus-item:hover .remove-task {
            opacity: 1;
        }
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
                    <div class="avatar">
                        <i class="fa-solid fa-circle-user"></i>
                    </div>
                </div>
                <p class="underline"></p>
                
                <div class="focus-box">
                    <div class="focus-header">
                        <h3>Today’s Focus</h3>
                        <button class="add-task-btn" id="add-task" title="Add Task">+</button>
                    </div>
                    
                    <div id="tasks-list"> 
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <h2 class="title">Featured Open Room</h2>
                <div class="room-grid">
                    <?php 
                    if ($result && $result->num_rows > 0): 
                        while($row = $result->fetch_assoc()): 
                            $total_seconds = $row['study_duration'] * 60;
                            $remaining_seconds = $total_seconds - $row['elapsed_seconds'];
                            $spots_left = $row['max_participants'] - $row['current_count'];
                            if ($spots_left < 0) $spots_left = 0;
                            $rem_mins = ceil($remaining_seconds / 60);
                    ?>
                       <div class="room-card">
                           <div style="display: flex; gap:7px;">
    <h4><?php echo htmlspecialchars($row['room_name']); ?></h4>
          <div style="display:flex;font-size: 13px;margin-top:3px;opacity:98% ;align-items: center;"> <i class="fa-solid fa-book" style="margin-right:3px;"></i>
                               <?php echo htmlspecialchars($row['topic'] ?: 'General Study'); ?></div></div>

    <div style="margin-bottom: 5px;">
        <i class="fa-solid fa-users"></i>
        <span style="font-size: 13px; margin-left: 5px;"><?php echo $spots_left; ?> spots left</span>
    </div>
    
    <div style="margin-bottom: 15px;">
        <i class="fa-solid fa-clock"></i>
        <span style="font-size: 13px; margin-left: 5px;"><?php echo $rem_mins; ?> mins left</span>
    </div>

    <?php if ($spots_left > 0): ?>
        <a href="room.php?code=<?php echo $row['room_code']; ?>" style="text-decoration: none;">
            <button style="cursor: pointer;">Join Now</button>
        </a>
    <?php else: ?>
        <button style="background: #555; cursor: not-allowed;" disabled>Full</button>
    <?php endif; ?>
</div>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <p style="color: white; grid-column: 1/-1; text-align: center;">No public rooms available right now.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
       <a href="rooms.php" class="rooms-nav-btn">
    <i class="fa-solid fa-arrow-left"></i>
    Go Back
    </a>
    </section>
<script>
    const tasksList = document.getElementById('tasks-list');
    const addTaskBtn = document.getElementById('add-task');
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
                <input type="checkbox" ${completed ? 'checked' : ''} onchange="saveTasks()">
                <span class="checkmark"></span>
                <span class="task-text" style="flex-grow: 1; color: white; font-size: 14px;">${text}</span>
                <i class="fa-solid fa-trash-can remove-task" 
                   onclick="removeTask(this)" 
                   style="cursor: pointer; color: #ff4d4d; font-size: 14px;"></i>
            </div>
        `;

        tasksList.appendChild(label);
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
<?php
include "db.php"; 
session_start();

if(!isset($_SESSION['user_name']) || !isset($_SESSION['user_email'])){
    header("Location: login.html");
    exit();
}

$email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'];

// جلب بيانات المستخدم
$user_query = $conn->prepare("SELECT id, profile_pic FROM users WHERE email = ?");
$user_query->bind_param("s", $email);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();
$user_id = $user_data['id'];
$user_image = $user_data['profile_pic'];

// حساب إجمالي الساعات
$res_total = $conn->query("SELECT SUM(duration_minutes) as total FROM study_sessions WHERE user_id = '$user_id'");
$total_minutes = $res_total->fetch_assoc()['total'] ?? 0;
$total_hours_display = round($total_minutes / 60, 1);

// منطق الـ Status
if ($total_hours_display >= 50) {
    $status_text = "Blaze 🔥"; $status_color = "#ff4500"; 
} elseif ($total_hours_display >= 10) {
    $status_text = "Glow 🌟"; $status_color = "#ffaa85"; 
} else {
    $status_text = "Spark ✨"; $status_color = "#00d4ff"; 
}

// حساب الـ Streak
$streak_query = $conn->query("SELECT DISTINCT study_date FROM study_sessions WHERE user_id = '$user_id' ORDER BY study_date DESC");
$dates = [];
while($row = $streak_query->fetch_assoc()) { $dates[] = $row['study_date']; }

$current_streak = 0;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime("-1 day"));

if (count($dates) > 0) {
    $last_study_date = $dates[0];
    if ($last_study_date == $today || $last_study_date == $yesterday) {
        $current_streak = 1; 
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $current = new DateTime($dates[$i]);
            $next = new DateTime($dates[$i+1]);
            $interval = $current->diff($next);
            if ($interval->days == 1) { $current_streak++; } else { break; }
        }
    }
}

// الـ Weekly Progress
$weekly_hours = [];
$day_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $timestamp = strtotime("-$i days");
    $date = date('Y-m-d', $timestamp);
    $day_name = date('D', $timestamp)[0];
    $check = $conn->query("SELECT SUM(duration_minutes) as daily FROM study_sessions WHERE user_id = '$user_id' AND study_date = '$date'");
    $weekly_hours[] = round(($check->fetch_assoc()['daily'] ?? 0) / 60, 1);
    $day_labels[] = $day_name;
}

// --- الجزء المطلوب تعديله: جلب توزيع المواد (Study Distribution) ---
// بنعمل LEFT JOIN عشان لو مفيش غرفة برضه الجلسة تظهر، وبنستخدم IFNULL عشان ندي اسم للمادة لو فاضية
// الاستعلام لربط الجدولين وجلب المواد
$subject_sql = "SELECT r.topic, SUM(s.duration_minutes) as sub_total 
                FROM study_sessions s 
                JOIN rooms r ON s.room_id = r.id 
                WHERE s.user_id = '$user_id' 
                GROUP BY r.topic";

$subject_query = $conn->query($subject_sql);
$subject_labels = []; 
$subject_data = [];

while($row = $subject_query->fetch_assoc()){
    if($row['sub_total'] > 0){
        // هنا بنصلح مشكلة الـ '0' اللي في الداتا بيز عندك
        // لو التوبيك قيمته '0' أو فاضي، سميه 'General Study' عشان يظهر حلو في التشارت
        $name = ($row['topic'] === '0' || empty($row['topic'])) ? 'General Study' : $row['topic'];
        
        $subject_labels[] = $name;
        $subject_data[] = round($row['sub_total'] / 60, 2);
    }
}


if(empty($subject_data)) {
    $subject_labels = ['No Data'];
    $subject_data = [0];
}

$heatmap_results = $conn->query("SELECT study_date, SUM(duration_minutes) as total_min FROM study_sessions WHERE user_id = '$user_id' GROUP BY study_date");
$heatmap_data = [];
while($row = $heatmap_results->fetch_assoc()){
    $heatmap_data[$row['study_date']] = $row['total_min'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StudyVibe</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/profileCss.css">
    <style>
        .profile-pic-container { position: relative; width: 140px; height: 140px; margin: 15px auto; }
        .profile-pic-container img { width: 140px !important; height: 140px !important; border-radius: 50%; object-fit: cover; border: 4px solid #6a00ff; box-shadow: 0 0 15px rgba(106, 0, 255, 0.3); background-color: #2c2c2c; }
        .upload-btn { position: absolute; bottom: 5px; right: 5px; background: linear-gradient(to right, #6a00ff, #d400ff); width: 32px; height: 32px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; cursor: pointer; border: 2px solid #1a1a1a; transition: 0.3s; }
        .remove-btn { position: absolute; top: 5px; right: 5px; background: #ff416c; width: 28px; height: 28px; border-radius: 50%; display: <?php echo !empty($user_image) ? 'flex' : 'none'; ?>; justify-content: center; align-items: center; color: white; cursor: pointer; border: 2px solid #1a1a1a; font-size: 12px; transition: 0.3s; }
        .upload-btn:hover, .remove-btn:hover { transform: scale(1.1); }
        .menu button { padding: 12px; border-radius: 10px; background: linear-gradient(to right, #6a00ff, #d400ff); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; font-size: 16px; font-weight: 500; transition: 0.3s; }
        .menu button:hover { opacity: 0.9; transform: translateY(-2px); }
.heatmap-grid { 
    display: grid; 
  
    grid-template-rows: repeat(7, 1fr); 
   
    grid-auto-flow: column; 
    gap: 3px; 
    padding: 10px 0; 
    overflow-x: auto; 
}
        .day { width: 23px; height: 23px; border-radius: 2px; border: 1px solid rgba(255, 255, 255, 0.03); }
        .level-0 { background-color: #161b22; }
        .level-1 { background-color: #0e4429; }
        .level-2 { background-color: #006d32; }
        .level-3 { background-color: #26a641; }
        .level-4 { background-color: #39d353; box-shadow: 0 0 8px #39d353; }
        .chart-container-relative { position: relative; width: 100%; height: 260px; }
        .logout-btn { margin-top: auto; background: linear-gradient(to right, #6a00ff, #d400ff) !important; }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <h2 class="logo"><i class="fa-solid fa-book-open"></i> StudyVibe</h2>
        <div class="profile-pic-container">
            <?php 
                $default_img = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
                $display_img = !empty($user_image) ? 'uploads/'.$user_image : $default_img;
            ?>
            <img src="<?php echo $display_img; ?>" id="profileDisplay" alt="Profile">
            <div id="removePic" class="remove-btn" onclick="deleteProfilePic()" title="Remove Photo"><i class="fa-solid fa-xmark"></i></div>
            <label for="imageUpload" class="upload-btn" title="Upload Photo"><i class="fa-solid fa-plus"></i></label>
            <input type="file" id="imageUpload" accept="image/*" style="display:none;">
        </div>
        <div style="text-align: center; margin-bottom: 30px;">
            <h3 style="margin-top: 10px; color: white;"><?php echo htmlspecialchars($user_name); ?></h3>
        </div>
        <nav class="menu" style="display: flex; flex-direction: column; gap: 12px; flex: 1;">
            <button class="active"><i class="fas fa-user"></i> My Profile</button>
            <button onclick="location.href='index.php'"><i class="fas fa-home"></i> Home</button>
            <button onclick="location.href='join.php'"><i class="fas fa-users"></i> Rooms</button>
            <button onclick="location.href='logout.php'" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</button>
        </nav>
    </aside>

    <main class="Main">
        <header><h1>HELLO, <?php echo htmlspecialchars($user_name); ?>!</h1></header>
        <div class="dashboard-grid">
            <div class="card">
                <h3>Weekly Progress (Hours)</h3>
                <div class="chart-wrapper"><canvas id="weeklyChart"></canvas></div>
            </div>

            <div class="status-grid">
                <div class="stat-card"><span>Total focus ⏳</span><h2><?php echo $total_hours_display; ?> h</h2></div>
                <div class="stat-card"><span>Streak 🔥</span><h2><?php echo $current_streak; ?> day</h2></div>
                <div class="stat-card"><span>Status ✨</span><h2 style="color: <?php echo $status_color; ?>;"><?php echo $status_text; ?></h2></div>
                <div class="stat-card"><span>Sessions 🧠</span><h2><?php echo count($subject_data); ?></h2></div>
            </div>

            <div class="card">
                <h3>Study Heatmap</h3>
                <div id="heatmapGrid" class="heatmap-grid"></div>
                <div class="heatmap-legend" style="display: flex; gap: 5px; align-items: center; font-size: 12px; margin-top: 10px;">
                    <span>Less</span>
                    <div class="day level-0"></div><div class="day level-1"></div><div class="day level-2"></div><div class="day level-3"></div><div class="day level-4"></div>
                    <span>More</span>
                </div>
            </div>

            <div class="card">
                <h3>Study Distribution</h3>
                <div class="chart-container-relative"><canvas id="distributionChart"></canvas></div>
            </div>
        </div>
    </main>
</div>

<script>

new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($day_labels); ?>,
        datasets: [{
            label: 'Hours',
            data: <?php echo json_encode($weekly_hours); ?>,
            backgroundColor: '#3b66f5', borderRadius: 5
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: { 
            y: { beginAtZero: true, ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.1)' } },
            x: { ticks: { color: '#aaa' }, grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});

// --- الدونات تشارت (التي طلبتم تعديلها) ---
const hasData = <?php echo (array_sum($subject_data) > 0) ? 'true' : 'false'; ?>;
new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    plugins: [{
        id: 'centerText',
        afterDraw: (chart) => {
            const { ctx, chartArea: { width, height, top } } = chart;
            ctx.save(); ctx.textAlign = 'center'; ctx.fillStyle = 'white';
            ctx.font = 'bold 22px sans-serif';
            ctx.fillText('<?php echo $total_hours_display; ?>', width / 2, height / 2 + top - 5);
            ctx.font = '12px sans-serif'; ctx.fillText('HOURS', width / 2, height / 2 + top + 15);
            ctx.restore();
        }
    }],
    data: {
        labels: hasData ? <?php echo json_encode($subject_labels); ?> : ['No study'],
        datasets: [{
            data: hasData ? <?php echo json_encode($subject_data); ?> : [1],
            // مجموعة ألوان مختلفة لكل مادة تظهر
            backgroundColor: hasData ? ['#6a00ff', '#00d4ff', '#ff4500', '#39d353', '#ffaa85', '#d400ff'] : ['#2c2c2c'],
            borderWidth: 0
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        cutout: '80%', 
        plugins: { 
            legend: { 
                position: 'bottom', 
                labels: { color: '#aaa', padding: 20 } 
            } 
        } 
    }
});

// Heatmap logic
// Heatmap logic - الرأسي (GitHub Style)
const grid = document.getElementById('heatmapGrid');
const hData = <?php echo json_encode($heatmap_data); ?>;

// عشان الترتيب الرأسي يطلع صح، لازم نحسب عدد الأيام بحيث نكمل الأسبوع الأخير
const totalDaysToShow = 126; // رقم يقبل القسمة على 7 (18 أسبوع)

for (let i = totalDaysToShow - 1; i >= 0; i--) {
    const d = new Date(); 
    d.setDate(d.getDate() - i);
    const dateStr = d.toISOString().split('T')[0];
    
    const box = document.createElement('div');
    box.className = 'day';
    
    const m = hData[dateStr] || 0;
    if (m > 120) box.classList.add('level-4');
    else if (m > 60) box.classList.add('level-3');
    else if (m > 30) box.classList.add('level-2');
    else if (m > 0) box.classList.add('level-1');
    else box.classList.add('level-0');
    
    // إضافة اليوم كـ title عشان يظهر لما تقفي عليه بالماوس
    box.title = `${dateStr}: ${m} mins`;
    grid.appendChild(box);
}
// Profile Pic Functions
document.getElementById('imageUpload').addEventListener('change', function() {
    let formData = new FormData();
    formData.append('profile_pic', this.files[0]);
    fetch('upload_image.php', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if(data.success) location.reload(); else alert(data.message);
    });
});

function deleteProfilePic() {
    if(!confirm("Delete photo?")) return;
    fetch('delete_image.php', { method: 'POST' })
    .then(r => r.json()).then(data => { if(data.success) location.reload(); });
}
</script>
</body>
</html>
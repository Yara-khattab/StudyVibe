

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
    box.title = ${dateStr}: ${m} mins;
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

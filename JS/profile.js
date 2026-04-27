const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }
};


new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
        datasets: [{
            data: [11, 10, 18, 12, 11, 13, 8],
            backgroundColor: '#3b66f5',
            borderRadius: 5
        }]
    },
    options: {
        ...chartOptions,
        scales: {
            y: {
                grid: { color: '#333', borderDash: [5, 5] },
                ticks: { color: '#888' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#888' }
            }
        }
    }
});


const centerText = {
    id: 'centerText',
    afterDraw: (chart) => {
        const { ctx, chartArea: { width, height, top } } = chart;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.fillStyle = 'white';

        ctx.font = 'bold 20px sans-serif';
        ctx.fillText('10', width / 2, height / 2 + top - 5);

        ctx.font = '12px sans-serif';
        ctx.fillText('H', width / 2, height / 2 + top + 15);

        ctx.restore();
    }
};

new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    plugins: [centerText],
    data: {
        labels: ['Web', 'OS', 'Prog', 'Media'],
        datasets: [{
            data: [35, 25, 25, 15],
            backgroundColor: ['#8b5cf6', '#f472b6', '#0ea5e9', '#fbbf24'],
            borderWidth: 0
        }]
    },
    options: {
        ...chartOptions,
        cutout: '80%',
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    color: '#fff',
                    usePointStyle: true,
                    font: { size: 10 }
                }
            }
        }
    }
});


const heatmap = document.getElementById('heatmap');

for (let i = 0; i < 90; i++) {
    const day = document.createElement('div');
    day.className = 'day';

    const r = Math.random();

    if (r > 0.9) day.classList.add('level-4');
    else if (r > 0.7) day.classList.add('level-2');
    else if (r > 0.5) day.classList.add('level-1');

    heatmap.appendChild(day);
}
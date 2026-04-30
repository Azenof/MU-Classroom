<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

// Fetch recent data for summary
$materials_count = $db->prepare("SELECT COUNT(*) FROM materials WHERE user_id = ?");
$materials_count->execute([$_SESSION['user_id']]);
$materials_count = $materials_count->fetchColumn();

$notes_count = $db->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
$notes_count->execute([$_SESSION['user_id']]);
$notes_count = $notes_count->fetchColumn();

$questions_count = $db->prepare("SELECT COUNT(*) FROM past_questions WHERE user_id = ?");
$questions_count->execute([$_SESSION['user_id']]);
$questions_count = $questions_count->fetchColumn();

// Calculate CGPA for dashboard display
$stmt = $db->prepare("SELECT SUM(grade * credits) as total_points, SUM(credits) as total_credits FROM course_results WHERE user_id = ? AND grade > 0");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
$cgpa = ($stats['total_credits'] > 0) ? ($stats['total_points'] / $stats['total_credits']) : 0;
$completed_credits = $stats['total_credits'] ?: 0;

// Fetch semester-wise data for trend chart
$stmt = $db->prepare("SELECT semester, SUM(grade * credits) as sem_points, SUM(credits) as sem_credits 
                      FROM course_results 
                      WHERE user_id = ? AND grade > 0 
                      GROUP BY semester 
                      ORDER BY semester ASC");
$stmt->execute([$_SESSION['user_id']]);
$semester_data = $stmt->fetchAll();

$chart_labels = [];
$chart_gpas = [];
foreach ($semester_data as $row) {
    $chart_labels[] = $row['semester'];
    $chart_gpas[] = round($row['sem_points'] / $row['sem_credits'], 2);
}

$due_dates = $db->query("SELECT * FROM events WHERE due_date >= date('now', 'localtime') ORDER BY due_date ASC LIMIT 5")->fetchAll();

// Proactive Reminder Logic
$show_reminder = false;
$imminent_event = null;
if (!empty($due_dates)) {
    $imminent_event = $due_dates[0];
    $show_reminder = true;
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($show_reminder && $imminent_event): ?>
    <?php
        $date_obj = new DateTime($imminent_event['due_date']);
        $friendly_date = $date_obj->format('l, F jS');
    ?>
    <div id="proactive-reminder" class="proactive-reminder">
        <div class="reminder-content">
            <span class="reminder-icon">📅</span>
            <span class="reminder-text">
                Quick reminder! Your <strong><?php echo htmlspecialchars($imminent_event['title']); ?></strong> 
                (<?php echo $imminent_event['type']; ?>) is coming up on <strong><?php echo $friendly_date; ?></strong>.
            </span>
        </div>
        <button class="reminder-btn" onclick="dismissReminder()">Got it!</button>
    </div>

    <script>
    function dismissReminder() {
        const reminder = document.getElementById('proactive-reminder');
        reminder.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        reminder.style.opacity = '0';
        reminder.style.transform = 'translateY(-10px)';
        setTimeout(() => reminder.remove(), 300);
    }
    </script>
<?php endif; ?>

<h1>Student Dashboard</h1>
<p style="color: #6c757d; margin-bottom: 30px;">Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! Here is what's happening in your classroom:</p>

<div class="card-grid">
    <div class="card" style="animation-delay: 0.1s;">
        <h3>📚 Materials</h3>
        <p class="stat-count" data-target="<?php echo $materials_count; ?>">0</p>
        <a href="materials.php" class="card-link">View All &rarr;</a>
    </div>
    <div class="card" style="animation-delay: 0.2s;">
        <h3>📝 Notes</h3>
        <p class="stat-count" data-target="<?php echo $notes_count; ?>">0</p>
        <a href="notes.php" class="card-link">View All &rarr;</a>
    </div>
    <div class="card" style="animation-delay: 0.3s;">
        <h3>📸 Archive</h3>
        <p class="stat-count" data-target="<?php echo $questions_count; ?>">0</p>
        <a href="past_questions.php" class="card-link">Explore &rarr;</a>
    </div>
    <div class="card card-cgpa" style="animation-delay: 0.4s;">
        <h3>📊 CGPA</h3>
        <p class="stat-count" data-target="<?php echo number_format($cgpa, 2); ?>" data-decimals="2">0.00</p>
        <a href="cgpa.php" class="card-link">Manage Grades &rarr;</a>
    </div>
</div>

<!-- Analytics Section -->
<div class="analytics-grid">
    <div class="card card-large" style="animation-delay: 0.5s;">
        <h3>🎓 Degree Progress</h3>
        <div style="height: 220px; position: relative; margin-bottom: 10px;">
            <canvas id="progressChart"></canvas>
        </div>
        <p style="text-align: center; margin-top: 10px; font-size: 1rem; color: #495057;">
            <strong><?php echo $completed_credits; ?></strong> / 160 Credits
        </p>
    </div>
    <div class="card card-large" style="animation-delay: 0.6s;">
        <h3>📈 GPA Trend</h3>
        <div style="height: 250px; position: relative;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<div style="margin-top: 40px;">
    <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.2em;">⏳</span> Upcoming Due Dates
    </h3>
    <?php if (empty($due_dates)): ?>
        <div class="card" style="padding: 30px; text-align: center; color: #6c757d; border-style: dashed;">
            <p style="font-size: 1rem; font-weight: normal;">No upcoming exams or assignments! Enjoy your free time. 🎉</p>
        </div>
    <?php else: ?>
        <div style="background: #fff; border-radius: 16px; border: 1px solid #f1f3f5; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <?php foreach ($due_dates as $event): ?>
            <div class="item" style="padding: 18px 25px; display: flex; align-items: center; border-bottom: 1px solid #f8f9fa; transition: background 0.2s;">
                <span class="due-date <?php echo strtolower($event['type']); ?>" style="width: 130px; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">
                    • <?php echo htmlspecialchars($event['type']); ?>
                </span>
                <strong style="flex: 1; color: #212529; font-weight: 600;"><?php echo htmlspecialchars($event['title']); ?></strong>
                <span style="color: #6c757d; font-size: 0.9rem;"><?php echo date('D, M jS', strtotime($event['due_date'])); ?></span>
            </div>
        <?php endforeach; ?>
        </div>
        <p style="margin-top: 20px;">
            <a href="calendar.php" style="color: #004085; font-weight: 600; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                View Full Calendar <span>&rarr;</span>
            </a>
        </p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let progressChart, trendChart;

function initCharts() {
    if (progressChart) progressChart.destroy();
    if (trendChart) trendChart.destroy();

    // Degree Progress Chart - Red
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    progressChart = new Chart(progressCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Remaining'],
            datasets: [{
                data: [<?php echo $completed_credits; ?>, <?php echo max(0, 160 - $completed_credits); ?>],
                backgroundColor: ['#9d3d3d', '#e9ecef'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '75%',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // GPA Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Semester GPA',
                data: <?php echo json_encode($chart_gpas); ?>,
                borderColor: '#004085',
                backgroundColor: 'rgba(0, 64, 133, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#9d3d3d',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 8
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 4.0,
                    ticks: { stepSize: 1, color: '#6c757d' },
                    grid: { color: '#f8f9fa' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#6c757d' }
                }
            },
            plugins: { legend: { display: false } }
        }
    });
}

document.addEventListener('DOMContentLoaded', initCharts);
</script>

<?php include 'includes/footer.php'; ?>

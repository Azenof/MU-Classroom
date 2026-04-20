<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

// Fetch recent data for summary
$materials_count = $db->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$notes_count = $db->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$questions_count = $db->query("SELECT COUNT(*) FROM past_questions")->fetchColumn();
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
<p>Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! Here is what's happening in your classroom:</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <div style="border: 1px solid #ddd; padding: 20px; border-radius: 12px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0;">📚 Materials</h3>
        <p>There are <strong><?php echo $materials_count; ?></strong> resources available.</p>
        <a href="materials.php" style="color: #004085; font-weight: bold; text-decoration: none;">View All &rarr;</a>
    </div>
    <div style="border: 1px solid #ddd; padding: 20px; border-radius: 12px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0;">📝 Shared Notes</h3>
        <p><strong><?php echo $notes_count; ?></strong> notes have been contributed.</p>
        <a href="notes.php" style="color: #004085; font-weight: bold; text-decoration: none;">View All &rarr;</a>
    </div>
    <div style="border: 1px solid #ddd; padding: 20px; border-radius: 12px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0;">📸 Past Questions</h3>
        <p><strong><?php echo $questions_count; ?></strong> questions in the archive.</p>
        <a href="past_questions.php" style="color: #6a1b9a; font-weight: bold; text-decoration: none;">Explore Archive &rarr;</a>
    </div>
</div>

<div style="margin-top: 30px;">
    <h3>Upcoming Due Dates</h3>
    <?php if (empty($due_dates)): ?>
        <p>No upcoming exams or assignments!</p>
    <?php else: ?>
        <div style="background: white; border-radius: 12px; border: 1px solid #ddd; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <?php foreach ($due_dates as $event): ?>
            <div class="item" style="padding: 15px 20px; display: flex; align-items: center; border-bottom: 1px solid #eee;">
                <span class="due-date <?php echo strtolower($event['type']); ?>" style="width: 130px; display: inline-block;">[<?php echo strtoupper($event['type']); ?>]</span>
                <strong style="flex: 1;"><?php echo htmlspecialchars($event['title']); ?></strong>
                <span style="color: #666; font-size: 0.95em;"><?php echo date('D, M jS', strtotime($event['due_date'])); ?></span>
            </div>
        <?php endforeach; ?>
        </div>
        <p style="margin-top: 15px;"><a href="calendar.php" style="color: #004085; font-weight: bold; text-decoration: none;">View Full Calendar &rarr;</a></p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

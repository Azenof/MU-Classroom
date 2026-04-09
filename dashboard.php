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
$due_dates = $db->query("SELECT * FROM events WHERE due_date >= date('now') ORDER BY due_date ASC LIMIT 5")->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<h1>Student Dashboard</h1>
<p>Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! Here is what's happening in your classroom:</p>

<div style="display: flex; gap: 20px;">
    <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 8px;">
        <h3>Course Materials</h3>
        <p>There are <strong><?php echo $materials_count; ?></strong> materials available.</p>
        <a href="materials.php">View All</a>
    </div>
    <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 8px;">
        <h3>Shared Notes</h3>
        <p><strong><?php echo $notes_count; ?></strong> notes have been shared by students.</p>
        <a href="notes.php">View All</a>
    </div>
</div>

<div style="margin-top: 30px;">
    <h3>Upcoming Due Dates</h3>
    <?php if (empty($due_dates)): ?>
        <p>No upcoming exams or assignments!</p>
    <?php else: ?>
        <ul>
        <?php foreach ($due_dates as $event): ?>
            <li class="item">
                <span class="due-date">[<?php echo strtoupper($event['type']); ?>]</span>
                <strong><?php echo htmlspecialchars($event['title']); ?></strong> - Due: <?php echo $event['due_date']; ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <a href="calendar.php">View All Due Dates</a>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

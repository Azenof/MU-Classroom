<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

// Add Event Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $due_date = $_POST['due_date'];
    $stmt = $db->prepare("INSERT INTO events (title, type, due_date) VALUES (?, ?, ?)");
    $stmt->execute([$title, $type, $due_date]);
    header("Location: calendar.php");
    exit;
}

$events = $db->query("SELECT * FROM events ORDER BY due_date ASC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Exams & Assignments Due Dates</h1>
<p>Keep track of important deadlines for your courses.</p>

<div style="background: #fff3e0; padding: 15px; border: 1px solid #ffe0b2; border-radius: 8px; margin-bottom: 20px;">
    <h3>Add Important Date</h3>
    <form method="POST">
        <input type="text" name="title" placeholder="Event Name (e.g., Mid-term Exam)" required>
        <select name="type">
            <option value="assignment">Assignment</option>
            <option value="exam">Exam</option>
        </select>
        <label>Due Date:</label>
        <input type="date" name="due_date" required>
        <button type="submit" style="background-color: #ff9800;">Add Event</button>
    </form>
</div>

<h3>Upcoming Deadlines</h3>
<?php if (empty($events)): ?>
    <p>No deadlines scheduled yet.</p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
        <div class="item">
            <span class="due-date">[<?php echo strtoupper($event['type']); ?>]</span>
            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
            <br>
            <span style="color: #555;">Deadline: <?php echo date('F j, Y', strtotime($event['due_date'])); ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

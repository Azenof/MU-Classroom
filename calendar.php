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
    $description = $_POST['description'] ?? '';
    $stmt = $db->prepare("INSERT INTO events (title, type, due_date, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $type, $due_date, $description]);
    header("Location: calendar.php");
    exit;
}

// Delete Event Logic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: calendar.php");
    exit;
}

// Fetch upcoming events
$upcoming_events = $db->query("SELECT * FROM events WHERE due_date >= date('now', 'localtime') ORDER BY due_date ASC")->fetchAll();

// Fetch past events
$past_events = $db->query("SELECT * FROM events WHERE due_date < date('now', 'localtime') ORDER BY due_date DESC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Exams & Assignments Due Dates</h1>
<p>Keep track of important deadlines for your courses.</p>

<div style="background: #fff3e0; padding: 15px; border: 1px solid #ffe0b2; border-radius: 8px; margin-bottom: 20px;">
    <h3>Add Important Date</h3>
    <form method="POST">
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="title" placeholder="Event Name (e.g., Mid-term Exam)" required style="flex: 2;">
                <select name="type" style="flex: 1;">
                    <option value="assignment">Assignment</option>
                    <option value="exam">Exam</option>
                    <option value="CT">CT</option>
                    <option value="presentation">Presentation</option>
                    <option value="project">Project</option>
                </select>
                <input type="date" name="due_date" required style="flex: 1;">
            </div>
            <textarea name="description" placeholder="Optional details (e.g., Topics: AI Ethics, Neural Networks...)" rows="2"></textarea>
            <button type="submit" style="background-color: #004085; align-self: flex-start; padding: 10px 20px;">Add Event</button>
        </div>
    </form>
</div>

<h3>Upcoming Deadlines</h3>
<?php if (empty($upcoming_events)): ?>
    <p>No upcoming deadlines scheduled.</p>
<?php else: ?>
    <?php foreach ($upcoming_events as $event): ?>
        <div class="item" style="border-bottom: 1px solid #eee; padding: 15px 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span class="due-date <?php echo strtolower($event['type']); ?>">[<?php echo strtoupper($event['type']); ?>]</span>
                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                    <br>
                    <span style="color: #555;">Deadline: <?php echo date('F j, Y', strtotime($event['due_date'])); ?></span>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if (!empty($event['description'])): ?>
                        <button onclick="toggleDetails(<?php echo $event['id']; ?>)" 
                                style="background: #e3f2fd; border: 1px solid #2196f3; color: #2196f3; cursor: pointer; padding: 5px 10px; border-radius: 4px;">Details</button>
                    <?php endif; ?>
                    <a href="calendar.php?delete=<?php echo $event['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this event?')" 
                       style="color: #dc3545; text-decoration: none; font-size: 0.9em; border: 1px solid #dc3545; padding: 4px 10px; border-radius: 4px;">Delete</a>
                </div>
            </div>
            <?php if (!empty($event['description'])): ?>
                <div id="details-<?php echo $event['id']; ?>" style="display: none; background: #f9f9f9; border-left: 3px solid #2196f3; margin-top: 10px; padding: 10px; font-size: 0.95em; color: #444;">
                    <strong>Details:</strong><br>
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($past_events)): ?>
    <h3 style="margin-top: 40px; color: #666;">Past Events</h3>
    <div style="opacity: 0.7;">
        <?php foreach ($past_events as $event): ?>
            <div class="item" style="border-bottom: 1px solid #eee; padding: 15px 0; border-left: 4px solid #ccc; padding-left: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span class="due-date <?php echo strtolower($event['type']); ?>" style="filter: grayscale(1); opacity: 0.6;">[<?php echo strtoupper($event['type']); ?>]</span>
                        <strong style="text-decoration: line-through; color: #888;"><?php echo htmlspecialchars($event['title']); ?></strong>
                        <br>
                        <span style="color: #999;">Ended: <?php echo date('F j, Y', strtotime($event['due_date'])); ?></span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <?php if (!empty($event['description'])): ?>
                            <button onclick="toggleDetails(<?php echo $event['id']; ?>)" 
                                    style="background: #f5f5f5; border: 1px solid #ddd; color: #666; cursor: pointer; padding: 5px 10px; border-radius: 4px;">Details</button>
                        <?php endif; ?>
                        <a href="calendar.php?delete=<?php echo $event['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this history?')" 
                           style="color: #888; text-decoration: none; font-size: 0.8em; border: 1px solid #ccc; padding: 3px 8px; border-radius: 4px;">Remove</a>
                    </div>
                </div>
                <?php if (!empty($event['description'])): ?>
                    <div id="details-<?php echo $event['id']; ?>" style="display: none; background: #fafafa; border-left: 3px solid #ccc; margin-top: 10px; padding: 10px; font-size: 0.9em; color: #777;">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleDetails(id) {
    var details = document.getElementById('details-' + id);
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

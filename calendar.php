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

<h1>Exams & Due Dates</h1>
<p style="color: #6c757d; margin-bottom: 30px;">Keep track of important deadlines and academic milestones.</p>

<div class="card" style="margin-bottom: 40px; border-top: 4px solid #004085;">
    <h3 style="margin-top: 0; margin-bottom: 20px;">📅 Add New Deadline</h3>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <input type="text" name="title" placeholder="Event Name (e.g., Mid-term Exam)" required>
            <select name="type">
                <option value="assignment">Assignment</option>
                <option value="exam">Exam</option>
                <option value="CT">CT</option>
                <option value="presentation">Presentation</option>
                <option value="project">Project</option>
            </select>
            <input type="date" name="due_date" required>
        </div>
        <textarea name="description" placeholder="Optional details (e.g., Topics: AI Ethics, Neural Networks...)" rows="2" style="width: 100%; box-sizing: border-box; margin-bottom: 15px;"></textarea>
        <button type="submit">Add to Calendar</button>
    </form>
</div>

<h3 style="margin-bottom: 20px;">Upcoming Deadlines</h3>
<?php if (empty($upcoming_events)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: #6c757d; border-style: dashed;">
        <p>No upcoming deadlines scheduled. Keep up the good work! ✨</p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach ($upcoming_events as $index => $event): ?>
            <div class="card" style="animation-delay: <?php echo ($index * 0.05); ?>s; padding: 20px 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="text-align: center; min-width: 60px; padding-right: 20px; border-right: 1px solid #f1f3f5;">
                            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #6c757d;">
                                <?php echo date('M', strtotime($event['due_date'])); ?>
                            </div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #212529;">
                                <?php echo date('d', strtotime($event['due_date'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="due-date <?php echo strtolower($event['type']); ?>" style="font-size: 0.7rem; letter-spacing: 0.05em; text-transform: uppercase; font-weight: 700;">
                                • <?php echo htmlspecialchars($event['type']); ?>
                            </span>
                            <strong style="display: block; font-size: 1.15rem; color: #003366; margin-top: 4px;">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </strong>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <?php if (!empty($event['description'])): ?>
                            <button onclick="toggleDetails(<?php echo $event['id']; ?>)" 
                                    style="background: #e7f1ff; border: none; color: #004085; cursor: pointer; padding: 8px 15px; border-radius: 8px; font-weight: 700; font-size: 0.85rem;">
                                Details
                            </button>
                        <?php endif; ?>
                        <a href="calendar.php?delete=<?php echo $event['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this event?')" 
                           style="color: #9d3d3d; text-decoration: none; font-size: 0.85rem; font-weight: 700; padding: 8px; border-radius: 8px; background: #fff5f5; border: 1px solid #f8d7da;">
                            Delete
                        </a>
                    </div>
                </div>
                <?php if (!empty($event['description'])): ?>
                    <div id="details-<?php echo $event['id']; ?>" style="display: none; background: #fafbfc; border-radius: 8px; margin-top: 15px; padding: 15px; font-size: 0.95rem; color: #495057; border: 1px solid #f1f3f5;">
                        <strong>Note:</strong><br>
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($past_events)): ?>
    <h3 style="margin-top: 50px; margin-bottom: 20px; color: #6c757d;">Completed / Past Events</h3>
    <div style="display: flex; flex-direction: column; gap: 10px; opacity: 0.7;">
        <?php foreach ($past_events as $event): ?>
            <div class="card" style="padding: 15px 25px; border-left: 4px solid #dee2e6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <span style="color: #adb5bd; font-size: 0.85rem; min-width: 100px;">
                            <?php echo date('M j, Y', strtotime($event['due_date'])); ?>
                        </span>
                        <strong style="color: #6c757d; text-decoration: line-through;"><?php echo htmlspecialchars($event['title']); ?></strong>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <?php if (!empty($event['description'])): ?>
                            <button onclick="toggleDetails(<?php echo $event['id']; ?>)" 
                                    style="background: #f8f9fa; border: none; color: #6c757d; cursor: pointer; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem;">
                                Details
                            </button>
                        <?php endif; ?>
                        <a href="calendar.php?delete=<?php echo $event['id']; ?>" 
                           onclick="return confirm('Remove from history?')" 
                           style="color: #adb5bd; text-decoration: none; font-size: 0.75rem; font-weight: 600;">
                            Remove
                        </a>
                    </div>
                </div>
                <?php if (!empty($event['description'])): ?>
                    <div id="details-<?php echo $event['id']; ?>" style="display: none; background: #fafbfc; border-radius: 8px; margin-top: 15px; padding: 15px; font-size: 0.9rem; color: #6c757d; border: 1px dashed #eee;">
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

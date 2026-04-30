<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

// Add Note Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $content]);
    header("Location: notes.php");
    exit;
}

// Delete Note Logic
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: notes.php");
    exit;
}

$stmt = $db->prepare("SELECT notes.*, users.name as author FROM notes JOIN users ON notes.user_id = users.id WHERE notes.user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Shared Notes</h1>
<p style="color: #6c757d; margin-bottom: 30px;">Collaborate with your classmates by sharing and reading notes.</p>

<div class="card" style="margin-bottom: 40px; border-top: 4px solid #004085;">
    <h3 style="margin-top: 0; margin-bottom: 20px;">✍️ Share a New Note</h3>
    <form method="POST">
        <input type="text" name="title" placeholder="Note Title" required>
        <textarea name="content" placeholder="Write your notes here..." rows="5" required style="width: 100%; box-sizing: border-box; resize: vertical; margin-bottom: 10px;"></textarea>
        <button type="submit">Share Note</button>
    </form>
</div>

<h3 style="margin-bottom: 20px;">Community Notes</h3>
<?php if (empty($notes)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: #6c757d; border-style: dashed;">
        <p>No notes shared yet. Be the first to share your knowledge! 🚀</p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($notes as $index => $note): ?>
            <div class="card" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f3f5;">
                    <strong style="font-size: 1.25rem; color: #004085;"><?php echo htmlspecialchars($note['title']); ?></strong> 
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 0.85rem; color: #6c757d; text-align: right;">
                            <span>by <strong><?php echo htmlspecialchars($note['author']); ?></strong></span><br>
                            <span><?php echo date('M j, Y', strtotime($note['created_at'])); ?></span>
                        </div>
                        <a href="notes.php?delete=<?php echo $note['id']; ?>" 
                           onclick="return confirm('Delete this note?')" 
                           style="color: #9d3d3d; text-decoration: none; font-size: 0.75rem; font-weight: 700; border: 1px solid #f8d7da; padding: 5px 10px; border-radius: 6px; background: #fff5f5; transition: all 0.2s;">
                            Delete
                        </a>
                    </div>
                </div>
                <p style="line-height: 1.7; color: #495057; font-size: 1rem;"><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php include 'includes/footer.php'; ?>

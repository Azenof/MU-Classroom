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

$notes = $db->query("SELECT notes.*, users.name as author FROM notes JOIN users ON notes.user_id = users.id ORDER BY created_at DESC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Shared Notes</h1>
<p>Collaborate with your classmates by sharing and reading notes.</p>

<div style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; margin-bottom: 20px;">
    <h3>Share a New Note</h3>
    <form method="POST">
        <input type="text" name="title" placeholder="Note Title" required>
        <textarea name="content" placeholder="Write your notes here..." rows="5" required></textarea>
        <button type="submit">Share Note</button>
    </form>
</div>

<h3>Community Notes</h3>
<?php if (empty($notes)): ?>
    <p>No notes shared yet. Be the first!</p>
<?php else: ?>
    <?php foreach ($notes as $note): ?>
        <div class="item">
            <strong><?php echo htmlspecialchars($note['title']); ?></strong> 
            <small>by <?php echo htmlspecialchars($note['author']); ?> on <?php echo $note['created_at']; ?></small>
            <p><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

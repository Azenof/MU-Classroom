<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

// Add Material Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $url = $_POST['url'];
    $type = $_POST['type'];
    $stmt = $db->prepare("INSERT INTO materials (title, url, type) VALUES (?, ?, ?)");
    $stmt->execute([$title, $url, $type]);
    header("Location: materials.php");
    exit;
}

$materials = $db->query("SELECT * FROM materials ORDER BY created_at DESC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Course Materials</h1>
<p>Find your lecture slides, PDFs, and reading materials here.</p>

<div style="background: #eef9ff; padding: 15px; border: 1px solid #b3e5fc; border-radius: 8px; margin-bottom: 20px;">
    <h3>Add Material</h3>
    <form method="POST">
        <input type="text" name="title" placeholder="Material Title" required>
        <input type="url" name="url" placeholder="Material Link (URL)" required>
        <select name="type">
            <option value="Lecture Slide">Lecture Slide</option>
            <option value="PDF Book">PDF Book</option>
            <option value="Assignment Brief">Assignment Brief</option>
            <option value="Video">Video</option>
        </select>
        <button type="submit" style="background-color: #28a745;">Add Material</button>
    </form>
</div>

<h3>Available Resources</h3>
<?php if (empty($materials)): ?>
    <p>No materials available yet.</p>
<?php else: ?>
    <?php foreach ($materials as $item): ?>
        <div class="item">
            <strong><?php echo htmlspecialchars($item['title']); ?></strong> (<?php echo htmlspecialchars($item['type']); ?>)
            <br>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank">View Material</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

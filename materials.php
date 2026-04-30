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
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO materials (user_id, title, url, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $url, $type]);
    header("Location: materials.php");
    exit;
}

// Delete Material Logic
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM materials WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: materials.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM materials WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$materials = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Course Materials</h1>
<p style="color: #6c757d; margin-bottom: 30px;">Find your lecture slides, PDFs, and reading materials here.</p>

<div class="card" style="margin-bottom: 40px; border-top: 4px solid #004085;">
    <h3 style="margin-top: 0; margin-bottom: 20px;">➕ Add Material</h3>
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <input type="text" name="title" placeholder="Material Title" required style="grid-column: span 2;">
        <input type="url" name="url" placeholder="Material Link (URL)" required>
        <select name="type">
            <option value="Lecture Slide">Lecture Slide</option>
            <option value="PDF Book">PDF Book</option>
            <option value="Assignment Brief">Assignment Brief</option>
            <option value="Video">Video</option>
        </select>
        <button type="submit" style="grid-column: span 2;">Add Material</button>
    </form>
</div>

<h3 style="margin-bottom: 20px;">Available Resources</h3>
<?php if (empty($materials)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: #6c757d; border-style: dashed;">
        <p>No materials available yet. Start by adding one above!</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($materials as $index => $item): ?>
            <div class="card" style="animation-delay: <?php echo ($index * 0.05); ?>s;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: #004085; text-transform: uppercase; background: #e7f1ff; padding: 4px 10px; border-radius: 20px;">
                        <?php echo htmlspecialchars($item['type']); ?>
                    </span>
                </div>
                <strong style="font-size: 1.1rem; color: #212529; display: block; margin-bottom: 15px; line-height: 1.4;">
                    <?php echo htmlspecialchars($item['title']); ?>
                </strong>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" class="card-link">
                        View Resource &rarr;
                    </a>
                    <a href="materials.php?delete=<?php echo $item['id']; ?>" 
                       onclick="return confirm('Delete this material?')" 
                       style="color: #9d3d3d; text-decoration: none; font-size: 0.75rem; font-weight: 700; border: 1px solid #f8d7da; padding: 5px 10px; border-radius: 6px; background: #fff5f5; transition: all 0.2s;">
                        Delete
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php include 'includes/footer.php'; ?>

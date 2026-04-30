<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

$message = '';
if (isset($_SESSION['upload_success'])) {
    $message = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    unset($_SESSION['delete_message']);
}

// Handle Delete Logic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $user_id = $_SESSION['user_id'];
    // Get image path and verify ownership first
    $stmt = $db->prepare("SELECT image_path FROM past_questions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $question = $stmt->fetch();
    
    if ($question) {
        if (file_exists($question['image_path'])) {
            unlink($question['image_path']);
        }
        $stmt = $db->prepare("DELETE FROM past_questions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['delete_message'] = '<p style="color: var(--mu-red);">Question deleted successfully.</p>';
        header("Location: past_questions.php");
        exit;
    }
}

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['question_image'])) {
    $course = $_POST['course_name'];
    $type = $_POST['exam_type'];
    $batch = $_POST['batch'];
    $user_id = $_SESSION['user_id'];
    
    $file = $_FILES['question_image'];
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = 'uploads/questions/' . $fileName;
    
    // Simple validation
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (in_array($file['type'], $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $db->prepare("INSERT INTO past_questions (user_id, course_name, exam_type, batch, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $course, $type, $batch, $targetPath]);
            $_SESSION['upload_success'] = '<p style="color: #28a745;">Question uploaded successfully!</p>';
            header("Location: past_questions.php");
            exit;
        } else {
            $message = '<p style="color: var(--mu-red);">Failed to move uploaded file.</p>';
        }
    } else {
        $message = '<p style="color: var(--mu-red);">Invalid file type. Please upload an image.</p>';
    }
}

$stmt = $db->prepare("SELECT * FROM past_questions WHERE user_id = ? ORDER BY batch DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$questions = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Past Exam Questions</h1>
<p style="color: #6c757d; margin-bottom: 30px;">Upload and view questions from previous exams and tests.</p>

<?php if ($message): ?>
    <div class="card" style="padding: 12px 20px; margin-bottom: 20px; background: #f8f9fa; border-left: 4px solid #004085;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 40px; border-top: 4px solid #004085;">
    <h3 style="margin-top: 0; margin-bottom: 20px;">📸 Upload New Question Photo</h3>
    <form method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <input type="text" name="course_name" placeholder="Course Name (e.g., Data Structures)" required>
            <select name="exam_type" required>
                <option value="Final Exam">Final Exam</option>
                <option value="Mid-term">Mid-term</option>
                <option value="CT">CT</option>
                <option value="Quiz">Quiz</option>
            </select>
            <input type="text" name="batch" placeholder="Batch (e.g. 52nd)" required>
        </div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Select Image:</label>
            <div style="position: relative;">
                <input type="file" name="question_image" accept="image/*" required 
                       style="border: 2px dashed #dee2e6; padding: 20px; width: 100%; box-sizing: border-box; border-radius: 8px; cursor: pointer; text-align: center;">
            </div>
        </div>
        <button type="submit">Upload Question</button>
    </form>
</div>

<h3 style="margin-bottom: 20px;">Question Archive</h3>
<?php if (empty($questions)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: #6c757d; border-style: dashed;">
        <p>No questions uploaded yet. Be the first to help out! 🤝</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
        <?php foreach ($questions as $index => $q): ?>
            <div class="card" style="padding: 0; overflow: hidden; animation-delay: <?php echo ($index * 0.05); ?>s;">
                <a href="<?php echo htmlspecialchars($q['image_path']); ?>" target="_blank" style="display: block; position: relative; height: 200px; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($q['image_path']); ?>" alt="Question Image" 
                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px 15px; color: white;">
                        <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; background: rgba(0,64,133,0.8); padding: 3px 8px; border-radius: 4px;">
                            <?php echo htmlspecialchars($q['batch']); ?> Batch
                        </span>
                    </div>
                </a>
                <div style="padding: 15px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <div style="flex: 1;">
                        <strong style="display: block; color: #212529; font-size: 1.1rem; margin-bottom: 4px;"><?php echo htmlspecialchars($q['course_name']); ?></strong>
                        <span style="font-size: 0.85rem; color: #6c757d; font-weight: 500;"><?php echo htmlspecialchars($q['exam_type']); ?></span>
                    </div>
                    <a href="past_questions.php?delete=<?php echo $q['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this question?')" 
                       style="color: #9d3d3d; text-decoration: none; font-size: 0.75rem; font-weight: 700; border: 1px solid #f8d7da; padding: 5px 10px; border-radius: 6px; background: #fff5f5; transition: all 0.2s;">
                        Delete
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php include 'includes/footer.php'; ?>

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
    // Get image path first to delete the file
    $stmt = $db->prepare("SELECT image_path FROM past_questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch();
    
    if ($question) {
        if (file_exists($question['image_path'])) {
            unlink($question['image_path']);
        }
        $stmt = $db->prepare("DELETE FROM past_questions WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['delete_message'] = '<p style="color: #9d3d3d;">Question deleted successfully.</p>';
        header("Location: past_questions.php");
        exit;
    }
}

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['question_image'])) {
    $course = $_POST['course_name'];
    $type = $_POST['exam_type'];
    $batch = $_POST['batch'];
    
    $file = $_FILES['question_image'];
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = 'uploads/questions/' . $fileName;
    
    // Simple validation
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (in_array($file['type'], $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $db->prepare("INSERT INTO past_questions (course_name, exam_type, batch, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course, $type, $batch, $targetPath]);
            $_SESSION['upload_success'] = '<p style="color: green;">Question uploaded successfully!</p>';
            header("Location: past_questions.php");
            exit;
        } else {
            $message = '<p style="color: red;">Failed to move uploaded file.</p>';
        }
    } else {
        $message = '<p style="color: red;">Invalid file type. Please upload an image.</p>';
    }
}

$questions = $db->query("SELECT * FROM past_questions ORDER BY batch DESC, created_at DESC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Past Exam Questions</h1>
<p>Upload and view questions from previous exams and tests.</p>

<?php echo $message; ?>

<div style="background: #f3e5f5; padding: 20px; border: 1px solid #ce93d8; border-radius: 12px; margin-bottom: 30px;">
    <h3>Upload New Question Photo</h3>
    <form method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <input type="text" name="course_name" placeholder="Course Name (e.g., Data Structures)" required>
            <select name="exam_type" required>
                <option value="Final Exam">Final Exam</option>
                <option value="Mid-term">Mid-term</option>
                <option value="CT">CT</option>
                <option value="Quiz">Quiz</option>
            </select>
            <input type="text" name="batch" placeholder="Batch (e.g. 52nd)" required>
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Select Image:</label>
            <input type="file" name="question_image" accept="image/*" required>
        </div>
        <button type="submit" style="background-color: #6a1b9a;">Upload Question</button>
    </form>
</div>

<h3>Archive</h3>
<?php if (empty($questions)): ?>
    <p>No questions uploaded yet. Be the first to help out!</p>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <?php foreach ($questions as $q): ?>
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <a href="<?php echo $q['image_path']; ?>" target="_blank">
                    <img src="<?php echo $q['image_path']; ?>" alt="Question Image" style="width: 100%; height: 180px; object-fit: cover; border-bottom: 1px solid #eee;">
                </a>
                <div style="padding: 12px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <strong style="display: block; color: #6a1b9a;"><?php echo htmlspecialchars($q['course_name']); ?></strong>
                        <span style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($q['exam_type']); ?> - Batch: <?php echo htmlspecialchars($q['batch']); ?></span>
                    </div>
                    <a href="past_questions.php?delete=<?php echo $q['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this question?')" 
                       style="color: #9d3d3d; text-decoration: none; font-size: 0.8em; border: 1px solid #9d3d3d; padding: 3px 8px; border-radius: 4px;">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

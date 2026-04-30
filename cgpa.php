<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'includes/db.php';

function cleanupPassedSupplis($db, $user_id) {
    $stmt = $db->prepare("DELETE FROM course_results 
                          WHERE user_id = ? 
                          AND grade = 0 
                          AND course_name IN (
                              SELECT course_name FROM course_results 
                              WHERE user_id = ? AND grade > 0
                          )");
    $stmt->execute([$user_id, $user_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['transcript'])) {
    $upload_dir = 'uploads/transcripts/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $filename = time() . '_' . basename($_FILES['transcript']['name']);
    $path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['transcript']['tmp_name'], $path)) {
        $cmd = "python3 " . escapeshellarg(__DIR__ . '/ai_chatbot/scanner.py') . " " . escapeshellarg($path) . " 2>&1";
        $output = shell_exec($cmd);
        $data = json_decode($output, true);
        
        if (is_array($data) && !isset($data['error'])) {
            foreach ($data as $course) {
                $stmt = $db->prepare("INSERT INTO course_results (user_id, course_name, credits, grade, letter_grade, semester, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $course['course_name'], 
                    $course['credits'], 
                    $course['grade'], 
                    $course['letter_grade'] ?? '',
                    $course['semester'],
                    $course['status'] ?? ''
                ]);
            }
            cleanupPassedSupplis($db, $_SESSION['user_id']);
            header("Location: cgpa.php");
            exit;
        } else {
            $ocr_error = isset($data['error']) ? $data['error'] : $output;
        }
    } else {
        $upload_error = "File upload failed. Error Code: " . $_FILES['transcript']['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_course'])) {
    $id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $credits = (float)$_POST['credits'];
    $grade = (float)$_POST['grade'];
    $lg = $_POST['letter_grade'];
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE course_results SET course_name = ?, credits = ?, grade = ?, letter_grade = ?, status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$course_name, $credits, $grade, $lg, $status, $id, $_SESSION['user_id']]);
    
    cleanupPassedSupplis($db, $_SESSION['user_id']);
    header("Location: cgpa.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM course_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: cgpa.php");
    exit;
}

if (isset($_GET['delete_semester'])) {
    $stmt = $db->prepare("DELETE FROM course_results WHERE semester = ? AND user_id = ?");
    $stmt->execute([$_GET['delete_semester'], $_SESSION['user_id']]);
    header("Location: cgpa.php");
    exit;
}

if (isset($_GET['delete_all'])) {
    $stmt = $db->prepare("DELETE FROM course_results WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: cgpa.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM course_results WHERE user_id = ? ORDER BY semester DESC");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll();

$semesters = [];
$total_points = 0;
$total_credits = 0;

foreach ($results as $row) {
    if (!isset($semesters[$row['semester']])) {
        $semesters[$row['semester']] = ['courses' => [], 'points' => 0, 'credits' => 0];
    }
    $semesters[$row['semester']]['courses'][] = $row;
    
    if ($row['grade'] > 0) {
        $semesters[$row['semester']]['points'] += ($row['grade'] * $row['credits']);
        $semesters[$row['semester']]['credits'] += $row['credits'];
        
        $total_points += ($row['grade'] * $row['credits']);
        $total_credits += $row['credits'];
    }
}

$cgpa = ($total_credits > 0) ? ($total_points / $total_credits) : 0;
?>

<?php include 'includes/header.php'; ?>

<style>
    .edit-input { width: 100%; padding: 5px; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box; background: var(--bg-color); color: var(--text-primary); }
    .action-btn { cursor: pointer; text-decoration: none; font-size: 0.9em; padding: 4px 8px; border-radius: 4px; border: 1px solid; transition: all 0.2s; background: none; }
    .btn-edit { color: var(--mu-blue); border-color: var(--mu-blue); }
    .btn-edit:hover { background: var(--mu-blue-light); }
    .btn-delete { color: var(--mu-red); border-color: var(--mu-red); }
    .btn-delete:hover { background: var(--mu-red-light); }
    .btn-save { color: #28a745; border-color: #28a745; background: rgba(40, 167, 69, 0.1); display: none; }
    .display-mode { }
    .edit-mode { display: none; }
</style>

<h1>CGPA Calculator</h1>

<div class="card" style="margin-bottom: 30px; border-top: 4px solid #004085;">
    <h3 style="margin-top: 0; margin-bottom: 20px;">📄 Scan Academic Record</h3>
    <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: center;">
        <div style="flex: 1;">
            <input type="file" name="transcript" accept="image/*" required 
                   style="border: 1px dashed #dee2e6; padding: 10px; width: 100%; box-sizing: border-box; border-radius: 8px;">
        </div>
        <button type="submit" style="white-space: nowrap;">Upload & Scan</button>
    </form>
    <p style="font-size: 0.85rem; color: #6c757d; margin-top: 15px;">
        <span style="color: #004085; font-weight: bold;">Pro-tip:</span> Passed courses automatically replace previous "Suppli" attempts.
    </p>
</div>

<div class="card" style="margin-bottom: 40px; border-left: 6px solid #9d3d3d; flex-direction: row; align-items: center; justify-content: space-between; padding: 25px 30px;">
    <div>
        <h3 style="margin: 0; color: #495057; font-size: 1.1rem;">Cumulative CGPA</h3>
        <p style="margin: 5px 0 0 0; font-size: 2.2rem; font-weight: 800; color: #9d3d3d;">
            <?php echo number_format($cgpa, 2); ?>
        </p>
    </div>
    <?php if (!empty($semesters)): ?>
        <a href="cgpa.php?delete_all=1" onclick="return confirm('Delete ALL records?')" 
           style="background: #fff5f5; color: #9d3d3d; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; border: 1px solid #f8d7da; transition: all 0.2s;">
            Reset All Data
        </a>
    <?php endif; ?>
</div>

<?php foreach ($semesters as $semester_name => $data): 
    $sem_gpa = ($data['credits'] > 0) ? ($data['points'] / $data['credits']) : 0;
?>
    <div class="card" style="padding: 0; margin-top: 35px; border: 1px solid #f1f3f5; box-shadow: 0 10px 30px rgba(0,0,0,0.04);">
        <div style="padding: 20px 25px; background: #fafbfc; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; border-radius: 16px 16px 0 0;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #003366;"><?php echo htmlspecialchars($semester_name); ?></h3>
                <span style="font-size: 0.85rem; color: #fff; font-weight: 800; background: #004085; padding: 4px 12px; border-radius: 20px;">
                    GPA: <?php echo number_format($sem_gpa, 2); ?>
                </span>
            </div>
            <a href="cgpa.php?delete_semester=<?php echo urlencode($semester_name); ?>" onclick="return confirm('Delete this semester?')" 
               style="color: #6c757d; font-size: 0.8rem; text-decoration: none; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid #eee;">
                Remove Semester
            </a>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #fff; color: #495057; border-bottom: 2px solid #f1f3f5;">
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Course</th>
                        <th style="padding: 15px; text-align: center; width: 80px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Credits</th>
                        <th style="padding: 15px; text-align: center; width: 60px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">LG</th>
                        <th style="padding: 15px; text-align: center; width: 80px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">GP</th>
                        <th style="padding: 15px; text-align: center; width: 130px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        <th style="padding: 15px 25px; text-align: center; width: 140px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['courses'] as $course): ?>
                        <tr id="row-<?php echo $course['id']; ?>" style="border-bottom: 1px solid #f8f9fa; transition: background 0.2s;">
                            <form method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <td style="padding: 15px 25px;">
                                    <span class="display-mode" style="font-weight: 600; color: #212529;"><?php echo htmlspecialchars($course['course_name'] ?? ''); ?></span>
                                    <input type="text" name="course_name" class="edit-input edit-mode" value="<?php echo htmlspecialchars($course['course_name'] ?? ''); ?>" required>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span class="display-mode" style="color: #495057;"><?php echo $course['credits']; ?></span>
                                    <input type="number" step="0.5" name="credits" class="edit-input edit-mode" value="<?php echo $course['credits']; ?>" required>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span class="display-mode" style="font-weight: 800; color: #004085;"><?php echo htmlspecialchars($course['letter_grade'] ?? ''); ?></span>
                                    <input type="text" name="letter_grade" class="edit-input edit-mode" value="<?php echo htmlspecialchars($course['letter_grade'] ?? ''); ?>">
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span class="display-mode" style="font-weight: 700; color: #212529;"><?php echo number_format($course['grade'], 2); ?></span>
                                    <input type="number" step="0.01" name="grade" class="edit-input edit-mode" value="<?php echo $course['grade']; ?>" required>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span class="display-mode">
                                        <?php 
                                            $status_text = htmlspecialchars($course['status'] ?: 'Completed');
                                            $dot_color = is_numeric(stripos($status_text, 'Completed')) ? '#28a745' : 
                                                       (is_numeric(stripos($status_text, 'Suppli')) ? '#ffc107' : 
                                                       (is_numeric(stripos($status_text, 'Retake')) ? '#dc3545' : '#6c757d'));
                                        ?>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: #495057;">
                                            <span style="width: 8px; height: 8px; background: <?php echo $dot_color; ?>; border-radius: 50%;"></span>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </span>
                                    <input type="text" name="status" class="edit-input edit-mode" value="<?php echo htmlspecialchars($course['status'] ?: 'Completed'); ?>">
                                </td>
                                <td style="padding: 15px 25px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                                    <button type="button" class="action-btn btn-edit display-mode" style="border-radius: 8px;" onclick="toggleEdit(<?php echo $course['id']; ?>)">Edit</button>
                                    <button type="submit" name="edit_course" class="action-btn btn-save edit-mode" style="background: #28a745; color: white; border: none; border-radius: 8px;">Save</button>
                                    <button type="button" class="action-btn edit-mode" style="border: 1px solid #ddd; color: #6c757d; border-radius: 8px;" onclick="toggleEdit(<?php echo $course['id']; ?>)">Cancel</button>
                                    <a href="cgpa.php?delete=<?php echo $course['id']; ?>" class="action-btn btn-delete display-mode" style="border-radius: 8px;" onclick="return confirm('Delete this course?')">Delete</a>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<script>
function toggleEdit(id) {
    const row = document.getElementById('row-' + id);
    const displayElements = row.querySelectorAll('.display-mode');
    const editElements = row.querySelectorAll('.edit-mode');
    const isEdit = editElements[0].style.display === 'block' || editElements[0].style.display === 'inline-block';
    displayElements.forEach(el => el.style.display = isEdit ? 'inline-block' : 'none');
    editElements.forEach(el => el.style.display = isEdit ? 'none' : 'block');
}
</script>

<?php include 'includes/footer.php'; ?>

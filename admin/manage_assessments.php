<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle form submission (Create new assessment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $local_course_id = $_POST['local_course_id'];
    $assessment_title = $_POST['assessment_title'];
    $due_date = $_POST['due_date'];

    // File upload
    $fileName = null;
    if (!empty($_FILES['file']['name'])) {
        $fileName = time() . "_" . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], "../uploads/assessments/".$fileName);
    }

    $stmt = $pdo->prepare("INSERT INTO assessments (local_course_id, assessment_title, due_date, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$local_course_id, $assessment_title, $due_date, $fileName]);
    $success = "Assessment created successfully!";
}

// Fetch local courses
$courses = $pdo->query("SELECT * FROM local_courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing assessments
$assessments = $pdo->query("
    SELECT a.*, lc.course_name
    FROM assessments a
    JOIN local_courses lc ON a.local_course_id = lc.local_course_id
    ORDER BY a.assigned_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Local Course Assessments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2>📑 Create Local Course Assessment</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Create form -->
    <form method="POST" enctype="multipart/form-data" class="card p-3 mb-4">
        <div class="mb-3">
            <label>Local Course</label>
            <select name="local_course_id" class="form-control" required>
                <option value="">-- Select Local Course --</option>
                <?php foreach($courses as $c): ?>
                    <option value="<?= $c['local_course_id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Assessment Title</label>
            <input type="text" name="assessment_title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Due Date</label>
            <input type="datetime-local" name="due_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Upload File</label>
            <input type="file" name="file" class="form-control">
        </div>
        <button class="btn btn-primary">Create Assessment</button>
    </form>

    <!-- Existing Assessments -->
    <h3>📋 Existing Assessments</h3>
    <table class="table table-bordered">
        <tr>
            <th>ID</th><th>Course</th><th>Title</th><th>Due Date</th><th>File</th><th>Created</th>
        </tr>
        <?php foreach($assessments as $a): ?>
            <tr>
                <td><?= $a['assessment_id'] ?></td>
                <td><?= htmlspecialchars($a['course_name']) ?></td>
                <td><?= htmlspecialchars($a['assessment_title']) ?></td>
                <td><?= date("d M Y H:i", strtotime($a['due_date'])) ?></td>
                <td>
                    <?php if ($a['file_path']): ?>
                        <a href="../uploads/assessments/<?= htmlspecialchars($a['file_path']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= $a['assigned_at'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Student Submissions -->
    <h3 class="mt-5">📥 Student Submissions</h3>
    <table class="table table-bordered">
        <tr>
            <th>Assessment</th>
            <th>Course</th>
            <th>File</th>
            <th>Submitted At</th>
        </tr>
        <?php foreach($assessments as $a): 
            // Find all submissions for this assessment
            $submissionFiles = glob("../uploads/submitted_local_assessments/*_{$a['assessment_id']}_*");
            if ($submissionFiles):
                foreach ($submissionFiles as $filePath):
        ?>
            <tr>
                <td><?= htmlspecialchars($a['assessment_title']) ?></td>
                <td><?= htmlspecialchars($a['course_name']) ?></td>
                <td><a href="<?= $filePath ?>" target="_blank">Download</a></td>
                <td><?= date("d M Y H:i", filemtime($filePath)) ?></td>
            </tr>
        <?php 
                endforeach;
            endif;
        endforeach; ?>
    </table>
</div>
</body>
</html>

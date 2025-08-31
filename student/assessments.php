<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Only students
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'];
$message = "";

// ✅ Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assessment_id'])) {
    $assessment_id = intval($_POST['assessment_id']);

    if (isset($_FILES['submission']) && $_FILES['submission']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/submitted_local_assessments/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // unique filename => studentID_assessmentID_filename
        $fileName = $student_id . "_" . $assessment_id . "_" . basename($_FILES['submission']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['submission']['tmp_name'], $targetPath)) {
            $message = "✅ File submitted successfully!";
        } else {
            $message = "❌ Failed to save file!";
        }
    } else {
        $message = "❌ No file selected or upload error!";
    }
}

// Fetch student enrolled local courses
$enrolled_courses = $pdo->prepare("SELECT local_course_id FROM local_course_enrollments WHERE student_id = ?");
$enrolled_courses->execute([$student_id]);
$course_ids = $enrolled_courses->fetchAll(PDO::FETCH_COLUMN);

if (!$course_ids) {
    $assessments = [];
} else {
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $assessments = $pdo->prepare("
        SELECT a.*, lc.course_name
        FROM assessments a
        JOIN local_courses lc ON a.local_course_id = lc.local_course_id
        WHERE a.local_course_id IN ($placeholders)
        ORDER BY a.assigned_at DESC
    ");
    $assessments->execute($course_ids);
    $assessments = $assessments->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Local Course Assessments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2>📚 My Assessments</h2>

    <?php if($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if($assessments): ?>
        <table class="table table-bordered">
            <tr>
                <th>Title</th><th>Course</th><th>Due Date</th><th>File</th><th>Submit</th>
            </tr>
            <?php foreach($assessments as $a): 
                // check if already submitted
                $submittedFile = glob("../uploads/submitted_local_assessments/{$student_id}_{$a['assessment_id']}_*");
            ?>
                <tr>
                    <td><?= htmlspecialchars($a['assessment_title']) ?></td>
                    <td><?= htmlspecialchars($a['course_name']) ?></td>
                    <td><?= date("d M Y", strtotime($a['due_date'])) ?></td>
                    <td>
                        <?php if ($a['file_path']): ?>
                            <a href="../uploads/assessments/<?= htmlspecialchars($a['file_path']) ?>" target="_blank">Download</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($submittedFile): ?>
                            <span class="badge bg-success">✅ Submitted</span>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data" class="d-flex">
                                <input type="hidden" name="assessment_id" value="<?= $a['assessment_id'] ?>">
                                <input type="file" name="submission" class="form-control form-control-sm me-2" required>
                                <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No assessments assigned yet for your enrolled local courses.</div>
    <?php endif; ?>
</div>
</body>
</html>

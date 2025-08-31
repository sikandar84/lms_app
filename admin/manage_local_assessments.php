<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Step 1: Get list of local courses
$courses = $pdo->query("SELECT * FROM local_courses")->fetchAll(PDO::FETCH_ASSOC);

$studentsData = [];
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Step 2: Get students enrolled in this course
    $stmt = $pdo->prepare("
        SELECT ce.enrollment_id, u.user_id AS student_id, u.full_name, u.email
        FROM course_enrollments ce
        JOIN users u ON ce.student_id = u.user_id
        WHERE ce.course_id = ?
    ");
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 3: For each student, check their assessments + submissions
    foreach ($students as $student) {
        $stmt2 = $pdo->prepare("
            SELECT a.id AS assessment_id, a.title, a.due_date, sa.file_path, sa.status
            FROM assessments a
            LEFT JOIN submitted_assessments sa 
                ON a.id = sa.assessment_id AND sa.student_id = ?
            WHERE a.course_id = ?
        ");
        $stmt2->execute([$student['student_id'], $course_id]);
        $assessments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $studentsData[] = [
            'student' => $student,
            'assessments' => $assessments
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submitted Assessments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">📂 View Submitted Assessments</h2>

        <!-- Course selection -->
        <form method="get" class="mb-4">
            <label for="course_id" class="form-label">Select Local Course:</label>
            <select name="course_id" id="course_id" class="form-select" required>
                <option value="">-- Choose Course --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['local_course_id']; ?>" 
                        <?= (isset($_GET['course_id']) && $_GET['course_id'] == $c['local_course_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary mt-2">View</button>
        </form>

        <?php if (!empty($studentsData)): ?>
            <?php foreach ($studentsData as $data): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        👨‍🎓 <?= htmlspecialchars($data['student']['name']); ?> 
                        (<?= htmlspecialchars($data['student']['email']); ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['assessments'])): ?>
                            <p>No assessments assigned for this course.</p>
                        <?php else: ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Assessment</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Submitted File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['assessments'] as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['title']); ?></td>
                                            <td><?= htmlspecialchars($a['due_date']); ?></td>
                                            <td>
                                                <?= $a['status'] ? htmlspecialchars($a['status']) : 'Not Submitted'; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($a['file_path'])): ?>
                                                    <a href="../uploads/assessments/<?= htmlspecialchars($a['file_path']); ?>" target="_blank" class="btn btn-sm btn-success">View File</a>
                                                <?php else: ?>
                                                    ❌ No File
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (isset($_GET['course_id'])): ?>
            <p>No students enrolled in this course.</p>
        <?php endif; ?>
    </div>
</body>
</html>

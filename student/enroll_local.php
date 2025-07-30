<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Redirect non-students
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'];
$message = "";

// ✅ Check visa decision
$visa_stmt = $pdo->prepare("SELECT visa_decision FROM visa_status WHERE student_id = ?");
$visa_stmt->execute([$student_id]);
$visa = $visa_stmt->fetch(PDO::FETCH_ASSOC);
$can_enroll = $visa && strtolower($visa['visa_decision']) === 'rejected';

// ✅ Check if already enrolled in local course
$check = $pdo->prepare("
    SELECT e.*, lc.course_name
    FROM local_course_enrollments e
    JOIN local_courses lc ON e.local_course_id = lc.local_course_id
    WHERE e.student_id = ?
");
$check->execute([$student_id]);
$enrolled = $check->fetch(PDO::FETCH_ASSOC);

// ✅ Handle enrollment
if ($can_enroll && isset($_POST['enroll']) && !$enrolled) {
    $local_course_id = intval($_POST['local_course_id']);
    $stmt = $pdo->prepare("INSERT INTO local_course_enrollments (student_id, local_course_id) VALUES (?, ?)");
    if ($stmt->execute([$student_id, $local_course_id])) {
        header("Location: enroll_local.php?msg=success");
        exit();
    } else {
        $message = "❌ Enrollment failed.";
    }
}

// ✅ Fetch all local courses
$courses = $pdo->query("SELECT * FROM local_courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Local Course Enrollment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { max-width: 700px; margin-top: 60px; }
  </style>
</head>
<body>
<div class="container">
  <h3 class="mb-4">📘 Local Course Enrollment</h3>
  <a href="studentdashboard.php" class="btn btn-outline-secondary mb-3">🏠 Back to Dashboard</a>

  <?php if (!$can_enroll): ?>
    <div class="alert alert-warning">
      ⚠️ You can only enroll in local courses if your visa application has been <strong>rejected</strong>.
    </div>
  <?php elseif ($enrolled): ?>
    <div class="alert alert-success">
      ✅ You are already enrolled in: <strong><?= htmlspecialchars($enrolled['course_name']) ?></strong>
    </div>
  <?php else: ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
      <div class="alert alert-success">✅ Successfully enrolled in selected course.</div>
    <?php elseif ($message): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label for="local_course_id" class="form-label">Select a Local Course</label>
        <select name="local_course_id" id="local_course_id" class="form-select" required>
          <option value="">-- Choose a course --</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c['local_course_id'] ?>">
              <?= htmlspecialchars($c['course_name']) ?> (<?= htmlspecialchars($c['course_duration']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="enroll" class="btn btn-primary">📥 Enroll</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = $error = "";

// ➕ Handle New Course Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['course_name']);
    $desc = trim($_POST['course_description']);
    $duration = trim($_POST['course_duration']);

    if ($name && $desc && $duration) {
        $stmt = $pdo->prepare("INSERT INTO local_courses (course_name, course_description, course_duration) VALUES (?, ?, ?)");
        $stmt->execute([$name, $desc, $duration]);
        $success = "✅ Local course added successfully!";
    } else {
        $error = "❌ All fields are required.";
    }
}

// ❌ Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM local_courses WHERE local_course_id = ?")->execute([$id]);
    header("Location: manage_local_courses.php?msg=Deleted successfully");
    exit();
}

// 📋 Fetch all local courses
$courses = $pdo->query("SELECT * FROM local_courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// 📋 Fetch enrollments (students who chose courses)
$enrollments = $pdo->query("
    SELECT e.enrollment_id, e.enrolled_at, s.full_name AS student_name, s.email, lc.course_name, lc.course_duration
    FROM local_course_enrollments e
    JOIN users s ON e.student_id = s.user_id
    JOIN local_courses lc ON e.local_course_id = lc.local_course_id
    ORDER BY e.enrolled_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Local Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .container { max-width: 1000px; margin-top: 40px; }
    .card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
  </style>
</head>
<body>
<div class="container">
  <h3 class="mb-4">🏫 Manage Local Courses</h3>
  <a href="admindashboard.php" class="btn btn-outline-secondary mb-3">🏠 Back to Dashboard</a>

  <!-- ✅ Alerts -->
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>

  <!-- ➕ Add Course -->
  <div class="card p-4 mb-4">
    <h5>Add New Local Course</h5>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Course Name</label>
        <input type="text" name="course_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Course Description</label>
        <textarea name="course_description" class="form-control" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Course Duration</label>
        <input type="text" name="course_duration" class="form-control" placeholder="e.g. 6 months" required>
      </div>
      <button type="submit" name="add" class="btn btn-primary">➕ Add Course</button>
    </form>
  </div>

  <!-- 📋 Existing Courses -->
  <div class="card p-4 mb-4">
    <h5>📚 Local Courses List</h5>
    <?php if (empty($courses)): ?>
      <p>No local courses added yet.</p>
    <?php else: ?>
      <table class="table table-bordered table-hover align-middle mt-3">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Duration</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td><?= $c['local_course_id'] ?></td>
              <td><?= htmlspecialchars($c['course_name']) ?></td>
              <td><?= htmlspecialchars($c['course_duration']) ?></td>
              <td><?= $c['created_at'] ?></td>
              <td>
                <a href="?delete=<?= $c['local_course_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this course?')">🗑 Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- 👨‍🎓 Students Enrolled in Local Courses -->
  <div class="card p-4">
    <h5>👨‍🎓 Student Enrollments</h5>
    <?php if (empty($enrollments)): ?>
      <p>No student has enrolled in any local course yet.</p>
    <?php else: ?>
      <table class="table table-striped table-hover align-middle mt-3">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Duration</th>
            <th>Enrolled At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enrollments as $e): ?>
            <tr>
              <td><?= $e['enrollment_id'] ?></td>
              <td><?= htmlspecialchars($e['student_name']) ?></td>
              <td><?= htmlspecialchars($e['email']) ?></td>
              <td><?= htmlspecialchars($e['course_name']) ?></td>
              <td><?= htmlspecialchars($e['course_duration']) ?></td>
              <td><?= $e['enrolled_at'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>

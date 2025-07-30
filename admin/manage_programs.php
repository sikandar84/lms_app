<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Admin-only access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Add Program
if (isset($_POST['add_program'])) {
    $program_name = trim($_POST['program_name']);
    $university_id = intval($_POST['university_id']);
    $duration = trim($_POST['duration']);
    $tuition_fee = floatval($_POST['tuition_fee']);

    if ($program_name && $university_id && $duration) {
        $stmt = $pdo->prepare("INSERT INTO programs (program_name, university_id, duration, tuition_fee) VALUES (?, ?, ?, ?)");
        $stmt->execute([$program_name, $university_id, $duration, $tuition_fee]);
    }
    header("Location: manage_programs.php");
    exit();
}

// ✅ Update Program
if (isset($_POST['update_program'])) {
    $program_id = intval($_POST['program_id']);
    $program_name = trim($_POST['program_name']);
    $university_id = intval($_POST['university_id']);
    $duration = trim($_POST['duration']);
    $tuition_fee = floatval($_POST['tuition_fee']);

    $stmt = $pdo->prepare("UPDATE programs SET program_name = ?, university_id = ?, duration = ?, tuition_fee = ? WHERE program_id = ?");
    $stmt->execute([$program_name, $university_id, $duration, $tuition_fee, $program_id]);
    header("Location: manage_programs.php");
    exit();
}

// ✅ Delete Program
if (isset($_GET['delete_program'])) {
    $id = intval($_GET['delete_program']);
    $pdo->prepare("DELETE FROM programs WHERE program_id = ?")->execute([$id]);
    header("Location: manage_programs.php");
    exit();
}

// ✅ Add Course
if (isset($_POST['add_course'])) {
    $program_id = intval($_POST['program_id']);
    $course_name = trim($_POST['course_name']);
    $course_description = trim($_POST['course_description']);

    if ($program_id && $course_name) {
        $stmt = $pdo->prepare("INSERT INTO courses (program_id, course_name, course_description) VALUES (?, ?, ?)");
        $stmt->execute([$program_id, $course_name, $course_description]);
    }
    header("Location: manage_programs.php");
    exit();
}

// ✅ Delete Course
if (isset($_GET['delete_course'])) {
    $id = intval($_GET['delete_course']);
    $pdo->prepare("DELETE FROM courses WHERE course_id = ?")->execute([$id]);
    header("Location: manage_programs.php");
    exit();
}

// ✅ Fetch universities
$universities = $pdo->query("SELECT * FROM universities ORDER BY university_name ASC")->fetchAll();

// ✅ Fetch programs with university names
$programs = $pdo->query("
    SELECT p.*, u.university_name 
    FROM programs p 
    JOIN universities u ON p.university_id = u.university_id 
    ORDER BY p.program_id DESC
")->fetchAll();

// ✅ Fetch courses grouped by program_id
$courses = [];
$course_stmt = $pdo->query("SELECT * FROM courses");
while ($row = $course_stmt->fetch(PDO::FETCH_ASSOC)) {
    $courses[$row['program_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Programs & Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; }
    .container { margin-top: 40px; }
    .collapse-form { background: #f8f9fa; border-left: 4px solid #0d6efd; }
  </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">🎓 Manage University Programs & Courses</h2>
    <a href="admindashboard.php" class="btn btn-outline-secondary mb-4">🏠 Back to Home</a>

    <!-- ✅ Add Program Form -->
    <div class="card p-4 shadow-sm rounded-4 mb-4">
        <h5>Add New Program</h5>
        <form method="post">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Program Name</label>
                    <input type="text" name="program_name" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>University</label>
                    <select name="university_id" class="form-select" required>
                        <option value="">Select University</option>
                        <?php foreach ($universities as $uni): ?>
                            <option value="<?= $uni['university_id'] ?>"><?= htmlspecialchars($uni['university_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Duration</label>
                    <input type="text" name="duration" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Tuition Fee</label>
                    <input type="number" step="0.01" name="tuition_fee" class="form-control" required>
                </div>
            </div>
            <button name="add_program" class="btn btn-success">➕ Add Program</button>
        </form>
    </div>

    <!-- ✅ Programs Table -->
    <div class="card p-4 shadow-sm rounded-4">
        <h5>All Programs with Courses</h5>
        <table class="table table-bordered align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Program</th>
                    <th>University</th>
                    <th>Duration</th>
                    <th>Fee</th>
                    <th>Courses</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($programs as $index => $p): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($p['program_name']) ?></td>
                    <td><?= htmlspecialchars($p['university_name']) ?></td>
                    <td><?= htmlspecialchars($p['duration']) ?></td>
                    <td><?= number_format($p['tuition_fee'], 2) ?></td>
                    <td>
                        <?php if (!empty($courses[$p['program_id']])): ?>
                            <ul class="mb-2">
                                <?php foreach ($courses[$p['program_id']] as $c): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($c['course_name']) ?>:</strong>
                                        <?= htmlspecialchars($c['course_description']) ?>
                                        <a href="?delete_course=<?= $c['course_id'] ?>" class="text-danger ms-2" onclick="return confirm('Delete course?')">✖</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No courses added yet</em>
                        <?php endif; ?>

                        <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#addCourse<?= $p['program_id'] ?>">
                            ➕ Add Course
                        </button>

                        <!-- ✅ Add Course Collapsible Form -->
                        <div class="collapse mt-2" id="addCourse<?= $p['program_id'] ?>">
                            <form method="post" class="p-3 rounded collapse-form">
                                <input type="hidden" name="program_id" value="<?= $p['program_id'] ?>">
                                <div class="mb-2">
                                    <label class="form-label">Course Name</label>
                                    <input type="text" name="course_name" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Course Description</label>
                                    <textarea name="course_description" class="form-control" rows="2"></textarea>
                                </div>
                                <button name="add_course" class="btn btn-sm btn-success">Add Course</button>
                            </form>
                        </div>
                    </td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="program_id" value="<?= $p['program_id'] ?>">
                            <input type="hidden" name="program_name" value="<?= htmlspecialchars($p['program_name']) ?>">
                            <input type="hidden" name="university_id" value="<?= $p['university_id'] ?>">
                            <input type="hidden" name="duration" value="<?= htmlspecialchars($p['duration']) ?>">
                            <input type="hidden" name="tuition_fee" value="<?= $p['tuition_fee'] ?>">
                            <button name="update_program" class="btn btn-sm btn-primary">Update</button>
                            <a href="?delete_program=<?= $p['program_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this program?')">Delete</a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

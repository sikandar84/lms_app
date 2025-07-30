<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Fix for student_id: Must match your users table's `user_id`
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'] ?? null; // ✅ this must be user_id from session
$full_name = $_SESSION['full_name'] ?? 'Student';

$success = $error = "";
$programs = [];
$courses = [];
$selected_university_id = $_GET['university_id'] ?? null;
$selected_program_id = $_POST['program_id'] ?? null;

try {
    // Fetch programs by university
    if ($selected_university_id) {
        $stmt = $pdo->prepare("SELECT p.*, u.university_name FROM programs p JOIN universities u ON p.university_id = u.university_id WHERE p.university_id = ? ORDER BY p.program_id DESC");
        $stmt->execute([$selected_university_id]);
    } else {
        $stmt = $pdo->query("SELECT p.*, u.university_name FROM programs p JOIN universities u ON p.university_id = u.university_id ORDER BY p.program_id DESC");
    }
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch courses if program selected
    if ($selected_program_id) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE program_id = ?");
        $stmt->execute([$selected_program_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "❌ Failed to fetch data: " . $e->getMessage();
}

// ✅ Handle Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_now'])) {
    $program_id = intval($_POST['program_id']);
    $name = trim($_POST['full_name']);
    $age = intval($_POST['age']);
    $gpa = trim($_POST['gpa']);
    $graduated = $_POST['graduated'];

    $upload_dir = "../uploads/documents/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $fields = ['matric_card', 'fsc_card', 'transcript', 'resume'];
    $upload_paths = [];

    foreach ($fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            $filename = uniqid($field . "_") . "." . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                $upload_paths[$field] = $filename;
            } else {
                $error = "❌ Failed to upload: " . ucfirst(str_replace('_', ' ', $field));
                break;
            }
        }
    }

    if (empty($error) && $student_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO applications 
                (student_id, program_id, full_name, age, gpa, graduated, matric_card, fsc_card, transcript, resume) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $student_id,
                $program_id,
                $name,
                $age,
                $gpa,
                $graduated,
                $upload_paths['matric_card'] ?? null,
                $upload_paths['fsc_card'] ?? null,
                $upload_paths['transcript'] ?? null,
                $upload_paths['resume'] ?? null
            ]);
            $success = "✅ Application submitted successfully!";
        } catch (PDOException $e) {
            $error = "❌ Database error: " . $e->getMessage();
        }
    } else if (!$student_id) {
        $error = "❌ Session issue: student ID missing.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Apply for Program</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 900px; }
    .card { background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-2">🎓 Apply for a Program</h2>
  <a href="studentdashboard.php" class="btn btn-outline-secondary mb-4">🏠 Back to Dashboard</a>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (empty($programs)): ?>
    <div class="alert alert-warning">⚠️ No programs found<?= $selected_university_id ? " for selected university." : "." ?></div>
  <?php else: ?>
  <form method="POST" enctype="multipart/form-data" class="card">
    <div class="mb-3">
      <label class="form-label">Select Program</label>
      <select name="program_id" class="form-select" required onchange="this.form.submit()">
        <option value="">-- Choose a Program --</option>
        <?php foreach ($programs as $program): ?>
          <option value="<?= $program['program_id'] ?>" <?= ($program['program_id'] == $selected_program_id) ? 'selected' : '' ?>>
            <?= htmlspecialchars($program['program_name']) ?> - <?= htmlspecialchars($program['university_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if (!empty($courses)): ?>
    <div class="mb-3">
      <label class="form-label">Available Courses under this Program:</label>
      <ul class="list-group">
        <?php foreach ($courses as $course): ?>
          <li class="list-group-item">🎓 <?= htmlspecialchars($course['course_name']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="mb-3"><label class="form-label">Your Full Name</label><input type="text" name="full_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Age</label><input type="number" name="age" class="form-control" required min="1"></div>
    <div class="mb-3"><label class="form-label">GPA</label><input type="text" name="gpa" class="form-control" required></div>
    <div class="mb-3">
      <label class="form-label">Graduated?</label>
      <select name="graduated" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
      </select>
    </div>

    <div class="mb-3"><label class="form-label">Matric Result Card</label><input type="file" name="matric_card" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">FSC Result Card</label><input type="file" name="fsc_card" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Transcript (optional)</label><input type="file" name="transcript" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Resume (optional)</label><input type="file" name="resume" class="form-control"></div>

    <button class="btn btn-primary w-100" type="submit" name="apply_now">📤 Submit Application</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");
require_once("../includes/send_email.php"); // PHPMailer

// Only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all local courses
$stmt = $pdo->query("SELECT * FROM courses ORDER BY course_id DESC");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find students whose visa is rejected
$stmt = $pdo->query("
    SELECT v.student_id, u.full_name, u.email 
    FROM visa_status v
    JOIN users u ON v.student_id = u.user_id
    WHERE v.visa_decision = 'rejected'
");
$rejectedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send courses email with attachment
if (isset($_GET['send']) && isset($_GET['student_id'])) {
    $studentId = intval($_GET['student_id']);

    // Get student info
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $email = $student['email'];
        $name = $student['full_name'];
        // Build course list in HTML
        $courseListHtml = "<ul>";
        foreach ($courses as $course) {
            $courseListHtml .= "<li><b>" . htmlspecialchars($course['course_name']) . "</b>: " 
                             . htmlspecialchars($course['course_description']) . "</li>";
        }
        $courseListHtml .= "</ul>";

        // Prepare professional message
        $message = "
        <p>Dear {$name},</p>

        <p>We regret to inform you that your visa application has been <b style='color:red;'>rejected</b>.</p>

        <p>However, we are pleased to offer you the opportunity to continue your studies locally. 
        Below are the available <b>local courses</b> you can enroll in:</p>

        {$courseListHtml}

        <p>For further details and enrollment assistance, please contact our support team.</p>

        <br>
        <p>Best Regards,<br>
        <b>LMS Administration</b></p>
        ";


        // Create a sample file (PDF or txt) with course list
        $filePath = "../uploads/documents/local_courses_list.txt";
        $fileContent = "Available Local Courses:\n\n";
        foreach ($courses as $course) {
            $fileContent .= "- " . $course['course_name'] . " (" . $course['course_description'] . ")\n";
        }
        file_put_contents($filePath, $fileContent);

        // Send email with attachment
        sendEmail($email, "Visa Rejected - Local Courses Offered", $message, $filePath);

        // Save notification
        $note = "Your visa was rejected. Local course options have been emailed to you.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$studentId, $note]);

        header("Location: manage_courses.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send Local Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Send Local Courses to Students (Visa Rejected)</h3>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Email with local courses sent successfully!</div>
  <?php endif; ?>

  <h5>📚 Available Local Courses</h5>
  <ul class="list-group mb-4">
    <?php foreach ($courses as $c): ?>
      <li class="list-group-item">
        <b><?= htmlspecialchars($c['course_name']) ?></b> - <?= htmlspecialchars($c['course_description']) ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <h5>👨‍🎓 Students with Rejected Visa</h5>
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rejectedStudents as $s): ?>
        <tr>
          <td><?= $s['student_id'] ?></td>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td>
            <a href="?send=1&student_id=<?= $s['student_id'] ?>" class="btn btn-sm btn-primary">
              Send Local Courses
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>
</body>
</html>

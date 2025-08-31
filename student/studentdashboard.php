<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Redirect if not student
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'];
$full_name = $_SESSION['full_name'] ?? 'Student';

// ✅ Fetch applications with program and university details
$stmt = $pdo->prepare("
    SELECT a.application_id, u.university_name, p.program_name, a.application_status AS application_status, a.applied_at
    FROM applications a
    JOIN programs p ON a.program_id = p.program_id
    JOIN universities u ON p.university_id = u.university_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$student_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch latest visa status
$stmt = $pdo->prepare("SELECT * FROM visa_status WHERE student_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$student_id]);
$visa = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch invoices
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE student_id = ? ORDER BY issued_at DESC");
$stmt->execute([$student_id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .change-btn {
      display: inline-block;
      background-color: #4CAF50;
      color: white;
      padding: 10px 18px;
      text-decoration: none;
      font-size: 16px;
      border-radius: 8px;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .change-btn:hover {
      background-color: #45a049;
      transform: scale(1.05);
    }
    .sidebar {
      background-color: #0d6efd;
      height: 100vh;
      color: white;
    }
    .sidebar a {
      color: white;
      display: block;
      padding: 15px;
      text-decoration: none;
    }
    .sidebar a:hover {
      background-color: #0b5ed7;
    }
    .content {
      padding: 30px;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <h4 class="text-center py-3">Student Panel</h4>
      <a href="studentdashboard.php">Dashboard</a>
      <a href="apply.php">Apply to Programs</a>
      <a href="upload_documents.php">Upload Documents</a>
      <a href="invoices.php">Invoices</a>
      <a href="visa_status.php">Visa Status</a>
      <a href="offer_letters.php">Offer Letters</a>
      <a href="enroll_local.php">Enroll Local Courses</a>
      <a href="assessments.php">Assessments</a>
      <a href="assessments.php">Submit Assessment</a>
      <a href="notifications.php">Notifications</a>
      <a href="change_credentials.php" class="change-btn">Change Email/Password</a>
      <a href="../auth/login.php" class="text-danger">Logout</a>
    </div>

    <div class="col-md-10 content">
      <h2 class="mb-4">Welcome, <?= htmlspecialchars($full_name) ?>!</h2>

      <h4 class="mt-4">🎓 My Applications</h4>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Application ID</th>
            <th>University</th>
            <th>Program</th>
            <th>Status</th>
            <th>Applied At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $app): ?>
            <tr>
              <td><?= $app['application_id'] ?></td>
              <td><?= htmlspecialchars($app['university_name']) ?></td>
              <td><?= htmlspecialchars($app['program_name']) ?></td>
              <td><?= ucfirst($app['application_status']) ?></td>
              <td><?= $app['applied_at'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h4 class="mt-5">💰 My Invoices</h4>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issued At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td><?= $inv['invoice_id'] ?></td>
              <td>Rs. <?= number_format($inv['amount'], 2) ?></td>
              <td><?= ucfirst($inv['status']) ?></td>
              <td><?= $inv['issued_at'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h4 class="mt-5">🛂 Visa Status</h4>
      <?php if ($visa): ?>
        <div class="alert alert-info">
          Visa Decision: <strong><?= ucfirst($visa['visa_decision']) ?></strong><br>
          Last Updated: <?= $visa['updated_at'] ?>
        </div>
      <?php else: ?>
        <div class="alert alert-warning">No visa status available yet.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

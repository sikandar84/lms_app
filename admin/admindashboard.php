<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once('../config/db.php');

try {
    // ✅ Count entries
    $studentsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $financeCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'finance'")->fetchColumn();
    $universitiesCount = $pdo->query("SELECT COUNT(*) FROM universities")->fetchColumn();

    // ✅ Recent students
    $students = $pdo->query("SELECT user_id, full_name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Recent applications
    $applications = $pdo->query("
        SELECT 
            a.application_id,
            u.full_name AS student_name,
            p.program_name,
            a.application_status,
            a.applied_at
        FROM applications a
        JOIN users u ON a.student_id = u.user_id
        JOIN programs p ON a.program_id = p.program_id
        ORDER BY a.applied_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - LMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { overflow-x: hidden; background-color: #f8f9fa; }
    .sidebar { background-color: #343a40; min-height: 100vh; color: white; }
    .sidebar a {
      display: block;
      padding: 12px 20px;
      color: white;
      text-decoration: none;
    }
    .sidebar a:hover { background-color: #495057; }
    .count-box {
      background: white;
      border-radius: 10px;
      text-align: center;
      padding: 20px;
      box-shadow: 0 0 8px rgba(0,0,0,0.05);
    }
    .count-box h4 { font-size: 24px; margin-bottom: 5px; }
    .count-box p { font-size: 14px; color: gray; }
    .table-container { margin-top: 20px; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar p-0">
      <h5 class="text-center py-3 border-bottom">Admin Panel</h5>
      <a href="admindashboard.php">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_universities.php">Universities</a>
      <a href="manage_programs.php">Programs</a>
      <a href="manage_courses.php">Courses</a>
      <a href="manage_applications.php">Applications</a>
      <a href="manage_visa.php">Manage Visa</a>
      <a href="manage_offer_letters.php">Offer Letters</a>
      <a href="manage_notifications.php">Notifications</a>
      <a href="manage_invoices.php">Invoices</a>
      <a href="register_finance.php">Register Finance</a>
      <a href="change_credentials.php" class="text-success ms-3">Change Credentials</a>
      <a href="logout.php" class="text-danger ms-3">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 p-4">
      <h3 class="mb-4">Welcome, Admin</h3>

      <!-- Count Boxes -->
      <div class="row g-3">
        <div class="col-md-4">
          <div class="count-box">
            <h4><?= $studentsCount ?></h4>
            <p>Students</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="count-box">
            <h4><?= $financeCount ?></h4>
            <p>Finance Users</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="count-box">
            <h4><?= $universitiesCount ?></h4>
            <p>Universities</p>
          </div>
        </div>
      </div>

      <!-- Recent Students -->
      <div class="table-container mt-4">
        <h5>Recent Students</h5>
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= $s['created_at'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Recent Applications -->
      <div class="table-container mt-4">
        <h5>Recent Applications</h5>
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Program</th>
              <th>Status</th>
              <th>Applied At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applications as $a): ?>
              <tr>
                <td><?= $a['application_id'] ?></td> <!-- ✅ Corrected -->
                <td><?= htmlspecialchars($a['student_name']) ?></td>
                <td><?= htmlspecialchars($a['program_name']) ?></td>
                <td><?= ucfirst($a['application_status']) ?></td>
                <td><?= $a['applied_at'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>

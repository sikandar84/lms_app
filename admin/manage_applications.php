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

// Approve Application
if (isset($_GET['approve'])) {
    $appId = intval($_GET['approve']);

    $stmt = $pdo->prepare("SELECT student_id FROM applications WHERE application_id = ?");
    $stmt->execute([$appId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $app['student_id'] ?? null;

    if ($student_id) {
        $pdo->prepare("UPDATE applications SET application_status = 'approved' WHERE application_id = ?")->execute([$appId]);

        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $user['email'] ?? '';

        $message = "🎉 Your application has been approved!";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student_id, $message]);

        sendEmail($email, "Application Approved", $message);
    }

    header("Location: manage_applications.php");
    exit();
}

// Reject Application
if (isset($_GET['reject'])) {
    $appId = intval($_GET['reject']);

    $stmt = $pdo->prepare("SELECT student_id FROM applications WHERE application_id = ?");
    $stmt->execute([$appId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $app['student_id'] ?? null;

    if ($student_id) {
        $pdo->prepare("UPDATE applications SET application_status = 'rejected' WHERE application_id = ?")->execute([$appId]);

        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $user['email'] ?? '';

        $message = "❌ Your application has been rejected. Please contact support.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student_id, $message]);

        sendEmail($email, "Application Rejected", $message);
    }

    header("Location: manage_applications.php");
    exit();
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT a.*, u.full_name, u.email, p.program_name, un.university_name 
    FROM applications a
    JOIN users u ON a.student_id = u.user_id
    JOIN programs p ON a.program_id = p.program_id
    JOIN universities un ON p.university_id = un.university_id
";
if ($statusFilter) {
    $sql .= " WHERE a.application_status = " . $pdo->quote($statusFilter);
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = $pdo->query($sql);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Applications</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { margin-top: 40px; }
    .table thead { background-color: #0d6efd; color: white; }
    .table-responsive { overflow-x: auto; }
  </style>
</head>
<body>
<div class="container">
  <h3 class="mb-4">Student Applications</h3>

  <!-- Filter Form -->
  <form method="GET" class="mb-3">
    <div class="row g-2">
      <div class="col-md-4">
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="">-- Filter by Status --</option>
          <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student</th>
          <th>Email</th>
          <th>Program</th>
          <th>University</th>
          <th>Graduated</th>
          <th>GPA</th>
          <th>Resume</th>
          <th>Matric</th>
          <th>FSC</th>
          <th>Transcript</th>
          <th>Status</th>
          <th>Applied At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($applications as $app): ?>
          <tr>
            <td><?= $app['application_id'] ?></td>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['email']) ?></td>
            <td><?= htmlspecialchars($app['program_name']) ?></td>
            <td><?= htmlspecialchars($app['university_name']) ?></td>
            <td><?= ucfirst($app['graduated']) ?></td>
            <td><?= htmlspecialchars($app['gpa']) ?></td>
            <td><?= $app['resume'] ? '<a class="btn btn-sm btn-outline-primary" href="../uploads/documents/' . htmlspecialchars($app['resume']) . '" target="_blank">View</a>' : 'N/A' ?></td>
            <td><?= $app['matric_card'] ? '<a class="btn btn-sm btn-outline-secondary" href="../uploads/documents/' . htmlspecialchars($app['matric_card']) . '" target="_blank">View</a>' : 'N/A' ?></td>
            <td><?= $app['fsc_card'] ? '<a class="btn btn-sm btn-outline-secondary" href="../uploads/documents/' . htmlspecialchars($app['fsc_card']) . '" target="_blank">View</a>' : 'N/A' ?></td>
            <td><?= $app['transcript'] ? '<a class="btn btn-sm btn-outline-secondary" href="../uploads/documents/' . htmlspecialchars($app['transcript']) . '" target="_blank">View</a>' : 'N/A' ?></td>
            <td>
              <span class="badge bg-<?= $app['application_status'] === 'approved' ? 'success' : ($app['application_status'] === 'rejected' ? 'danger' : 'warning text-dark') ?>">
                <?= ucfirst($app['application_status']) ?>
              </span>
            </td>
            <td><?= $app['applied_at'] ?></td>
            <td>
              <?php if ($app['application_status'] === 'pending'): ?>
                <a href="?approve=<?= $app['application_id'] ?>" class="btn btn-sm btn-success mb-1">Approve</a>
                <a href="?reject=<?= $app['application_id'] ?>" class="btn btn-sm btn-danger">Reject</a>
              <?php else: ?>
                <button class="btn btn-sm btn-secondary" disabled>No Action</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
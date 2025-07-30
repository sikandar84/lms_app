<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");
require_once("../includes/send_email.php"); // contains sendEmail($to, $subject, $body)

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Handle Approve / Reject
if (isset($_GET['approve']) || isset($_GET['reject'])) {
    $id = $_GET['approve'] ?? $_GET['reject'];
    $status = isset($_GET['approve']) ? 'approved' : 'rejected';

    // Update visa status
    $stmt = $pdo->prepare("UPDATE visa_status SET visa_decision = ?, updated_at = NOW() WHERE visa_id = ?");
    $stmt->execute([$status, $id]);

    // Get student details
    $info = $pdo->prepare("
        SELECT vs.student_id, u.email, u.full_name
        FROM visa_status vs
        JOIN users u ON vs.student_id = u.user_id
        WHERE vs.visa_id = ?
    ");
    $info->execute([$id]);
    $user = $info->fetch();

    if ($user) {
        // Add notification
        $msg = "🛂 Your visa application has been {$status}.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$user['student_id'], $msg]);

        // Send email
        $emailBody = "
            Dear {$user['full_name']},<br><br>
            Your visa application has been <strong>{$status}</strong>.<br>
            Please log in to your LMS account to view the latest status and details.<br><br>
            Best regards,<br>
            LMS Admin Team
        ";
        sendEmail($user['email'], "Visa Application Status - " . ucfirst($status), $emailBody);
    }

    header("Location: manage_visa.php?msg=Visa application has been " . urlencode($status));
    exit();
}

// ✅ Fetch all visa applications
$visas = $pdo->query("
    SELECT vs.*, u.full_name, u.email
    FROM visa_status vs
    JOIN users u ON vs.student_id = u.user_id
    ORDER BY vs.updated_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Visa Applications</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      background-color: #343a40;
      height: 100vh;
      color: #fff;
      padding-top: 20px;
    }
    .sidebar a {
      color: #fff;
      display: block;
      padding: 12px 20px;
      text-decoration: none;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
    .content {
      padding: 40px;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar">
      <h5 class="text-center">Admin Panel</h5>
      <a href="admindashboard.php">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_universities.php">Manage Universities</a>
      <a href="manage_programs.php">Manage Programs</a>
      <a href="manage_courses.php">Manage Local Courses</a>
      <a href="manage_applications.php">Manage Applications</a>
      <a href="manage_offer_letters.php">Manage Offer Letters</a>
      <a href="manage_notifications.php">Manage Notifications</a>
      <a href="manage_invoices.php">Manage Invoices</a>
      <a href="manage_visa.php" class="fw-bold text-warning">Manage Visa</a>
      <a href="register_finance.php">Register Finance User</a>
      <a href="logout.php" class="text-danger">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 content">
      <h3 class="mb-4">🛂 Visa Applications</h3>

      <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php endif; ?>

      <?php if (empty($visas)): ?>
        <div class="alert alert-info">No visa applications found.</div>
      <?php else: ?>
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Email</th>
              <th>Documents</th>
              <th>Status</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visas as $v): ?>
              <tr>
                <td><?= $v['visa_id'] ?></td>
                <td><?= htmlspecialchars($v['full_name']) ?></td>
                <td><?= htmlspecialchars($v['email']) ?></td>
                <td>
                  <?php if ($v['passport_file']): ?>
                    <a href="../uploads/documents/<?= htmlspecialchars($v['passport_file']) ?>" target="_blank">Passport</a><br>
                  <?php endif; ?>
                  <?php if ($v['visa_form']): ?>
                    <a href="../uploads/documents/<?= htmlspecialchars($v['visa_form']) ?>" target="_blank">Visa Form</a><br>
                  <?php endif; ?>
                  <?php if ($v['photo_file']): ?>
                    <a href="../uploads/documents/<?= htmlspecialchars($v['photo_file']) ?>" target="_blank">Photo</a>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge text-bg-<?= 
                    $v['visa_decision'] === 'approved' ? 'success' : 
                    ($v['visa_decision'] === 'rejected' ? 'danger' : 'warning text-dark') ?>">
                    <?= ucfirst($v['visa_decision']) ?>
                  </span>
                </td>
                <td><?= $v['updated_at'] ?? '—' ?></td>
                <td>
                  <?php if ($v['visa_decision'] === 'pending'): ?>
                    <a href="?approve=<?= $v['visa_id'] ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Approve this visa application?')">Approve</a>
                    <a href="?reject=<?= $v['visa_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this visa application?')">Reject</a>
                  <?php else: ?>
                    <button class="btn btn-sm btn-secondary" disabled>No Action</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

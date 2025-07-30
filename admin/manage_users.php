<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Only Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Delete student
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
    $stmt->execute([$deleteId]);
    header("Location: manage_users.php");
    exit;
}

// Fetch students
$students = $pdo->query("SELECT user_id, full_name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC")->fetchAll();

// Fetch finance
$financeUsers = $pdo->query("SELECT user_id, full_name, email, created_at FROM users WHERE role = 'finance' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Manage Student Users</h2>
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $i => $s): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td><?= $s['created_at'] ?></td>
          <td>
            <a href="?delete=<?= $s['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this student?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="mt-5">Finance Users</h2>
  <table class="table table-bordered">
    <thead class="table-primary">
      <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($financeUsers as $i => $f): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($f['full_name']) ?></td>
          <td><?= htmlspecialchars($f['email']) ?></td>
          <td><?= $f['created_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>

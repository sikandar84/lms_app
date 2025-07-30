<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once('../config/db.php');

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = "⚠️ Email already exists!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, 'finance', NOW())");
        $stmt->execute([$full_name, $email, $password]);
        $success = "✅ Finance user registered successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Finance User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f9;
      padding: 40px;
    }
    .form-container {
      max-width: 600px;
      margin: auto;
      padding: 30px;
      background-color: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .message {
      font-weight: 500;
      font-size: 16px;
    }
  </style>
</head>
<body>

<div class="form-container">
  <h3 class="mb-4 text-center">Register Finance User</h3>

  <?php if ($success): ?>
    <div class="alert alert-success message"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-warning message"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <div class="mb-3">
      <label for="full_name" class="form-label">Full Name</label>
      <input type="text" class="form-control" id="full_name" name="full_name" required autofocus>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Set Password</label>
      <input type="password" class="form-control" id="password" name="password" required minlength="6">
    </div>
    <button type="submit" class="btn btn-primary w-100">Register Finance</button>
  </form>

  <div class="text-center mt-3">
    <a href="admindashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
  </div>
</div>

</body>
</html>

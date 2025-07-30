<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Check if admin is logged in
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id']; // This is user_id from users table
$full_name = $_SESSION['full_name'] ?? 'Admin';
$email = $_SESSION['email'] ?? null;

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        // ✅ Fix: Correct column name is user_id
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($old_password, $user['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed_new_password, $user_id])) {

                // ✅ Add notification
                $note = "Your password has been changed successfully.";
                $stmt2 = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                $stmt2->execute([$user_id, $note]);

                // ✅ Send confirmation email
                if ($email) {
                    $subject = "Password Changed Successfully";
                    $body = "Hello $full_name,\n\nThis is a confirmation that your LMS admin account password has been changed.\n\nIf this wasn’t you, please contact support immediately.\n\nRegards,\nLMS Team";
                    require_once("../includes/mailer.php");
                    sendEmail($email, $subject, $body);
                }

                $_SESSION['success'] = "Password changed successfully! Please log in again.";
                session_destroy();
                header("Location: ../auth/login.php");
                exit();
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Old password is incorrect.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg, #2c3e50, #3498db);
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 600px;
      margin: 80px auto;
      background-color: rgba(255,255,255,0.9);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .form-control {
      border-radius: 10px;
    }
    .btn-primary {
      border-radius: 10px;
      width: 100%;
      padding: 10px;
      font-weight: bold;
    }
    .message {
      text-align: center;
      margin-top: 20px;
      font-weight: bold;
    }
    .goback {
      position: absolute;
      top: 20px;
      left: 20px;
    }
  </style>
</head>
<body>
<a href="admindashboard.php" class="btn btn-warning goback">← Go Back</a>
<div class="container">
  <h2>Change Password</h2>

  <?php if ($success): ?>
    <div class="alert alert-success message"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger message"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label for="old_password" class="form-label">Old Password</label>
      <input type="password" name="old_password" id="old_password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="new_password" class="form-label">New Password</label>
      <input type="password" name="new_password" id="new_password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm New Password</label>
      <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="showPasswordToggle" onclick="togglePassword()">
      <label class="form-check-label" for="showPasswordToggle">
        Show Passwords
      </label>
    </div>

    <button type="submit" class="btn btn-primary">Update Password</button>
  </form>
</div>

<script>
  function togglePassword() {
    const fields = ['old_password', 'new_password', 'confirm_password'];
    fields.forEach(id => {
      const field = document.getElementById(id);
      field.type = field.type === 'password' ? 'text' : 'password';
    });
  }
</script>
</body>
</html>

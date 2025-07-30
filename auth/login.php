<?php
session_start();
require_once('../config/db.php');

$error = '';
$email = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Please enter a valid email.";
    } elseif (empty($password)) {
        $error = "⚠️ Password is required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // ✅ Set correct session values
                $_SESSION['id'] = $user['user_id']; // Important fix!
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect to role-based dashboard
                if ($user['role'] === 'student') {
                    header('Location: ../student/studentdashboard.php');
                } elseif ($user['role'] === 'admin') {
                    header('Location: ../admin/admindashboard.php');
                } elseif ($user['role'] === 'finance') {
                    header('Location: ../finance/financedashboard.php');
                }
                exit;
            } else {
                $error = "❌ Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "❌ Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - LMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f6f9;
    }
    .card {
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card p-4">
        <h3 class="text-center mb-3">🔐 LMS Login</h3>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <button class="btn btn-primary w-100">Login</button>
        </form>

        <p class="mt-3 text-center">
          New student? <a href="register.php">Register here</a><br>
          <a href="forgot_password.php">Forgot password?</a>
        </p>
      </div>
    </div>
  </div>
</div>

</body>
</html>

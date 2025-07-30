<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Only allow student
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$full_name = $_SESSION['full_name'] ?? 'Student';
$email = $_SESSION['email'] ?? null;

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "⚠️ All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "❌ New password and confirm password do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($old_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($update->execute([$hashed, $user_id])) {
                // Add notification
                $msg = "Your password has been changed successfully.";
                $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())")
                    ->execute([$user_id, $msg]);

                // Send email
                if ($email) {
                    require_once("../includes/mailer.php");
                    $subject = "Password Changed Successfully";
                    $body = "Hi $full_name,<br><br>Your LMS password was changed successfully.<br>If this wasn't you, please contact support immediately.<br><br>— LMS Team";
                    sendEmail($email, $subject, $body);
                }

                $success = "✅ Password changed successfully!";
            } else {
                $error = "❌ Failed to update password.";
            }
        } else {
            $error = "❌ Old password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #f1f8e9);
            font-family: 'Segoe UI', sans-serif;
        }
        .form-container {
            max-width: 500px;
            margin: 80px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
        }
        .btn-back {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2>🔒 Change Password</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Old Password</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary w-100">🔁 Update Password</button>
        </form>

        <div class="btn-back mt-4">
            <a href="studentdashboard.php" class="btn btn-outline-secondary">🏠 Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>

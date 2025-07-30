<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Ensure student is logged in
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'Student';

$notifications = [];

if ($student_id) {
    // ✅ Get notifications for the student
    $stmt = $pdo->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #1e3c72, #2a5298);
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .container {
      max-width: 900px;
      margin: 60px auto;
      background: rgba(255,255,255,0.05);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
    }

    .notification {
      background: rgba(255,255,255,0.1);
      border-left: 5px solid #00d4ff;
      padding: 15px 20px;
      margin-bottom: 15px;
      border-radius: 10px;
      transition: all 0.3s ease-in-out;
    }

    .notification:hover {
      transform: scale(1.02);
      background: rgba(255,255,255,0.15);
    }

    .date {
      font-size: 0.9rem;
      color: #ccc;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Hello, <?= htmlspecialchars($full_name) ?> 👋 – Your Notifications</h2>

  <?php if (!empty($notifications)): ?>
    <?php foreach ($notifications as $note): ?>
      <div class="notification">
        <p><?= htmlspecialchars($note['message']) ?></p>
        <div class="date"><?= date("F j, Y, g:i a", strtotime($note['created_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info text-center">No notifications yet.</div>
  <?php endif; ?>
</div>

</body>
</html>

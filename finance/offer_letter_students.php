<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

$success = $error = "";

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_FILES['offer_pdf'])) {
    $student_id = intval($_POST['student_id']);
    $file = $_FILES['offer_pdf'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $folder = "../uploads/offer_letters/";
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $filename = uniqid("offer_") . ".pdf";
            $destination = $folder . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("INSERT INTO offer_letters (student_id, filename, issued_at) VALUES (?, ?, NOW())");
                $stmt->execute([$student_id, $filename]);
                $success = "✅ Offer letter uploaded.";
            } else {
                $error = "❌ Failed to save file.";
            }
        } else {
            $error = "❌ Only PDF files allowed.";
        }
    } else {
        $error = "❌ File upload error.";
    }
}

// Fetch students
$students = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'student' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch uploaded letters
$stmt = $pdo->query("SELECT o.*, u.full_name, u.email FROM offer_letters o JOIN users u ON o.student_id = u.user_id ORDER BY o.issued_at DESC");
$letters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Offer Letters</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; padding: 30px; }
    .container { max-width: 900px; margin: auto; }
    .card { padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4">📩 Upload Offer Letter</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="card mb-5">
    <div class="mb-3">
      <label class="form-label">Select Student</label>
      <select name="student_id" class="form-select" required>
        <option value="">-- Choose Student --</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Upload PDF</label>
      <input type="file" name="offer_pdf" class="form-control" accept="application/pdf" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">📤 Upload Offer Letter</button>
  </form>

  <h3>📃 Issued Offer Letters</h3>
  <table class="table table-bordered bg-white">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Email</th>
        <th>Letter</th>
        <th>Issued</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($letters) > 0): ?>
        <?php foreach ($letters as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><a class="btn btn-sm btn-success" href="../uploads/offer_letters/<?= $row['filename'] ?>" target="_blank">View</a></td>
            <td><?= $row['issued_at'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No offer letters uploaded.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>

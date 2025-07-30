<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'];
$success = $error = "";

// Check if application is approved
$stmt = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? AND application_status = 'approved'");
$stmt->execute([$student_id]);
$approvedApp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$approvedApp) {
    die("<div style='padding:30px'><h3 style='color:red;'>❌ Visa application is only available after your application is approved.</h3><a href='studentdashboard.php' class='btn btn-secondary mt-3'>🏠 Back to Dashboard</a></div>");
}

// Check existing visa
$stmt = $pdo->prepare("SELECT * FROM visa_status WHERE student_id = ?");
$stmt->execute([$student_id]);
$visa = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Handle new submission (only if visa not submitted or rejected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$visa || $visa['visa_decision'] === 'rejected')) {
    $upload_dir = "../uploads/documents/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $passport_file = $_FILES['passport_file'];
    $visa_form = $_FILES['visa_form'];
    $photo_file = $_FILES['photo_file'];

    function saveFile($file, $prefix, $upload_dir) {
        if (!empty($file['name'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid($prefix . "_") . "." . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                return $filename;
            }
        }
        return null;
    }

    $passport = saveFile($passport_file, "passport", $upload_dir);
    $visa_form = saveFile($visa_form, "visa_form", $upload_dir);
    $photo = saveFile($photo_file, "photo", $upload_dir);

    if ($passport && $visa_form && $photo) {
        if ($visa && $visa['visa_decision'] === 'rejected') {
            // Reapply = Update existing row
            $stmt = $pdo->prepare("UPDATE visa_status SET passport_file=?, visa_form=?, photo_file=?, visa_decision='pending', updated_at=NOW() WHERE student_id=?");
            $stmt->execute([$passport, $visa_form, $photo, $student_id]);
        } else {
            // New insert
            $stmt = $pdo->prepare("INSERT INTO visa_status (student_id, passport_file, visa_form, photo_file) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $passport, $visa_form, $photo]);
        }

        $stmt = $pdo->prepare("SELECT * FROM visa_status WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $visa = $stmt->fetch(PDO::FETCH_ASSOC);

        $success = "✅ Visa application submitted successfully!";
    } else {
        $error = "❌ Failed to upload one or more files.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visa Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; margin-top: 40px; }
        .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-3">🛂 Visa Application</h3>
    <a href="studentdashboard.php" class="btn btn-outline-secondary mb-3">🏠 Back to Dashboard</a>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php
    $visa_decision = $visa['visa_decision'] ?? 'pending';

    // ✅ Show form only if no visa OR it was rejected
    if (!$visa || $visa_decision === 'rejected'):
    ?>
        <form method="POST" enctype="multipart/form-data" class="card">
            <h5><?= $visa ? "❌ Your previous visa was rejected. Please re-apply." : "📤 Submit your visa documents." ?></h5>
            <div class="mb-3">
                <label class="form-label">📄 Passport Scan (PDF or Image)</label>
                <input type="file" name="passport_file" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">📄 Visa Form (PDF)</label>
                <input type="file" name="visa_form" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">📸 Passport Size Photo (JPG/PNG)</label>
                <input type="file" name="photo_file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">📤 Submit Visa Application</button>
        </form>

        <?php if ($visa_decision === 'rejected'): ?>
            <div class="text-center mt-4">
                <a href="enroll_local.php" class="btn btn-warning">📚 Enroll in Local Course</a>
            </div>
        <?php endif; ?>
    
    <?php else: ?>
        <!-- ✅ Visa already submitted (approved or pending) -->
        <div class="card">
            <h5><?= $visa_decision === 'approved' ? "✅ Your visa has been approved." : "⏳ Your visa application is pending." ?></h5>
            <p><strong>Status:</strong> <?= ucfirst($visa_decision) ?></p>
            <p><strong>Last Updated:</strong> <?= $visa['updated_at'] ?? 'N/A' ?></p>
            <hr>
            <p>📄 <a href="../uploads/documents/<?= htmlspecialchars($visa['passport_file']) ?>" target="_blank">View Passport</a></p>
            <p>📄 <a href="../uploads/documents/<?= htmlspecialchars($visa['visa_form']) ?>" target="_blank">View Visa Form</a></p>
            <p>📸 <a href="../uploads/documents/<?= htmlspecialchars($visa['photo_file']) ?>" target="_blank">View Photo</a></p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = $error = "";

// Get students whose visa was rejected
$stmt = $pdo->prepare("
    SELECT u.user_id, u.full_name, u.email
    FROM users u
    JOIN visa_status v ON u.user_id = v.student_id
    WHERE v.visa_decision = 'rejected'
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle offer letter upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['offer_file'])) {
    $student_id = $_POST['student_id'];
    $file = $_FILES['offer_file'];

    if (!empty($student_id) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $folder = "../uploads/offer_letters/";
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $filename = "local_offer_" . $student_id . "_" . time() . ".pdf";
            $path = $folder . $filename;

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $relative_path = "uploads/offer_letters/" . $filename;

                $stmt = $pdo->prepare("INSERT INTO offer_letters (student_id, offer_type, offer_text) VALUES (?, 'local', ?)");
                $stmt->execute([$student_id, $relative_path]);

                $success = "Local course offer letter uploaded successfully.";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Only PDF files allowed.";
        }
    } else {
        $error = "Please select a student and a valid file.";
    }
}

// Fetch existing offer letters
$offers = $pdo->query("
    SELECT o.*, u.full_name, u.email
    FROM offer_letters o
    JOIN users u ON o.student_id = u.user_id
    WHERE o.offer_type = 'local'
    ORDER BY o.issued_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Upload Local Course Offer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Upload Local Course Offer Letter (Visa Rejected Students)</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm mb-5">
        <div class="mb-3">
            <label>Select Student</label>
            <select name="student_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Upload Offer Letter (PDF)</label>
            <input type="file" name="offer_file" class="form-control" accept="application/pdf" required>
        </div>
        <button class="btn btn-primary w-100">Upload Offer Letter</button>
    </form>

    <h4>Uploaded Local Course Offers</h4>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Email</th>
                <th>Letter File</th>
                <th>Issued At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($offers as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><a href="../<?= $row['offer_text'] ?>" class="btn btn-sm btn-success" target="_blank">View PDF</a></td>
                    <td><?= $row['issued_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($offers) === 0): ?>
                <tr><td colspan="5" class="text-center">No local offers yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

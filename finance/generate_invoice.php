<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";
$type = $_GET['type'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];
    $type = $_POST['type'] ?? '';
    $invoice_file = $_FILES['invoice_file'];

    if ($invoice_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/invoices/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($invoice_file["name"]);
        $target_path = $upload_dir . $file_name;

        if (!move_uploaded_file($invoice_file["tmp_name"], $target_path)) {
            $error = "Failed to upload invoice file.";
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO invoices (student_id, amount, status, issued_at) VALUES (?, ?, 'pending', NOW())");
            $stmt->execute([$student_id, $amount]);
            $success = "Invoice generated successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch students based on type
$students = [];
if ($type) {
    try {
        if ($type === 'application') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT u.user_id, u.full_name
                FROM users u
                JOIN applications a ON u.user_id = a.student_id
                WHERE a.application_status = 'approved'
            ");
        } elseif ($type === 'visa') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT u.user_id, u.full_name
                FROM users u
                JOIN visa_status vs ON u.user_id = vs.student_id
                WHERE vs.visa_decision = 'approved'
            ");
        } elseif ($type === 'local') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT u.user_id, u.full_name
                FROM users u
                JOIN visa_status vs ON u.user_id = vs.student_id
                WHERE vs.visa_decision = 'rejected'
            ");
        }

        if (isset($stmt)) {
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Failed to fetch students: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Invoice</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f7fa;
            padding: 40px;
        }

        .box {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #005f73;
        }

        .tab-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .tab-buttons a {
            flex: 1;
            margin: 0 5px;
            background-color: #008891;
            color: white;
            text-align: center;
            padding: 12px 0;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s ease;
        }

        .tab-buttons a:hover {
            background-color: #005f73;
        }

        label {
            margin-top: 10px;
            display: block;
            color: #333;
        }

        select, input[type="number"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #008891;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #005f73;
        }

        .msg {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success { background-color: #d1f7d6; color: green; }
        .error { background-color: #ffd7d7; color: red; }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #005f73;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Generate Invoice</h2>

    <div class="tab-buttons">
        <a href="?type=application">Application Approved</a>
        <a href="?type=visa">Visa Approved</a>
        <a href="?type=local">Visa Rejected (Local Course)</a>
    </div>

    <?php if ($success): ?>
        <div class="msg success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($type): ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <label for="student_id">Select Student</label>
        <select name="student_id" id="student_id" required>
            <option value="">-- Select --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= $student['user_id'] ?>"><?= htmlspecialchars($student['full_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="amount">Invoice Amount (PKR)</label>
        <input type="number" name="amount" id="amount" required>

        <label for="invoice_file">Upload Invoice File (PDF, JPG, PNG)</label>
        <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required>

        <button type="submit">Generate</button>
    </form>
    <?php else: ?>
        <p>Please choose a type from above to generate invoice.</p>
    <?php endif; ?>

    <a href="financedashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>

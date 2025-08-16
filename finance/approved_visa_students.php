<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Allow only finance users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

$students = [];
$error = "";

try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.email, vs.passport_no, vs.visa_type, vs.visa_decision, vs.visa_issued_date
        FROM visa_status vs
        INNER JOIN users u ON vs.student_id = u.user_id
        WHERE vs.visa_decision = 'approved'
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch approved visa students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approved Visa Students</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f3;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #005f73;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #008891;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .error {
            background-color: #ffd7d7;
            padding: 10px;
            color: red;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #005f73;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Students with Approved Visa</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (empty($students)): ?>
        <p>No students with approved visa found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Passport No</th>
                    <th>Visa Type</th>
                    <th>Visa Issued Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['passport_no']) ?></td>
                        <td><?= htmlspecialchars($s['visa_type']) ?></td>
                        <td><?= htmlspecialchars($s['visa_issued_date']) ?></td>
                        <td style="color: green; font-weight: bold;"><?= ucfirst($s['visa_decision']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="financedashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>

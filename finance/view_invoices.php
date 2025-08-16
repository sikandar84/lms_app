<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            invoices.*, 
            users.full_name AS student_name, 
            programs.program_name AS program_name, 
            universities.university_name AS university_name 
        FROM invoices 
        JOIN users ON invoices.student_id = users.user_id 
        LEFT JOIN applications ON applications.student_id = invoices.student_id 
        LEFT JOIN programs ON applications.program_id = programs.program_id 
        LEFT JOIN universities ON programs.university_id = universities.university_id
        ORDER BY invoices.issued_at DESC
    ");
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching invoices: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Invoices</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #005f73;
            padding: 20px;
            color: white;
            text-align: center;
        }

        .container {
            padding: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #008891;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .status-paid {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        .back-link {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #005f73;
            padding: 10px 15px;
            background: #e0f7fa;
            border-radius: 5px;
        }

        .back-link:hover {
            background: #b2ebf2;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>All Invoices</h2>
</div>

<div class="container">
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>University</th>
                <th>Program</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Invoice Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= htmlspecialchars($invoice['student_name']) ?></td>
                        <td><?= htmlspecialchars($invoice['university_name']) ?></td>
                        <td><?= htmlspecialchars($invoice['program_name']) ?></td>
                        <td>Rs. <?= number_format($invoice['amount']) ?></td>
                        <td>
                            <?php
                                $status = strtolower($invoice['status']);
                                echo "<span class='status-$status'>" . ucfirst($status) . "</span>";
                            ?>
                        </td>
                        <td><?= date("d-M-Y", strtotime($invoice['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No invoices found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="financedashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>

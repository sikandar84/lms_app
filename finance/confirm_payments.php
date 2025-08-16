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

// Handle confirmation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['invoice_id'])) {
    $invoice_id = $_POST['invoice_id'];

    try {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $success = "Payment confirmed successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all pending invoices
try {
    $stmt = $pdo->query("
        SELECT i.*, u.full_name
        FROM invoices i
        JOIN users u ON i.student_id = u.user_id
        WHERE i.status = 'pending'
        ORDER BY i.issued_at DESC
    ");
    $pending_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch invoices: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Payments</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f7fa;
            padding: 40px;
        }

        .box {
            max-width: 900px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #008891;
            color: white;
        }

        form {
            display: inline;
        }

        button {
            padding: 8px 16px;
            background-color: #005f73;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #003845;
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
    <h2>Confirm Student Payments</h2>

    <?php if ($success): ?>
        <div class="msg success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <?php if (count($pending_invoices) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Amount (PKR)</th>
                    <th>Issued Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_invoices as $invoice): ?>
                    <tr>
                        <td><?= htmlspecialchars($invoice['full_name']) ?></td>
                        <td><?= htmlspecialchars($invoice['amount']) ?></td>
                        <td><?= date('d M Y', strtotime($invoice['issued_at'])) ?></td>
                        <td><strong style="color: orange;">Pending</strong></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['invoice_id'] ?>">
                                <button type="submit">Confirm Payment</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; color: #555;">No pending invoices found.</p>
    <?php endif; ?>

    <a href="financedashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>

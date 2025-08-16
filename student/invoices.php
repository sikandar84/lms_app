<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// Redirect if not student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'];
$success = "";
$error = "";

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt_file'])) {
    $invoice_id = $_POST['invoice_id'];
    $file = $_FILES['receipt_file'];
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $filename = 'receipt_' . time() . '_' . basename($file['name']);
        $target = "../uploads/receipts/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            try {
                $stmt = $pdo->prepare("UPDATE invoices SET receipt_file = ?, status = 'paid' WHERE invoice_id = ? AND student_id = ?");
                $stmt->execute([$filename, $invoice_id, $student_id]);
                $success = "✅ Receipt uploaded and marked as paid.";
            } catch (PDOException $e) {
                $error = "❌ DB Error: " . $e->getMessage();
            }
        } else {
            $error = "❌ Failed to move uploaded file.";
        }
    } else {
        $error = "❌ Only PDF, JPG, JPEG, PNG files are allowed.";
    }
}

// Fetch invoices
$invoices = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE student_id = ? ORDER BY issued_at DESC");
    $stmt->execute([$student_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "❌ Failed to load invoices: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Invoices</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f6fc;
            padding: 40px;
        }
        .container {
            max-width: 960px;
            margin: auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #004c75;
            margin-bottom: 25px;
            text-align: center;
        }
        .msg {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success { background: #d9fdd3; color: #267b00; }
        .error { background: #ffe5e5; color: #cc0000; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: #0077b6;
            color: white;
        }

        input[type="file"], button {
            padding: 7px 10px;
            margin-top: 6px;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .paid { color: green; font-weight: bold; }
        .pending { color: orange; font-weight: bold; }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #0077b6;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        a.receipt-link {
            color: #00509e;
            text-decoration: none;
        }
        a.receipt-link:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
<div class="container">
    <h2>💼 Your Invoices</h2>

    <?php if ($success): ?>
        <div class="msg success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($invoices)): ?>
        <p>No invoices found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>#</th>
                <th>Amount (PKR)</th>
                <th>Invoice Type</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Upload Receipt</th>
            </tr>
            <?php foreach ($invoices as $index => $invoice): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= number_format($invoice['amount']) ?></td>
                    <td>
                        <?php
                        if ($invoice['type'] === 'application') echo "Application Approved";
                        elseif ($invoice['type'] === 'visa') echo "Visa Approved";
                        else echo "Local Course Enrollment";
                        ?>
                    </td>
                    <td class="<?= $invoice['status'] ?>">
                        <?= ucfirst($invoice['status']) ?>
                    </td>
                    <td>
                        <?php if ($invoice['receipt_file']): ?>
                            <a class="receipt-link" href="../uploads/receipts/<?= $invoice['receipt_file'] ?>" target="_blank">📄 View</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($invoice['status'] !== 'paid'): ?>
                            <form method="POST" enctype="multipart/form-data" class="upload-form">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['invoice_id'] ?>">
                                <input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <button type="submit">Upload</button>
                            </form>
                        <?php else: ?>
                            ✅ Paid
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <a class="back-link" href="studentdashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>

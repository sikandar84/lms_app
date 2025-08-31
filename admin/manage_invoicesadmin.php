<?php
session_start();
require_once "../config/db.php";
require_once "../includes/auth.php";

// ✅ Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = $error = "";

// ✅ Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$status, $invoice_id]);

    $message = "Invoice status updated successfully.";
}

// ✅ Handle invoice file upload (Admin replacing/uploading copy)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_invoice'])) {
    $invoice_id = intval($_POST['invoice_id']);

    if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === 0) {
        $targetDir = "../uploads/invoices/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["invoice_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["invoice_file"]["tmp_name"], $targetFilePath)) {
            $stmt = $pdo->prepare("UPDATE invoices SET file_path = ? WHERE id = ?");
            $stmt->execute([$fileName, $invoice_id]);
            $message = "Invoice file uploaded successfully.";
        } else {
            $error = "Failed to upload invoice file.";
        }
    }
}

// ✅ Fetch all invoices with student info
$stmt = $pdo->query("
    SELECT invoices.*, users.user_id AS user_id, users.full_name, users.email 
    FROM invoices 
    JOIN users ON invoices.student_id = users.user_id 
    ORDER BY invoices.issued_at DESC
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Invoices - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Manage Invoices</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Invoice ID</th>
                <th>Student</th>
                <th>Email</th>
                <th>Purpose</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
               
                <th>Student Proof</th>
                <th>Upload/Replace</th>
                <th>Change Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($invoices): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= htmlspecialchars($invoice['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($invoice['full_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($invoice['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($invoice['purpose'] ?? '') ?></td>
                        <td>$<?= htmlspecialchars($invoice['amount'] ?? '0') ?></td>
                        <td>
                            <span class="badge bg-<?= ($invoice['status'] ?? '') === 'Paid' ? 'success' : (($invoice['status'] ?? '') === 'Pending' ? 'warning' : 'danger') ?>">
                                <?= htmlspecialchars($invoice['status'] ?? 'Unknown') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($invoice['due_date'] ?? '') ?></td>
                        
                       
                        <!-- Student Payment Proof -->
                        <td>
                            <?php if (!empty($invoice['payment_proof'])): ?>
                                <a href="../uploads/invoices/<?= htmlspecialchars($invoice['payment_proof']) ?>" target="_blank" class="btn btn-sm btn-success">View</a>
                            <?php else: ?>
                                <span class="text-danger">Not Uploaded</span>
                            <?php endif; ?>
                        </td>

                        <!-- Upload New Invoice -->
                        <td>
                            <form method="post" enctype="multipart/form-data" class="d-flex">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?? '' ?>">
                                <input type="file" name="invoice_file" class="form-control form-control-sm me-2" required>
                                <button type="submit" name="upload_invoice" class="btn btn-sm btn-success">Upload</button>
                            </form>
                        </td>

                        <!-- Change Status -->
                        <td>
                            <form method="post" class="d-flex">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?? '' ?>">
                                <select name="status" class="form-select form-select-sm me-2">
                                    <option value="Pending" <?= ($invoice['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Paid" <?= ($invoice['status'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="Overdue" <?= ($invoice['status'] ?? '') === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                                    <option value="Cancelled" <?= ($invoice['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="11" class="text-center">No invoices found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

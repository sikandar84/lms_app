<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Student-only access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['id'] ?? null; 
$full_name  = $_SESSION['full_name'] ?? "Student";

$success = $error = "";
$invoices = [];

// ✅ Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id'])) {
    $invoice_id = intval($_POST['invoice_id']);
    if (!empty($_FILES['payment_proof']['name'])) {
        $upload_dir = "../uploads/invoices/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
        $filename = "proof_" . $student_id . "_" . time() . "." . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("UPDATE invoices SET payment_proof = ? WHERE invoice_id = ? AND student_id = ?");
            $stmt->execute([$filename, $invoice_id, $student_id]);
            $success = "✅ Payment proof uploaded successfully.";
        } else {
            $error = "❌ Failed to upload file.";
        }
    } else {
        $error = "⚠️ Please select a file.";
    }
}

// ✅ Fetch invoices with purpose + university name
if ($student_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT i.invoice_id, i.purpose, i.amount, i.status, i.due_date, i.issued_at,
                   i.invoice_file, i.payment_proof,
                   a.application_id, p.program_name, u.university_name
            FROM invoices i
            LEFT JOIN applications a ON i.application_id = a.application_id
            LEFT JOIN programs p ON a.program_id = p.program_id
            LEFT JOIN universities u ON p.university_id = u.university_id
            WHERE i.student_id = ?
            ORDER BY i.issued_at DESC
        ");
        $stmt->execute([$student_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "❌ Failed to fetch invoices: " . $e->getMessage();
    }
} else {
    $error = "❌ Session issue: student ID missing.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Invoices</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 1150px; }
    .card { border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
    .status-paid { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; }
    .status-unpaid { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; }
    .status-pending { background: #e5e7eb; color: #374151; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-3">💳 My Invoices</h2>
  <a href="studentdashboard.php" class="btn btn-outline-secondary mb-4">🏠 Back to Dashboard</a>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (empty($invoices)): ?>
    <div class="alert alert-warning">⚠️ No invoices found for you.</div>
  <?php else: ?>
    <div class="card p-4">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Purpose</th>
              <th>Program</th>
              <th>University</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Issued On</th>
              <th>Invoice File</th>
              <th>Payment Proof</th>
              <th>Upload Proof</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invoices as $invoice): ?>
              <tr>
                <td>#<?= htmlspecialchars($invoice['invoice_id']) ?></td>
                <td><?= htmlspecialchars(ucfirst($invoice['purpose'] ?? 'N/A')) ?></td>
                <td><?= htmlspecialchars($invoice['program_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($invoice['university_name'] ?? 'N/A') ?></td>
                <td><strong>$<?= htmlspecialchars($invoice['amount']) ?></strong></td>
                <td>
                  <?php if (strtolower($invoice['status']) === 'paid'): ?>
                    <span class="status-paid">Paid</span>
                  <?php elseif (strtolower($invoice['status']) === 'unpaid'): ?>
                    <span class="status-unpaid">Unpaid</span>
                  <?php else: ?>
                    <span class="status-pending"><?= htmlspecialchars($invoice['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= $invoice['due_date'] ? htmlspecialchars($invoice['due_date']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($invoice['issued_at']) ?></td>
                <td>
                  <?php if (!empty($invoice['invoice_file'])): ?>
                    <a href="../uploads/invoices/<?= htmlspecialchars($invoice['invoice_file']) ?>" target="_blank" class="btn btn-sm btn-primary">Download</a>
                  <?php else: ?>
                    <span class="text-muted">Not Uploaded</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($invoice['payment_proof'])): ?>
                    <a href="../uploads/invoices/<?= htmlspecialchars($invoice['payment_proof']) ?>" target="_blank" class="btn btn-sm btn-success">View</a>
                  <?php else: ?>
                    <span class="text-danger">Not Uploaded</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (strtolower($invoice['status']) !== 'paid'): ?>
                    <form method="post" enctype="multipart/form-data" class="d-flex">
                      <input type="hidden" name="invoice_id" value="<?= $invoice['invoice_id'] ?>">
                      <input type="file" name="payment_proof" class="form-control form-control-sm me-2" required>
                      <button type="submit" class="btn btn-sm btn-secondary">Upload</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">✔ No Action Needed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>

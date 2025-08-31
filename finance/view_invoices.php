<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Only finance role can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Fetch all invoices with student info
try {
    $stmt = $pdo->query("
        SELECT invoices.invoice_id, invoices.student_id, invoices.amount, invoices.status, 
               invoices.due_date, invoices.issued_at, invoices.purpose, 
               users.full_name AS student_name, users.email AS student_email
        FROM invoices
        JOIN users ON invoices.student_id = users.user_id
        ORDER BY invoices.issued_at DESC
    ");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching invoices: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Finance - View Invoices</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f5f6fa;
      font-family: Arial, sans-serif;
    }
    .container {
      margin-top: 40px;
    }
    h2 {
      margin-bottom: 20px;
      text-align: center;
    }
    .card {
      margin-bottom: 30px;
      border-radius: 12px;
      box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
    }
    .table {
      border-radius: 10px;
      overflow: hidden;
    }
    .status-paid {
      color: green;
      font-weight: bold;
    }
    .status-unpaid {
      color: red;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>💰 Finance Dashboard - Student Invoices</h2>

  <!-- Visa Invoices -->
  <div class="card">
    <div class="card-header bg-primary text-white">
      Visa Invoices
    </div>
    <div class="card-body">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $found = false;
          foreach ($invoices as $inv): 
            if ($inv['purpose'] === 'Visa'): $found = true; ?>
            <tr>
              <td><?= htmlspecialchars($inv['invoice_id']) ?></td>
              <td><?= htmlspecialchars($inv['student_name']) ?> (<?= htmlspecialchars($inv['student_email']) ?>)</td>
              <td>$<?= htmlspecialchars($inv['amount']) ?></td>
              <td class="<?= $inv['status'] === 'Paid' ? 'status-paid' : 'status-unpaid' ?>">
                <?= htmlspecialchars($inv['status']) ?>
              </td>
              <td><?= htmlspecialchars($inv['issue_date']) ?></td>
              <td><?= htmlspecialchars($inv['due_date']) ?></td>
            </tr>
          <?php endif; endforeach; ?>
          <?php if (!$found): ?>
            <tr><td colspan="6" class="text-center">No Visa invoices found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Application Invoices -->
  <div class="card">
    <div class="card-header bg-success text-white">
      Application Invoices
    </div>
    <div class="card-body">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $found = false;
          foreach ($invoices as $inv): 
            if ($inv['purpose'] === 'Application'): $found = true; ?>
            <tr>
              <td><?= htmlspecialchars($inv['invoice_id']) ?></td>
              <td><?= htmlspecialchars($inv['student_name']) ?> (<?= htmlspecialchars($inv['student_email']) ?>)</td>
              <td>$<?= htmlspecialchars($inv['amount']) ?></td>
              <td class="<?= $inv['status'] === 'Paid' ? 'status-paid' : 'status-unpaid' ?>">
                <?= htmlspecialchars($inv['status']) ?>
              </td>
              <td><?= htmlspecialchars($inv['issue_date']) ?></td>
              <td><?= htmlspecialchars($inv['due_date']) ?></td>
            </tr>
          <?php endif; endforeach; ?>
          <?php if (!$found): ?>
            <tr><td colspan="6" class="text-center">No Application invoices found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Local Course Invoices -->
  <div class="card">
    <div class="card-header bg-warning">
      Local Course Invoices
    </div>
    <div class="card-body">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $found = false;
          foreach ($invoices as $inv): 
            if ($inv['purpose'] === 'Local Course'): $found = true; ?>
            <tr>
              <td><?= htmlspecialchars($inv['invoice_id']) ?></td>
              <td><?= htmlspecialchars($inv['student_name']) ?> (<?= htmlspecialchars($inv['student_email']) ?>)</td>
              <td>$<?= htmlspecialchars($inv['amount']) ?></td>
              <td class="<?= $inv['status'] === 'Paid' ? 'status-paid' : 'status-unpaid' ?>">
                <?= htmlspecialchars($inv['status']) ?>
              </td>
              <td><?= htmlspecialchars($inv['issue_date']) ?></td>
              <td><?= htmlspecialchars($inv['due_date']) ?></td>
            </tr>
          <?php endif; endforeach; ?>
          <?php if (!$found): ?>
            <tr><td colspan="6" class="text-center">No Local Course invoices found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>

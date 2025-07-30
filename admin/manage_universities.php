<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Admin only
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// ✅ CREATE University
if (isset($_POST['create'])) {
    $name = trim($_POST['university_name']);
    $country = trim($_POST['country']);
    $website = trim($_POST['website']);

    if ($name && $country && $website) {
        $stmt = $pdo->prepare("INSERT INTO universities (university_name, country, website) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $country, $website])) {
            $success = "✅ University added.";
        } else {
            $error = "❌ Failed to add university.";
        }
    } else {
        $error = "⚠️ All fields are required.";
    }
}

// ✅ DELETE University
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM universities WHERE university_id = ?")->execute([$id]);
    header("Location: manage_universities.php");
    exit();
}

// ✅ UPDATE University
if (isset($_POST['update'])) {
    $id = intval($_POST['university_id']);
    $name = trim($_POST['university_name']);
    $country = trim($_POST['country']);
    $website = trim($_POST['website']);

    if ($id && $name && $country && $website) {
        $stmt = $pdo->prepare("UPDATE universities SET university_name = ?, country = ?, website = ? WHERE university_id = ?");
        $stmt->execute([$name, $country, $website, $id]);
        $success = "✅ University updated.";
    } else {
        $error = "⚠️ All fields must be filled.";
    }
}

// ✅ FETCH All Universities
$stmt = $pdo->query("SELECT * FROM universities ORDER BY university_name ASC");
$universities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Universities</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { margin-top: 40px; }
    .card { border-radius: 16px; }
    table input { font-size: 14px; }
  </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Manage Universities</h2>
    <a href="admindashboard.php" class="btn btn-outline-secondary mb-4">🏠 Back to Home</a>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ✅ ADD -->
    <div class="card shadow-sm p-4 mb-4">
        <h5>Add New University</h5>
        <form method="post">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">University Name</label>
                    <input type="text" name="university_name" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" required>
                </div>
            </div>
            <button name="create" class="btn btn-success">Add University</button>
        </form>
    </div>

    <!-- ✅ LIST + EDIT -->
    <div class="card shadow-sm p-4">
        <h5>Existing Universities</h5>
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Website</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($universities as $index => $uni): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="university_id" value="<?= $uni['university_id'] ?>">
                        <td><?= $index + 1 ?></td>
                        <td><input type="text" name="university_name" class="form-control" value="<?= htmlspecialchars($uni['university_name']) ?>" required></td>
                        <td><input type="text" name="country" class="form-control" value="<?= htmlspecialchars($uni['country']) ?>" required></td>
                        <td><input type="url" name="website" class="form-control" value="<?= htmlspecialchars($uni['website']) ?>" required></td>
                        <td>
                            <button name="update" class="btn btn-sm btn-primary">Update</button>
                            <a href="?delete=<?= $uni['university_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

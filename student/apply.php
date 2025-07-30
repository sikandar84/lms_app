<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

// ✅ Only allow student
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Fetch all universities
$stmt = $pdo->query("SELECT * FROM universities ORDER BY university_name ASC");
$universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Programs - LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-title {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-3 text-center">🌐 Explore Universities & Apply</h2>

    <!-- ✅ Back to Dashboard Button -->
    <div class="text-center mb-4">
        <a href="studentdashboard.php" class="btn btn-outline-secondary">🏠 Back to Dashboard</a>
    </div>

    <?php if (empty($universities)): ?>
        <div class="alert alert-warning text-center">
            ⚠️ No universities available at the moment.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($universities as $uni): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($uni['university_name']) ?></h5>
                            <p class="card-text small">
                                🌍 <strong>Country:</strong> <?= htmlspecialchars($uni['country']) ?><br>
                                🔗 <strong>Website:</strong>
                                <?php if (!empty($uni['website'])): ?>
                                    <a href="<?= htmlspecialchars($uni['website']) ?>" target="_blank" style="word-break: break-word;">
                                        <?= htmlspecialchars($uni['website']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </p>
                            <a href="apply_programs.php?university_id=<?= $uni['university_id'] ?>" class="btn btn-primary w-100">
                                View Programs / Apply
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require_once("../config/db.php");

// ✅ Finance-only access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error   = "";

// Which tab is active?
$tab = $_GET['tab'] ?? 'application'; // application | visa | local

// ---------- Handle invoice creation ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_invoice'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $amount     = trim($_POST['amount'] ?? '');
    $due_date   = $_POST['due_date'] ?? null;
    $purpose    = $_POST['purpose'] ?? ''; // Visa | Application | Local Course
    $tab        = $_POST['tab'] ?? 'application';

    $file_path_db = null;

    // Basic validation
    $allowed_purposes = ['Visa','Application','Local Course'];
    if ($student_id <= 0) {
        $error = "Please select a valid student.";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = "Please enter a valid positive amount.";
    } elseif (empty($due_date)) {
        $error = "Please select a due date.";
    } elseif (!in_array($purpose, $allowed_purposes, true)) {
        $error = "Invalid purpose selected.";
    }

    // ✅ Handle uploaded invoice file (optional)
    if (!$error && isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['invoice_file'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $dir = "../uploads/invoices/";
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','jpg','jpeg','png'];
            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Allowed: PDF, JPG, JPEG, PNG.";
            } else {
                $base = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($f['name'], PATHINFO_FILENAME));
                $safeName = time() . "_" . $base . "." . $ext;
                $target   = $dir . $safeName;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    // store relative path in DB (consistent with previous pages)
                    $file_path_db = "uploads/invoices/" . $safeName;
                } else {
                    $error = "Failed to upload invoice file.";
                }
            }
        } else {
            $error = "Failed to upload invoice file.";
        }
    }

    // ✅ Insert invoice into DB
    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO invoices (student_id, amount, purpose, due_date, status, issued_at, invoice_file)
                VALUES (?, ?, ?, ?, 'unpaid', NOW(), ?)
            ");
            $stmt->execute([$student_id, $amount, $purpose, $due_date, $file_path_db]);

            // Optional notification
            $note = "💳 A new invoice of PKR " . number_format((float)$amount, 0) . " has been issued for {$purpose}. Due: " . htmlspecialchars($due_date);
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student_id, $note]);

            $success = "✅ Invoice generated successfully.";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- Shared lists ----------
$universities = $pdo->query("SELECT university_id, university_name FROM universities ORDER BY university_name")->fetchAll(PDO::FETCH_ASSOC);
$localCourses = $pdo->query("SELECT local_course_id, course_name FROM local_courses ORDER BY course_name")->fetchAll(PDO::FETCH_ASSOC);

// ---------- Helpers ----------
function fetchProgramsByUniversity(PDO $pdo, $university_id) {
    $stmt = $pdo->prepare("SELECT program_id, program_name FROM programs WHERE university_id = ? ORDER BY program_name");
    $stmt->execute([intval($university_id)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchApprovedAppStudents(PDO $pdo, $program_id) {
    $sql = "SELECT a.application_id, u.user_id, u.full_name
            FROM applications a
            JOIN users u ON a.student_id = u.user_id
            WHERE a.program_id = ? AND a.application_status = 'approved'
            ORDER BY u.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([intval($program_id)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchVisaApprovedStudents(PDO $pdo, $program_id) {
    $sql = "SELECT a.application_id, u.user_id, u.full_name
            FROM applications a
            JOIN users u ON a.student_id = u.user_id
            JOIN visa_status vs ON vs.student_id = a.student_id
            WHERE a.program_id = ? AND a.application_status = 'approved' AND vs.visa_decision = 'approved'
            ORDER BY u.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([intval($program_id)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchLocalCourseStudents(PDO $pdo, $local_course_id) {
    $sql = "SELECT u.user_id, u.full_name
            FROM local_course_enrollments lce
            JOIN users u ON lce.student_id = u.user_id
            JOIN visa_status vs ON vs.student_id = u.user_id
            WHERE lce.local_course_id = ? AND vs.visa_decision = 'rejected'
            ORDER BY u.full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([intval($local_course_id)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Preload dependent lists
$app_uni_id    = $_GET['app_university_id']   ?? '';
$app_prog_id   = $_GET['app_program_id']      ?? '';
$visa_uni_id   = $_GET['visa_university_id']  ?? '';
$visa_prog_id  = $_GET['visa_program_id']     ?? '';
$loc_course_id = $_GET['local_course_id']     ?? '';

$appPrograms   = $app_uni_id  ? fetchProgramsByUniversity($pdo, $app_uni_id)   : [];
$visaPrograms  = $visa_uni_id ? fetchProgramsByUniversity($pdo, $visa_uni_id)  : [];
$appStudents   = $app_prog_id ? fetchApprovedAppStudents($pdo, $app_prog_id)   : [];
$visaStudents  = $visa_prog_id? fetchVisaApprovedStudents($pdo, $visa_prog_id) : [];
$localStudents = $loc_course_id? fetchLocalCourseStudents($pdo, $loc_course_id): [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Generate Invoice</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>.tab { display: none; }.tab.active { display: block; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl md:text-3xl font-bold">💳 Generate Invoice</h1>
      <a href="financedashboard.php" class="text-sm px-4 py-2 rounded bg-white border hover:bg-gray-100">← Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="mb-4 rounded border border-green-200 bg-green-50 text-green-700 px-3 py-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="mb-4 rounded border border-red-200 bg-red-50 text-red-700 px-3 py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Tabs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-6">
      <a href="?tab=application" class="text-center px-3 py-2 rounded font-semibold <?= $tab==='application'?'bg-blue-600 text-white':'bg-white border hover:bg-gray-50' ?>">Application Approved</a>
      <a href="?tab=visa"        class="text-center px-3 py-2 rounded font-semibold <?= $tab==='visa'?'bg-blue-600 text-white':'bg-white border hover:bg-gray-50' ?>">Visa Approved</a>
      <a href="?tab=local"       class="text-center px-3 py-2 rounded font-semibold <?= $tab==='local'?'bg-blue-600 text-white':'bg-white border hover:bg-gray-50' ?>">Local Courses</a>
    </div>

    <!-- ========== Application Approved (Purpose: Application) ========== -->
    <div id="tab-application" class="tab <?= $tab==='application'?'active':'' ?>">
      <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-xl font-semibold mb-4">✅ Application Approved</h2>

        <form method="GET" class="grid gap-3 md:grid-cols-3">
          <input type="hidden" name="tab" value="application">
          <div>
            <label class="block text-sm font-medium mb-1">University</label>
            <select name="app_university_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
              <option value="">-- Select University --</option>
              <?php foreach ($universities as $uni): ?>
                <option value="<?= $uni['university_id'] ?>" <?= ($app_uni_id==$uni['university_id'])?'selected':'' ?>><?= htmlspecialchars($uni['university_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Program</label>
            <select name="app_program_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()" <?= empty($appPrograms)?'disabled':'' ?>>
              <option value="">-- Select Program --</option>
              <?php foreach ($appPrograms as $p): ?>
                <option value="<?= $p['program_id'] ?>" <?= ($app_prog_id==$p['program_id'])?'selected':'' ?>><?= htmlspecialchars($p['program_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-end"><a href="?tab=application" class="text-sm px-3 py-2 border rounded bg-white hover:bg-gray-50">Reset</a></div>
        </form>

        <?php if (!empty($appStudents)): ?>
          <div class="mt-5 grid gap-4">
            <?php foreach ($appStudents as $st): ?>
              <form method="POST" enctype="multipart/form-data" class="rounded border p-4 md:flex md:items-center md:justify-between">
                <div>
                  <div class="font-semibold"><?= htmlspecialchars($st['full_name']) ?></div>
                  <div class="text-xs text-gray-500">Application ID: <?= (int)$st['application_id'] ?></div>
                  <div class="text-xs mt-1 px-2 py-1 inline-block rounded bg-blue-50 text-blue-700">Purpose: Application</div>
                </div>
                <div class="mt-3 md:mt-0 md:flex md:items-center md:gap-3">
                  <input type="hidden" name="tab" value="application">
                  <input type="hidden" name="create_invoice" value="1">
                  <input type="hidden" name="purpose" value="Application">
                  <input type="hidden" name="student_id" value="<?= (int)$st['user_id'] ?>">
                  <input type="number" step="0.01" min="1" name="amount" class="border rounded px-3 py-2 w-40" placeholder="Amount (PKR)" required>
                  <input type="date" name="due_date" class="border rounded px-3 py-2 w-44" required>
                  <input type="file" name="invoice_file" class="border rounded px-2 py-2 w-56" accept=".pdf,.jpg,.jpeg,.png">
                  <button class="mt-2 md:mt-0 bg-blue-600 text-white px-4 py-2 rounded">Generate</button>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ========== Visa Approved (Purpose: Visa) ========== -->
    <div id="tab-visa" class="tab <?= $tab==='visa'?'active':'' ?>">
      <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-xl font-semibold mb-4">🛂 Visa Approved</h2>

        <form method="GET" class="grid gap-3 md:grid-cols-3">
          <input type="hidden" name="tab" value="visa">
          <div>
            <label class="block text-sm font-medium mb-1">University</label>
            <select name="visa_university_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
              <option value="">-- Select University --</option>
              <?php foreach ($universities as $uni): ?>
                <option value="<?= $uni['university_id'] ?>" <?= ($visa_uni_id==$uni['university_id'])?'selected':'' ?>><?= htmlspecialchars($uni['university_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Program</label>
            <select name="visa_program_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()" <?= empty($visaPrograms)?'disabled':'' ?>>
              <option value="">-- Select Program --</option>
              <?php foreach ($visaPrograms as $p): ?>
                <option value="<?= $p['program_id'] ?>" <?= ($visa_prog_id==$p['program_id'])?'selected':'' ?>><?= htmlspecialchars($p['program_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-end"><a href="?tab=visa" class="text-sm px-3 py-2 border rounded bg-white hover:bg-gray-50">Reset</a></div>
        </form>

        <?php if (!empty($visaStudents)): ?>
          <div class="mt-5 grid gap-4">
            <?php foreach ($visaStudents as $st): ?>
              <form method="POST" enctype="multipart/form-data" class="rounded border p-4 md:flex md:items-center md:justify-between">
                <div>
                  <div class="font-semibold"><?= htmlspecialchars($st['full_name']) ?></div>
                  <div class="text-xs text-gray-500">Application ID: <?= (int)$st['application_id'] ?></div>
                  <div class="text-xs mt-1 px-2 py-1 inline-block rounded bg-blue-50 text-blue-700">Purpose: Visa</div>
                </div>
                <div class="mt-3 md:mt-0 md:flex md:items-center md:gap-3">
                  <input type="hidden" name="tab" value="visa">
                  <input type="hidden" name="create_invoice" value="1">
                  <input type="hidden" name="purpose" value="Visa">
                  <input type="hidden" name="student_id" value="<?= (int)$st['user_id'] ?>">
                  <input type="number" step="0.01" min="1" name="amount" class="border rounded px-3 py-2 w-40" placeholder="Amount (PKR)" required>
                  <input type="date" name="due_date" class="border rounded px-3 py-2 w-44" required>
                  <input type="file" name="invoice_file" class="border rounded px-2 py-2 w-56" accept=".pdf,.jpg,.jpeg,.png">
                  <button class="mt-2 md:mt-0 bg-blue-600 text-white px-4 py-2 rounded">Generate</button>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ========== Local Courses (Purpose: Local Course) ========== -->
    <div id="tab-local" class="tab <?= $tab==='local'?'active':'' ?>">
      <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-xl font-semibold mb-4">🏫 Local Courses (Visa Rejected)</h2>

        <form method="GET" class="grid gap-3 md:grid-cols-3">
          <input type="hidden" name="tab" value="local">
          <div>
            <label class="block text-sm font-medium mb-1">Local Course</label>
            <select name="local_course_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
              <option value="">-- Select Local Course --</option>
              <?php foreach ($localCourses as $lc): ?>
                <option value="<?= $lc['local_course_id'] ?>" <?= ($loc_course_id==$lc['local_course_id'])?'selected':'' ?>><?= htmlspecialchars($lc['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-end"><a href="?tab=local" class="text-sm px-3 py-2 border rounded bg-white hover:bg-gray-50">Reset</a></div>
        </form>

        <?php if (!empty($localStudents)): ?>
          <div class="mt-5 grid gap-4">
            <?php foreach ($localStudents as $st): ?>
              <form method="POST" enctype="multipart/form-data" class="rounded border p-4 md:flex md:items-center md:justify-between">
                <div>
                  <div class="font-semibold"><?= htmlspecialchars($st['full_name']) ?></div>
                  <div class="text-xs text-gray-500">Local Course ID: <?= htmlspecialchars($loc_course_id) ?></div>
                  <div class="text-xs mt-1 px-2 py-1 inline-block rounded bg-blue-50 text-blue-700">Purpose: Local Course</div>
                </div>
                <div class="mt-3 md:mt-0 md:flex md:items-center md:gap-3">
                  <input type="hidden" name="tab" value="local">
                  <input type="hidden" name="create_invoice" value="1">
                  <input type="hidden" name="purpose" value="Local Course">
                  <input type="hidden" name="student_id" value="<?= (int)$st['user_id'] ?>">
                  <input type="number" step="0.01" min="1" name="amount" class="border rounded px-3 py-2 w-40" placeholder="Amount (PKR)" required>
                  <input type="date" name="due_date" class="border rounded px-3 py-2 w-44" required>
                  <input type="file" name="invoice_file" class="border rounded px-2 py-2 w-56" accept=".pdf,.jpg,.jpeg,.png">
                  <button class="bg-blue-600 text-white px-4 py-2 rounded">Generate</button>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</body>
</html>

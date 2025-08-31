<?php
// migrate.php
$host = "localhost";
$user = "root";
$pass = "admin123";
$dbname = "lms_db";

try {
    // Connect to MySQL (without selecting db)
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<h3>🚀 Migration Started...</h3>";

    // Drop & create database
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    // Schema SQL
    $schema = <<<SQL

-- ================================
-- Users
-- ================================
CREATE TABLE users (
  user_id INT NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin','finance') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id)
);

-- ================================
-- Universities
-- ================================
CREATE TABLE universities (
  university_id INT NOT NULL AUTO_INCREMENT,
  university_name VARCHAR(150) NOT NULL,
  country VARCHAR(100) NOT NULL,
  website VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (university_id)
);

-- ================================
-- Programs
-- ================================
CREATE TABLE programs (
  program_id INT NOT NULL AUTO_INCREMENT,
  program_name VARCHAR(255) NOT NULL,
  university_id INT NOT NULL,
  duration VARCHAR(100) DEFAULT NULL,
  tuition_fee DECIMAL(10,2) DEFAULT NULL,
  description TEXT,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (program_id),
  CONSTRAINT fk_program_university FOREIGN KEY (university_id) REFERENCES universities(university_id) ON DELETE CASCADE
);

-- ================================
-- Courses (linked to programs)
-- ================================
CREATE TABLE courses (
  course_id INT NOT NULL AUTO_INCREMENT,
  program_id INT NOT NULL,
  course_name VARCHAR(150) NOT NULL,
  course_description TEXT,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (course_id),
  CONSTRAINT fk_course_program FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE
);

-- ================================
-- Applications
-- ================================
CREATE TABLE applications (
  application_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  program_id INT NOT NULL,
  full_name VARCHAR(255) DEFAULT NULL,
  age INT DEFAULT NULL,
  gpa VARCHAR(10) DEFAULT NULL,
  graduated VARCHAR(10) DEFAULT NULL,
  matric_card VARCHAR(255) DEFAULT NULL,
  fsc_card VARCHAR(255) DEFAULT NULL,
  transcript VARCHAR(255) DEFAULT NULL,
  resume VARCHAR(255) DEFAULT NULL,
  application_status ENUM('pending','approved','rejected') DEFAULT 'pending',
  applied_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE
);

-- ================================
-- Local Courses
-- ================================
CREATE TABLE local_courses (
  local_course_id INT NOT NULL AUTO_INCREMENT,
  course_name VARCHAR(255) NOT NULL,
  course_description TEXT,
  course_duration VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (local_course_id)
);

CREATE TABLE local_course_enrollments (
  enrollment_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  local_course_id INT NOT NULL,
  enrolled_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (enrollment_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (local_course_id) REFERENCES local_courses(local_course_id) ON DELETE CASCADE
);

-- ================================
-- Visa Status
-- ================================
CREATE TABLE visa_status (
  visa_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  visa_decision ENUM('pending','approved','rejected') DEFAULT 'pending',
  passport_file VARCHAR(255) DEFAULT NULL,
  visa_form VARCHAR(255) DEFAULT NULL,
  photo_file VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (visa_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ================================
-- Invoices
-- ================================
CREATE TABLE invoices (
  invoice_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  university_id INT DEFAULT NULL,
  visa_id INT DEFAULT NULL,
  application_id INT DEFAULT NULL,
  local_course_id INT DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  purpose VARCHAR(255) DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  status ENUM('unpaid','paid','pending') DEFAULT 'unpaid',
  issued_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  invoice_file VARCHAR(255) DEFAULT NULL,
  payment_proof VARCHAR(255) DEFAULT NULL,
  finance_file VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (invoice_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
  FOREIGN KEY (local_course_id) REFERENCES local_courses(local_course_id) ON DELETE SET NULL,
  FOREIGN KEY (visa_id) REFERENCES visa_status(visa_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ================================
-- Documents
-- ================================
CREATE TABLE documents (
  document_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  document_type VARCHAR(100) DEFAULT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (document_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ================================
-- Offer Letters
-- ================================
CREATE TABLE offer_letters (
  offer_letter_id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  university_id INT NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  issued_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (offer_letter_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (university_id) REFERENCES universities(university_id) ON DELETE CASCADE
);

-- ================================
-- Notifications
-- ================================
CREATE TABLE notifications (
  notification_id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ================================
-- Local Course Assessments
-- ================================
CREATE TABLE assessments (
  assessment_id INT NOT NULL AUTO_INCREMENT,
  local_course_id INT NOT NULL,
  assessment_title VARCHAR(150) NOT NULL,
  description TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  due_date DATETIME,
  assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (assessment_id),
  FOREIGN KEY (local_course_id) REFERENCES local_courses(local_course_id) ON DELETE CASCADE
);

SQL;

    // Execute schema
    $pdo->exec($schema);

    echo "<h4>✅ Database migrated successfully!</h4>";
} catch (PDOException $e) {
    die("<h4>❌ Migration failed: " . $e->getMessage() . "</h4>");
}
?>

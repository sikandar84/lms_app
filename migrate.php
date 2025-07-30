<?php
$host = 'localhost';
$dbname = 'lms_db';
$username = 'root';
$password = 'admin123';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // Drop tables (in reverse dependency order)
    $tables = [
        'submitted_assessments', 'assessments',
        'course_enrollments', 'local_course_enrollments',
        'visa_status', 'invoices', 'offer_letters',
        'documents', 'applications', 'courses',
        'programs', 'universities', 'notifications',
        'local_courses', 'users'
    ];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }

    // USERS
    $pdo->exec("
        CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'admin', 'finance') NOT NULL DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // UNIVERSITIES
    $pdo->exec("
        CREATE TABLE universities (
            university_id INT AUTO_INCREMENT PRIMARY KEY,
            university_name VARCHAR(150) NOT NULL,
            country VARCHAR(100) NOT NULL,
            website VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // PROGRAMS
    $pdo->exec("
        CREATE TABLE programs (
            program_id INT AUTO_INCREMENT PRIMARY KEY,
            program_name VARCHAR(255) NOT NULL,
            university_id INT NOT NULL,
            duration VARCHAR(100),
            tuition_fee DECIMAL(10,2),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (university_id) REFERENCES universities(university_id) ON DELETE CASCADE
        );
    ");

    // COURSES (linked to programs)
    $pdo->exec("
        CREATE TABLE courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            course_name VARCHAR(150) NOT NULL,
            course_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE
        );
    ");

    // APPLICATIONS
    $pdo->exec("
        CREATE TABLE applications (
            application_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            program_id INT NOT NULL,
            full_name VARCHAR(255),
            age INT,
            gpa VARCHAR(10),
            graduated VARCHAR(10),
            matric_card VARCHAR(255),
            fsc_card VARCHAR(255),
            transcript VARCHAR(255),
            resume VARCHAR(255),
            application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE
        );
    ");

    // DOCUMENTS (generic uploads)
    $pdo->exec("
        CREATE TABLE documents (
            document_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            document_type VARCHAR(100),
            file_path VARCHAR(255),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // OFFER LETTERS
    $pdo->exec("
        CREATE TABLE offer_letters (
            offer_letter_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            university_id INT NOT NULL,
            file_path VARCHAR(255),
            issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (university_id) REFERENCES universities(university_id) ON DELETE CASCADE
        );
    ");

    // INVOICES (for Finance Dashboard)
    $pdo->exec("
        CREATE TABLE invoices (
            invoice_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('unpaid', 'paid', 'pending') DEFAULT 'unpaid',
            issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // VISA STATUS
    $pdo->exec("
        CREATE TABLE visa_status (
            visa_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            visa_decision ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            passport_file VARCHAR(255),
            visa_form VARCHAR(255),
            photo_file VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // FOREIGN COURSE ENROLLMENTS
    $pdo->exec("
        CREATE TABLE course_enrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
        );
    ");

    // ASSESSMENTS (admin assigned)
    $pdo->exec("
        CREATE TABLE assessments (
            assessment_id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            assessment_title VARCHAR(150),
            file_path VARCHAR(255),
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
        );
    ");

    // STUDENT SUBMITTED ASSESSMENTS
    $pdo->exec("
        CREATE TABLE submitted_assessments (
            submission_id INT AUTO_INCREMENT PRIMARY KEY,
            assessment_id INT NOT NULL,
            student_id INT NOT NULL,
            file_path VARCHAR(255),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // NOTIFICATIONS (Admin -> student or system generated)
    $pdo->exec("
        CREATE TABLE notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // LOCAL COURSES
    $pdo->exec("
        CREATE TABLE local_courses (
            local_course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(255) NOT NULL,
            course_description TEXT,
            course_duration VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // LOCAL COURSE ENROLLMENTS
    $pdo->exec("
        CREATE TABLE local_course_enrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            local_course_id INT NOT NULL,
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (local_course_id) REFERENCES local_courses(local_course_id) ON DELETE CASCADE
        );
    ");

    echo "<h3 style='color: green;'>✅ LMS database migrated successfully with all tables!</h3>";

} catch (PDOException $e) {
    die("<h3 style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</h3>");
}
?>

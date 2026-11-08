<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/conn.php';
$sql = file_get_contents(__DIR__ . '/online_enroll.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;/s', $sql, $matches);
foreach ($matches[0] as $statement) {
    // Create missing structure only. Never import the dump's personal data/passwords.
    $conn->query(str_replace('ENGINE=MyISAM', 'ENGINE=InnoDB', $statement));
}
$email_column = $conn->query("SHOW COLUMNS FROM studenttbl LIKE 'emailaddress'")->fetch_assoc();
if ($email_column['Type'] === 'varchar(14)') {
    $conn->query('ALTER TABLE studenttbl MODIFY emailaddress VARCHAR(254) NOT NULL, MODIFY contact VARCHAR(32) NOT NULL');
}
$student_number = $conn->query("SHOW COLUMNS FROM studenttbl LIKE 'studnum'")->fetch_assoc();
if ($student_number['Type'] === 'varchar(11)') {
    $conn->query('ALTER TABLE studenttbl MODIFY studnum VARCHAR(64) NOT NULL');
}
if (!$conn->query("SHOW COLUMNS FROM auth_users LIKE 'student_id'")->num_rows) {
    $conn->query('ALTER TABLE auth_users ADD student_id INT NULL, ADD UNIQUE KEY auth_student_link (student_id)');
}
$conn->query("CREATE TABLE IF NOT EXISTS enrollment_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    section_id INT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    notes VARCHAR(1000) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY student_term (student_id, academic_year, semester),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS enrollment_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    subject_id INT NOT NULL,
    subject_code VARCHAR(100) NOT NULL,
    subject_title VARCHAR(100) NOT NULL,
    units DECIMAL(5,1) NOT NULL,
    UNIQUE KEY request_subject (request_id, subject_id),
    FOREIGN KEY (request_id) REFERENCES enrollment_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Portal tables ready; existing records preserved.\n";

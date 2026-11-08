<?php
// CLI only: never expose administrator creation through a web request.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '');
$database = getenv('DB_NAME') ?: 'online_enroll';
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $database)) {
    exit("Invalid database name.\n");
}
$conn->query('CREATE DATABASE IF NOT EXISTS `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$conn->select_db($database);
$conn->set_charset('utf8mb4');
$conn->query('CREATE TABLE IF NOT EXISTS auth_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT \'admin\',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$conn->query('CREATE TABLE IF NOT EXISTS auth_login_limits (
    ip_key CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_started DATETIME NOT NULL,
    INDEX (window_started)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
if ((int) $conn->query('SELECT COUNT(*) FROM auth_users')->fetch_row()[0] > 0) {
    exit("Authentication tables ready. Existing accounts were preserved.\n");
}
$username = 'admin';
$password = bin2hex(random_bytes(10));
$hash = password_hash($password, PASSWORD_DEFAULT);
$full_name = 'University Administrator';
$query = $conn->prepare('INSERT INTO auth_users (username, password_hash, full_name) VALUES (?, ?, ?)');
$query->bind_param('sss', $username, $hash, $full_name);
$query->execute();
echo "Administrator created.\nUsername: $username\nPassword: $password\n";

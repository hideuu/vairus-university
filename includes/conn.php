<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME') ?: 'online_enroll'
);
$conn->set_charset('utf8mb4');
$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

<?php
require __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Use the Log out button.');
}
if (!valid_csrf()) { http_response_code(403); exit('Reload the page and try again.'); }
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: index.php', true, 303);
exit;

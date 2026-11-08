<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('vairus_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

if (isset($_SESSION['user_id']) && time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function valid_csrf()
{
    return is_string($_POST['csrf_token'] ?? null)
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function require_role($role)
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php', true, 303);
        exit;
    }
    try {
        require __DIR__ . '/conn.php';
        $query = $conn->prepare('SELECT id, username, full_name, role, student_id FROM auth_users WHERE id = ? AND active = 1');
        $query->bind_param('i', $_SESSION['user_id']);
        $query->execute();
        $user = $query->get_result()->fetch_assoc();
        if (!$user) {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: ../index.php', true, 303);
            exit;
        }
        if ($user['role'] !== $role) {
            http_response_code(403);
            exit('You do not have access to this page.');
        }
        return $user;
    } catch (Throwable $error) {
        error_log('Account authorization failed: ' . $error->getMessage());
        http_response_code(503);
        exit('Account access is temporarily unavailable. Please try again later.');
    }
}

function require_admin() { return require_role('admin'); }

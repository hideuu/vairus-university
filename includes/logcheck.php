<?php
require_once __DIR__ . '/auth.php';
$login_error = '';
$login_username = '';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['role'] ?? 'admin') === 'student' ? 'student' : 'admin') . '/index.php', true, 303);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!valid_csrf()) {
        http_response_code(403);
        $login_error = 'Your login form expired. Please try again.';
    } elseif ($login_username === '' || strlen($login_username) > 64 || $password === '' || strlen($password) > 1024) {
        $login_error = 'Enter a valid username and password.';
    } else {
        try {
            require __DIR__ . '/conn.php';
            // A database-backed fixed window also applies across browser sessions.
            $ip_key = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $limit = $conn->prepare('INSERT INTO auth_login_limits (ip_key, attempts, window_started) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = IF(window_started < NOW() - INTERVAL 15 MINUTE, 1, attempts + 1), window_started = IF(window_started < NOW() - INTERVAL 15 MINUTE, NOW(), window_started)');
            $limit->bind_param('s', $ip_key);
            $limit->execute();
            $check = $conn->prepare('SELECT attempts FROM auth_login_limits WHERE ip_key = ?');
            $check->bind_param('s', $ip_key);
            $check->execute();
            $attempts = (int) $check->get_result()->fetch_assoc()['attempts'];
            $conn->query('DELETE FROM auth_login_limits WHERE window_started < NOW() - INTERVAL 1 DAY');
            if ($attempts > 10) {
                http_response_code(429);
                header('Retry-After: 900');
                $login_error = 'Too many login attempts. Please try again in 15 minutes.';
            } else {
                $query = $conn->prepare('SELECT id, password_hash, role, active FROM auth_users WHERE username = ? LIMIT 1');
                $query->bind_param('s', $login_username);
                $query->execute();
                $user = $query->get_result()->fetch_assoc();
                $dummy_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                $verified = password_verify($password, $user['password_hash'] ?? $dummy_hash);
                if ($user && $verified && (int) $user['active'] === 1 && in_array($user['role'], ['admin', 'student'], true)) {
                    session_regenerate_id(true);
                    $_SESSION = ['user_id' => (int) $user['id'], 'role' => $user['role'], 'last_activity' => time(), 'csrf_token' => bin2hex(random_bytes(32))];
                    header('Location: ' . ($user['role'] === 'student' ? 'student' : 'admin') . '/index.php', true, 303);
                    exit;
                }
                $login_error = 'Incorrect username or password.';
            }
        } catch (Throwable $error) {
            error_log('Account login failed: ' . $error->getMessage());
            http_response_code(503);
            $login_error = 'Login is temporarily unavailable. Please try again later.';
        }
    }
}

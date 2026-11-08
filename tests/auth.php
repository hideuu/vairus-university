<?php
// Run with TEST_ADMIN_PASSWORD set to the generated administrator password.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$password = getenv('TEST_ADMIN_PASSWORD');
if (!$password) { exit("Set TEST_ADMIN_PASSWORD before running.\n"); }
require __DIR__ . '/../includes/conn.php';
$base = 'http://localhost/vairusuniversity/public/';
$cookie = tempnam(sys_get_temp_dir(), 'vairus-auth-');
$ip = hash('sha256', '127.0.0.1');
$read = $conn->prepare('SELECT attempts, window_started FROM auth_login_limits WHERE ip_key = ?');
$read->bind_param('s', $ip);
$read->execute();
$previous = $read->get_result()->fetch_assoc();
$clear = $conn->prepare('DELETE FROM auth_login_limits WHERE ip_key = ?');
$clear->bind_param('s', $ip);

function request_page($path, $fields = null) {
    global $base, $cookie;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_TIMEOUT => 15, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]);
    if ($fields !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields)); }
    $response = curl_exec($curl);
    if ($response === false) { throw new RuntimeException(curl_error($curl)); }
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$code, $response];
}
function check($condition, $name) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $name); }
    echo 'PASS: ' . $name . "\n";
}
function csrf($response) {
    if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $response, $match)) { throw new RuntimeException('Missing CSRF token'); }
    return $match[1];
}
try {
    $clear->execute();
    [$code, $page] = request_page('admin/index.php');
    check($code === 303, 'Anonymous administrator access redirects');
    check(stripos($page, 'HttpOnly') !== false && stripos($page, 'SameSite=Lax') !== false, 'Protected session cookie');
    [$code, $page] = request_page('index.php');
    check($code === 200, 'Login page loads');
    $token = csrf($page);
    [$code] = request_page('index.php', ['username' => 'admin', 'password' => $password]);
    check($code === 403, 'Missing CSRF token rejected');
    [$code, $page] = request_page('index.php', ['csrf_token' => $token, 'username' => "' OR 1=1 --", 'password' => 'invalid']);
    check($code === 200 && strpos($page, 'Incorrect username or password.') !== false, 'SQL injection login rejected');
    [$code, $page] = request_page('index.php', ['csrf_token' => $token, 'username' => 'admin', 'password' => $password]);
    check($code === 303 && strpos($page, 'Location: admin/index.php') !== false, 'Administrator login succeeds');
    [$code, $page] = request_page('admin/index.php');
    check($code === 200 && strpos($page, 'University Administrator') !== false, 'Authenticated administrator page accessible');
    $logout_token = csrf($page);
    check($logout_token !== $token, 'CSRF token rotates at login');
    [$code] = request_page('admin/logout.php');
    check($code === 405, 'Logout requires POST');
    [$code] = request_page('admin/logout.php', ['csrf_token' => 'invalid']);
    check($code === 403, 'Logout rejects invalid CSRF');
    [$code] = request_page('admin/logout.php', ['csrf_token' => $logout_token]);
    check($code === 303, 'Logout succeeds');
    [$code] = request_page('admin/index.php');
    check($code === 303, 'Logout removes access');
    [$code, $page] = request_page('index.php');
    $token = csrf($page);
    $clear->execute();
    for ($attempt = 0; $attempt < 11; $attempt++) {
        [$code] = request_page('index.php', ['csrf_token' => $token, 'username' => 'admin', 'password' => 'incorrect']);
        check($code === ($attempt < 10 ? 200 : 429), 'Login throttle attempt ' . ($attempt + 1));
    }
    [$code] = request_page('../database/setup_auth.php');
    // The setup script also rejects web requests when the project root is served.
    check($code === 404, 'Administrator setup is inaccessible over HTTP');
} finally {
    $clear->execute();
    if ($previous) {
        $restore = $conn->prepare('INSERT INTO auth_login_limits (ip_key, attempts, window_started) VALUES (?, ?, ?)');
        $restore->bind_param('sis', $ip, $previous['attempts'], $previous['window_started']);
        $restore->execute();
    }
    if (is_file($cookie)) { unlink($cookie); }
}

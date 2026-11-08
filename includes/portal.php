<?php
require_once __DIR__ . '/auth.php';
$user = require_role($portal_role);
require __DIR__ . '/conn.php';
require __DIR__ . '/portal_data.php';
require __DIR__ . '/portal_actions.php';
require __DIR__ . '/portal_view.php';
require __DIR__ . '/report.php';
$modules = portal_modules();
$navigation = $portal_role === 'admin'
    ? ['dashboard'=>'Overview','enrollments'=>'Enrollments','students'=>'Students','courses'=>'Courses','subjects'=>'Subjects','sections'=>'Sections','schedules'=>'Schedules','reports'=>'Reports','users'=>'Accounts','settings'=>'Settings']
    : ['dashboard'=>'Overview','enroll'=>'Enroll in subjects','enrollments'=>'My enrollments','schedules'=>'My schedule','profile'=>'My profile','settings'=>'Settings'];
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'dashboard';
if (!isset($navigation[$page])) { http_response_code(404); exit('Page not found.'); }
$student = $portal_role === 'student' && $user['student_id'] ? row('SELECT * FROM studenttbl WHERE ID = ?',[$user['student_id']]) : null;
$error = '';
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        [$destination,$message] = handle_portal_post();
        $_SESSION['flash'] = $message;
        header('Location: index.php'.$destination,true,303);
        exit;
    } catch (DomainException $exception) { $error = $exception->getMessage(); }
    catch (Throwable $exception) {
        error_log('Portal operation failed: '.$exception->getMessage());
        http_response_code(500);
        $error = 'Your changes could not be saved. Please try again or contact your administrator.';
    }
}
$title = $navigation[$page];
$primary_keys = $portal_role === 'admin' ? ['dashboard','students','subjects','sections','reports'] : ['dashboard','enroll','enrollments','profile'];
$secondary_keys = array_diff(array_keys($navigation), $primary_keys);
// Views can set a 404/500 after the shared shell; send headers after rendering.
ob_start();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1E2A5A">
    <title><?= escape_html($title) ?> | Vairus University</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../portal.css">
    <script src="../form.js" defer></script>
    <script src="../portal.js" defer></script>
</head>
<body class="portal-body portal-<?= escape_html($portal_role) ?> page-<?= escape_html($page) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="topbar">
    <div class="header-inner">
        <a class="sidebar-brand" href="index.php"><span class="brand-mark" aria-hidden="true">V<span></span></span><span>Vairus University<small><?= $portal_role === 'admin' ? 'Admin console' : 'Student portal' ?></small></span></a>
        <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="portal-navigation" hidden><?= icon('menu') ?><span>Menu</span></button>
        <div class="sidebar" id="portal-navigation">
            <nav aria-label="Main navigation">
            <?php foreach ($primary_keys as $key): echo portal_nav_link($key,$navigation[$key],$page); endforeach; ?>
            <details class="nav-more"><summary>More</summary><div class="more-links">
            <?php foreach ($secondary_keys as $key): echo portal_nav_link($key,$navigation[$key],$page); endforeach; ?>
            </div></details>
            </nav>
        </div>
        <div class="account-menu"><span class="avatar" aria-hidden="true"><?= escape_html(mb_strtoupper(mb_substr($user['full_name'],0,1))) ?></span><span class="account-name"><?= escape_html($user['full_name']) ?><small><?= $portal_role === 'admin' ? 'Administrator' : 'Student' ?></small></span><form action="../logout.php" method="POST"><?= csrf_input() ?><button class="logout-button" title="Log out" aria-label="Log out"><?= icon('logout') ?></button></form></div>
    </div>
</header>
<button class="navigation-backdrop" type="button" aria-label="Close navigation" tabindex="-1" hidden></button>
<div class="workspace">
    <main id="main-content" class="main-content" tabindex="-1">
        <?php if ($flash): ?><div class="notice notice-success" role="status"><?= icon('check') ?><span><?= escape_html($flash) ?></span></div><?php endif; ?>
        <?php if ($error): ?><div class="notice notice-error" role="alert" tabindex="-1" data-form-feedback><?= icon('info') ?><span><?= escape_html($error) ?></span></div><?php endif; ?>
        <?php
        try {
            if ($page === 'dashboard') { render_dashboard(); }
            elseif ($page === 'settings') { render_settings(); }
            elseif ($portal_role === 'admin' && $page === 'reports') { render_reports(); }
            elseif ($portal_role === 'student' && $page === 'profile') { render_profile(); }
            elseif ($portal_role === 'student' && $page === 'enroll') { render_enroll(); }
            elseif ($page === 'enrollments') { render_enrollments(); }
            elseif ($portal_role === 'student' && $page === 'schedules') { render_student_schedule(); }
            else { render_records($page); }
        } catch (Throwable $exception) {
            error_log('Portal rendering failed: '.$exception->getMessage());
            http_response_code(500);
            echo '<div class="empty-state"><h1>This page is temporarily unavailable.</h1><p>Please reload or contact your administrator.</p></div>';
        }
        ?>
    </main>
    <footer class="workspace-footer"><span>Vairus University</span><span>Online enrollment portal</span></footer>
</div>
<?php if ($portal_role === 'student'): ?>
<nav class="student-bottom-nav" aria-label="Student shortcuts">
<?php foreach (['dashboard'=>'Home','enroll'=>'Enroll','enrollments'=>'Enrollments','profile'=>'Profile'] as $key=>$label): echo portal_nav_link($key,$label,$page); endforeach; ?>
</nav>
<?php endif; ?>
</body>
</html>
<?php ob_end_flush(); ?>

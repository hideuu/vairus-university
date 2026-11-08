<?php require __DIR__ . '/../includes/logcheck.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1E2A5A">
<title>Log in | Vairus University</title>
<link href="styles.css" rel="stylesheet">
<script src="form.js" defer></script>
<script src="login.js" defer></script>
</head>
<body>
<main class="login-section">
<section class="university-intro" aria-label="Vairus University">
<img class="campus-image" src="assets/img/university_bg.jpg" alt="University building overlooking a landscaped courtyard">
<div class="intro-shade"></div>
<div class="university-brand"><span class="brand-mark" aria-hidden="true">V<span></span></span><span>Vairus<br>University</span></div>
<div class="intro-copy"><span class="gold-rule" aria-hidden="true"></span><h1>Your next chapter<br>starts here.</h1><p>A place to learn, grow, and move forward.<br>Welcome to your university community.</p></div>
<p class="intro-footer">Vairus University &middot; Online enrollment</p>
</section>
<section class="login-panel" aria-labelledby="login-heading">
<div class="panel-top">University portal<span class="portal-indicator" aria-hidden="true"></span></div>
<div class="login-container">
<h2 id="login-heading">Welcome back.</h2>
<p class="login-description">Log in to your university account.</p>
<form method="POST" id="login-form"<?= $login_error !== '' ? ' aria-describedby="form-status"' : '' ?>>
<input type="hidden" name="csrf_token" value="<?= escape_html($_SESSION['csrf_token']) ?>">
<div class="form-field"><label for="username">Username</label><input id="username" type="text" name="username" autocomplete="username" maxlength="64" required placeholder="Enter your username" autocapitalize="none" spellcheck="false" value="<?= escape_html($login_username) ?>"></div>
<div class="form-field"><label for="password">Password</label><div class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"><button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" hidden>Show</button></div></div>
<button type="submit" class="login-button">Log in <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button>
<?php if ($login_error !== ''): ?><p id="form-status" role="alert" tabindex="-1" data-form-feedback><?= escape_html($login_error) ?></p><?php endif; ?>
</form>
<div class="account-help"><p>Need help accessing your account?</p><p>Contact your university administrator.</p></div>
</div>
<footer class="panel-footer"><span>Vairus University</span><span>Learn. Grow. Belong.</span></footer>
</section>
</main>
</body>
</html>

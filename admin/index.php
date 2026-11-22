<?php
declare(strict_types=1);
const UP = '../';
const ADMIN = true;
require __DIR__ . '/../inc/bootstrap.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:; frame-ancestors 'none'");
header('X-Robots-Tag: noindex, nofollow');

$sv = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$showFa = ($sv['show_fa'] ?? '1') !== '0';
$showEn = ($sv['show_en'] ?? '1') !== '0';
if (!$showFa && !$showEn) {
    $showFa = true;
}
$langs = [];
if ($showFa) {
    $langs[] = 'fa';
}
if ($showEn) {
    $langs[] = 'en';
}
$ui = (($sv['default_lang'] ?? 'fa') === 'en') ? 'en' : 'fa';
if (!in_array($ui, $langs, true)) {
    $ui = $langs[0];
}
$faUi = $ui === 'fa';
$pwaName = ($sv['short_name'] ?? '') ?: (($sv['app_name'] ?? '') ?: 'Admin');
$hasIcon = is_file(ROOT . '/uploads/icon-192.png');
$icon = $hasIcon ? '../uploads/icon-192.png?v=' . rawurlencode((string) ($sv['icon'] ?? '1')) : '../assets/icon.svg';
$color = $sv['theme_color'] ?? '#6c5ce7';
$brand = trim((string) ($sv[$faUi ? 'name_fa' : 'name_en'] ?? ''));
if ($brand === '') {
    $brand = trim((string) ($sv[$faUi ? 'name_en' : 'name_fa'] ?? ''));
}
if ($brand === '') {
    $brand = $faUi ? 'پیشخوان' : 'Dashboard';
}
$in = !empty($_SESSION['uid']);
$cfg = ['csrf' => $_SESSION['csrf'], 'langs' => $langs, 'lang' => $ui, 'donate' => (($sv['donate_on'] ?? '0') === '1'), 'donateAsk' => (($sv['donate_on'] ?? '0') === '1' && ($sv['donate_ask'] ?? '1') !== '0')] + ($in ? ['schema' => require ROOT . '/inc/schema.php'] : []);
?>
<!doctype html>
<html lang="<?= $ui ?>" dir="<?= $faUi ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="<?= e($color) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e($pwaName) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<title><?= e($pwaName) ?></title>
<link rel="manifest" href="manifest.php">
<link rel="apple-touch-icon" href="<?= e($icon) ?>">
<link rel="icon" href="<?= e($icon) ?>">
<script src="../assets/js/boot.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Vazirmatn:wght@400;600;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/app.css?v=8">
<link rel="stylesheet" href="../assets/css/admin.css?v=15">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/admin.js?v=17" defer></script>
</head>
<body data-cfg="<?= e(json_encode($cfg)) ?>">
<?php if (!$in): ?>
<main class="wrap">
  <form id="login" class="glass pad login">
    <img class="logo" src="../assets/icon.svg" alt="">
    <h1><?= $faUi ? 'ورود' : 'Welcome back' ?></h1>
    <p class="muted"><?= $faUi ? 'برای مدیریت صفحه وارد شوید.' : 'Sign in to manage your page.' ?></p>
    <label class="f"><span><?= $faUi ? 'نام کاربری' : 'Username' ?></span><input name="user" autocomplete="username" required autofocus></label>
    <label class="f"><span><?= $faUi ? 'رمز عبور' : 'Password' ?></span><input name="pass" type="password" autocomplete="current-password" required></label>
    <button class="btn pri block"><?= $faUi ? 'ورود' : 'Sign in' ?></button>
  </form>
</main>
<?php else: ?>
<div class="shell">
  <aside class="side glass" id="menu-panel">
    <div class="brand"><img src="../assets/icon.svg" alt=""><b><?= e($brand) ?></b></div>
    <nav id="nav" aria-label="<?= $faUi ? 'بخش‌ها' : 'Sections' ?>"></nav>
    <div class="foot">
      <a class="btn" href="../" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i><span><?= $faUi ? 'مشاهده سایت' : 'View site' ?></span></a>
      <button class="btn" id="mode" type="button"><i class="fa-solid fa-circle-half-stroke"></i><span><?= $faUi ? 'حالت شب و روز' : 'Light / dark' ?></span></button>
      <button class="btn" id="out" type="button"><i class="fa-solid fa-right-from-bracket"></i><span><?= $faUi ? 'خروج' : 'Sign out' ?></span></button>
    </div>
  </aside>
  <section class="content">
    <header class="top"><h1 id="title"></h1><div id="actions"></div></header>
    <div id="main"></div>
  </section>
  <button class="menu-fab" id="menu" type="button" aria-controls="menu-panel" aria-expanded="false"><span></span><span></span><span></span></button>
</div>
<?php endif ?>
<div id="toast" role="status" aria-live="polite"></div>
</body>
</html>

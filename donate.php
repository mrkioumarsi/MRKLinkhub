<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/public.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $send = static function (array $body, int $code = 200): void {
        http_response_code($code);
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    };
    try {
        $csrf = (string) ($_SESSION['csrf'] ?? '');
        $got = (string) ($_SERVER['HTTP_X_CSRF'] ?? '');
        if ($csrf === '' || !hash_equals($csrf, $got)) {
            $send(['error' => 'Session expired. Reload the page.'], 403);
        }
        $s = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        if (($s['donate_on'] ?? '0') !== '1') {
            $send(['error' => 'Donate is off'], 403);
        }
        $wait = lock_wait('donate');
        if ($wait > 0) {
            $send(['error' => 'Too many attempts', 'wait' => $wait], 429);
        }
        $name = trim((string) (preg_replace('/\s+/u', ' ', strip_tags((string) ($_POST['who'] ?? ''))) ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($name === '' || $len > 80 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            $wait = lock_fail('donate');
            if ($wait > 0) {
                $send(['error' => 'Too many attempts', 'wait' => $wait], 429);
            }
            $send(['error' => 'Name is required'], 400);
        }
        $wait = lock_fail('donate');
        if ($wait > 0) {
            $send(['error' => 'Too many attempts', 'wait' => $wait], 429);
        }
        donor_add($name);
        $schema = require __DIR__ . '/inc/schema.php';
        $coinIcons = $schema['donates']['fields']['icon'][2];
        donates_ready();
        $rows = db()->query("SELECT icon, title_fa, title_en, address FROM donates WHERE active = 1 AND address <> '' ORDER BY sort, id")->fetchAll();
        $out = [];
        foreach ($rows as $x) {
            $icon = in_array($x['icon'], $coinIcons, true) ? $x['icon'] : $coinIcons[0];
            $out[] = [
                'icon' => $icon,
                'title_fa' => (string) $x['title_fa'],
                'title_en' => (string) $x['title_en'],
                'address' => (string) $x['address'],
            ];
        }
        $send(['ok' => 1, 'rows' => $out]);
    } catch (Throwable $e) {
        $send(['error' => 'Request failed'], 500);
    }
}

track_visit();

$b = public_bundle(true);
$s = $b['s'];
$lang = $b['lang'];
$color = $s['theme_color'] ?? '#6c5ce7';
$faName = (string) ($s['name_fa'] ?? '');
$enName = (string) ($s['name_en'] ?? '');
$who = $lang === 'fa' ? 'حمایت' : 'Donate';
$site = $b['showFa'] && $b['showEn']
    ? trim($enName . ' | ' . $faName, ' |')
    : ($lang === 'fa' ? ($faName !== '' ? $faName : $enName) : ($enName !== '' ? $enName : $faName));
$pageTitle = trim($site . ' | ' . $who, ' |');
$force = $b['forced'] !== '' ? ' data-langforce="' . e($b['forced']) . '"' : '';
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'fa' ? 'rtl' : 'ltr' ?>" data-langs="<?= e(implode(',', $b['enabled'])) ?>"<?= $force ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="theme-color" content="<?= e($color) ?>">
<link rel="manifest" href="manifest.php">
<link rel="apple-touch-icon" href="<?= !empty($s['icon']) ? 'uploads/icon-192.png?v=' . e($s['icon']) : 'assets/icon.svg' ?>">
<link rel="icon" href="<?= !empty($s['icon']) ? 'uploads/icon-192.png?v=' . e($s['icon']) : 'assets/icon.svg' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Vazirmatn:wght@400;600;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="assets/js/boot.js"></script>
<link rel="stylesheet" href="assets/css/app.css?v=<?= MRK_CSS ?>">
<script src="assets/js/qr.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/app.js?v=<?= MRK_JS ?>"></script>
</head>
<body>
<main class="wrap">
<?php if (!$b['donateOn']): ?>
  <section class="glass pad donate-gate">
    <h1><span data-l="fa">حمایت</span><span data-l="en">Donate</span></h1>
    <p class="muted"><span data-l="fa">حمایت خاموش است.</span><span data-l="en">Donate is turned off.</span></p>
  </section>
<?php else: ?>
  <form id="gate" class="glass pad donate-gate" data-csrf="<?= e($_SESSION['csrf'] ?? '') ?>">
    <h1><span data-l="fa">حمایت</span><span data-l="en">Donate</span></h1>
    <p class="muted"><span data-l="fa">نام خود را بنویسید.</span><span data-l="en">Write your name.</span></p>
    <label class="ask">
      <span data-l="fa">نام</span>
      <span data-l="en">Name</span>
      <input name="who" maxlength="80" required autocomplete="name">
    </label>
    <p id="lock" class="muted" hidden></p>
    <button class="btn pri text-btn" type="submit"><span data-l="fa">تایید</span><span data-l="en">Confirm</span></button>
  </form>
  <section id="wallets" hidden>
    <h1><span data-l="fa">حمایت</span><span data-l="en">Donate</span></h1>
    <p class="who"><span data-l="fa">از طرف </span><span data-l="en">From </span><b id="who-name"></b></p>
    <div id="wallet-list" class="accs"></div>
    <p id="wallet-empty" class="glass pad muted" hidden><span data-l="fa">هنوز آدرسی ثبت نشده.</span><span data-l="en">No addresses yet.</span></p>
  </section>
<?php endif ?>
</main>
<?php public_bar($b, true); ?>
</body>
</html>

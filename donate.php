<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/public.php';
track_visit();

$b = public_bundle(true);
$s = $b['s'];
$lang = $b['lang'];
$L = $b['L'];
$schema = require __DIR__ . '/inc/schema.php';
$coinIcons = $schema['donates']['fields']['icon'][2];
$rows = [];
if ($b['donateOn']) {
    try {
        donates_ready();
        $rows = db()->query("SELECT * FROM donates WHERE active = 1 AND address <> '' ORDER BY sort, id")->fetchAll();
    } catch (Throwable $e) {
        $rows = [];
    }
}
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
  <form id="gate" class="glass pad donate-gate">
    <h1><span data-l="fa">حمایت</span><span data-l="en">Donate</span></h1>
    <p class="muted"><span data-l="fa">نام خود را بنویسید.</span><span data-l="en">Write your name.</span></p>
    <label class="ask">
      <span data-l="fa">نام</span>
      <span data-l="en">Name</span>
      <input name="who" maxlength="80" required autocomplete="name">
    </label>
    <button class="btn pri text-btn" type="submit"><span data-l="fa">تایید</span><span data-l="en">Confirm</span></button>
  </form>
  <section id="wallets" hidden>
    <h1><span data-l="fa">حمایت</span><span data-l="en">Donate</span></h1>
    <p class="who"><span data-l="fa">از طرف </span><span data-l="en">From </span><b id="who-name"></b></p>
<?php if (!$rows): ?>
    <p class="glass pad muted"><span data-l="fa">هنوز آدرسی ثبت نشده.</span><span data-l="en">No addresses yet.</span></p>
<?php else: ?>
    <div class="accs">
<?php foreach ($rows as $x):
    $icon = in_array($x['icon'], $coinIcons, true) ? $x['icon'] : $coinIcons[0];
?>
      <article class="acc glass">
        <button class="acc-h" type="button" aria-expanded="false">
          <i class="<?= e($icon) ?>"></i>
          <span><?= $L($x['title_fa'], $x['title_en']) ?></span>
          <i class="fa-solid fa-chevron-down chev"></i>
        </button>
        <div class="acc-b" hidden>
          <p class="addr" dir="ltr"><?= e($x['address']) ?></p>
          <div class="qr" data-qr="<?= e($x['address']) ?>"></div>
          <button class="btn pri text-btn" type="button" data-copy="addr" data-icon="fa-solid fa-copy" data-text="<?= e($x['address']) ?>">
            <i class="fa-solid fa-copy"></i>
            <span data-l="fa">کپی آدرس</span>
            <span data-l="en">Copy address</span>
          </button>
        </div>
      </article>
<?php endforeach ?>
    </div>
<?php endif ?>
  </section>
<?php endif ?>
</main>
<?php public_bar($b, true); ?>
</body>
</html>

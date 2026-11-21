<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';
track_visit();

$s = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$get = fn(string $t): array => db()->query("SELECT * FROM `$t` WHERE active = 1 ORDER BY sort, id")->fetchAll();
$slides = array_filter($get('slides'), fn($x) => $x['image'] !== '');
$links = $get('links');
$projects = $get('projects');

$showFa = ($s['show_fa'] ?? '1') !== '0';
$showEn = ($s['show_en'] ?? '1') !== '0';
if (!$showFa && !$showEn) {
    $showFa = true;
}
$enabled = [];
if ($showFa) {
    $enabled[] = 'fa';
}
if ($showEn) {
    $enabled[] = 'en';
}
$pref = ($s['default_lang'] ?? 'fa') === 'en' ? 'en' : 'fa';
$lang = in_array($pref, $enabled, true) ? $pref : $enabled[0];
$L = function ($fa, $en) use ($showFa, $showEn) {
    $fa = (string) $fa;
    $en = (string) $en;
    $html = '';
    if ($showFa) {
        $html .= '<span data-l="fa">' . e($fa !== '' ? $fa : $en) . '</span>';
    }
    if ($showEn) {
        $html .= '<span data-l="en">' . e($en !== '' ? $en : $fa) . '</span>';
    }
    return $html;
};
$img = fn($f) => 'uploads/' . rawurlencode($f);
$ext = fn($u) => str_starts_with($u, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '';
$color = $s['theme_color'] ?? '#6c5ce7';
$faName = (string) ($s['name_fa'] ?? '');
$enName = (string) ($s['name_en'] ?? '');
$pageTitle = $showFa && $showEn
    ? trim($enName . ' | ' . $faName, ' |')
    : ($lang === 'fa' ? ($faName !== '' ? $faName : $enName) : ($enName !== '' ? $enName : $faName));
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'fa' ? 'rtl' : 'ltr' ?>" data-langs="<?= e(implode(',', $enabled)) ?>">
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
<link rel="stylesheet" href="assets/css/app.css?v=9">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/app.js?v=8"></script>
</head>
<body>
<main class="wrap">
  <header class="me glass">
<?php if (!empty($s['avatar'])): ?>
    <img class="av" src="<?= e($img($s['avatar'])) ?>" alt="">
<?php else: ?>
    <div class="av"></div>
<?php endif ?>
    <h1><?= $L($s['name_fa'] ?? '', $s['name_en'] ?? '') ?></h1>
    <p><?= $L($s['bio_fa'] ?? '', $s['bio_en'] ?? '') ?></p>
  </header>

<?php if ($slides): ?>
  <section class="slider glass" aria-roledescription="carousel">
    <div class="track">
<?php foreach ($slides as $x): $tag = $x['url'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="slide"<?= $x['url'] ? ' href="' . e($x['url']) . '"' . $ext($x['url']) . ' data-hit="slides:' . (int) $x['id'] . '"' : '' ?>>
        <img src="<?= e($img($x['image'])) ?>" alt="">
<?php if ($x['title_fa'] . $x['title_en'] !== ''): ?>
        <span class="cap"><?= $L($x['title_fa'], $x['title_en']) ?></span>
<?php endif ?>
      </<?= $tag ?>>
<?php endforeach ?>
    </div>
<?php if (count($slides) > 1): ?>
    <button class="nav prev" type="button" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="nav next" type="button" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="dots"><?= str_repeat('<b></b>', count($slides)) ?></div>
<?php endif ?>
  </section>
<?php endif ?>

  <nav class="links" aria-label="Links">
<?php foreach ($links as $x): ?>
    <a class="lnk glass" href="<?= e($x['url']) ?>" data-hit="links:<?= (int) $x['id'] ?>"<?= $ext($x['url']) ?>>
      <i class="<?= e($x['icon']) ?>"></i><span><?= $L($x['title_fa'], $x['title_en']) ?></span>
    </a>
<?php endforeach ?>
  </nav>

<?php if ($projects): ?>
  <h2><?= $L('پروژه‌ها', 'Projects') ?></h2>
  <section class="tiles">
<?php foreach ($projects as $x): $tag = $x['url'] ? 'a' : 'div'; ?>
    <<?= $tag ?> class="tile glass"<?= $x['url'] ? ' href="' . e($x['url']) . '"' . $ext($x['url']) . ' data-hit="projects:' . (int) $x['id'] . '"' : '' ?>>
<?php if ($x['image']): ?>
      <img src="<?= e($img($x['image'])) ?>" alt="" loading="lazy">
<?php endif ?>
      <div><h3><?= $L($x['title_fa'], $x['title_en']) ?></h3><p><?= $L($x['desc_fa'], $x['desc_en']) ?></p></div>
    </<?= $tag ?>>
<?php endforeach ?>
  </section>
<?php endif ?>
</main>
<div class="tools">
<?php if (count($enabled) > 1): ?>
  <button class="btn" id="lang" type="button" aria-label="Language"></button>
<?php endif ?>
  <button class="btn" id="theme" type="button" aria-label="Theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
</div>
<div class="bar">
  <a class="btn" id="help" href="https://mrkioumarsi.ir" target="_blank" rel="noopener noreferrer" aria-label="<?= $lang === 'fa' ? 'راهنما' : 'Help' ?>"><i class="fa-solid fa-question"></i></a>
</div>
</body>
</html>

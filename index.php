<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/public.php';
track_visit();
records_ready();

$b = public_bundle(false);
$s = $b['s'];
$showFa = $b['showFa'];
$showEn = $b['showEn'];
$enabled = $b['enabled'];
$lang = $b['lang'];
$L = $b['L'];
$load = function (string $table): array {
    try {
        return db()->query('SELECT * FROM `' . $table . '` WHERE active = 1 ORDER BY sort, id')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
};
$slides = array_values(array_filter($load('slides'), fn($x) => ($x['image'] ?? '') !== ''));
$links = $load('links');
$jobs = $load('jobs');
$projects = $load('projects');
$edu = $load('edu');
$papers = $load('papers');
$img = fn($f) => 'uploads/' . rawurlencode((string) $f);
$ext = fn($u) => str_starts_with((string) $u, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '';
$filled = fn($a, $b): bool => trim((string) $a) !== '' || trim((string) $b) !== '';
$plain = function (string $fa, string $en) use ($lang): string {
    $fa = trim($fa);
    $en = trim($en);
    return $lang === 'fa' ? ($fa !== '' ? $fa : $en) : ($en !== '' ? $en : $fa);
};
$color = $s['theme_color'] ?? '#6c5ce7';
$faName = (string) ($s['name_fa'] ?? '');
$enName = (string) ($s['name_en'] ?? '');
$pageTitle = $showFa && $showEn
    ? trim($enName . ' | ' . $faName, ' |')
    : ($lang === 'fa' ? ($faName !== '' ? $faName : $enName) : ($enName !== '' ? $enName : $faName));
$seoTitle = mrk_line($s, 'seo_title_fa', 'seo_title_en', $lang);
if ($seoTitle !== '') {
    $pageTitle = $seoTitle;
}
$desc = mrk_line($s, 'seo_desc_fa', 'seo_desc_en', $lang);
if ($desc === '') {
    $desc = mrk_line($s, 'bio_fa', 'bio_en', $lang);
}
$canonical = mrk_canonical('index.php');
$image = '';
if (!empty($s['avatar'])) {
    $image = mrk_abs($img($s['avatar']));
} elseif (is_file(ROOT . '/uploads/icon-512.png')) {
    $image = mrk_abs('uploads/icon-512.png');
}
$hasMe = $filled($faName, $enName) || $filled($s['bio_fa'] ?? '', $s['bio_en'] ?? '') || !empty($s['avatar']);
$cv = function (array $rows, string $faTitle, string $enTitle, bool $org, string $hit) use ($L, $filled, $ext): void {
    if (!$rows) {
        return;
    }
    echo '<section class="block"><h2>' . $L($faTitle, $enTitle) . '</h2><div class="cv">';
    foreach ($rows as $x) {
        $url = trim((string) ($x['url'] ?? ''));
        $tag = $url !== '' ? 'a' : 'article';
        echo '<' . $tag . ' class="cv-item glass"';
        if ($url !== '') {
            echo ' href="' . e($url) . '"' . $ext($url);
            if ($hit !== '') {
                echo ' data-hit="' . e($hit) . ':' . (int) $x['id'] . '"';
            }
        }
        echo '><h3>' . $L($x['title_fa'] ?? '', $x['title_en'] ?? '') . '</h3>';
        $meta = [];
        if ($org && $filled($x['org_fa'] ?? '', $x['org_en'] ?? '')) {
            $meta[] = $L($x['org_fa'], $x['org_en']);
        }
        if ($filled($x['when_fa'] ?? '', $x['when_en'] ?? '')) {
            $meta[] = $L($x['when_fa'], $x['when_en']);
        }
        if ($meta) {
            echo '<p class="cv-meta">' . implode('<span class="sep"> · </span>', $meta) . '</p>';
        }
        if ($filled($x['desc_fa'] ?? '', $x['desc_en'] ?? '')) {
            echo '<p>' . $L($x['desc_fa'], $x['desc_en']) . '</p>';
        }
        echo '</' . $tag . '>';
    }
    echo '</div></section>';
};
$same = [];
foreach ($links as $x) {
    $u = (string) ($x['url'] ?? '');
    if (preg_match('~^https?://~i', $u)) {
        $same[] = $u;
    }
}
$personName = mrk_line($s, 'name_fa', 'name_en', $lang);
if ($personName === '') {
    $personName = $pageTitle;
}
$ld = [
    '@context' => 'https://schema.org',
    '@type' => 'ProfilePage',
    'mainEntity' => array_filter([
        '@type' => 'Person',
        'name' => $personName,
        'description' => $desc,
        'url' => $canonical,
        'image' => $image,
        'sameAs' => $same,
    ], fn($v) => $v !== '' && $v !== []),
];
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'fa' ? 'rtl' : 'ltr' ?>" data-langs="<?= e(implode(',', $enabled)) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php public_meta($pageTitle, $desc, $canonical, $image, $lang, true); ?>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/app.js?v=<?= MRK_JS ?>"></script>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body>
<main class="wrap">
<?php foreach (mrk_section_order($s) as $part): ?>
<?php if ($part === 'me' && $hasMe): ?>
  <header class="me glass">
<?php if (!empty($s['avatar'])): ?>
    <img class="av" src="<?= e($img($s['avatar'])) ?>" alt="<?= e($plain($faName, $enName)) ?>">
<?php else: ?>
    <div class="av"></div>
<?php endif ?>
<?php if ($filled($faName, $enName)): ?>
    <h1><?= $L($faName, $enName) ?></h1>
<?php endif ?>
<?php if ($filled($s['bio_fa'] ?? '', $s['bio_en'] ?? '')): ?>
    <p><?= $L($s['bio_fa'] ?? '', $s['bio_en'] ?? '') ?></p>
<?php endif ?>
  </header>
<?php elseif ($part === 'slides' && $slides): ?>
  <section class="slider glass" aria-roledescription="carousel">
    <div class="track">
<?php foreach ($slides as $x): $tag = $x['url'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="slide"<?= $x['url'] ? ' href="' . e($x['url']) . '"' . $ext($x['url']) . ' data-hit="slides:' . (int) $x['id'] . '"' : '' ?>>
        <img src="<?= e($img($x['image'])) ?>" alt="<?= e($plain((string) $x['title_fa'], (string) $x['title_en'])) ?>">
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
<?php elseif ($part === 'links' && $links): ?>
  <nav class="links" aria-label="<?= $lang === 'fa' ? 'لینک‌ها' : 'Links' ?>">
<?php foreach ($links as $x): ?>
    <a class="lnk glass" href="<?= e($x['url']) ?>" data-hit="links:<?= (int) $x['id'] ?>"<?= $ext($x['url']) ?>>
      <i class="<?= e($x['icon']) ?>"></i><span><?= $L($x['title_fa'], $x['title_en']) ?></span>
    </a>
<?php endforeach ?>
  </nav>
<?php elseif ($part === 'jobs'): ?>
<?php $cv($jobs, 'سوابق شغلی', 'Work', true, ''); ?>
<?php elseif ($part === 'projects' && $projects): ?>
  <section class="block">
    <h2><?= $L('پروژه‌ها', 'Projects') ?></h2>
    <div class="tiles">
<?php foreach ($projects as $x): $tag = $x['url'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="tile glass"<?= $x['url'] ? ' href="' . e($x['url']) . '"' . $ext($x['url']) . ' data-hit="projects:' . (int) $x['id'] . '"' : '' ?>>
<?php if ($x['image']): ?>
        <img src="<?= e($img($x['image'])) ?>" alt="<?= e($plain((string) $x['title_fa'], (string) $x['title_en'])) ?>" loading="lazy">
<?php endif ?>
        <div><h3><?= $L($x['title_fa'], $x['title_en']) ?></h3><?php if ($filled($x['desc_fa'], $x['desc_en'])): ?><p><?= $L($x['desc_fa'], $x['desc_en']) ?></p><?php endif ?></div>
      </<?= $tag ?>>
<?php endforeach ?>
    </div>
  </section>
<?php elseif ($part === 'edu'): ?>
<?php $cv($edu, 'سوابق تحصیلی', 'Education', true, ''); ?>
<?php elseif ($part === 'papers'): ?>
<?php $cv($papers, 'مقاله‌های علمی', 'Papers', false, 'papers'); ?>
<?php endif ?>
<?php endforeach ?>
</main>
<?php public_bar($b, false); ?>
</body>
</html>

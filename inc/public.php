<?php
declare(strict_types=1);

const MRK_CSS = '14';
const MRK_JS = '14';

function public_bundle(bool $queryLang = false): array
{
    $s = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
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
    $forced = '';
    if ($queryLang) {
        $q = (string) ($_GET['lang'] ?? '');
        if (in_array($q, $enabled, true)) {
            $lang = $q;
            $forced = $q;
        }
    }
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
    return [
        's' => $s,
        'showFa' => $showFa,
        'showEn' => $showEn,
        'enabled' => $enabled,
        'lang' => $lang,
        'forced' => $forced,
        'showHelp' => ($s['show_help'] ?? '1') !== '0',
        'donateOn' => ($s['donate_on'] ?? '0') === '1',
        'askName' => ($s['donate_ask'] ?? '1') !== '0',
        'L' => $L,
    ];
}

function public_bar(array $b, bool $back): void
{
    $fa = $b['lang'] === 'fa';
    $aria = function (string $faText, string $enText) use ($fa): string {
        return ' data-fa="' . e($faText) . '" data-en="' . e($enText) . '" aria-label="' . e($fa ? $faText : $enText) . '"';
    };
    echo '<div class="bar">';
    echo '<button class="btn" id="theme" type="button"' . $aria('حالت شب و روز', 'Theme') . '><i class="fa-solid fa-circle-half-stroke"></i></button>';
    if (!empty($b['donateOn'])) {
        echo '<a class="btn" id="donate" href="donate.php"' . $aria('حمایت', 'Donate') . '><i class="fa-solid fa-circle-dollar-to-slot"></i></a>';
    }
    echo '<button class="btn" id="copy-site" type="button" data-copy="page" data-icon="fa-solid fa-link"' . $aria('کپی لینک', 'Copy link') . '><i class="fa-solid fa-link"></i></button>';
    if (count($b['enabled']) > 1) {
        echo '<button class="btn" id="lang" type="button" aria-label="' . e($fa ? 'زبان' : 'Language') . '"></button>';
    }
    if (!empty($b['showHelp'])) {
        echo '<a class="btn" id="help" href="https://mrkioumarsi.ir" target="_blank" rel="noopener noreferrer"' . $aria('راهنما', 'Help') . '><i class="fa-solid fa-question"></i></a>';
    }
    if ($back) {
        echo '<a class="btn" id="back" href="./"' . $aria('بازگشت', 'Back') . '><i class="fa-solid fa-arrow-left"></i></a>';
    }
    echo '</div>';
}

function mrk_line(array $s, string $faKey, string $enKey, string $lang): string
{
    $fa = trim((string) ($s[$faKey] ?? ''));
    $en = trim((string) ($s[$enKey] ?? ''));
    $text = $lang === 'fa' ? ($fa !== '' ? $fa : $en) : ($en !== '' ? $en : $fa);
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function mrk_section_order(array $s): array
{
    $default = ['me', 'slides', 'links', 'jobs', 'projects', 'edu', 'papers'];
    $out = [];
    foreach (explode(',', (string) ($s['section_order'] ?? '')) as $id) {
        $id = trim($id);
        if (in_array($id, $default, true) && !in_array($id, $out, true)) {
            $out[] = $id;
        }
    }
    foreach ($default as $id) {
        if (!in_array($id, $out, true)) {
            $out[] = $id;
        }
    }
    return $out;
}

function mrk_origin(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.:-]+$/', $host)) {
        return '';
    }
    return (request_is_https() ? 'https' : 'http') . '://' . $host;
}

function mrk_abs(string $rel): string
{
    $origin = mrk_origin();
    if ($origin === '') {
        return '';
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : rtrim($dir, '/');
    return $origin . $dir . '/' . ltrim($rel, '/');
}

function mrk_canonical(string $file, string $query = ''): string
{
    $origin = mrk_origin();
    if ($origin === '') {
        return '';
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : rtrim($dir, '/');
    $path = $file === 'index.php' ? ($dir === '' ? '/' : $dir . '/') : $dir . '/' . $file;
    return $origin . $path . $query;
}

function public_meta(string $title, string $desc, string $canonical, string $image, string $lang, bool $index): void
{
    if (function_exists('mb_substr')) {
        $desc = mb_substr($desc, 0, 300, 'UTF-8');
    } elseif (strlen($desc) > 300) {
        $desc = substr($desc, 0, 300);
    }
    $desc = trim($desc);
    $locale = $lang === 'fa' ? 'fa_IR' : 'en_US';
    echo '<title>' . e($title) . '</title>' . "\n";
    if ($desc !== '') {
        echo '<meta name="description" content="' . e($desc) . '">' . "\n";
    }
    echo '<meta name="robots" content="' . ($index ? 'index,follow' : 'noindex,nofollow') . '">' . "\n";
    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . e($canonical) . '">' . "\n";
        echo '<meta property="og:url" content="' . e($canonical) . '">' . "\n";
    }
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="' . e($title) . '">' . "\n";
    echo '<meta property="og:title" content="' . e($title) . '">' . "\n";
    echo '<meta property="og:locale" content="' . e($locale) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<meta name="twitter:title" content="' . e($title) . '">' . "\n";
    if ($desc !== '') {
        echo '<meta property="og:description" content="' . e($desc) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . e($desc) . '">' . "\n";
    }
    if ($image !== '') {
        echo '<meta property="og:image" content="' . e($image) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . e($image) . '">' . "\n";
    }
}

<?php
declare(strict_types=1);

const MRK_CSS = '13';
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

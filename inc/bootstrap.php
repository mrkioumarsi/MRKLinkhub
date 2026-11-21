<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: ' . UP . 'install/');
    exit;
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
date_default_timezone_set('Asia/Tehran');

function request_is_https(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
        return true;
    }
    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function expire_cookie(string $name, string $path, bool $secure): void
{
    setcookie($name, '', [
        'expires' => time() - 42000,
        'path' => $path,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function session_dir(): string
{
    $candidates = [dirname(ROOT) . '/.mrk-sessions', ROOT . '/.mrk-sessions'];
    $root = realpath(ROOT) ?: ROOT;
    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            continue;
        }
        $real = realpath($dir) ?: $dir;
        if (str_starts_with($real, $root)) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        return $dir;
    }
    return '';
}

if (defined('ADMIN')) {
    $secure = request_is_https();
    $life = 60 * 60 * 24 * 30;
    $save = session_dir();
    if ($save !== '') {
        ini_set('session.save_path', $save);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_lifetime', (string) $life);
    ini_set('session.cookie_path', '/');
    ini_set('session.gc_maxlifetime', (string) $life);
    session_name('lbsid');
    session_set_cookie_params([
        'lifetime' => $life,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    expire_cookie('lbsid', '/admin', $secure);
    expire_cookie('lbsid', '/admin/', $secure);
    session_start();
    setcookie(session_name(), session_id(), [
        'expires' => time() + $life,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';
        $pdo = new PDO(
            "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function visits_ready(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec('CREATE TABLE IF NOT EXISTS visits (day DATE NOT NULL PRIMARY KEY, n INT UNSIGNED NOT NULL DEFAULT 0)');
    $done = true;
}

function track_visit(): void
{
    try {
        $day = date('Y-m-d');
        if (($_COOKIE['lv'] ?? '') === $day) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|wget|curl/i', $ua)) {
            return;
        }
        visits_ready();
        db()->prepare('INSERT INTO visits (day, n) VALUES (?, 1) ON DUPLICATE KEY UPDATE n = n + 1')->execute([$day]);
        setcookie('lv', $day, [
            'expires' => strtotime('tomorrow'),
            'path' => '/',
            'secure' => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } catch (Throwable $e) {
    }
}

function visit_stats(string $lang = 'fa'): array
{
    visits_ready();
    $from = date('Y-m-d', strtotime('-29 days'));
    $st = db()->prepare('SELECT day, n FROM visits WHERE day >= ?');
    $st->execute([$from]);
    $map = [];
    foreach ($st->fetchAll() as $r) {
        $map[substr((string) $r['day'], 0, 10)] = (int) $r['n'];
    }
    $days = [];
    $today = $week = $month = 0;
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $n = $map[$d] ?? 0;
        $days[] = ['d' => $d, 'n' => $n];
        $month += $n;
        if ($i < 7) {
            $week += $n;
        }
        if ($i === 0) {
            $today = $n;
        }
    }
    return ['days' => $days, 'today' => $today, 'week' => $week, 'month' => $month, 'clicks' => click_list($lang)];
}

function track_click(string $kind, int $id): void
{
    try {
        if (!in_array($kind, ['links', 'slides', 'projects'], true) || $id < 1) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|wget|curl/i', $ua)) {
            return;
        }
        $sql = 'SELECT id FROM `' . $kind . '` WHERE id = ? AND active = 1';
        if ($kind !== 'links') {
            $sql .= " AND url <> ''";
        }
        $st = db()->prepare($sql);
        $st->execute([$id]);
        if (!$st->fetch()) {
            return;
        }
        db()->exec('CREATE TABLE IF NOT EXISTS clicks (kind VARCHAR(16) NOT NULL, item INT UNSIGNED NOT NULL, n INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (kind, item))');
        db()->prepare('INSERT INTO clicks (kind, item, n) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE n = n + 1')->execute([$kind, $id]);
    } catch (Throwable $e) {
    }
}

function click_list(string $lang): array
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS clicks (kind VARCHAR(16) NOT NULL, item INT UNSIGNED NOT NULL, n INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (kind, item))');
        $map = [];
        foreach (db()->query('SELECT kind, item, n FROM clicks') as $r) {
            $map[$r['kind'] . ':' . $r['item']] = (int) $r['n'];
        }
        $out = [];
        $en = $lang === 'en';
        $add = function (string $kind, string $where) use (&$out, $map, $en): void {
            $rows = db()->query('SELECT id, title_fa, title_en FROM `' . $kind . '` WHERE active = 1' . $where . ' ORDER BY sort, id')->fetchAll();
            foreach ($rows as $r) {
                $faT = trim((string) $r['title_fa']);
                $enT = trim((string) $r['title_en']);
                $title = $en ? ($enT !== '' ? $enT : $faT) : ($faT !== '' ? $faT : $enT);
                $out[] = ['t' => $title !== '' ? $title : ('#' . $r['id']), 'n' => $map[$kind . ':' . $r['id']] ?? 0];
            }
        };
        $add('slides', " AND url <> ''");
        $add('links', '');
        $add('projects', " AND url <> ''");
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

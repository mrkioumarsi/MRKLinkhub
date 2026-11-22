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
mrk_tz();

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

function mrk_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
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
        session_start();
        setcookie(session_name(), session_id(), [
            'expires' => time() + $life,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

if (defined('ADMIN')) {
    mrk_session();
    $dead = gmdate('D, d M Y H:i:s', time() - 42000) . ' GMT';
    $secureFlag = request_is_https() ? '; secure' : '';
    foreach (['/admin', '/admin/'] as $path) {
        header(
            'Set-Cookie: lbsid=deleted; expires=' . $dead . '; Max-Age=0; path=' . $path . $secureFlag . '; HttpOnly; SameSite=Lax',
            false
        );
    }
}

function mrk_tz(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $tz = 'Asia/Tehran';
    try {
        $st = db()->prepare('SELECT v FROM settings WHERE k = ?');
        $st->execute(['tz']);
        $got = (string) $st->fetchColumn();
        if ($got !== '' && in_array($got, timezone_identifiers_list(), true)) {
            $tz = $got;
        }
    } catch (Throwable $e) {
    }
    date_default_timezone_set($tz);
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

function donates_ready(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec("CREATE TABLE IF NOT EXISTS donates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        icon VARCHAR(60) NOT NULL DEFAULT '',
        title_fa VARCHAR(190) NOT NULL DEFAULT '',
        title_en VARCHAR(190) NOT NULL DEFAULT '',
        address VARCHAR(500) NOT NULL DEFAULT '',
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function donate_rows(): array
{
    $schema = require __DIR__ . '/schema.php';
    $coinIcons = $schema['donates']['fields']['icon'][2];
    donates_ready();
    $rows = db()->query("SELECT icon, title_fa, title_en, address FROM donates WHERE active = 1 AND address <> '' ORDER BY sort, id")->fetchAll();
    $out = [];
    foreach ($rows as $x) {
        $icon = in_array($x['icon'], $coinIcons, true) ? (string) $x['icon'] : $coinIcons[0];
        $out[] = [
            'icon' => $icon,
            'title_fa' => (string) $x['title_fa'],
            'title_en' => (string) $x['title_en'],
            'address' => (string) $x['address'],
        ];
    }
    return $out;
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

function lock_span(int $strike): int
{
    if ($strike <= 1) {
        return 3600;
    }
    if ($strike === 2) {
        return 12 * 3600;
    }
    if ($strike === 3) {
        return 24 * 3600;
    }
    $shift = min(2, max(0, $strike - 4));
    return (7 * 86400) << $shift;
}

function locks_ready(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec("CREATE TABLE IF NOT EXISTS locks (
        scope VARCHAR(16) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        strikes INT UNSIGNED NOT NULL DEFAULT 0,
        tries INT UNSIGNED NOT NULL DEFAULT 0,
        seen INT UNSIGNED NOT NULL DEFAULT 0,
        until INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (scope, ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return strlen($ip) > 45 ? substr($ip, 0, 45) : $ip;
}

function lock_wait(string $scope): int
{
    locks_ready();
    $st = db()->prepare('SELECT until FROM locks WHERE scope = ? AND ip = ?');
    $st->execute([$scope, client_ip()]);
    $wait = (int) $st->fetchColumn() - time();
    return $wait > 0 ? $wait : 0;
}

function lock_fail(string $scope): int
{
    locks_ready();
    $ip = client_ip();
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('INSERT IGNORE INTO locks (scope, ip, strikes, tries, seen, until) VALUES (?, ?, 0, 0, 0, 0)')->execute([$scope, $ip]);
        $st = $db->prepare('SELECT strikes, tries, seen, until FROM locks WHERE scope = ? AND ip = ? FOR UPDATE');
        $st->execute([$scope, $ip]);
        $row = $st->fetch() ?: ['strikes' => 0, 'tries' => 0, 'seen' => 0, 'until' => 0];
        $now = time();
        $until = (int) $row['until'];
        if ($until > $now) {
            $db->commit();
            return $until - $now;
        }
        $tries = (int) $row['tries'];
        $seen = (int) $row['seen'];
        mrk_tz();
        if ($seen > 0 && date('Y-m-d', $seen) !== date('Y-m-d')) {
            $tries = 0;
        }
        $tries++;
        $strikes = (int) $row['strikes'];
        $wait = 0;
        if ($tries >= 5) {
            $strikes++;
            $tries = 0;
            $wait = lock_span($strikes);
            $until = $now + $wait;
        } else {
            $until = 0;
        }
        $db->prepare('UPDATE locks SET strikes = ?, tries = ?, seen = ?, until = ? WHERE scope = ? AND ip = ?')
            ->execute([$strikes, $tries, $now, $until, $scope, $ip]);
        $db->commit();
        return $wait;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function lock_clear(string $scope): void
{
    try {
        locks_ready();
        db()->prepare('DELETE FROM locks WHERE scope = ? AND ip = ?')->execute([$scope, client_ip()]);
    } catch (Throwable $e) {
    }
}

function donors_ready(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db = db();
    $db->exec("CREATE TABLE IF NOT EXISTS donors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        day DATE NULL,
        n INT UNSIGNED NOT NULL DEFAULT 1,
        t INT UNSIGNED NOT NULL,
        UNIQUE KEY ip_day (ip, day),
        KEY (t)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cols = [];
    foreach ($db->query('SHOW COLUMNS FROM donors') as $c) {
        $cols[(string) $c['Field']] = true;
    }
    if (!isset($cols['ip'])) {
        $db->exec("ALTER TABLE donors ADD ip VARCHAR(45) NOT NULL DEFAULT ''");
    }
    if (!isset($cols['day'])) {
        $db->exec('ALTER TABLE donors ADD day DATE NULL');
    }
    if (!isset($cols['n'])) {
        $db->exec('ALTER TABLE donors ADD n INT UNSIGNED NOT NULL DEFAULT 1');
    }
    $db->exec("DELETE FROM donors WHERE ip = '' OR day IS NULL");
    $has = false;
    foreach ($db->query('SHOW INDEX FROM donors') as $ix) {
        if ((string) ($ix['Key_name'] ?? '') === 'ip_day') {
            $has = true;
            break;
        }
    }
    if (!$has) {
        $db->exec('DELETE d1 FROM donors d1 INNER JOIN donors d2 ON d1.ip = d2.ip AND d1.day = d2.day AND d1.id > d2.id');
        $db->exec('ALTER TABLE donors ADD UNIQUE KEY ip_day (ip, day)');
    }
    $done = true;
}

function donor_purge(): void
{
    donors_ready();
    mrk_tz();
    db()->prepare("DELETE FROM donors WHERE ip = '' OR day IS NULL OR day < ?")->execute([date('Y-m-d')]);
}

function donor_add(string $name): void
{
    donor_purge();
    $ip = client_ip();
    if ($ip === '') {
        return;
    }
    $now = time();
    db()->prepare('INSERT INTO donors (name, ip, day, n, t) VALUES (?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE n = n + 1, t = ?')
        ->execute([$name, $ip, date('Y-m-d'), $now, $now]);
}

function donor_list(): array
{
    try {
        donor_purge();
        $st = db()->prepare('SELECT name, n FROM donors WHERE day = ? AND ip <> \'\' ORDER BY t DESC LIMIT 40');
        $st->execute([date('Y-m-d')]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = ['n' => (string) $r['name'], 'c' => (int) $r['n']];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function donate_names_on(): bool
{
    try {
        $s = db()->query("SELECT k, v FROM settings WHERE k IN ('donate_on','donate_ask')")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {
        return false;
    }
    return ($s['donate_on'] ?? '0') === '1' && ($s['donate_ask'] ?? '1') !== '0';
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
    return ['days' => $days, 'today' => $today, 'week' => $week, 'month' => $month, 'clicks' => click_list($lang), 'donors' => donate_names_on() ? donor_list() : []];
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
                $key = ['slides' => 'slide', 'links' => 'link', 'projects' => 'project'][$kind] ?? 'link';
                $out[] = ['t' => $title !== '' ? $title : ('#' . $r['id']), 'n' => $map[$kind . ':' . $r['id']] ?? 0, 'k' => $key];
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

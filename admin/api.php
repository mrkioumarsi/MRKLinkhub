<?php
declare(strict_types=1);
const UP = '../';
const ADMIN = true;
require __DIR__ . '/../inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $d, int $code = 200)
{
    http_response_code($code);
    echo json_encode($d);
    exit;
}

// Every request must carry the session's CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(['error' => 'Invalid request'], 405);
}
// PHP drops the whole body when it exceeds post_max_size.
if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    out(['error' => 'The file is larger than the server allows. Use a smaller image.'], 413);
}
if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF'] ?? '')) {
    out(['error' => 'Session expired. Reload the page.'], 403);
}

$db = db();
$a = $_POST['action'] ?? '';

if ($a === 'login') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $db->prepare('DELETE FROM attempts WHERE t < ?')->execute([time() - 900]);
    $n = $db->prepare('SELECT COUNT(*) FROM attempts WHERE ip = ?');
    $n->execute([$ip]);
    if ((int) $n->fetchColumn() >= 5) {
        out(['error' => 'Too many attempts. Try again in 15 minutes.'], 429);
    }
    $u = $db->prepare('SELECT id, pass FROM admins WHERE username = ?');
    $u->execute([(string) ($_POST['user'] ?? '')]);
    $row = $u->fetch();
    if (!$row || !password_verify((string) ($_POST['pass'] ?? ''), $row['pass'])) {
        $db->prepare('INSERT INTO attempts (ip, t) VALUES (?, ?)')->execute([$ip, time()]);
        out(['error' => 'Wrong username or password'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $row['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    $db->prepare('DELETE FROM attempts WHERE ip = ?')->execute([$ip]);
    out(['ok' => 1]);
}

if (empty($_SESSION['uid'])) {
    out(['error' => 'Not authorized'], 401);
}

if ($a === 'stats') {
    try {
        out(visit_stats(($_POST['lang'] ?? '') === 'en' ? 'en' : 'fa'));
    } catch (Throwable $e) {
        out(['days' => [], 'today' => 0, 'week' => 0, 'month' => 0, 'clicks' => []]);
    }
}

if ($a === 'logout') {
    $_SESSION = [];
    $secure = request_is_https();
    expire_cookie(session_name(), '/', $secure);
    expire_cookie(session_name(), '/admin', $secure);
    expire_cookie(session_name(), '/admin/', $secure);
    session_destroy();
    out(['ok' => 1]);
}

if ($a === 'update') {
    require ROOT . '/inc/update.php';
    try {
        out((($_POST['op'] ?? '') === 'apply') ? apply_update() : check_update());
    } catch (Throwable $e) {
        out(['error' => $e->getMessage()], 500);
    }
}

if ($a === 'password') {
    $st = $db->prepare('SELECT pass FROM admins WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    if (!password_verify((string) ($_POST['cur'] ?? ''), (string) $st->fetchColumn())) {
        out(['error' => 'Current password is wrong'], 403);
    }
    $new = (string) ($_POST['new'] ?? '');
    if (strlen($new) < 10) {
        out(['error' => 'New password needs at least 10 characters'], 422);
    }
    $db->prepare('UPDATE admins SET pass = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']]);
    out(['ok' => 1]);
}

if ($a === 'upload') {
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $big = [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE];
        out(['error' => in_array($f['error'] ?? 0, $big, true) ? 'The file is larger than the server allows' : 'Upload failed'], 400);
    }
    if ($f['size'] > 5000000) {
        out(['error' => 'Image is larger than 5 MB'], 400);
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
    if (!$ext) {
        out(['error' => 'Only JPG, PNG or WebP images are allowed'], 400);
    }
    if (!is_writable(ROOT . '/uploads')) {
        out(['error' => 'The uploads folder is not writable. Set its permission to 755.'], 500);
    }
    $name = bin2hex(random_bytes(8)) . ".$ext";
    $dest = ROOT . '/uploads/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        out(['error' => 'Could not save the file'], 500);
    }
    @chmod($dest, 0644);
    // PWA icon: center-crop to a square and export the two sizes browsers require.
    if (($_POST['field'] ?? '') === 'icon' && function_exists('imagecreatefromstring')) {
        $im = @imagecreatefromstring((string) file_get_contents($dest));
        if ($im) {
            $w = imagesx($im);
            $h = imagesy($im);
            $m = min($w, $h);
            foreach ([192, 512] as $px) {
                $o = imagecreatetruecolor($px, $px);
                imagealphablending($o, false);
                imagesavealpha($o, true);
                imagecopyresampled($o, $im, 0, 0, intdiv($w - $m, 2), intdiv($h - $m, 2), $px, $px, $m, $m);
                imagepng($o, ROOT . "/uploads/icon-$px.png");
            }
        }
    }
    out(['file' => $name]);
}

$S = require ROOT . '/inc/schema.php';
$ent = (string) ($_POST['entity'] ?? '');
if (!isset($S[$ent])) {
    out(['error' => 'Bad request'], 400);
}

if ($a === 'list') {
    if ($ent === 'settings') {
        out(['rows' => (object) $db->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR)]);
    }
    out(['rows' => $db->query("SELECT * FROM `$ent` ORDER BY sort, id")->fetchAll()]);
}

if ($a === 'save') {
    $parts = [
        'intro' => ['name_fa', 'name_en', 'bio_fa', 'bio_en', 'avatar'],
        'lang' => ['default_lang', 'show_fa', 'show_en'],
        'pwa' => ['app_name', 'short_name', 'theme_color', 'icon'],
    ];
    $only = null;
    if ($ent === 'settings') {
        $part = (string) ($_POST['part'] ?? '');
        if (!isset($parts[$part])) {
            out(['error' => 'Bad request'], 400);
        }
        $only = array_flip($parts[$part]);
    }
    $v = [];
    foreach ($S[$ent]['fields'] as $k => $d) {
        if ($only !== null && !isset($only[$k])) {
            continue;
        }
        if ($k === 'sort' && !isset($_POST['sort'])) {
            continue; // order is managed with the arrow buttons
        }
        $x = trim((string) ($_POST[$k] ?? ''));
        if ($d[1] === 'check') {
            $x = (int) !empty($_POST[$k]);
        } elseif ($d[1] === 'number') {
            $x = (int) $x;
        } elseif ($d[1] === 'url' && $x !== '' && !preg_match('~^(https?://|mailto:|tel:)~i', $x)) {
            out(['error' => "{$d[0]} must start with https://, mailto: or tel:"], 422);
        } elseif ($d[1] === 'select' && !in_array($x, $d[2], true)) {
            $x = $d[2][0];
        } elseif ($d[1] === 'image' && $x !== '' && !preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $x)) {
            $x = '';
        } elseif ($d[1] === 'color' && !preg_match('/^#[0-9a-f]{6}$/i', $x)) {
            $x = '#6c5ce7';
        }
        $v[$k] = $x;
    }
    if ($ent === 'settings') {
        if (isset($v['show_fa'], $v['show_en']) && !$v['show_fa'] && !$v['show_en']) {
            $v['show_fa'] = 1;
        }
        if (isset($v['default_lang'])) {
            $on = [];
            if (!isset($v['show_fa']) || $v['show_fa']) {
                $on[] = 'fa';
            }
            if (!isset($v['show_en']) || $v['show_en']) {
                $on[] = 'en';
            }
            if (!in_array($v['default_lang'], $on, true)) {
                $v['default_lang'] = $on[0];
            }
        }
        $st = $db->prepare('REPLACE INTO settings (k, v) VALUES (?, ?)');
        foreach ($v as $k => $x) {
            $st->execute([$k, $x]);
        }
        out(['ok' => 1]);
    }
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        $v['sort'] = (int) $db->query("SELECT COALESCE(MAX(sort), 0) + 1 FROM `$ent`")->fetchColumn();
    }
    $ks = array_keys($v); // keys come from the schema whitelist, never from the request
    if ($id) {
        $set = implode(', ', array_map(fn($k) => "`$k` = :$k", $ks));
        $db->prepare("UPDATE `$ent` SET $set WHERE id = :id")->execute($v + ['id' => $id]);
    } else {
        $db->prepare("INSERT INTO `$ent` (`" . implode('`, `', $ks) . '`) VALUES (:' . implode(', :', $ks) . ')')->execute($v);
    }
    out(['ok' => 1]);
}

if ($a === 'move' && $ent !== 'settings') {
    $ids = array_map('intval', $db->query("SELECT id FROM `$ent` ORDER BY sort, id")->fetchAll(PDO::FETCH_COLUMN));
    $i = array_search((int) ($_POST['id'] ?? 0), $ids, true);
    if ($i !== false) {
        $j = $i + (($_POST['dir'] ?? '') === 'up' ? -1 : 1);
        if (isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        }
    }
    $st = $db->prepare("UPDATE `$ent` SET sort = ? WHERE id = ?");
    foreach ($ids as $n => $id) {
        $st->execute([$n + 1, $id]);
    }
    out(['ok' => 1]);
}

if ($a === 'delete' && $ent !== 'settings') {
    $db->prepare("DELETE FROM `$ent` WHERE id = ?")->execute([(int) ($_POST['id'] ?? 0)]);
    out(['ok' => 1]);
}

out(['error' => 'Unknown action'], 400);

<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$cfgFile = $root . '/inc/config.php';
if (is_file($cfgFile)) {
    exit('Already installed. Delete the install folder.');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$err = '';
$done = false;
$old = [
    'host' => 'localhost',
    'name' => '',
    'user' => '',
    'auser' => 'admin',
];
foreach ($old as $key => $value) {
    if (isset($_POST[$key])) {
        $old[$key] = (string) $_POST[$key];
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (PHP_VERSION_ID < 80000 || !extension_loaded('pdo_mysql')) {
            throw new RuntimeException('PHP 8.0 or newer with pdo_mysql is required.');
        }
        $auser = trim((string) ($_POST['auser'] ?? ''));
        $apass = (string) ($_POST['apass'] ?? '');
        if (!preg_match('/^\w{3,30}$/', $auser) || strlen($apass) < 10) {
            throw new RuntimeException('Username: 3-30 letters or digits. Password: at least 10 characters.');
        }

        $db = [
            'host' => trim((string) ($_POST['host'] ?? '')),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'user' => trim((string) ($_POST['user'] ?? '')),
            'pass' => (string) ($_POST['pass'] ?? ''),
        ];
        if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '') {
            throw new RuntimeException('Database host, name, and user are required.');
        }

        $pdo = new PDO(
            'mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=utf8mb4',
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $uploads = $root . '/uploads';
        if (!is_dir($uploads) && !mkdir($uploads, 0755, true)) {
            throw new RuntimeException('Could not create the uploads folder. Create it and set permission 755.');
        }

        $base = "title_fa VARCHAR(190) NOT NULL DEFAULT '', title_en VARCHAR(190) NOT NULL DEFAULT '', url VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1";
        $image = "image VARCHAR(100) NOT NULL DEFAULT ''";
        $pk = 'id INT AUTO_INCREMENT PRIMARY KEY';
        $tables = [
            'settings (k VARCHAR(40) PRIMARY KEY, v TEXT)',
            'admins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(30) NOT NULL UNIQUE, pass VARCHAR(255) NOT NULL)',
            'attempts (ip VARCHAR(45) NOT NULL, t INT NOT NULL, KEY (ip))',
            'locks (scope VARCHAR(16) NOT NULL, ip VARCHAR(45) NOT NULL, strikes INT UNSIGNED NOT NULL DEFAULT 0, tries INT UNSIGNED NOT NULL DEFAULT 0, seen INT UNSIGNED NOT NULL DEFAULT 0, until INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (scope, ip))',
            "donors (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, ip VARCHAR(45) NOT NULL DEFAULT '', day DATE NULL, n INT UNSIGNED NOT NULL DEFAULT 1, t INT UNSIGNED NOT NULL, UNIQUE KEY ip_day (ip, day), KEY (t))",
            "slides ($pk, $base, $image)",
            "links ($pk, icon VARCHAR(60) NOT NULL DEFAULT '', $base)",
            "projects ($pk, $base, desc_fa TEXT, desc_en TEXT, $image)",
            "jobs ($pk, title_fa VARCHAR(190) NOT NULL DEFAULT '', title_en VARCHAR(190) NOT NULL DEFAULT '', org_fa VARCHAR(190) NOT NULL DEFAULT '', org_en VARCHAR(190) NOT NULL DEFAULT '', when_fa VARCHAR(80) NOT NULL DEFAULT '', when_en VARCHAR(80) NOT NULL DEFAULT '', desc_fa TEXT, desc_en TEXT, sort INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1)",
            "edu ($pk, title_fa VARCHAR(190) NOT NULL DEFAULT '', title_en VARCHAR(190) NOT NULL DEFAULT '', org_fa VARCHAR(190) NOT NULL DEFAULT '', org_en VARCHAR(190) NOT NULL DEFAULT '', when_fa VARCHAR(80) NOT NULL DEFAULT '', when_en VARCHAR(80) NOT NULL DEFAULT '', desc_fa TEXT, desc_en TEXT, sort INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1)",
            "papers ($pk, title_fa VARCHAR(190) NOT NULL DEFAULT '', title_en VARCHAR(190) NOT NULL DEFAULT '', when_fa VARCHAR(80) NOT NULL DEFAULT '', when_en VARCHAR(80) NOT NULL DEFAULT '', desc_fa TEXT, desc_en TEXT, url VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1)",
            "donates ($pk, icon VARCHAR(60) NOT NULL DEFAULT '', title_fa VARCHAR(190) NOT NULL DEFAULT '', title_en VARCHAR(190) NOT NULL DEFAULT '', address VARCHAR(500) NOT NULL DEFAULT '', sort INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1)",
        ];
        foreach ($tables as $table) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $table . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        $pdo->prepare('INSERT INTO admins (username, pass) VALUES (?, ?)')->execute([
            $auser,
            password_hash($apass, PASSWORD_DEFAULT),
        ]);
        $settings = $pdo->prepare('INSERT IGNORE INTO settings (k, v) VALUES (?, ?)');
        foreach ([
            'name_fa' => 'نام شما',
            'name_en' => 'Your Name',
            'bio_fa' => 'توسعه‌دهنده وب',
            'bio_en' => 'Web developer',
            'default_lang' => 'fa',
            'show_fa' => '1',
            'show_en' => '1',
            'show_help' => '1',
            'donate_on' => '0',
            'donate_ask' => '1',
            'tz' => 'Asia/Tehran',
            'section_order' => 'me,slides,links,jobs,projects,edu,papers',
            'app_name' => 'My Links',
            'short_name' => 'Links',
            'theme_color' => '#6c5ce7',
        ] as $key => $value) {
            $settings->execute([$key, $value]);
        }
        $link = $pdo->prepare('INSERT INTO links (icon, title_fa, title_en, url, sort) VALUES (?, ?, ?, ?, ?)');
        $link->execute(['fa-brands fa-github', 'گیت‌هاب', 'GitHub', 'https://github.com', 1]);
        $link->execute(['fa-brands fa-linkedin', 'لینکدین', 'LinkedIn', 'https://linkedin.com', 2]);

        $written = file_put_contents($cfgFile, "<?php\nreturn " . var_export($db, true) . ";\n", LOCK_EX);
        if ($written === false) {
            throw new RuntimeException('Could not write inc/config.php. Make the inc folder writable, then run the installer again.');
        }
        @chmod($cfgFile, 0640);
        @unlink(__FILE__);
        $done = true;
    } catch (Throwable $e) {
        $err = $e instanceof PDOException
            ? 'Database error. Check the host, database name, user, and password.'
            : $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Install</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<main class="wrap adm">
<?php if ($done): ?>
  <div class="glass pad login">
    <h1>Installed</h1>
    <p class="muted">The site is ready. Delete the <code>install</code> folder if it is still there.</p>
    <p><a class="btn pri" href="../admin/">Open admin</a></p>
  </div>
<?php else: ?>
  <form method="post" class="glass pad login">
    <h1>Install</h1>
<?php if ($err !== ''): ?>
    <p class="err"><?= h($err) ?></p>
<?php endif ?>
    <label class="f"><span>Database host</span><input name="host" value="<?= h($old['host']) ?>" required></label>
    <label class="f"><span>Database name</span><input name="name" value="<?= h($old['name']) ?>" required></label>
    <label class="f"><span>Database user</span><input name="user" value="<?= h($old['user']) ?>" required></label>
    <label class="f"><span>Database password</span><input name="pass" type="password" autocomplete="off"></label>
    <label class="f"><span>Admin username</span><input name="auser" value="<?= h($old['auser']) ?>" required></label>
    <label class="f"><span>Admin password (10+ characters)</span><input name="apass" type="password" autocomplete="new-password" required></label>
    <button class="btn pri">Install</button>
  </form>
<?php endif ?>
</main>
</body>
</html>

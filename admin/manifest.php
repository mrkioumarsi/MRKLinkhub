<?php
declare(strict_types=1);
const UP = '../';
require __DIR__ . '/../inc/bootstrap.php';

$s = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$v = (string) ($s['icon'] ?? '');
$bust = rawurlencode($v !== '' ? $v : '1');
$icons = is_file(ROOT . '/uploads/icon-512.png')
    ? [
        ['src' => '/uploads/icon-192.png?v=' . $bust, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/uploads/icon-512.png?v=' . $bust, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
    ]
    : [['src' => '/assets/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any']];
$color = $s['theme_color'] ?? '#6c5ce7';
$name = ($s['app_name'] ?? '') ?: 'Dashboard';
$short = ($s['short_name'] ?? '') ?: 'Admin';

header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'id' => '/admin/',
    'name' => $name,
    'short_name' => $short,
    'start_url' => '/admin/',
    'scope' => '/admin/',
    'display' => 'standalone',
    'background_color' => '#f3f0ff',
    'theme_color' => $color,
    'icons' => $icons,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
